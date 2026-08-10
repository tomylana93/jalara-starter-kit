# Data Table

- `resources/js/components/data-table/` holds the generic table (`DataTable.vue`,
  `DataTableColumnHeader.vue`, `DataTableFilter.vue`, `DataTablePagination.vue`,
  `types.ts`). Its domain-free and server-controlled-state constraints are a
  Project Rule on that glob.
- The page, not the table, owns the visit, the domain `columns.ts` factory, and
  Wayfinder query serialization; those constraints are a Project Rule on
  `resources/js/pages/**`. Column `accessorKey` MUST equal the backend sort key
  (`mem:backend/tables`).
- Timestamps arrive as UTC ISO 8601 and render through
  `resources/js/lib/dateTime.ts` (`formatBrowserDateTime`), which never passes a
  `timeZone` to `Intl` — that omission is what applies the viewer's zone. Tests
  pin the zone themselves; see `mem:testing`.
