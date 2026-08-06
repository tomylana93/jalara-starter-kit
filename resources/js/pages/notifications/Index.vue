<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
} from '@lucide/vue';
import { computed } from 'vue';
import PageWrapper from '@/components/PageWrapper.vue';
import { Button, buttonVariants } from '@/components/ui/button';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyTitle,
} from '@/components/ui/empty';
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemGroup,
    ItemTitle,
} from '@/components/ui/item';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationFirst,
    PaginationItem,
    PaginationLast,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import { translate, useTranslations } from '@/composables/useTranslations';
import { index, read, readAll } from '@/routes/notifications';
import type {
    NotificationFilter,
    NotificationItem,
    NotificationPayload,
} from '@/types';

type LayoutProps = {
    locale: string;
    fallbackLocale: string;
};

const props = defineProps<{
    notifications: NotificationPayload;
    filter: NotificationFilter;
}>();

defineOptions({
    layout: (layoutProps: LayoutProps) => ({
        breadcrumbs: [
            {
                title: translate(
                    'notification.page.title',
                    layoutProps.locale,
                    layoutProps.fallbackLocale,
                ),
                href: index(),
            },
        ],
    }),
});

const { t } = useTranslations();

const filters: NotificationFilter[] = ['all', 'unread'];

const items = computed(() => props.notifications.data);
const meta = computed(() => props.notifications.meta);

const hasUnread = computed(() =>
    items.value.some((item) => item.read_at === null),
);

const summary = computed(() =>
    t('notification.summary', {
        from: meta.value.from ?? 0,
        to: meta.value.to ?? 0,
        total: meta.value.total,
    }),
);

/*
 * Filtering restarts paging, so the page is deliberately dropped rather than
 * carried over to a window that may not exist under the new filter.
 */
const filterHref = (filter: NotificationFilter) =>
    index({ query: filter === 'all' ? {} : { filter } });

const goToPage = (page: number): void => {
    router.get(
        index({
            query: {
                ...(props.filter === 'all' ? {} : { filter: props.filter }),
                ...(page === 1 ? {} : { page }),
            },
        }),
        {},
        { preserveScroll: true, preserveState: true, replace: true },
    );
};

const markAsRead = (item: NotificationItem): void => {
    router.patch(read(item.id), {}, { preserveScroll: true });
};

const markAllAsRead = (): void => {
    router.patch(readAll(), {}, { preserveScroll: true });
};

/*
 * The read and the navigation travel in the same request: the server redirects
 * to the destination it holds. Two visits fired side by side race each other,
 * and the `back()` response of the read wins, which flickers the destination
 * and lands the user back on this page.
 */
const open = (item: NotificationItem): void => {
    router.patch(read(item.id), { open: true });
};
</script>

<template>
    <div class="contents">
        <Head :title="t('notification.page.title')" />

        <PageWrapper
            :title="t('notification.page.title')"
            :description="t('notification.page.description')"
        >
            <template #actions>
                <Button
                    v-if="hasUnread"
                    variant="outline"
                    data-test="notification-mark-all"
                    @click="markAllAsRead"
                >
                    {{ t('notification.button.mark_all') }}
                </Button>
            </template>

            <div class="space-y-4">
                <nav
                    class="flex items-center gap-2"
                    :aria-label="t('notification.filter.label')"
                >
                    <Link
                        v-for="option in filters"
                        :key="option"
                        :href="filterHref(option)"
                        :class="
                            buttonVariants({
                                variant:
                                    props.filter === option
                                        ? 'secondary'
                                        : 'ghost',
                                size: 'sm',
                            })
                        "
                        :aria-current="
                            props.filter === option ? 'page' : undefined
                        "
                        :data-test="`notification-filter-${option}`"
                        preserve-scroll
                    >
                        {{ t(`notification.filter.${option}`) }}
                    </Link>
                </nav>

                <Empty
                    v-if="items.length === 0"
                    class="border border-sidebar-border/70"
                    data-test="notification-empty"
                >
                    <EmptyHeader>
                        <EmptyTitle>
                            {{ t('notification.empty.title') }}
                        </EmptyTitle>
                        <EmptyDescription
                            data-test="notification-empty-description"
                        >
                            {{ t(`notification.empty.${props.filter}`) }}
                        </EmptyDescription>
                    </EmptyHeader>
                </Empty>

                <ItemGroup v-else class="gap-2" data-test="notification-list">
                    <Item
                        v-for="item in items"
                        :key="item.id"
                        variant="outline"
                        class="flex-col items-start gap-3 border-sidebar-border/70 sm:flex-row sm:items-start"
                        :data-test="`notification-row-${item.id}`"
                        :data-unread="item.read_at === null ? 'true' : 'false'"
                    >
                        <ItemContent>
                            <ItemTitle class="gap-2">
                                <span
                                    v-if="item.read_at === null"
                                    class="size-1.5 shrink-0 rounded-full bg-primary"
                                    aria-hidden="true"
                                ></span>
                                {{ item.title }}
                            </ItemTitle>
                            <ItemDescription>
                                {{ item.message }}
                            </ItemDescription>
                        </ItemContent>

                        <ItemActions>
                            <Button
                                v-if="item.url !== null"
                                variant="outline"
                                size="sm"
                                :data-test="`notification-open-${item.id}`"
                                @click="open(item)"
                            >
                                {{ t('notification.button.open') }}
                            </Button>
                            <Button
                                v-if="item.read_at === null"
                                variant="ghost"
                                size="sm"
                                :data-test="`notification-read-${item.id}`"
                                @click="markAsRead(item)"
                            >
                                {{ t('notification.button.mark_read') }}
                            </Button>
                        </ItemActions>
                    </Item>
                </ItemGroup>

                <div
                    v-if="meta.total > 0"
                    class="flex flex-col items-center justify-between gap-4 sm:flex-row sm:gap-2"
                >
                    <p
                        class="text-sm text-muted-foreground"
                        data-test="notification-summary"
                    >
                        {{ summary }}
                    </p>

                    <Pagination
                        v-if="meta.lastPage > 1"
                        :page="meta.page"
                        :items-per-page="meta.perPage"
                        :total="meta.total"
                        :sibling-count="1"
                        show-edges
                        :aria-label="t('notification.pagination.label')"
                        class="mx-0 w-auto justify-end"
                        @update:page="goToPage"
                    >
                        <PaginationContent v-slot="{ items: pages }">
                            <PaginationFirst
                                :aria-label="t('notification.pagination.first')"
                                data-test="notification-first-page"
                            >
                                <ChevronsLeft class="size-4" />
                            </PaginationFirst>
                            <PaginationPrevious
                                :aria-label="
                                    t('notification.pagination.previous')
                                "
                                data-test="notification-previous-page"
                            >
                                <ChevronLeft class="size-4" />
                            </PaginationPrevious>

                            <template v-for="(item, position) in pages">
                                <PaginationItem
                                    v-if="item.type === 'page'"
                                    :key="`page-${item.value}`"
                                    :value="item.value"
                                    :is-active="item.value === meta.page"
                                    :data-test="`notification-page-${item.value}`"
                                >
                                    {{ item.value }}
                                </PaginationItem>
                                <PaginationEllipsis
                                    v-else
                                    :key="`ellipsis-${position}`"
                                    :index="position"
                                />
                            </template>

                            <PaginationNext
                                :aria-label="t('notification.pagination.next')"
                                data-test="notification-next-page"
                            >
                                <ChevronRight class="size-4" />
                            </PaginationNext>
                            <PaginationLast
                                :aria-label="t('notification.pagination.last')"
                                data-test="notification-last-page"
                            >
                                <ChevronsRight class="size-4" />
                            </PaginationLast>
                        </PaginationContent>
                    </Pagination>
                </div>
            </div>
        </PageWrapper>
    </div>
</template>
