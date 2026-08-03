import { echo } from '@laravel/echo-vue';
import { computed, reactive, readonly } from 'vue';
import { chatRequest, ChatRequestError } from '@/lib/chatClient';
import type { ImageUploadRecord } from '@/lib/imageUploads';
import {
    activeImageUploads,
    cancelImageUpload,
    pollImageUpload,
    uploadErrorKey,
} from '@/lib/imageUploads';
import {
    index as conversationIndex,
    read as conversationRead,
    show as conversationShow,
} from '@/routes/chat/conversations';
import { store as messageStore } from '@/routes/chat/messages';
import {
    destroy as reactionDestroy,
    update as reactionUpdate,
} from '@/routes/chat/messages/reaction';
import { index as recipientIndex } from '@/routes/chat/recipients';
import { index as imageUploadIndex } from '@/routes/media/image-uploads';
import type {
    ChatConversation,
    ChatConversationPayload,
    ChatConversationWindow,
    ChatMessage,
    ChatProfile,
} from '@/types';

/**
 * The shortest term the recipient directory answers, matching the server rule.
 */
export const RECIPIENT_SEARCH_MINIMUM = 2;

type ConnectionState = 'connected' | 'reconnecting';

type ChatState = {
    conversations: ChatConversation[];
    lastPage: number;
    page: number;
    activeId: string | null;
    /* Windows fetched by the widget, which has no Inertia page props to read. */
    messages: Record<string, ChatMessage[]>;
    /* Arrivals over the socket, merged on top of whatever a surface already holds. */
    live: Record<string, ChatMessage[]>;
    hasOlder: Record<string, boolean>;
    loadingConversations: boolean;
    loadingMessages: boolean;
    loadingOlder: boolean;
    sending: boolean;
    uploadProgress: number | null;
    /*
     * Image messages the server has accepted but not published yet. A message
     * does not exist until its image is processed, so this is all a surface has
     * to show for one in the meantime.
     *
     * A list rather than a single record: several may legitimately be in flight
     * at once, and the second must not erase what the first is showing or
     * silently take over its cancellation.
     */
    pendingImageUploads: ImageUploadRecord[];
    drafts: Record<string, string>;
    connection: ConnectionState;
    error: string | null;
};

/*
 * Module scope on purpose: the chat page and the floating widget are separate
 * components that must agree on one socket subscription and one connection
 * state, and the widget has to survive an Inertia navigation without losing
 * what it was showing.
 *
 * Paging is deliberately NOT owned here for the chat page: there the inbox and
 * the transcript are Inertia scroll props, and Inertia merges each page. This
 * store only pages for the widget, which has no page props of its own.
 */
const state = reactive<ChatState>({
    conversations: [],
    lastPage: 1,
    page: 1,
    activeId: null,
    messages: {},
    live: {},
    hasOlder: {},
    loadingConversations: false,
    loadingMessages: false,
    loadingOlder: false,
    sending: false,
    uploadProgress: null,
    pendingImageUploads: [],
    drafts: {},
    connection: 'connected',
    error: null,
});

const subscribed = new Set<string>();
const reconnectHandlers = new Set<() => void>();
type ChatAvailability = {
    enabled: boolean;
    imageUploadsEnabled: boolean;
};

const availabilityHandlers = new Set<
    (availability: ChatAvailability) => void
>();
let controlSubscribed = false;
let connectionBound = false;
let scopedUserId: string | null = null;

const conversationChannel = (conversationId: string): string =>
    `chat.conversation.${conversationId}`;

/**
 * Resolve the Echo instance, or null where there is none (SSR and unit tests).
 */
const connection = (): ReturnType<typeof echo> | null => {
    try {
        return echo() ?? null;
    } catch {
        return null;
    }
};

const sortConversations = (): void => {
    state.conversations.sort((first, second) => {
        const left = first.last_message_at ?? '';
        const right = second.last_message_at ?? '';

        return right.localeCompare(left);
    });
};

const upsertConversation = (conversation: ChatConversation): void => {
    const index = state.conversations.findIndex(
        (item) => item.id === conversation.id,
    );

    if (index === -1) {
        state.conversations.unshift(conversation);
    } else {
        state.conversations[index] = conversation;
    }

    sortConversations();
    subscribeTo(conversation.id);
};

const recordLive = (message: ChatMessage): void => {
    const existing = state.live[message.conversation_id] ?? [];

    if (existing.some((item) => item.id === message.id)) {
        return;
    }

    state.live[message.conversation_id] = [...existing, message];
};

const applyIncomingMessage = (message: ChatMessage): void => {
    recordLive(message);

    const conversation = state.conversations.find(
        (item) => item.id === message.conversation_id,
    );

    if (!conversation) {
        return;
    }

    conversation.last_message = message;
    conversation.last_message_at = message.created_at;

    /*
     * The channel echoes the sender's own message back as well, so only what
     * the other side wrote may raise the unread count.
     */
    if (message.sender_id === conversation.participant?.id) {
        conversation.unread_count += 1;
    }

    sortConversations();
};

const replaceReaction = (
    messageId: string,
    userId: string,
    reaction: ChatMessage['reactions'][number] | null,
): void => {
    const apply = (message: ChatMessage): void => {
        if (message.id !== messageId) {
            return;
        }

        message.reactions = [
            ...message.reactions.filter((item) => item.user_id !== userId),
            ...(reaction ? [reaction] : []),
        ];
    };

    Object.values(state.messages).flat().forEach(apply);
    Object.values(state.live).flat().forEach(apply);
    state.conversations.forEach((conversation) => {
        if (conversation.last_message) {
            apply(conversation.last_message);
        }
    });
};

const applyReadReceipt = (payload: {
    conversation_id: string;
    user_id: string;
    last_read_at: string | null;
}): void => {
    const conversation = state.conversations.find(
        (item) => item.id === payload.conversation_id,
    );

    if (!conversation) {
        return;
    }

    if (conversation.participant?.id === payload.user_id) {
        conversation.peer_read_at = payload.last_read_at;

        return;
    }

    conversation.last_read_at = payload.last_read_at;
    conversation.unread_count = 0;
};

/**
 * Listen to one conversation's private channel.
 *
 * Subscribing twice to the same conversation would duplicate every arrival, so
 * the conversations already joined are tracked.
 */
const subscribeTo = (conversationId: string): void => {
    if (subscribed.has(conversationId)) {
        return;
    }

    const instance = connection();

    if (!instance) {
        return;
    }

    subscribed.add(conversationId);

    instance
        .private(conversationChannel(conversationId))
        .listen('.chat.message', (payload: { message: ChatMessage }) => {
            applyIncomingMessage(payload.message);
        })
        .listen(
            '.chat.read',
            (payload: {
                conversation_id: string;
                user_id: string;
                last_read_at: string | null;
            }) => {
                applyReadReceipt(payload);
            },
        )
        .listen(
            '.chat.reaction',
            (payload: {
                message_id: string;
                user_id: string;
                reaction: ChatMessage['reactions'][number] | null;
            }) => {
                replaceReaction(
                    payload.message_id,
                    payload.user_id,
                    payload.reaction,
                );
            },
        );
};

/**
 * Watch the socket so a surface can say it is catching up, and reload from the
 * server on reconnect rather than trusting whatever the client still holds.
 */
const watchConnection = (onReconnect?: () => void): void => {
    if (onReconnect) {
        reconnectHandlers.add(onReconnect);
    }

    if (connectionBound) {
        return;
    }

    const instance = connection();
    const pusher = (
        instance as unknown as {
            connector?: {
                pusher?: {
                    connection?: {
                        bind: (
                            event: string,
                            handler: (payload: unknown) => void,
                        ) => void;
                    };
                };
            };
        }
    )?.connector?.pusher?.connection;

    if (!pusher) {
        return;
    }

    connectionBound = true;

    pusher.bind('state_change', (payload: unknown) => {
        const current = (payload as { current?: string }).current;

        if (current === 'connected') {
            const wasReconnecting = state.connection === 'reconnecting';
            state.connection = 'connected';

            if (wasReconnecting) {
                state.live = {};
                reconnectHandlers.forEach((handler) => handler());
            }

            return;
        }

        if (current === 'unavailable' || current === 'connecting') {
            state.connection = 'reconnecting';
        }
    });
};

const loadConversations = async (page = 1): Promise<void> => {
    state.loadingConversations = true;

    try {
        const payload = await chatRequest<ChatConversationPayload>(
            conversationIndex({ query: page === 1 ? {} : { page } }),
        );

        state.conversations =
            page === 1
                ? payload.data
                : [...state.conversations, ...payload.data];
        state.page = payload.meta.page;
        state.lastPage = payload.meta.lastPage;
        state.error = null;

        sortConversations();
        state.conversations.forEach((conversation) =>
            subscribeTo(conversation.id),
        );
    } catch (error) {
        state.error = messageFor(error);
    } finally {
        state.loadingConversations = false;
    }
};

/**
 * Adopt the inbox page Inertia has merged for the chat page.
 *
 * Inertia owns fetching and merging the pages; the store mirrors the result so
 * realtime arrivals can be applied on top of it without a round trip.
 */
const seedConversations = (
    conversations: readonly ChatConversation[],
): void => {
    state.conversations = conversations.map((conversation) => ({
        ...conversation,
    }));

    sortConversations();
    state.conversations.forEach((conversation) => subscribeTo(conversation.id));
};

const openConversation = async (
    conversationId: string,
    options: { force?: boolean } = {},
): Promise<void> => {
    state.activeId = conversationId;
    subscribeTo(conversationId);

    if (!options.force && state.messages[conversationId] !== undefined) {
        return;
    }

    state.loadingMessages = true;

    try {
        const payload = await chatRequest<ChatConversationWindow>(
            conversationShow(conversationId),
        );

        state.messages[conversationId] = payload.messages;
        state.hasOlder[conversationId] = payload.hasMore;
        upsertConversation(payload.conversation);
        state.error = null;
    } catch (error) {
        state.error = messageFor(error);
    } finally {
        state.loadingMessages = false;
    }
};

/**
 * Walk further into the history of the widget's open conversation.
 */
const loadOlderMessages = async (conversationId: string): Promise<void> => {
    const known = state.messages[conversationId] ?? [];
    const oldest = known[0];

    if (!oldest || state.hasOlder[conversationId] !== true) {
        return;
    }

    state.loadingOlder = true;

    try {
        const payload = await chatRequest<ChatConversationWindow>(
            conversationShow(conversationId, { query: { before: oldest.id } }),
        );

        /* Prepended, never replacing what is already on screen. */
        state.messages[conversationId] = [...payload.messages, ...known];
        state.hasOlder[conversationId] = payload.hasMore;
    } catch (error) {
        state.error = messageFor(error);
    } finally {
        state.loadingOlder = false;
    }
};

/** Merge a stored message and its conversation into the local view. */
const applySentMessage = (
    conversation: ChatConversation,
    message: ChatMessage,
): void => {
    upsertConversation(conversation);
    recordLive(message);

    if (state.messages[conversation.id] !== undefined) {
        state.messages[conversation.id] = [
            ...state.messages[conversation.id],
            message,
        ];
    }

    state.activeId = conversation.id;
    state.error = null;
};

/** Track, refresh, or drop one in-flight upload without disturbing the others. */
const trackPending = (record: ImageUploadRecord): void => {
    const index = state.pendingImageUploads.findIndex(
        (item) => item.id === record.id,
    );

    if (index === -1) {
        state.pendingImageUploads.push(record);

        return;
    }

    state.pendingImageUploads[index] = record;
};

const forgetPending = (uploadId: string): void => {
    state.pendingImageUploads = state.pendingImageUploads.filter(
        (item) => item.id !== uploadId,
    );
};

/**
 * Follow an accepted image message until the queue publishes it.
 *
 * Resolves with the created message, or null when it did not get created —
 * because processing failed, because it was cancelled, or because this client
 * simply stopped waiting. Nothing here belongs to the request that started it:
 * this runs in the background long after that request was answered.
 */
const awaitImageMessage = async (
    accepted: ImageUploadRecord,
): Promise<ChatMessage | null> => {
    trackPending(accepted);

    try {
        const settled = await pollImageUpload(accepted, {
            onUpdate: trackPending,
        });

        if (settled === null) {
            /* Still running server-side; only this client gave up watching. */
            state.error = 'media.upload.message.timed_out';

            return null;
        }

        if (
            settled.status !== 'ready' ||
            settled.message === null ||
            settled.conversation === null
        ) {
            state.error = uploadErrorKey(settled);

            return null;
        }

        const message = settled.message as unknown as ChatMessage;
        applySentMessage(
            settled.conversation as unknown as ChatConversation,
            message,
        );

        return message;
    } finally {
        forgetPending(accepted.id);
    }
};

/**
 * Abandon an image message that has not been published yet.
 *
 * Best effort: a job that finishes first still creates its message, and that
 * message is kept rather than thrown away. With nothing named, the oldest
 * pending upload is the one abandoned, which is the one a surface showing a
 * single cancel control is asking about.
 */
const cancelPendingImage = async (uploadId?: string): Promise<void> => {
    const pending =
        uploadId === undefined
            ? state.pendingImageUploads[0]
            : state.pendingImageUploads.find((item) => item.id === uploadId);

    if (!pending) {
        return;
    }

    try {
        const settled = await cancelImageUpload(pending.cancel_url);

        if (
            settled?.status === 'ready' &&
            settled.message &&
            settled.conversation
        ) {
            applySentMessage(
                settled.conversation as unknown as ChatConversation,
                settled.message as unknown as ChatMessage,
            );
        }
    } catch {
        /* The pending state is cleared either way. */
    }

    forgetPending(pending.id);
};

/**
 * Pick up image messages that were still processing when the page was left.
 *
 * Anything already being watched is skipped, so a surface mounting a second
 * time does not start a duplicate poll for the same upload.
 */
const restorePendingImage = async (): Promise<void> => {
    try {
        const active = await activeImageUploads(imageUploadIndex());
        const unwatched = active.filter(
            (upload) =>
                upload.target === 'chat-image' &&
                !state.pendingImageUploads.some(
                    (item) => item.id === upload.id,
                ),
        );

        await Promise.all(unwatched.map(awaitImageMessage));
    } catch {
        /* Recovery is a convenience; chat works without it. */
    }
};

/**
 * What a surface learns the moment the server has taken the message.
 *
 * A text message is stored synchronously and arrives complete. An image message
 * is only *accepted*: `settled` is the background watcher that eventually says
 * whether it became a message, and awaiting it is optional — a surface that
 * only needs to clear its composer never has to.
 */
export type SendOutcome = {
    accepted: boolean;
    /** The stored message, for a text message only. */
    message: ChatMessage | null;
    /** Resolves when a queued image message is published, or null for text. */
    settled: Promise<ChatMessage | null> | null;
};

const rejected: SendOutcome = { accepted: false, message: null, settled: null };

/**
 * Send a message, opening the conversation when a recipient is named.
 *
 * Nothing is added locally before the server answers: a message only exists
 * once it is stored.
 *
 * This resolves when the transfer is done, never later. An image still has the
 * queue ahead of it at that point, but that work is the server's — holding the
 * composer or the navigation guard through it would block the user on something
 * that no longer needs them present.
 */
const sendMessage = async (payload: {
    body: string;
    image?: File | null;
    conversationId?: string | null;
    recipientId?: string | null;
}): Promise<SendOutcome> => {
    const body = payload.body.trim();

    if ((body === '' && !payload.image) || state.sending) {
        return rejected;
    }

    state.sending = true;
    state.uploadProgress = payload.image ? 0 : null;

    try {
        const data = new FormData();

        if (body !== '') {
            data.append('body', body);
        }

        if (payload.image) {
            data.append('image', payload.image);
        }

        if (payload.conversationId) {
            data.append('conversation_id', payload.conversationId);
        } else if (payload.recipientId) {
            data.append('recipient_id', payload.recipientId);
        }

        const response = await chatRequest<
            | { conversation: ChatConversation; message: ChatMessage }
            | { data: ImageUploadRecord }
        >(messageStore(), data, undefined, (percentage) => {
            state.uploadProgress = percentage;
        });

        /*
         * A message carrying an image is only *accepted* here: it is created
         * once the queue has processed the image. The watcher is started, not
         * awaited, so this returns as soon as the bytes are in.
         */
        if ('data' in response) {
            return {
                accepted: true,
                message: null,
                settled: awaitImageMessage(response.data),
            };
        }

        applySentMessage(response.conversation, response.message);

        return { accepted: true, message: response.message, settled: null };
    } catch (error) {
        state.error = messageFor(error);

        return rejected;
    } finally {
        state.sending = false;
        state.uploadProgress = null;
    }
};

const updateReaction = async (
    message: ChatMessage,
    userId: string,
    emoji: string | null,
): Promise<void> => {
    const previous =
        message.reactions.find((item) => item.user_id === userId) ?? null;
    const optimistic = emoji
        ? {
              id: previous?.id ?? `optimistic-${message.id}`,
              user_id: userId,
              emoji,
          }
        : null;

    replaceReaction(message.id, userId, optimistic);

    try {
        const response = emoji
            ? await chatRequest<{ reaction: ChatMessage['reactions'][number] }>(
                  reactionUpdate(message.id),
                  { emoji },
              )
            : await chatRequest<{ reaction: null }>(
                  reactionDestroy(message.id),
              );

        replaceReaction(message.id, userId, response.reaction);
    } catch (error) {
        replaceReaction(message.id, userId, previous);
        state.error = messageFor(error);
    }
};

/**
 * Report the newest message the viewer actually saw.
 */
const markRead = async (
    conversationId: string,
    messageId: string,
): Promise<void> => {
    const conversation = state.conversations.find(
        (item) => item.id === conversationId,
    );

    if (conversation) {
        conversation.unread_count = 0;
    }

    try {
        await chatRequest(conversationRead(conversationId), {
            message_id: messageId,
        });
    } catch {
        /* A failed receipt is retried the next time the conversation is read. */
    }
};

const searchRecipients = async (
    term: string,
    signal?: AbortSignal,
): Promise<ChatProfile[]> => {
    if (term.trim().length < RECIPIENT_SEARCH_MINIMUM) {
        return [];
    }

    const payload = await chatRequest<{ data: ChatProfile[] }>(
        recipientIndex({ query: { search: term.trim() } }),
        undefined,
        signal,
    );

    return payload.data;
};

const messageFor = (error: unknown): string => {
    if (error instanceof ChatRequestError) {
        if (error.status === 429) {
            return 'chat.message.rate_limited';
        }

        if (error.status === 403) {
            return 'chat.message.disabled';
        }
    }

    return 'chat.message.send_failed';
};

/**
 * Drop everything the client holds. Used when chat is switched off, so no
 * conversation or message survives in a closed surface.
 */
const reset = (): void => {
    state.conversations = [];
    state.messages = {};
    state.live = {};
    state.hasOlder = {};
    state.activeId = null;
    state.page = 1;
    state.lastPage = 1;
    state.error = null;
    subscribed.clear();
};

/**
 * Listen for the global chat toggle so an administrator's change reaches every
 * online client without a reload.
 */
const watchAvailability = (
    onChange: (availability: ChatAvailability) => void,
): void => {
    availabilityHandlers.add(onChange);

    if (controlSubscribed) {
        return;
    }

    const instance = connection();

    if (!instance) {
        return;
    }

    controlSubscribed = true;

    instance
        .private('chat.control')
        .listen(
            '.chat.availability',
            (payload: { enabled: boolean; image_uploads_enabled: boolean }) => {
                if (!payload.enabled) {
                    reset();
                }

                const availability = {
                    enabled: payload.enabled,
                    imageUploadsEnabled: payload.image_uploads_enabled,
                };
                availabilityHandlers.forEach((handler) =>
                    handler(availability),
                );
            },
        );
};

const scopeToUser = (userId: string | null): void => {
    if (scopedUserId === userId) {
        return;
    }

    scopedUserId = userId;
    state.drafts = {};

    if (typeof window === 'undefined' || userId === null) {
        return;
    }

    try {
        const stored = window.sessionStorage.getItem(`chat-drafts:${userId}`);
        state.drafts = stored
            ? (JSON.parse(stored) as Record<string, string>)
            : {};
    } catch {
        state.drafts = {};
    }
};

const setDraft = (key: string, value: string): void => {
    state.drafts[key] = value;

    if (typeof window === 'undefined' || scopedUserId === null) {
        return;
    }

    try {
        window.sessionStorage.setItem(
            `chat-drafts:${scopedUserId}`,
            JSON.stringify(state.drafts),
        );
    } catch {
        /* Draft persistence is best effort. */
    }
};

export function useChat() {
    const activeConversation = computed<ChatConversation | null>(
        () =>
            state.conversations.find((item) => item.id === state.activeId) ??
            null,
    );

    /**
     * Everything the socket delivered for one conversation since the last
     * server response.
     */
    const liveMessagesFor = (
        conversationId: string | null,
    ): readonly ChatMessage[] =>
        conversationId === null ? [] : (state.live[conversationId] ?? []);

    /* The widget's transcript: its fetched window plus later arrivals. */
    const activeMessages = computed<ChatMessage[]>(() => {
        if (state.activeId === null) {
            return [];
        }

        const fetched = state.messages[state.activeId] ?? [];
        const seen = new Set(fetched.map((message) => message.id));

        return [
            ...fetched,
            ...(state.live[state.activeId] ?? []).filter(
                (message) => !seen.has(message.id),
            ),
        ];
    });

    const hasOlderMessages = computed<boolean>(() =>
        state.activeId === null
            ? false
            : state.hasOlder[state.activeId] === true,
    );

    return {
        state: readonly(state),
        activeConversation,
        activeMessages,
        liveMessagesFor,
        hasOlderMessages,
        loadConversations,
        seedConversations,
        loadMoreConversations: () =>
            state.page < state.lastPage
                ? loadConversations(state.page + 1)
                : Promise.resolve(),
        openConversation,
        loadOlderMessages,
        sendMessage,
        cancelPendingImage,
        restorePendingImage,
        markRead,
        searchRecipients,
        subscribeTo,
        watchAvailability,
        watchConnection,
        updateReaction,
        scopeToUser,
        draftFor: (key: string): string => state.drafts[key] ?? '',
        setDraft,
        reset,
        clearActive: (): void => {
            state.activeId = null;
        },
        clearError: (): void => {
            state.error = null;
        },
    };
}
