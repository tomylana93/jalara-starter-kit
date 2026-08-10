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

## Selection, column visibility, and filters are table-owned and domain-free
`DataTable` owns `rowSelection` as controlled state and requires a `getRowId` prop so identity is the domain UUID, never the row index; it emits `selection-change` with those UUIDs. Selection means "these rows, this server page": it clears whenever the effective query or the set of row ids changes, and survives a column-visibility change.

`DataTableColumnHeader` takes the structural `SortableColumn` type, not `Column<TData, TValue>` — a generic SFC passed through `h()` cannot unify its generics and fails type-check.

Hideable columns declare an already-translated `meta.label` (augmented onto TanStack's `ColumnMeta` in `data-table/types.ts`); the visibility menu is built from `getAllColumns().filter(c => c.getCanHide())`, so select/actions never appear. The empty row's colspan must use `getVisibleLeafColumns().length`.

Filters stay domain free: the page passes `TableFilterConfig[]` (key, label, options) and the table only routes the key back into `TableQuery.filters`. Filters render before the Columns menu, ride the `pendingQuery` snapshot, reset to page 1, and join `selectionScope` so a filter change clears the selection. An emptied filter key is deleted rather than sent as an empty list.
