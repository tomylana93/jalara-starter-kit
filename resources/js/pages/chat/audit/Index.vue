<script setup lang="ts">
import { Head, InfiniteScroll, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import PageWrapper from '@/components/PageWrapper.vue';
import { buttonVariants } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { translate, useTranslations } from '@/composables/useTranslations';
import { index, show } from '@/routes/chat/audit';
import type { ChatAuditConversation } from '@/types';

type LayoutProps = {
    locale: string;
    fallbackLocale: string;
};

const props = defineProps<{
    conversations: { data: ChatAuditConversation[] };
    search: string | null;
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

const rows = computed(() => props.conversations.data);
const term = ref(props.search ?? '');

let debounce: ReturnType<typeof setTimeout> | null = null;

/*
 * A new term starts a new result set, so the merged pages have to be reset or
 * the previous search would stay on screen underneath the new one.
 */
const runSearch = (value: string): void => {
    router.visit(
        index({ query: value.trim() === '' ? {} : { search: value.trim() } }),
        {
            only: ['conversations', 'search'],
            reset: ['conversations'],
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
};

watch(term, (value) => {
    if (debounce !== null) {
        clearTimeout(debounce);
    }

    debounce = setTimeout(() => runSearch(value), 300);
});
</script>

<template>
    <div class="contents">
        <Head :title="t('chat.audit.title')" />

        <PageWrapper
            :title="t('chat.audit.title')"
            :description="t('chat.audit.description')"
        >
            <div class="space-y-4">
                <p
                    class="text-sm text-muted-foreground"
                    data-test="chat-audit-notice"
                >
                    {{ t('chat.audit.notice') }}
                </p>

                <div class="max-w-sm">
                    <label class="sr-only" for="chat-audit-search">
                        {{ t('chat.audit.label.search') }}
                    </label>
                    <Input
                        id="chat-audit-search"
                        v-model="term"
                        type="search"
                        :placeholder="t('chat.audit.placeholder.search')"
                        data-test="chat-audit-search"
                    />
                </div>

                <p
                    v-if="rows.length === 0"
                    class="rounded-xl border py-12 text-center font-medium"
                    data-test="chat-audit-empty"
                >
                    {{
                        props.search
                            ? t('chat.audit.empty.search')
                            : t('chat.audit.empty.conversations')
                    }}
                </p>

                <InfiniteScroll
                    v-else
                    data="conversations"
                    preserve-url
                    items-element="#chat-audit-list"
                >
                    <ul
                        id="chat-audit-list"
                        class="space-y-2"
                        data-test="chat-audit-list"
                    >
                        <li
                            v-for="conversation in rows"
                            :key="conversation.id"
                            class="flex flex-col gap-3 rounded-xl border p-4 sm:flex-row sm:items-center sm:justify-between"
                            :data-test="`chat-audit-row-${conversation.id}`"
                        >
                            <div class="min-w-0 space-y-1">
                                <p class="truncate font-medium">
                                    {{
                                        conversation.participants
                                            .map(
                                                (participant) =>
                                                    participant.name,
                                            )
                                            .join(' · ')
                                    }}
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    {{ t('chat.audit.label.messages') }}:
                                    {{ conversation.message_count }}
                                </p>
                            </div>

                            <Link
                                :href="show(conversation.id)"
                                :class="
                                    buttonVariants({
                                        variant: 'outline',
                                        size: 'sm',
                                    })
                                "
                                :data-test="`chat-audit-open-${conversation.id}`"
                            >
                                {{ t('chat.audit.button.open') }}
                            </Link>
                        </li>
                    </ul>
                </InfiniteScroll>
            </div>
        </PageWrapper>
    </div>
</template>
