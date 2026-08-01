<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import ChatConversationList from '@/components/chat/ChatConversationList.vue';
import ChatPanel from '@/components/chat/ChatPanel.vue';
import ChatRecipientSearch from '@/components/chat/ChatRecipientSearch.vue';
import PageWrapper from '@/components/PageWrapper.vue';
import { Button } from '@/components/ui/button';
import { useChat } from '@/composables/useChat';
import { translate, useTranslations } from '@/composables/useTranslations';
import { useUploadGuard } from '@/composables/useUploadGuard';
import { chatRequest } from '@/lib/chatClient';
import { index } from '@/routes/chat';
import {
    destroy as chatContextDestroy,
    store as chatContextStore,
} from '@/routes/chat/context';
import type { ChatConversation, ChatMessage, ChatProfile } from '@/types';

type ScrollPayload<TItem> = {
    data: TItem[];
};

type LayoutProps = {
    locale: string;
    fallbackLocale: string;
};

const props = defineProps<{
    conversations: ScrollPayload<ChatConversation>;
    messages: ScrollPayload<ChatMessage>;
    activeConversation: ChatConversation | null;
}>();

defineOptions({
    layout: (layoutProps: LayoutProps) => ({
        breadcrumbs: [
            {
                title: translate(
                    'chat.page.title',
                    layoutProps.locale,
                    layoutProps.fallbackLocale,
                ),
                href: index(),
            },
        ],
    }),
});

/* Comfortably inside the server's TrackChatPageContext window. */
const CONTEXT_REFRESH_MS = 60_000;

/**
 * An opaque identifier for this page instance.
 *
 * It carries no authority: the server always scopes it to the authenticated
 * user. `randomUUID` needs a secure context, so a plain random token stands in
 * where it is unavailable.
 */
const createContextId = (): string => {
    if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) {
        return crypto.randomUUID();
    }

    return `${Math.random().toString(36).slice(2)}${Date.now().toString(36)}`;
};

const { t } = useTranslations();
const page = usePage();

const {
    state,
    liveMessagesFor,
    sendMessage,
    markRead,
    seedConversations,
    subscribeTo,
    watchAvailability,
    watchConnection,
    updateReaction,
    scopeToUser,
} = useChat();
const { beginUpload } = useUploadGuard();

const currentUserId = computed(() => page.props.auth.user?.id ?? null);
const enabled = ref(page.props.chat.enabled);
const imageUploadsEnabled = ref(page.props.chat.imageUploadsEnabled);
const pendingRecipient = ref<ChatProfile | null>(null);
const draftKey = computed(
    () =>
        props.activeConversation?.id ??
        (pendingRecipient.value
            ? `recipient:${pendingRecipient.value.id}`
            : 'new'),
);

/*
 * Inertia owns the transcript's pages and hands them back newest first, so the
 * list is reversed for display. Messages that arrived over the socket since the
 * last response are appended after them and deduplicated by id.
 */
const messages = computed<ChatMessage[]>(() => {
    const merged = [...props.messages.data].reverse();
    const seen = new Set(merged.map((message) => message.id));

    for (const message of liveMessagesFor(
        props.activeConversation?.id ?? null,
    )) {
        if (!seen.has(message.id)) {
            merged.push(message);
            seen.add(message.id);
        }
    }

    return merged;
});

const showPanelOnMobile = computed(
    () => props.activeConversation !== null || pendingRecipient.value !== null,
);

/*
 * While this page is open it shows every conversation, so none of them needs a
 * notification. The server is told so, and reminded before its record expires;
 * leaving the page clears it immediately. Nothing about this reaches any other
 * user, and no read marker moves.
 *
 * The identifier is fixed for the lifetime of this page instance, so a second
 * tab reports its own context and closing one tab never unsilences the other.
 */
const contextId = createContextId();

let contextTimer: ReturnType<typeof setInterval> | null = null;

const reportOpen = (): void => {
    void chatRequest(chatContextStore({ query: { context: contextId } })).catch(
        () => {
            /* A missed report only means a notification arrives; nothing breaks. */
        },
    );
};

onMounted(() => {
    scopeToUser(currentUserId.value);
    watchConnection(() => {
        /* The server is the only truth after a dropped socket. */
        router.reload({ reset: ['messages', 'conversations'] });
    });

    watchAvailability((next) => {
        enabled.value = next.enabled;
        imageUploadsEnabled.value = next.imageUploadsEnabled;
    });

    if (props.activeConversation !== null) {
        subscribeTo(props.activeConversation.id);
    }

    reportOpen();
    contextTimer = setInterval(reportOpen, CONTEXT_REFRESH_MS);
});

onUnmounted(() => {
    if (contextTimer !== null) {
        clearInterval(contextTimer);
        contextTimer = null;
    }

    void chatRequest(
        chatContextDestroy({ query: { context: contextId } }),
    ).catch(() => {
        /* The server record expires on its own if this never lands. */
    });
});

/*
 * Inertia owns fetching and merging the inbox pages; the store mirrors the
 * merged list so a realtime arrival can move a conversation to the top and
 * raise its unread count without another round trip.
 */
watch(
    () => props.conversations.data,
    (conversations) => seedConversations(conversations),
    { immediate: true },
);

/*
 * Selecting a conversation is a visit, not a fetch: the transcript is a scroll
 * prop, so it has to be reset or the new conversation's page one would merge
 * into the previous conversation's transcript.
 */
const select = (conversationId: string, withInbox = false): void => {
    pendingRecipient.value = null;

    router.visit(index({ query: { conversation: conversationId } }), {
        only: withInbox
            ? ['conversations', 'messages', 'activeConversation']
            : ['messages', 'activeConversation'],
        reset: withInbox ? ['conversations', 'messages'] : ['messages'],
        preserveScroll: true,
        preserveState: true,
    });
};

const startWith = (recipient: ChatProfile): void => {
    pendingRecipient.value = recipient;
};

const send = async (
    payload: { body: string; image: File | null },
    complete: (succeeded: boolean) => void,
): Promise<void> => {
    const conversationId = props.activeConversation?.id ?? null;
    const upload = payload.image ? beginUpload() : null;

    const message = await sendMessage({
        body: payload.body,
        image: payload.image,
        conversationId,
        recipientId:
            conversationId === null ? pendingRecipient.value?.id : null,
    });

    upload?.release();
    complete(message !== null);

    if (message !== null) {
        pendingRecipient.value = null;
    }

    /* A first message opens a conversation the inbox has not heard of yet. */
    if (message !== null && conversationId === null) {
        select(message.conversation_id, true);
    }
};

const react = (message: ChatMessage, emoji: string | null): void => {
    if (currentUserId.value) {
        void updateReaction(message, currentUserId.value, emoji);
    }
};

const back = (): void => {
    pendingRecipient.value = null;

    router.visit(index(), {
        only: ['messages', 'activeConversation'],
        reset: ['messages'],
        preserveScroll: true,
        preserveState: true,
    });
};

const seen = (messageId: string): void => {
    if (props.activeConversation !== null) {
        void markRead(props.activeConversation.id, messageId);
    }
};
</script>

<template>
    <div class="contents">
        <Head :title="t('chat.page.title')" />

        <PageWrapper
            :title="t('chat.page.title')"
            :description="t('chat.page.description')"
        >
            <p
                v-if="!enabled"
                class="rounded-xl border py-12 text-center font-medium"
                data-test="chat-disabled"
            >
                {{ t('chat.message.disabled') }}
            </p>

            <template v-else>
                <p
                    v-if="state.connection === 'reconnecting'"
                    class="mb-3 rounded-lg bg-muted px-3 py-2 text-xs text-muted-foreground"
                    data-test="chat-reconnecting"
                >
                    {{ t('chat.label.reconnecting') }}
                </p>

                <div
                    class="grid h-[calc(100vh-16rem)] min-h-[28rem] grid-cols-1 overflow-hidden rounded-xl border md:grid-cols-[20rem_1fr]"
                >
                    <div
                        class="flex min-h-0 flex-col border-r"
                        :class="showPanelOnMobile ? 'hidden md:flex' : 'flex'"
                    >
                        <ChatRecipientSearch @select="startWith" />

                        <ChatConversationList
                            :conversations="state.conversations"
                            :active-id="props.activeConversation?.id ?? null"
                            scroll-prop="conversations"
                            @select="select"
                        />
                    </div>

                    <div
                        class="flex min-h-0 flex-col"
                        :class="showPanelOnMobile ? 'flex' : 'hidden md:flex'"
                    >
                        <div class="border-b p-2 md:hidden">
                            <Button
                                variant="ghost"
                                size="sm"
                                data-test="chat-back"
                                @click="back"
                            >
                                {{ t('chat.label.conversations') }}
                            </Button>
                        </div>

                        <ChatPanel
                            :conversation="props.activeConversation"
                            :pending-recipient="pendingRecipient"
                            :messages="messages"
                            :current-user-id="currentUserId"
                            scroll-prop="messages"
                            :sending="state.sending"
                            :upload-progress="state.uploadProgress"
                            :image-uploads-enabled="imageUploadsEnabled"
                            :draft-key="draftKey"
                            :error="state.error"
                            @send="send"
                            @seen="seen"
                            @react="react"
                        />
                    </div>
                </div>
            </template>
        </PageWrapper>
    </div>
</template>
