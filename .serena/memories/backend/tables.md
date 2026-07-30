# Server-Side Tables

- Any paginated list screen goes through `App\Tables\AbstractTable` (generic
  `@template TModel of Model`) plus the `App\Tables\TableQuery` readonly DTO. Do
  not hand-roll search/sort/pagination in a controller.
- `TableQuery::fromValidated()` coerces every value into the allowed set
  (`PER_PAGE_OPTIONS` 10/25/50, `DIRECTIONS` asc/desc, page >= 1, trimmed search
  or null). Defaults: page 1, perPage 10, direction desc.
- A subclass declares `query()`, `searchable()`, `sortable()`, `defaultSort()`,
  `transform()`. `sortable()` maps a *client sort key* to a column; client input
  is never used as a column name and an unknown key silently falls back to
  `defaultSort()`. Expose the map as a public const (see
  `UsersTable::SORTABLE`) so the Form Request validating the query and the
  executor share one source of truth.
- `paginate()` returns the explicit payload contract, not a serialized paginator:
  `data`, `meta`, and `state` are *siblings*. `state` reports the query the
  server actually resolved (search/sort/direction/perPage) and `meta` the row
  window (page/perPage/perPageOptions/total/lastPage/from/to); the client renders
  from those rather than from its own optimism. A secondary order by the
  tie-breaker column keeps paging stable across equal sort values.
- `paginate()` clamps the page itself: Laravel's paginator accepts any positive
  page and would answer past the end with an empty window, so the total is
  counted first, the page is capped to the resulting last page, and that count is
  passed back as `paginate(total:)` so clamping costs no extra query. An empty
  result set resolves to page 1 with `from`/`to` null.
- A row never carries a server-formatted timestamp. Send the instant as UTC ISO
  8601 (`->toISOString()`) and let the browser format it — see the timezone rule
  in `mem:backend/settings`. The controller passes `GeneralSettings::$dateFormat`
  as a separate scalar page prop; the table itself takes no settings dependency.
- Filters are reusable and domain free: `TableQuery::fromValidated($validated,
  $filterKeys)` keeps only the keys the caller names, normalizing each to a
  distinct non-empty `list<string>` and dropping a key that selects nothing, so
  an empty list never has to mean "match everything". Expose the keys as a
  public const (`UsersTable::FILTERABLE`) alongside `SORTABLE`. Subclasses opt in
  by overriding `applyFilters()`, which runs after search and before the count,
  and still own mapping each key to a safe constraint — a filter key is never a
  raw column. Values inside one key are alternatives, separate keys narrow
  together. `state.filters` echoes what was applied.
- Exporting a selection writes a temp `.xlsx` via
  `SimpleExcelWriter::create($path)` and returns
  `response()->download(...)->deleteFileAfterSend()`. Do NOT use
  `SimpleExcelWriter::streamDownload()`: its `toBrowser()` calls `exit`, which
  bypasses the response cycle and cannot be asserted against.
- Consumed by the generic frontend table described in `mem:frontend/data_table`.
