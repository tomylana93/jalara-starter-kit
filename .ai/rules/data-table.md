---
paths:
  - 'resources/js/components/data-table/**'
---

# Data Table

## Generic data table stays domain-free and server-controlled
This subtree must never import a domain type or a domain route, and must never filter, sort, or paginate client-side. Built on `@tanstack/vue-table` with `manualPagination` and `manualSorting`; filtering never reaches TanStack at all, so there is no `manualFiltering` counterpart. Sorting/pagination state is controlled — computed from the server payload, never mutated locally. `onSortingChange`/`onPaginationChange` resolve the updater and emit a single `query-change` carrying the full `TableQuery`; the consuming page owns the visit. Every emit goes through the `pendingQuery` snapshot, which each emit advances before publishing; building a query straight from the server props lets two interactions that overlap one round trip discard each other.

## Register only the four v9 features; never a client row model
TanStack Table v9 gates both runtime APIs and their types behind explicit registration. `features.ts` registers exactly `rowSortingFeature`, `rowPaginationFeature`, `rowSelectionFeature`, `columnVisibilityFeature`, plus a `columnMeta: metaHelper<DataTableColumnMeta>()` slot, and every `ColumnDef` takes `DataTableFeatures` as its first generic.

Never add a row-model factory (`createSortedRowModel`, `createFilteredRowModel`, `createPaginatedRowModel`). This table is server-controlled: the backend owns order, membership, and page. `manualSorting`/`manualPagination` currently mask a stray row model, so the two guards must both hold — `DataTable.test.ts` "never re-derives the server result set locally" fails only when a row model is registered *and* its manual flag is lost.

There is no `manualFiltering` option here: it belongs to `columnFilteringFeature`, which is deliberately unregistered. Filtering is entirely server-side.

v9 fixes the table's column `TValue` at `unknown`, so `DataTable.vue` is generic over `TData extends RowData` only — reintroducing a `TValue` parameter cannot satisfy `useTable`.
