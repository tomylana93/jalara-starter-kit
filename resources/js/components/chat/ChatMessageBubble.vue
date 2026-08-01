<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { SmilePlus } from '@lucide/vue';
import { computed } from 'vue';
import {
    Attachment,
    AttachmentMedia,
    AttachmentTrigger,
} from '@/components/ui/attachment';
import { Bubble, BubbleContent, BubbleReactions } from '@/components/ui/bubble';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    Message,
    MessageContent,
    MessageFooter,
} from '@/components/ui/message';
import { MessageScrollerItem } from '@/components/ui/message-scroller';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { useTranslations } from '@/composables/useTranslations';
import type { ChatMessage } from '@/types';

const EMOJIS = [
    '👍',
    '❤️',
    '😂',
    '😮',
    '😢',
    '🙏',
    '👎',
    '🎉',
    '🔥',
    '👀',
    '🤔',
    '😡',
];

type Props = {
    message: ChatMessage;
    currentUserId: string | null;
    latestOutgoing?: boolean;
    read?: boolean;
    audit?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    latestOutgoing: false,
    read: false,
    audit: false,
});

const emit = defineEmits<{
    react: [message: ChatMessage, emoji: string | null];
}>();

const page = usePage();
const { t } = useTranslations();
const own = computed(() => props.message.sender_id === props.currentUserId);
const reaction = computed(() => props.message.reactions[0] ?? null);
const time = computed(() => {
    if (!props.message.created_at) {
        return '';
    }

    return new Intl.DateTimeFormat(page.props.locale, {
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(props.message.created_at));
});

const chooseReaction = (emoji: string): void => {
    if (props.audit || own.value || props.currentUserId === null) {
        return;
    }

    emit(
        'react',
        props.message,
        reaction.value?.emoji === emoji ? null : emoji,
    );
};
</script>

<template>
    <MessageScrollerItem
        :message-id="props.message.id"
        :data-test="`chat-message-${props.message.id}`"
    >
        <Message :align="own ? 'end' : 'start'">
            <MessageContent>
                <Bubble
                    :align="own ? 'end' : 'start'"
                    :variant="own ? 'default' : 'muted'"
                    :class="props.message.reactions.length > 0 ? 'mb-3' : ''"
                >
                    <Dialog v-if="props.message.image">
                        <DialogTrigger as-child>
                            <Attachment
                                orientation="vertical"
                                size="sm"
                                class="w-48! overflow-hidden border-0 bg-transparent"
                                data-test="chat-message-image"
                            >
                                <AttachmentMedia
                                    variant="image"
                                    class="w-full!"
                                >
                                    <img
                                        :src="props.message.image.url"
                                        :alt="t('chat.label.image')"
                                        class="aspect-square w-full object-cover"
                                    />
                                </AttachmentMedia>
                                <AttachmentTrigger
                                    :aria-label="t('chat.button.preview_image')"
                                />
                            </Attachment>
                        </DialogTrigger>
                        <DialogContent
                            class="max-w-4xl border-0 bg-transparent p-0 shadow-none"
                        >
                            <DialogTitle class="sr-only">
                                {{ t('chat.label.image') }}
                            </DialogTitle>
                            <DialogDescription class="sr-only">
                                {{ t('chat.label.image_preview') }}
                            </DialogDescription>
                            <img
                                :src="props.message.image.url"
                                :alt="t('chat.label.image')"
                                class="max-h-[85vh] w-full rounded-lg object-contain"
                            />
                        </DialogContent>
                    </Dialog>

                    <BubbleContent
                        v-if="props.message.body"
                        class="whitespace-pre-wrap"
                        data-test="chat-message-body"
                    >
                        {{ props.message.body }}
                    </BubbleContent>

                    <BubbleReactions
                        v-if="reaction"
                        :align="own ? 'end' : 'start'"
                        data-test="chat-message-reaction"
                    >
                        {{ reaction.emoji }}
                    </BubbleReactions>
                </Bubble>

                <Popover v-if="!props.audit && !own">
                    <PopoverTrigger as-child>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="mt-1 size-6 rounded-full text-muted-foreground"
                            :aria-label="t('chat.button.react')"
                            data-test="chat-reaction-picker"
                        >
                            <SmilePlus class="size-3.5" />
                        </Button>
                    </PopoverTrigger>
                    <PopoverContent class="grid w-auto grid-cols-6 gap-1 p-2">
                        <button
                            v-for="emoji in EMOJIS"
                            :key="emoji"
                            type="button"
                            class="rounded-md p-1.5 text-lg hover:bg-muted"
                            :class="reaction?.emoji === emoji ? 'bg-muted' : ''"
                            @click="chooseReaction(emoji)"
                        >
                            {{ emoji }}
                        </button>
                    </PopoverContent>
                </Popover>
            </MessageContent>

            <MessageFooter class="gap-1">
                <span>{{ time }}</span>
                <template v-if="own && props.latestOutgoing">
                    <span aria-hidden="true">·</span>
                    <span data-test="chat-read-receipt">
                        {{
                            props.read
                                ? t('chat.label.read')
                                : t('chat.label.sent')
                        }}
                    </span>
                </template>
            </MessageFooter>
        </Message>
    </MessageScrollerItem>
</template>
