<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { useTranslations } from '@/composables/useTranslations';
import { breadcrumbLayout } from '@/lib/breadcrumbs';
import { dashboard } from '@/routes';

defineOptions({
    /*
     * Inertia hands every shared prop to the page component, and these pages
     * render a fragment, so undeclared props would otherwise leak onto the DOM
     * as extraneous attributes.
     */
    inheritAttrs: false,
    layout: breadcrumbLayout(() => [
        { key: 'dashboard.page.title', href: dashboard() },
    ]),
});

const { t } = useTranslations();
</script>

<template>
    <Head :title="t('dashboard.page.title')" />

    <div
        class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
    >
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
        </div>
        <div
            class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
        >
            <PlaceholderPattern />
        </div>
    </div>
</template>
