<script setup lang="ts" generic="TData, TValue">
import { ChevronDown } from '@lucide/vue';
import type {
    ColumnDef,
    PaginationState,
    RowSelectionState,
    SortingState,
    Updater,
    VisibilityState,
} from '@tanstack/vue-table';
import { FlexRender, getCoreRowModel, useVueTable } from '@tanstack/vue-table';
import { watchDebounced } from '@vueuse/core';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useTranslations } from '@/composables/useTranslations';
import DataTableFilter from './DataTableFilter.vue';
import DataTablePagination from './DataTablePagination.vue';
import type {
    TableFilterConfig,
    TableFilters,
    TablePayload,
    TableQuery,
    TableSortDirection,
} from './types';

const props = defineProps<{
    columns: ColumnDef<TData, TValue>[];
    payload: TablePayload<TData>;
    /* Stable domain identity; a row index would break selection across pages. */
    getRowId: (row: TData) => string;
    /* Domain-free filter descriptors; the server owns what each key means. */
    filters?: TableFilterConfig[];
    searchPlaceholder?: string;
    emptyTitle?: string;
    emptyDescription?: string;
}>();

const emit = defineEmits<{
    'query-change': [query: TableQuery];
    'selection-change': [ids: string[]];
}>();

const { t } = useTranslations();

const normalizeSearch = (term: string): string | null =>
    term.trim() === '' ? null : term.trim();

const searchTerm = ref(props.payload.state.search ?? '');

const queryFromPayload = (payload: TablePayload<TData>): TableQuery => ({
    search: payload.state.search,
    sort: payload.state.sort,
    direction: payload.state.direction,
    page: payload.meta.page,
    perPage: payload.meta.perPage,
    filters: payload.state.filters,
});

/*
 * The query the next request is built from. Deriving it from the server props
 * alone would let two interactions that overlap a single round trip discard each
 * other, so every emit advances this snapshot first.
 */
const pendingQuery = ref<TableQuery>(queryFromPayload(props.payload));

watch(
    () => props.payload,
    (payload) => {
        const localSearch = normalizeSearch(searchTerm.value);
        /* A term still waiting on its debounce outlives an older response. */
        const searchAwaitingDebounce = localSearch !== payload.state.search;

        pendingQuery.value = {
            ...queryFromPayload(payload),
            search: searchAwaitingDebounce ? localSearch : payload.state.search,
        };

        if (!searchAwaitingDebounce) {
            searchTerm.value = payload.state.search ?? '';
        }
    },
);

const emitQuery = (overrides: Partial<TableQuery>): void => {
    pendingQuery.value = { ...pendingQuery.value, ...overrides };

    emit('query-change', { ...pendingQuery.value });
};

watchDebounced(
    searchTerm,
    (term) => {
        const search = normalizeSearch(term);

        if (search === props.payload.state.search) {
            return;
        }

        /* A narrowed result set invalidates the current page. */
        emitQuery({ search, page: 1 });
    },
    { debounce: 300 },
);

const selectedFilterValues = (key: string): string[] =>
    pendingQuery.value.filters[key] ?? [];

/*
 * A filter narrows the result set, so the current page stops being meaningful.
 * An empty selection drops the key entirely rather than sending an empty list.
 */
const applyFilter = (key: string, values: string[]): void => {
    const filters: TableFilters = { ...pendingQuery.value.filters };

    if (values.length === 0) {
        delete filters[key];
    } else {
        filters[key] = values;
    }

    emitQuery({ filters, page: 1 });
};

const sorting = computed<SortingState>(() => [
    {
        id: props.payload.state.sort,
        desc: props.payload.state.direction === 'desc',
    },
]);

const pagination = computed<PaginationState>(() => ({
    pageIndex: props.payload.meta.page - 1,
    pageSize: props.payload.meta.perPage,
}));

const rowSelection = ref<RowSelectionState>({});
const columnVisibility = ref<VisibilityState>({});

const resolveUpdater = <T,>(updater: Updater<T>, current: T): T =>
    typeof updater === 'function'
        ? (updater as (old: T) => T)(current)
        : updater;

const table = useVueTable({
    get data() {
        return props.payload.data;
    },
    get columns() {
        return props.columns;
    },
    get rowCount() {
        return props.payload.meta.total;
    },
    getRowId: (row) => props.getRowId(row),
    getCoreRowModel: getCoreRowModel(),
    manualPagination: true,
    manualSorting: true,
    manualFiltering: true,
    state: {
        get sorting() {
            return sorting.value;
        },
        get pagination() {
            return pagination.value;
        },
        get rowSelection() {
            return rowSelection.value;
        },
        get columnVisibility() {
            return columnVisibility.value;
        },
    },
    onSortingChange: (updater) => {
        const next = resolveUpdater(updater, sorting.value)[0];

        if (!next) {
            return;
        }

        emitQuery({
            sort: next.id,
            direction: (next.desc
                ? 'desc'
                : 'asc') satisfies TableSortDirection,
            page: 1,
        });
    },
    onPaginationChange: (updater) => {
        const next = resolveUpdater(updater, pagination.value);

        emitQuery({
            page: next.pageIndex + 1,
            perPage: next.pageSize,
        });
    },
    onRowSelectionChange: (updater) => {
        rowSelection.value = resolveUpdater(updater, rowSelection.value);
    },
    onColumnVisibilityChange: (updater) => {
        columnVisibility.value = resolveUpdater(
            updater,
            columnVisibility.value,
        );
    },
});

/*
 * Selection only ever means "these rows, on this server page". Anything that
 * changes which rows are on screen therefore clears it; hiding a column does
 * not, because the rows stay the same.
 */
const selectionScope = computed(() =>
    JSON.stringify([
        pendingQuery.value.search,
        pendingQuery.value.sort,
        pendingQuery.value.direction,
        pendingQuery.value.page,
        pendingQuery.value.perPage,
        pendingQuery.value.filters,
        props.payload.data.map((row) => props.getRowId(row)),
    ]),
);

watch(selectionScope, () => {
    if (Object.keys(rowSelection.value).length > 0) {
        rowSelection.value = {};
    }
});

watch(rowSelection, () => {
    emit(
        'selection-change',
        table.getSelectedRowModel().rows.map((row) => row.id),
    );
});

const hideableColumns = computed(() =>
    table.getAllColumns().filter((column) => column.getCanHide()),
);

const columnLabel = (columnId: string): string =>
    table.getColumn(columnId)?.columnDef.meta?.label ?? columnId;

const visibleColumnCount = computed(() => table.getVisibleLeafColumns().length);

const selectedCount = computed(() => table.getSelectedRowModel().rows.length);
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <Input
                v-model="searchTerm"
                type="search"
                class="max-w-sm"
                data-test="table-search"
                :aria-label="searchPlaceholder ?? t('common.table.search')"
                :placeholder="searchPlaceholder ?? t('common.table.search')"
            />

            <div class="flex items-center gap-2">
                <slot name="actions" />

                <DataTableFilter
                    v-for="filter in props.filters ?? []"
                    :key="filter.key"
                    :filter-key="filter.key"
                    :label="filter.label"
                    :options="filter.options"
                    :selected="selectedFilterValues(filter.key)"
                    @update:selected="applyFilter(filter.key, $event)"
                />

                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button
                            variant="outline"
                            data-test="table-columns-menu"
                        >
                            {{ t('common.table.columns.label') }}
                            <ChevronDown class="ml-2 size-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuLabel>
                            {{ t('common.table.columns.description') }}
                        </DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuCheckboxItem
                            v-for="column in hideableColumns"
                            :key="column.id"
                            :model-value="column.getIsVisible()"
                            :data-test="`table-column-toggle-${column.id}`"
                            @update:model-value="
                                (value) => column.toggleVisibility(!!value)
                            "
                        >
                            {{ columnLabel(column.id) }}
                        </DropdownMenuCheckboxItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>

        <div class="overflow-x-auto rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow
                        v-for="headerGroup in table.getHeaderGroups()"
                        :key="headerGroup.id"
                    >
                        <TableHead
                            v-for="header in headerGroup.headers"
                            :key="header.id"
                        >
                            <FlexRender
                                v-if="!header.isPlaceholder"
                                :render="header.column.columnDef.header"
                                :props="header.getContext()"
                            />
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <template v-if="table.getRowModel().rows.length">
                        <TableRow
                            v-for="row in table.getRowModel().rows"
                            :key="row.id"
                            :data-state="
                                row.getIsSelected() ? 'selected' : undefined
                            "
                            :data-test="`table-row-${row.id}`"
                        >
                            <TableCell
                                v-for="cell in row.getVisibleCells()"
                                :key="cell.id"
                            >
                                <FlexRender
                                    :render="cell.column.columnDef.cell"
                                    :props="cell.getContext()"
                                />
                            </TableCell>
                        </TableRow>
                    </template>
                    <TableRow v-else>
                        <TableCell
                            :colspan="visibleColumnCount"
                            class="h-32 text-center"
                            data-test="table-empty"
                        >
                            <p class="font-medium">
                                {{ emptyTitle ?? t('common.table.empty') }}
                            </p>
                            <p
                                v-if="emptyDescription"
                                class="mt-1 text-sm text-muted-foreground"
                            >
                                {{ emptyDescription }}
                            </p>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <p
            v-if="selectedCount > 0"
            class="text-sm text-muted-foreground"
            data-test="table-selected-summary"
        >
            {{
                t('common.table.selected', {
                    count: selectedCount,
                    total: table.getRowModel().rows.length,
                })
            }}
        </p>

        <DataTablePagination
            :meta="payload.meta"
            @update:page="emitQuery({ page: $event })"
            @update:per-page="emitQuery({ perPage: $event, page: 1 })"
        />
    </div>
</template>
