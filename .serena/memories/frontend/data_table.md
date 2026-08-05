# Data Table

- `resources/js/components/data-table/` holds the generic table (`DataTable.vue`,
  `DataTableColumnHeader.vue`, `DataTableFilter.vue`, `DataTablePagination.vue`,
  `types.ts`). Its domain-free and server-controlled-state constraints are a
  Project Rule on that glob.
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
- Filters stay domain free: the page passes `TableFilterConfig[]` (key, label,
  options) and the table only routes the key back into `TableQuery.filters`.
  Filters render before the Columns menu, ride the `pendingQuery` snapshot, reset
  to page 1, and join `selectionScope` so a filter change clears the selection.
  An emptied filter key is deleted rather than sent as an empty list.
- Wayfinder `queryParams` serializes an array as `key[]=v` (URL-encoded), which
  Laravel parses back as an ordered list — that is how `ids[]` and the filter
  arrays reach the server. Spread `query.filters` into the visit's `query`.
- A reka-ui `DropdownMenuItem as-child` wrapping an Inertia `Link` must be given
  `route(...).url`, not the route definition object: the bound href lands on the
  anchor as `[object Object]` before Inertia can resolve it.
- Under jsdom, `DropdownMenuContent` is force mounted, so a menu item is findable
  without opening the menu. Assert on the trigger's presence for authorization
  gating; a closed-menu assertion proves nothing.
- Timestamps arrive as UTC ISO 8601 and render through
  `resources/js/lib/dateTime.ts` (`formatBrowserDateTime`), which never passes a
  `timeZone` to `Intl` — that omission is what applies the viewer's zone. Vitest
  pins `TZ: 'Asia/Jakarta'` in `vite.config.ts` so this is observably not UTC.
