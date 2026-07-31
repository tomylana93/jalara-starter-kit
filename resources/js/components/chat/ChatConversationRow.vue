<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { getInitials } from '@/composables/useInitials';
import { useTranslations } from '@/composables/useTranslations';
import type { ChatConversation } from '@/types';

type Props = {
    conversation: ChatConversation;
    active: boolean;
};

const props = defineProps<Props>();

const emit = defineEmits<{
    select: [conversationId: string];
}>();

const { t } = useTranslations();
</script>

<template>
    <li>
        <button
            type="button"
            class="flex w-full cursor-pointer items-center gap-3 px-4 py-3 text-left transition-colors hover:bg-accent/50 aria-[current=true]:bg-accent"
            :aria-current="props.active"
            :data-test="`chat-conversation-${props.conversation.id}`"
            :data-unread="
                props.conversation.unread_count > 0 ? 'true' : 'false'
            "
            @click="emit('select', props.conversation.id)"
        >
            <Avatar class="size-9 shrink-0 rounded-full">
                <AvatarImage
                    v-if="props.conversation.participant?.avatar"
                    :src="props.conversation.participant.avatar"
                    :alt="props.conversation.participant.name"
                />
                <AvatarFallback
                    class="bg-primary/10 text-xs font-semibold text-primary"
                >
                    {{ getInitials(props.conversation.participant?.name) }}
                </AvatarFallback>
            </Avatar>

            <span class="min-w-0 flex-1">
                <span class="flex items-center justify-between gap-2">
                    <span class="truncate text-sm font-medium">
                        {{ props.conversation.participant?.name }}
                    </span>
                    <span
                        v-if="props.conversation.unread_count > 0"
                        class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-primary px-1.5 text-[0.625rem] leading-none font-medium text-primary-foreground tabular-nums"
                        :data-test="`chat-unread-${props.conversation.id}`"
                    >
                        {{
                            props.conversation.unread_count > 9
                                ? '9+'
                                : props.conversation.unread_count
                        }}
                    </span>
                </span>
                <span
                    class="mt-0.5 block truncate text-xs text-muted-foreground"
                >
                    {{
                        props.conversation.participant?.available === false
                            ? t('chat.label.unavailable')
                            : (props.conversation.last_message?.body ??
                              props.conversation.participant?.role ??
                              '')
                    }}
                </span>
            </span>
        </button>
    </li>
</template>
