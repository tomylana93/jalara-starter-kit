<script setup lang="ts">
import { InfiniteScroll } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import ChatMessageBubble from '@/components/chat/ChatMessageBubble.vue';
import { Button } from '@/components/ui/button';
import {
    MessageScroller,
    MessageScrollerButton,
    MessageScrollerContent,
    MessageScrollerProvider,
    MessageScrollerViewport,
} from '@/components/ui/message-scroller';
import { useTranslations } from '@/composables/useTranslations';
import type { ChatMessage } from '@/types';

type Props = {
    /* Ordered oldest first; the caller owns the ordering. */
    messages: readonly ChatMessage[];
    currentUserId: string | null;
    /*
     * Name of the Inertia scroll prop backing the transcript. Set on the chat
     * page, where Inertia owns paging and merges each page into the transcript.
     * The widget leaves it unset and asks for older messages explicitly.
     */
    scrollProp?: string | null;
    hasOlder?: boolean;
    loadingOlder?: boolean;
    peerReadAt?: string | null;
};

const props = withDefaults(defineProps<Props>(), {
    scrollProp: null,
    hasOlder: false,
    loadingOlder: false,
    peerReadAt: null,
});

const emit = defineEmits<{
    loadOlder: [];
    seen: [messageId: string];
}>();

const { t } = useTranslations();

/* How close to the end still counts as reading the live edge. */
const EDGE_THRESHOLD = 48;

const atEdge = ref(true);

const reportSeen = (): void => {
    const newest = props.messages[props.messages.length - 1];

    if (newest && newest.sender_id !== props.currentUserId) {
        emit('seen', newest.id);
    }
};

/*
 * The scroller owns following the live edge and Inertia owns fetching pages;
 * this listener only decides whether the reader is actually at the edge,
 * because a receipt must not move while old history is being read.
 */
const onScroll = (event: Event): void => {
    const element = event.target as HTMLElement | null;

    if (!element) {
        return;
    }

    atEdge.value =
        element.scrollHeight - element.scrollTop - element.clientHeight <
        EDGE_THRESHOLD;

    if (atEdge.value) {
        reportSeen();
    }
};

watch(
    () => props.messages[props.messages.length - 1]?.id,
    () => {
        if (atEdge.value) {
            reportSeen();
        }
    },
    { immediate: true },
);

watch(
    () => props.messages[0]?.conversation_id,
    () => {
        atEdge.value = true;
    },
);
</script>

<template>
    <MessageScrollerProvider
        :auto-scroll="true"
        default-scroll-position="end"
        :scroll-edge-threshold="EDGE_THRESHOLD"
    >
        <MessageScroller class="min-h-0 flex-1">
            <MessageScrollerViewport
                class="px-4 py-3"
                data-test="chat-message-list"
                @scroll="onScroll"
            >
                <p
                    v-if="props.messages.length === 0"
                    class="py-10 text-center text-sm"
                    data-test="chat-messages-empty"
                >
                    <span class="block font-medium">
                        {{ t('chat.empty.messages') }}
                    </span>
                    <span class="mt-1 block text-muted-foreground">
                        {{ t('chat.empty.messages_description') }}
                    </span>
                </p>

                <InfiniteScroll
                    v-else-if="props.scrollProp"
                    :data="props.scrollProp"
                    reverse
                    :auto-scroll="false"
                    preserve-url
                    items-element="#chat-transcript"
                >
                    <template #loading>
                        <p
                            class="py-2 text-center text-xs text-muted-foreground"
                        >
                            {{ t('chat.button.load_older') }}
                        </p>
                    </template>

                    <MessageScrollerContent id="chat-transcript" class="gap-3">
                        <ChatMessageBubble
                            v-for="message in props.messages"
                            :key="message.id"
                            :message="message"
                            :current-user-id="props.currentUserId"
                        />
                    </MessageScrollerContent>
                </InfiniteScroll>

                <MessageScrollerContent v-else class="gap-3">
                    <div v-if="props.hasOlder" class="text-center">
                        <Button
                            variant="ghost"
                            size="sm"
                            :disabled="props.loadingOlder"
                            data-test="chat-load-older"
                            @click="emit('loadOlder')"
                        >
                            {{ t('chat.button.load_older') }}
                        </Button>
                    </div>

                    <ChatMessageBubble
                        v-for="message in props.messages"
                        :key="message.id"
                        :message="message"
                        :current-user-id="props.currentUserId"
                    />
                </MessageScrollerContent>

                <p
                    v-if="props.peerReadAt && props.messages.length > 0"
                    class="pt-2 text-right text-xs text-muted-foreground"
                    data-test="chat-read-receipt"
                >
                    {{ t('chat.label.read') }}
                </p>
            </MessageScrollerViewport>

            <MessageScrollerButton
                direction="end"
                size="sm"
                class="w-auto gap-1 px-3"
                data-test="chat-jump-to-latest"
            >
                {{ t('chat.button.jump_to_latest') }}
            </MessageScrollerButton>
        </MessageScroller>
    </MessageScrollerProvider>
</template>
