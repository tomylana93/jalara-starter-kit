<script setup lang="ts">
import { computed } from 'vue';
import { Bubble, BubbleContent } from '@/components/ui/bubble';
import { Message, MessageContent } from '@/components/ui/message';
import { MessageScrollerItem } from '@/components/ui/message-scroller';
import type { ChatMessage } from '@/types';

type Props = {
    message: ChatMessage;
    currentUserId: string | null;
};

const props = defineProps<Props>();

const own = computed(() => props.message.sender_id === props.currentUserId);
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
                >
                    <BubbleContent class="whitespace-pre-wrap">
                        {{ props.message.body }}
                    </BubbleContent>
                </Bubble>
            </MessageContent>
        </Message>
    </MessageScrollerItem>
</template>
