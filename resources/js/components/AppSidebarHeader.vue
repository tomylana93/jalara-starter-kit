<script setup lang="ts">
import { Search } from '@lucide/vue';
import AppearanceToggle from '@/components/AppearanceToggle.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import NotificationBell from '@/components/NotificationBell.vue';
import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useTranslations } from '@/composables/useTranslations';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const { t } = useTranslations();

const openGlobalSearch = (): void => {
    window.dispatchEvent(new CustomEvent('open-global-search'));
};
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <div class="ml-auto flex items-center gap-2">
            <Button
                variant="ghost"
                size="icon"
                :aria-label="t('navigation.menu.search')"
                data-test="global-search-trigger"
                @click="openGlobalSearch"
            >
                <Search />
            </Button>
            <NotificationBell />
            <AppearanceToggle />
        </div>
    </header>
</template>
