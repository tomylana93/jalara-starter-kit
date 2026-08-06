<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { useEchoNotification } from '@laravel/echo-vue';
import { Bell } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useTranslations } from '@/composables/useTranslations';
import { index, read, readAll } from '@/routes/notifications';
import type { NotificationItem } from '@/types';

/* Matches the server's bell limit in HandleInertiaRequests. */
const BELL_LIMIT = 5;

const { t } = useTranslations();
const page = usePage();

const shared = computed(() => page.props.notificationBell);
const userId = computed(() => page.props.auth.user?.id ?? null);

/*
 * Realtime arrivals are held locally and merged ahead of the shared prop, so a
 * broadcast shows up without waiting for the next Inertia response. Once the
 * server includes the same row in its shared payload, the id dedupe below drops
 * the local copy.
 */
const live = ref<NotificationItem[]>([]);

const items = computed<NotificationItem[]>(() => {
    const seen = new Set<string>();

    return [...live.value, ...shared.value.items]
        .filter((item) => {
            if (seen.has(item.id)) {
                return false;
            }

            seen.add(item.id);

            return true;
        })
        .slice(0, BELL_LIMIT);
});

const unreadCount = computed(
    () =>
        shared.value.unreadCount +
        live.value.filter(
            (item) =>
                item.read_at === null &&
                !shared.value.items.some((known) => known.id === item.id),
        ).length,
);

const hasUnread = computed(() => unreadCount.value > 0);

const badgeLabel = computed(() =>
    unreadCount.value > 9 ? '9+' : String(unreadCount.value),
);

/*
 * The shared prop is authoritative once it catches up, so any locally held row
 * it already contains is released to avoid double counting.
 */
watch(
    () => shared.value.items,
    (known) => {
        live.value = live.value.filter(
            (item) => !known.some((row) => row.id === item.id),
        );
    },
);

useEchoNotification<NotificationItem>(
    /* Empty while unauthenticated; the bell only renders for a user. */
    `App.Models.User.${userId.value ?? ''}`,
    (notification) => {
        if (live.value.some((item) => item.id === notification.id)) {
            return;
        }

        live.value = [notification, ...live.value];
    },
);

const markAsRead = (item: NotificationItem): void => {
    if (item.read_at !== null) {
        return;
    }

    router.patch(read(item.id), {}, { preserveScroll: true });
};

const markAllAsRead = (): void => {
    if (!hasUnread.value) {
        return;
    }

    router.patch(readAll(), {}, { preserveScroll: true });
};

/*
 * A notification may carry no destination, so the row stays a plain item and
 * only records the read state.
 *
 * When it does carry one, the read and the navigation travel in the same
 * request: the server redirects to the destination it holds. Two visits fired
 * side by side race each other, and the `back()` response of the read wins,
 * which flickers the destination and lands the user back where they were.
 */
const open = (item: NotificationItem): void => {
    if (item.url === null) {
        markAsRead(item);

        return;
    }

    router.patch(read(item.id), { open: true });
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger :as-child="true">
            <Button
                variant="ghost"
                size="icon"
                class="group relative h-9 w-9 cursor-pointer"
                data-test="notification-bell"
                :aria-label="
                    hasUnread
                        ? t('notification.bell.unread', {
                              count: unreadCount,
                          })
                        : t('notification.bell.label')
                "
            >
                <Bell class="size-5 opacity-80 group-hover:opacity-100" />
                <Badge
                    v-if="hasUnread"
                    class="absolute -top-0.5 -right-0.5 h-4 min-w-4 justify-center rounded-full border-transparent bg-red-600 px-1 text-[0.625rem] leading-none text-white tabular-nums"
                    data-test="notification-badge"
                >
                    {{ badgeLabel }}
                </Badge>
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-80">
            <DropdownMenuLabel class="flex items-center justify-between gap-2">
                <span>{{ t('notification.bell.title') }}</span>
                <Button
                    v-if="hasUnread"
                    type="button"
                    variant="link"
                    class="h-auto p-0 text-xs font-normal text-muted-foreground"
                    data-test="notification-mark-all"
                    @click="markAllAsRead"
                >
                    {{ t('notification.button.mark_all') }}
                </Button>
            </DropdownMenuLabel>

            <DropdownMenuSeparator />

            <p
                v-if="items.length === 0"
                class="px-2 py-6 text-center text-sm text-muted-foreground"
                data-test="notification-empty"
            >
                {{ t('notification.empty.title') }}
            </p>

            <DropdownMenuItem
                v-for="item in items"
                :key="item.id"
                class="flex cursor-pointer flex-col items-start gap-1"
                :data-test="`notification-item-${item.id}`"
                :data-unread="item.read_at === null ? 'true' : 'false'"
                @select="open(item)"
            >
                <span class="flex w-full items-center gap-2">
                    <span
                        v-if="item.read_at === null"
                        class="size-1.5 shrink-0 rounded-full bg-primary"
                        aria-hidden="true"
                    ></span>
                    <span class="truncate text-sm font-medium">
                        {{ item.title }}
                    </span>
                </span>
                <span class="line-clamp-2 text-xs text-muted-foreground">
                    {{ item.message }}
                </span>
            </DropdownMenuItem>

            <DropdownMenuSeparator />

            <DropdownMenuItem :as-child="true" class="cursor-pointer">
                <Link
                    :href="index()"
                    class="w-full text-center text-sm"
                    data-test="notification-view-all"
                >
                    {{ t('notification.button.view_all') }}
                </Link>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
