import {
    columnVisibilityFeature,
    metaHelper,
    rowPaginationFeature,
    rowSelectionFeature,
    rowSortingFeature,
    tableFeatures,
} from '@tanstack/vue-table';

/**
 * The metadata a column carries for the domain-free visibility menu.
 *
 * v9 replaces v8's global `ColumnMeta` declaration merging with a per-table
 * `columnMeta` slot, so the label type travels with the feature set instead of
 * being augmented onto every table in the application.
 */
export type DataTableColumnMeta = {
    label?: string;
};

/**
 * The feature set every generic data table and its column definitions share.
 *
 * v9 gates both the runtime APIs and their types behind explicit registration,
 * so a column definition must be typed with the same feature object the table
 * is built from.
 *
 * Deliberately no row-model factories: this table is server-controlled. The
 * backend owns order, membership, and page, so registering
 * `sortedRowModel`, `filteredRowModel`, or `paginatedRowModel` would let the
 * client silently re-derive a result set the server already resolved.
 */
export const dataTableFeatures = tableFeatures({
    rowSortingFeature,
    rowPaginationFeature,
    rowSelectionFeature,
    columnVisibilityFeature,
    columnMeta: metaHelper<DataTableColumnMeta>(),
});

export type DataTableFeatures = typeof dataTableFeatures;
