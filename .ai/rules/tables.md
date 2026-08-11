---
paths:
  - 'app/Tables/**'
---

# Tables

## Server-driven tables extend AbstractTable
A listing backed by the frontend data table is a class extending `AbstractTable`, implementing `query()`, `searchable()`, `sortable()`, `defaultSort()`, and `transform()`. Do not assemble paginated listing payloads in a controller.

## Search with whereLike, never a raw 'like' operator
`LIKE` is case-insensitive on SQLite and MySQL but case-sensitive on PostgreSQL. A table built with `whereAny($columns, 'like', ...)` therefore stops matching "budi" against "Budi" the moment it runs on the production engine, with no error to explain it. This shipped and was only caught by running the suite against a real PostgreSQL server.

Use `orWhereLike($column, $term)` inside a `where(fn (Builder $b) => ...)` group. `whereLike` is case-insensitive by default and emits the correct operator per driver.
