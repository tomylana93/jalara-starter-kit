<script setup lang="ts">
import { SmilePlus } from '@lucide/vue';
import type { AcceptableValue } from 'reka-ui';
import { computed } from 'vue';
import ChatMessageMeta from '@/components/chat/ChatMessageMeta.vue';
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
import { Message, MessageContent } from '@/components/ui/message';
import { MessageScrollerItem } from '@/components/ui/message-scroller';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { useTranslations } from '@/composables/useTranslations';
import { formatBrowserTime } from '@/lib/dateTime';
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
    read?: boolean;
    audit?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    read: false,
    audit: false,
});

const emit = defineEmits<{
    react: [message: ChatMessage, emoji: string | null];
}>();

const { t } = useTranslations();
const own = computed(() => props.message.sender_id === props.currentUserId);
const reaction = computed(() => props.message.reactions[0] ?? null);
const time = computed(() =>
    props.message.created_at ? formatBrowserTime(props.message.created_at) : '',
);

/* Only the sender is told whether a message was read; the reader already knows. */
const status = computed<'none' | 'sent' | 'read'>(() => {
    if (!own.value) {
        return 'none';
    }

    return props.read ? 'read' : 'sent';
});

/**
 * Apply the picker's new selection.
 *
 * The toggle group reports the resulting selection rather than the emoji that
 * was pressed, so choosing the emoji already applied arrives here as an empty
 * selection - which is exactly the "remove my reaction" case.
 */
const chooseReaction = (value: AcceptableValue | AcceptableValue[]): void => {
    if (props.audit || own.value || props.currentUserId === null) {
        return;
    }

    emit(
        'react',
        props.message,
        typeof value === 'string' && value !== '' ? value : null,
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
                                <!--
                                    A message with text trails its meta after
                                    that text instead, so this only covers the
                                    image-only case. The scrim is what keeps it
                                    readable over a bright photo.
                                -->
                                <ChatMessageMeta
                                    v-if="!props.message.body"
                                    :time="time"
                                    :status="status"
                                    class="pointer-events-none absolute right-1.5 bottom-1.5 z-20 rounded-full bg-black/55 px-1.5 py-0.5 text-white"
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

                    <BubbleContent v-if="props.message.body">
                        <span
                            class="whitespace-pre-wrap"
                            data-test="chat-message-body"
                            >{{ props.message.body }}</span
                        >
                        <!--
                            Its own row under the text rather than trailing it,
                            so a long last line never has to share space with the
                            meta, and settled into the bottom-right corner from
                            there.
                        -->
                        <ChatMessageMeta
                            :time="time"
                            :status="status"
                            class="mt-1 flex w-full justify-end opacity-70"
                        />
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
                    <PopoverContent class="w-auto p-2">
                        <!--
                            `grid` overrides the primitive's single-row `flex`:
                            twelve emoji belong in a 6-column block, not a
                            segmented control.
                        -->
                        <ToggleGroup
                            type="single"
                            :model-value="reaction?.emoji ?? ''"
                            class="grid grid-cols-6 gap-1"
                            @update:model-value="chooseReaction"
                        >
                            <ToggleGroupItem
                                v-for="emoji in EMOJIS"
                                :key="emoji"
                                :value="emoji"
                                class="px-0 text-lg"
                                :aria-label="emoji"
                                :data-test="`chat-reaction-option-${emoji}`"
                            >
                                {{ emoji }}
                            </ToggleGroupItem>
                        </ToggleGroup>
                    </PopoverContent>
                </Popover>
            </MessageContent>
        </Message>
    </MessageScrollerItem>
</template>
