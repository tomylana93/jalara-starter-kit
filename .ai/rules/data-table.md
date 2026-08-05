---
paths:
  - 'resources/js/components/data-table/**'
---

# Data Table

## Generic data table stays domain-free and server-controlled
This subtree must never import a domain type or a domain route, and must never filter, sort, or paginate client-side. Built on `@tanstack/vue-table` with `manualPagination`, `manualSorting`, and `manualFiltering`. Sorting/pagination state is controlled — computed from the server payload, never mutated locally. `onSortingChange`/`onPaginationChange` resolve the updater and emit a single `query-change` carrying the full `TableQuery`; the consuming page owns the visit. Every emit goes through the `pendingQuery` snapshot, which each emit advances before publishing; building a query straight from the server props lets two interactions that overlap one round trip discard each other.
