<script setup lang="ts">
import { Head, InfiniteScroll, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import PageWrapper from '@/components/PageWrapper.vue';
import { Bubble, BubbleContent } from '@/components/ui/bubble';
import { buttonVariants } from '@/components/ui/button';
import {
    Message,
    MessageContent,
    MessageHeader,
} from '@/components/ui/message';
import {
    MessageScroller,
    MessageScrollerButton,
    MessageScrollerContent,
    MessageScrollerItem,
    MessageScrollerProvider,
    MessageScrollerViewport,
} from '@/components/ui/message-scroller';
import { translate, useTranslations } from '@/composables/useTranslations';
import { index } from '@/routes/chat/audit';
import type { ChatAuditLog, ChatMessage, ChatProfile } from '@/types';

type AuditConversation = {
    id: string;
    participants: ChatProfile[];
    last_message_at: string | null;
    message_count: number;
};

type LayoutProps = {
    locale: string;
    fallbackLocale: string;
};

const props = defineProps<{
    conversation: AuditConversation;
    messages: { data: ChatMessage[] };
    auditLogs: { data: ChatAuditLog[] };
}>();

defineOptions({
    layout: (layoutProps: LayoutProps) => ({
        breadcrumbs: [
            {
                title: translate(
                    'chat.audit.title',
                    layoutProps.locale,
                    layoutProps.fallbackLocale,
                ),
                href: index(),
            },
        ],
    }),
});

const { t } = useTranslations();

const title = computed(() =>
    props.conversation.participants
        .map((participant) => participant.name)
        .join(' · '),
);

const nameFor = (senderId: string): string =>
    props.conversation.participants.find(
        (participant) => participant.id === senderId,
    )?.name ?? senderId;
</script>

<template>
    <div class="contents">
        <Head :title="t('chat.audit.title')" />

        <PageWrapper :title="title" :description="t('chat.audit.notice')">
            <template #actions>
                <Link
                    :href="index()"
                    :class="buttonVariants({ variant: 'outline', size: 'sm' })"
                    data-test="chat-audit-back"
                >
                    {{ t('chat.audit.button.back') }}
                </Link>
            </template>

            <div class="space-y-6">
                <section class="space-y-2">
                    <h2 class="text-sm font-medium">
                        {{ t('chat.audit.label.messages') }}:
                        <span data-test="chat-audit-message-count">
                            {{ props.conversation.message_count }}
                        </span>
                    </h2>

                    <p
                        v-if="props.messages.data.length === 0"
                        class="rounded-xl border py-10 text-center text-sm"
                        data-test="chat-audit-messages-empty"
                    >
                        {{ t('chat.audit.empty.messages') }}
                    </p>

                    <MessageScrollerProvider
                        v-else
                        default-scroll-position="start"
                    >
                        <MessageScroller
                            class="h-[28rem] rounded-xl border"
                            data-test="chat-audit-messages"
                        >
                            <MessageScrollerViewport class="p-4">
                                <!--
                                    The transcript is paged oldest first, so the
                                    whole history is reachable by scrolling down
                                    rather than capped at a first window.
                                -->
                                <InfiniteScroll
                                    data="messages"
                                    preserve-url
                                    items-element="#chat-audit-transcript"
                                >
                                    <MessageScrollerContent
                                        id="chat-audit-transcript"
                                        class="gap-3"
                                    >
                                        <MessageScrollerItem
                                            v-for="message in props.messages
                                                .data"
                                            :key="message.id"
                                            :message-id="message.id"
                                            :data-test="`chat-audit-message-${message.id}`"
                                        >
                                            <Message align="start">
                                                <MessageContent>
                                                    <MessageHeader>
                                                        {{
                                                            nameFor(
                                                                message.sender_id,
                                                            )
                                                        }}
                                                    </MessageHeader>
                                                    <Bubble variant="muted">
                                                        <BubbleContent
                                                            class="whitespace-pre-wrap"
                                                        >
                                                            {{ message.body }}
                                                        </BubbleContent>
                                                    </Bubble>
                                                </MessageContent>
                                            </Message>
                                        </MessageScrollerItem>
                                    </MessageScrollerContent>
                                </InfiniteScroll>
                            </MessageScrollerViewport>

                            <MessageScrollerButton direction="end" />
                        </MessageScroller>
                    </MessageScrollerProvider>
                </section>

                <section class="space-y-2">
                    <h2 class="text-sm font-medium">
                        {{ t('chat.audit.label.access_log') }}
                    </h2>

                    <p
                        v-if="props.auditLogs.data.length === 0"
                        class="rounded-xl border py-10 text-center text-sm"
                        data-test="chat-audit-logs-empty"
                    >
                        {{ t('chat.audit.empty.logs') }}
                    </p>

                    <InfiniteScroll
                        v-else
                        data="auditLogs"
                        preserve-url
                        items-element="#chat-audit-log-list"
                    >
                        <ul
                            id="chat-audit-log-list"
                            class="space-y-2 rounded-xl border p-4"
                            data-test="chat-audit-logs"
                        >
                            <li
                                v-for="log in props.auditLogs.data"
                                :key="log.id"
                                class="text-sm"
                                :data-test="`chat-audit-log-${log.id}`"
                            >
                                <span class="font-medium">
                                    {{ log.viewer.name }}
                                </span>
                                <span class="text-muted-foreground">
                                    — {{ log.viewed_at }}
                                    <template v-if="log.ip_address">
                                        · {{ log.ip_address }}
                                    </template>
                                </span>
                            </li>
                        </ul>
                    </InfiniteScroll>
                </section>
            </div>
        </PageWrapper>
    </div>
</template>
