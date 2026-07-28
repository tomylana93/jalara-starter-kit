<script setup lang="ts">
import { useBranding } from '@/composables/useBranding';
import type { BreadcrumbItem } from '@/types';

const { breadcrumbs = [] } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();

const { appLayout } = useBranding();
</script>

<template>
    <component :is="appLayout" :breadcrumbs="breadcrumbs">
        <slot />
    </component>
</template>
