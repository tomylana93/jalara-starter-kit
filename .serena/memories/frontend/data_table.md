# Data Table

- `resources/js/components/data-table/` holds the generic, domain-free table
  (`DataTable.vue`, `DataTableColumnHeader.vue`, `DataTablePagination.vue`,
  `types.ts`). It must never import a domain type, a domain route, or perform
  client-side filtering/sorting/pagination.
- Built on `@tanstack/vue-table` with `manualPagination`, `manualSorting`, and
  `manualFiltering`. Sorting/pagination state is *controlled*: computed from the
  server payload, never mutated locally. `onSortingChange`/`onPaginationChange`
  resolve the updater and emit a single `query-change` carrying the full
  `TableQuery`; the page owns the visit.
- The consuming page turns `query-change` into a Wayfinder `router.get` with
  `only: ['users']`, `preserveState`, `preserveScroll`, `replace` — that is what
  parks table state in the URL. Wayfinder `queryParams` drops null, so an absent
  search never appears.
- Columns live domain-local in `pages/<domain>/<resource>/columns.ts` as a
  `create<X>Columns(t)` factory: the translator is injected because column defs
  are built outside a component setup scope. Column `accessorKey` MUST equal the
  backend sort key (`mem:backend/tables`).
- `DataTableColumnHeader` takes the structural `SortableColumn` type, not
  `Column<TData, TValue>`: a generic SFC passed through `h()` cannot unify its
  generics and fails type-check.
- Every emit goes through the `pendingQuery` snapshot, which each emit advances
  before publishing. Building a query straight from the server props lets two
  interactions that overlap one round trip discard each other. A search term
  still awaiting its debounce survives an older response landing.
- Row selection follows the official shadcn-vue example and lives in the domain
  `columns.ts` as an `id: 'select'` column: `Checkbox` bound to
  `getIsAllPageRowsSelected() || (getIsSomePageRowsSelected() && 'indeterminate')`
  / `toggleAllPageRowsSelected`, rows to `getIsSelected` / `toggleSelected`, with
  `enableSorting: false` and `enableHiding: false`. Use `aria-label`, not the
  doc's `ariaLabel` — a camelCase fallthrough attr reaches the DOM lowercased and
  is not a valid ARIA attribute.
- `DataTable` owns `rowSelection` as controlled state and requires a `getRowId`
  prop so identity is the domain UUID, never the row index. It emits
  `selection-change` with those UUIDs. Selection means "these rows, this server
  page": it clears whenever the effective query or the set of row ids changes, and
  survives a column-visibility change.
- Hideable columns declare an already-translated `meta.label` (augmented onto
  TanStack's `ColumnMeta` in `data-table/types.ts`); the visibility menu is built
  from `getAllColumns().filter(c => c.getCanHide())`, so select/actions never
  appear. The empty row's colspan must use `getVisibleLeafColumns().length`.
- Timestamps arrive as UTC ISO 8601 and render through
  `resources/js/lib/dateTime.ts` (`formatBrowserDateTime`), which never passes a
  `timeZone` to `Intl` — that omission is what applies the viewer's zone. Vitest
  pins `TZ: 'Asia/Jakarta'` in `vite.config.ts` so this is observably not UTC.
