<script setup lang="ts">
import AppearanceToggle from '@/components/AppearanceToggle.vue';
import { useBranding } from '@/composables/useBranding';

const { title = '', description = '' } = defineProps<{
    title?: string;
    description?: string;
}>();

const { authLayout } = useBranding();
</script>

<template>
    <div class="relative">
        <div class="absolute top-4 right-4 z-50 sm:top-6 sm:right-6">
            <AppearanceToggle />
        </div>

        <component :is="authLayout" :title="title" :description="description">
            <slot />
        </component>
    </div>
</template>
