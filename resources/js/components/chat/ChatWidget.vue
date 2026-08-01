<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ArrowLeft, MessagesSquare, Minus, X } from '@lucide/vue';
import { useMediaQuery } from '@vueuse/core';
import { computed, onMounted, ref, watch } from 'vue';
import ChatConversationList from '@/components/chat/ChatConversationList.vue';
import ChatPanel from '@/components/chat/ChatPanel.vue';
import ChatRecipientSearch from '@/components/chat/ChatRecipientSearch.vue';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useChat } from '@/composables/useChat';
import { useTranslations } from '@/composables/useTranslations';
import { useUploadGuard } from '@/composables/useUploadGuard';
import type { ChatMessage, ChatProfile } from '@/types';

/**
 * Where the widget's own state lives between Inertia navigations.
 *
 * Session storage, not local storage: the widget is expected to come back
 * within the same browser session, not to be restored days later.
 */
/* Matches Tailwind's `lg` breakpoint; below it the widget is not rendered. */
const DESKTOP_QUERY = '(min-width: 1024px)';

type WidgetState = {
    open: boolean;
    minimized: boolean;
    conversationId: string | null;
};

const { t } = useTranslations();
const page = usePage();
const isDesktop = useMediaQuery(DESKTOP_QUERY);

const {
    state,
    activeConversation,
    activeMessages,
    hasOlderMessages,
    loadConversations,
    loadMoreConversations,
    openConversation,
    loadOlderMessages,
    sendMessage,
    markRead,
    watchAvailability,
    watchConnection,
    clearActive,
    updateReaction,
    scopeToUser,
} = useChat();
const { beginUpload } = useUploadGuard();

const enabled = ref(page.props.chat.enabled);
const imageUploadsEnabled = ref(page.props.chat.imageUploadsEnabled);
const open = ref(false);
const minimized = ref(false);
const pendingRecipient = ref<ChatProfile | null>(null);

const currentUserId = computed(() => page.props.auth.user?.id ?? null);
const storageKey = computed(
    () => `chat-widget:${currentUserId.value ?? 'guest'}`,
);
const draftKey = computed(
    () =>
        activeConversation.value?.id ??
        (pendingRecipient.value
            ? `recipient:${pendingRecipient.value.id}`
            : 'new'),
);
const unreadCount = computed(() => page.props.chat.unreadCount);

/* The chat page owns the conversation; a second copy would fight it for reads. */
const onChatPage = computed(() => page.url.split('?')[0] === '/chat');

const visible = computed(
    () => enabled.value && isDesktop.value && !onChatPage.value,
);

const readStored = (): WidgetState | null => {
    if (typeof window === 'undefined') {
        return null;
    }

    try {
        const raw = window.sessionStorage.getItem(storageKey.value);

        return raw === null ? null : (JSON.parse(raw) as WidgetState);
    } catch {
        return null;
    }
};

const persist = (): void => {
    if (typeof window === 'undefined') {
        return;
    }

    try {
        window.sessionStorage.setItem(
            storageKey.value,
            JSON.stringify({
                open: open.value,
                minimized: minimized.value,
                conversationId: state.activeId,
            } satisfies WidgetState),
        );
    } catch {
        /* A storage quota or a private session must not break the widget. */
    }
};

watch(
    currentUserId,
    (nextUserId, previousUserId) => {
        scopeToUser(nextUserId);

        if (previousUserId !== undefined && nextUserId !== previousUserId) {
            open.value = false;
            minimized.value = false;
            pendingRecipient.value = null;
            clearActive();
        }
    },
    { immediate: true },
);

onMounted(async () => {
    watchConnection(() => {
        /* The server is the only truth after a dropped socket. */
        void loadConversations(1);

        if (state.activeId !== null) {
            void openConversation(state.activeId, { force: true });
        }
    });

    watchAvailability((next) => {
        enabled.value = next.enabled;
        imageUploadsEnabled.value = next.imageUploadsEnabled;

        if (!next.enabled) {
            open.value = false;
            persist();
        }
    });

    const stored = readStored();

    if (stored === null || !stored.open) {
        return;
    }

    open.value = true;
    minimized.value = stored.minimized;

    if (state.conversations.length === 0) {
        await loadConversations(1);
    }

    if (stored.conversationId !== null) {
        await openConversation(stored.conversationId);
    }
});

watch([open, minimized, () => state.activeId], persist);

const toggle = async (): Promise<void> => {
    if (open.value) {
        minimized.value = !minimized.value;

        return;
    }

    open.value = true;
    minimized.value = false;

    if (state.conversations.length === 0) {
        await loadConversations(1);
    }
};

const close = (): void => {
    open.value = false;
    minimized.value = false;
    clearActive();
    pendingRecipient.value = null;
};

const select = async (conversationId: string): Promise<void> => {
    pendingRecipient.value = null;
    await openConversation(conversationId);
};

const startWith = (recipient: ChatProfile): void => {
    clearActive();
    pendingRecipient.value = recipient;
};

const send = async (
    payload: { body: string; image: File | null },
    complete: (succeeded: boolean) => void,
): Promise<void> => {
    const conversationId = activeConversation.value?.id ?? null;
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
};

const react = (message: ChatMessage, emoji: string | null): void => {
    if (currentUserId.value) {
        void updateReaction(message, currentUserId.value, emoji);
    }
};

const loadOlder = (): void => {
    if (state.activeId !== null) {
        void loadOlderMessages(state.activeId);
    }
};

/*
 * A minimized widget is not a reading context, so nothing is marked as read
 * while it is collapsed and the notification for that message stays.
 */
const seen = (messageId: string): void => {
    if (state.activeId !== null && !minimized.value) {
        void markRead(state.activeId, messageId);
    }
};
</script>

<template>
    <div
        v-if="visible"
        class="fixed right-4 bottom-4 z-50 flex flex-col items-end gap-2"
        data-test="chat-widget"
    >
        <section
            v-if="open"
            class="flex h-[30rem] w-[22rem] flex-col overflow-hidden rounded-xl border bg-background shadow-lg"
            :class="minimized ? 'h-auto' : ''"
            data-test="chat-widget-panel"
            :data-minimized="minimized ? 'true' : 'false'"
        >
            <header
                class="flex items-center justify-between gap-2 border-b px-3 py-2"
            >
                <TooltipProvider :delay-duration="0">
                    <Tooltip v-if="activeConversation || pendingRecipient">
                        <TooltipTrigger as-child>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="size-7"
                                :aria-label="t('chat.label.conversations')"
                                data-test="chat-widget-back"
                                @click="
                                    clearActive();
                                    pendingRecipient = null;
                                "
                            >
                                <ArrowLeft class="size-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            {{ t('chat.label.conversations') }}
                        </TooltipContent>
                    </Tooltip>
                    <div v-else class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold">
                            {{ t('chat.page.title') }}
                        </p>
                        <p class="truncate text-xs text-muted-foreground">
                            {{ t('chat.page.description') }}
                        </p>
                    </div>
                    <span class="flex shrink-0 items-center gap-1">
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="size-7"
                                    :aria-label="
                                        minimized
                                            ? t('chat.button.expand')
                                            : t('chat.button.minimize')
                                    "
                                    data-test="chat-widget-minimize"
                                    @click="minimized = !minimized"
                                >
                                    <Minus class="size-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>
                                {{
                                    minimized
                                        ? t('chat.button.expand')
                                        : t('chat.button.minimize')
                                }}
                            </TooltipContent>
                        </Tooltip>
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="size-7"
                                    :aria-label="t('chat.button.close')"
                                    data-test="chat-widget-close"
                                    @click="close"
                                >
                                    <X class="size-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>
                                {{ t('chat.button.close') }}
                            </TooltipContent>
                        </Tooltip>
                    </span>
                </TooltipProvider>
            </header>

            <div v-if="!minimized" class="flex min-h-0 flex-1 flex-col">
                <p
                    v-if="state.connection === 'reconnecting'"
                    class="bg-muted px-3 py-1 text-xs text-muted-foreground"
                    data-test="chat-widget-reconnecting"
                >
                    {{ t('chat.label.reconnecting') }}
                </p>

                <div
                    v-if="
                        activeConversation === null && pendingRecipient === null
                    "
                    class="flex min-h-0 flex-1 flex-col"
                >
                    <ChatRecipientSearch @select="startWith" />
                    <ChatConversationList
                        :conversations="state.conversations"
                        :active-id="state.activeId"
                        :loading="state.loadingConversations"
                        :can-load-more="state.page < state.lastPage"
                        @select="select"
                        @load-more="loadMoreConversations"
                    />
                </div>

                <div v-else class="flex min-h-0 flex-1 flex-col">
                    <ChatPanel
                        :conversation="activeConversation"
                        :pending-recipient="pendingRecipient"
                        :messages="activeMessages"
                        :current-user-id="currentUserId"
                        :sending="state.sending"
                        :upload-progress="state.uploadProgress"
                        :image-uploads-enabled="imageUploadsEnabled"
                        :draft-key="draftKey"
                        :has-older="hasOlderMessages"
                        :loading-older="state.loadingOlder"
                        :error="state.error"
                        @send="send"
                        @load-older="loadOlder"
                        @seen="seen"
                        @react="react"
                    />
                </div>
            </div>
        </section>

        <Button
            v-else
            class="relative size-12 rounded-full shadow-lg"
            size="icon"
            :aria-label="t('chat.page.title')"
            data-test="chat-widget-toggle"
            @click="toggle"
        >
            <MessagesSquare class="size-5" />
            <span
                v-if="unreadCount > 0"
                class="absolute -top-1 -right-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-[0.625rem] leading-none text-white tabular-nums"
                data-test="chat-widget-badge"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
        </Button>
    </div>
</template>
