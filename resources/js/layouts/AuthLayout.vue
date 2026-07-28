<script setup lang="ts">
import { useBranding } from '@/composables/useBranding';

const { title = '', description = '' } = defineProps<{
    title?: string;
    description?: string;
}>();

const { authLayout } = useBranding();
</script>

<template>
    <component :is="authLayout" :title="title" :description="description">
        <slot />
    </component>
</template>
