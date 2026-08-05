<script setup lang="ts">
import { InfiniteScroll } from '@inertiajs/vue3';
import ChatConversationRow from '@/components/chat/ChatConversationRow.vue';
import { Button } from '@/components/ui/button';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyTitle,
} from '@/components/ui/empty';
import { useTranslations } from '@/composables/useTranslations';
import type { ChatConversation } from '@/types';

type Props = {
    conversations: readonly ChatConversation[];
    activeId: string | null;
    /*
     * Name of the Inertia scroll prop backing the inbox. Set on the chat page,
     * where Inertia owns paging and merges each page into the list; the widget
     * leaves it unset and asks for the next page explicitly.
     */
    scrollProp?: string | null;
    loading?: boolean;
    canLoadMore?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    scrollProp: null,
    loading: false,
    canLoadMore: false,
});

const emit = defineEmits<{
    select: [conversationId: string];
    loadMore: [];
}>();

const { t } = useTranslations();
</script>

<template>
    <div class="flex h-full min-h-0 flex-col">
        <Empty
            v-if="props.conversations.length === 0 && !props.loading"
            data-test="chat-conversations-empty"
        >
            <EmptyHeader>
                <EmptyTitle class="text-sm">
                    {{ t('chat.empty.conversations') }}
                </EmptyTitle>
                <EmptyDescription>
                    {{ t('chat.empty.conversations_description') }}
                </EmptyDescription>
            </EmptyHeader>
        </Empty>

        <InfiniteScroll
            v-else-if="props.scrollProp"
            :data="props.scrollProp"
            preserve-url
            items-element="#chat-conversations"
            class="min-h-0 flex-1 scroll-fade-y overflow-y-auto"
        >
            <ul id="chat-conversations" data-test="chat-conversation-list">
                <ChatConversationRow
                    v-for="conversation in props.conversations"
                    :key="conversation.id"
                    :conversation="conversation"
                    :active="conversation.id === props.activeId"
                    @select="(id) => emit('select', id)"
                />
            </ul>
        </InfiniteScroll>

        <div v-else class="min-h-0 flex-1 scroll-fade-y overflow-y-auto">
            <ul data-test="chat-conversation-list">
                <ChatConversationRow
                    v-for="conversation in props.conversations"
                    :key="conversation.id"
                    :conversation="conversation"
                    :active="conversation.id === props.activeId"
                    @select="(id) => emit('select', id)"
                />
            </ul>

            <div v-if="props.canLoadMore" class="border-t p-2">
                <Button
                    variant="ghost"
                    size="sm"
                    class="w-full"
                    :disabled="props.loading"
                    data-test="chat-load-more-conversations"
                    @click="emit('loadMore')"
                >
                    {{ t('chat.button.load_older') }}
                </Button>
            </div>
        </div>
    </div>
</template>
