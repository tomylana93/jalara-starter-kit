<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import ChatWidget from '@/components/chat/ChatWidget.vue';
import GlobalSearch from '@/components/GlobalSearch.vue';
import { SidebarProvider } from '@/components/ui/sidebar';
import type { AppVariant } from '@/types';

type Props = {
    variant?: AppVariant;
};

withDefaults(defineProps<Props>(), {
    variant: 'sidebar',
});

const isOpen = usePage().props.sidebarOpen;
</script>

<template>
    <div v-if="variant === 'header'" class="flex min-h-screen w-full flex-col">
        <slot />
        <ChatWidget />
        <GlobalSearch />
    </div>
    <SidebarProvider v-else :default-open="isOpen">
        <slot />
        <ChatWidget />
        <GlobalSearch />
    </SidebarProvider>
</template>
