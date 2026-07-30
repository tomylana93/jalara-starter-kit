<script setup lang="ts">
import {
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
} from '@lucide/vue';
import { computed } from 'vue';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslations } from '@/composables/useTranslations';
import type { TableMeta } from './types';

const props = defineProps<{
    meta: TableMeta;
}>();

const emit = defineEmits<{
    'update:page': [page: number];
    'update:perPage': [perPage: number];
}>();

const { t } = useTranslations();

const perPage = computed({
    get: () => String(props.meta.perPage),
    set: (value: string) => emit('update:perPage', Number(value)),
});

const summary = computed(() =>
    t('common.table.summary', {
        from: props.meta.from ?? 0,
        to: props.meta.to ?? 0,
        total: props.meta.total,
    }),
);
</script>

<template>
    <div
        class="flex flex-col items-center justify-between gap-4 sm:flex-row sm:gap-2"
    >
        <p class="text-sm text-muted-foreground" data-test="table-summary">
            {{ summary }}
        </p>

        <div class="flex flex-col items-center gap-4 sm:flex-row sm:gap-6">
            <div class="flex items-center gap-2">
                <span
                    id="table-per-page-label"
                    class="text-sm whitespace-nowrap"
                >
                    {{ t('common.table.per_page') }}
                </span>
                <Select v-model="perPage">
                    <SelectTrigger
                        class="w-[4.5rem]"
                        aria-labelledby="table-per-page-label"
                        data-test="table-per-page"
                    >
                        <SelectValue>{{ meta.perPage }}</SelectValue>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in meta.perPageOptions"
                            :key="option"
                            :value="String(option)"
                            :data-test="`table-per-page-option-${option}`"
                        >
                            {{ option }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <Pagination
                :page="meta.page"
                :items-per-page="meta.perPage"
                :total="meta.total"
                :sibling-count="1"
                show-edges
                :aria-label="t('common.table.pagination.label')"
                class="mx-0 w-auto justify-end"
                @update:page="emit('update:page', $event)"
            >
                <PaginationContent v-slot="{ items }">
                    <PaginationFirst
                        :aria-label="t('common.table.pagination.first')"
                        data-test="table-first-page"
                    >
                        <ChevronsLeft class="size-4" />
                    </PaginationFirst>
                    <PaginationPrevious
                        :aria-label="t('common.table.pagination.previous')"
                        data-test="table-previous-page"
                    >
                        <ChevronLeft class="size-4" />
                    </PaginationPrevious>

                    <template v-for="(item, index) in items">
                        <PaginationItem
                            v-if="item.type === 'page'"
                            :key="`page-${item.value}`"
                            :value="item.value"
                            :is-active="item.value === meta.page"
                            :data-test="`table-page-${item.value}`"
                        >
                            {{ item.value }}
                        </PaginationItem>
                        <PaginationEllipsis
                            v-else
                            :key="`ellipsis-${index}`"
                            :index="index"
                        />
                    </template>

                    <PaginationNext
                        :aria-label="t('common.table.pagination.next')"
                        data-test="table-next-page"
                    >
                        <ChevronRight class="size-4" />
                    </PaginationNext>
                    <PaginationLast
                        :aria-label="t('common.table.pagination.last')"
                        data-test="table-last-page"
                    >
                        <ChevronsRight class="size-4" />
                    </PaginationLast>
                </PaginationContent>
            </Pagination>
        </div>
    </div>
</template>
