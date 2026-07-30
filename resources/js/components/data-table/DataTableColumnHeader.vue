<script setup lang="ts">
import { ArrowDown, ArrowUp, ChevronsUpDown } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import type { SortableColumn } from './types';

const props = defineProps<{
    column: SortableColumn;
    title: string;
}>();

const { t } = useTranslations();

const sorted = computed(() => props.column.getIsSorted());

const icon = computed(() => {
    if (sorted.value === 'asc') {
        return ArrowUp;
    }

    if (sorted.value === 'desc') {
        return ArrowDown;
    }

    return ChevronsUpDown;
});

const label = computed(() =>
    sorted.value === 'asc'
        ? t('common.table.sort.descending')
        : t('common.table.sort.ascending'),
);
</script>

<template>
    <Button
        v-if="props.column.getCanSort()"
        variant="ghost"
        size="sm"
        class="-ml-3 h-8"
        :aria-label="`${props.title}: ${label}`"
        :data-test="`sort-${props.column.id}`"
        @click="props.column.toggleSorting(sorted === 'asc')"
    >
        <span>{{ props.title }}</span>
        <component :is="icon" class="ml-2 size-4" />
    </Button>
    <span v-else>{{ props.title }}</span>
</template>
