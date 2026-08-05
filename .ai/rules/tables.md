---
paths:
  - 'app/Tables/**'
---

# Tables

## Server-driven tables extend AbstractTable
A listing backed by the frontend data table is a class extending `AbstractTable`, implementing `query()`, `searchable()`, `sortable()`, `defaultSort()`, and `transform()`. Do not assemble paginated listing payloads in a controller.
