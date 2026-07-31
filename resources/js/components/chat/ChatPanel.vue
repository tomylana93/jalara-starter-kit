<script setup lang="ts">
import { computed } from 'vue';
import ChatComposer from '@/components/chat/ChatComposer.vue';
import ChatMessageList from '@/components/chat/ChatMessageList.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { getInitials } from '@/composables/useInitials';
import { useTranslations } from '@/composables/useTranslations';
import type { ChatConversation, ChatMessage, ChatProfile } from '@/types';

type Props = {
    conversation: ChatConversation | null;
    /* Set while composing the first message to somebody new. */
    pendingRecipient?: ChatProfile | null;
    messages: readonly ChatMessage[];
    currentUserId: string | null;
    /* Set on the chat page, where Inertia owns the transcript's paging. */
    scrollProp?: string | null;
    sending?: boolean;
    hasOlder?: boolean;
    loadingOlder?: boolean;
    error?: string | null;
};

const props = withDefaults(defineProps<Props>(), {
    pendingRecipient: null,
    scrollProp: null,
    sending: false,
    hasOlder: false,
    loadingOlder: false,
    error: null,
});

const emit = defineEmits<{
    send: [body: string];
    loadOlder: [];
    seen: [messageId: string];
}>();

const { t } = useTranslations();

const participant = computed<ChatProfile | null>(
    () => props.conversation?.participant ?? props.pendingRecipient,
);

/* History stays readable when the other side can no longer receive anything. */
const canSend = computed(() => participant.value?.available === true);
</script>

<template>
    <div class="flex h-full min-h-0 flex-col">
        <div
            v-if="participant"
            class="flex items-center gap-3 border-b px-4 py-3"
            data-test="chat-panel-header"
        >
            <Avatar class="size-9 shrink-0 rounded-full">
                <AvatarImage
                    v-if="participant.avatar"
                    :src="participant.avatar"
                    :alt="participant.name"
                />
                <AvatarFallback
                    class="bg-primary/10 text-xs font-semibold text-primary"
                >
                    {{ getInitials(participant.name) }}
                </AvatarFallback>
            </Avatar>
            <div class="min-w-0">
                <p class="truncate text-sm font-medium">
                    {{ participant.name }}
                </p>
                <p class="truncate text-xs text-muted-foreground">
                    {{
                        participant.available
                            ? (participant.role ?? '')
                            : t('chat.label.unavailable')
                    }}
                </p>
            </div>
        </div>

        <p
            v-if="!participant"
            class="flex flex-1 flex-col items-center justify-center gap-1 p-8 text-center text-sm"
            data-test="chat-panel-empty"
        >
            <span class="font-medium">{{ t('chat.empty.unselected') }}</span>
            <span class="text-muted-foreground">
                {{ t('chat.empty.unselected_description') }}
            </span>
        </p>

        <template v-else>
            <ChatMessageList
                :messages="props.messages"
                :current-user-id="props.currentUserId"
                :scroll-prop="props.scrollProp"
                :has-older="props.hasOlder"
                :loading-older="props.loadingOlder"
                :peer-read-at="props.conversation?.peer_read_at ?? null"
                @load-older="emit('loadOlder')"
                @seen="(messageId) => emit('seen', messageId)"
            />

            <p
                v-if="!canSend"
                class="border-t px-4 py-3 text-xs text-muted-foreground"
                data-test="chat-peer-unavailable"
            >
                {{ t('chat.message.peer_unavailable') }}
            </p>

            <p
                v-if="props.error"
                class="border-t px-4 py-2 text-xs text-destructive"
                data-test="chat-error"
            >
                {{ t(props.error) }}
            </p>

            <ChatComposer
                v-if="canSend"
                :sending="props.sending"
                @send="(body) => emit('send', body)"
            />
        </template>
    </div>
</template>
