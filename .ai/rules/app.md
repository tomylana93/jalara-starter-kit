---
paths:
  - 'app/**'
---

# App

## Model ids are UUIDv7 strings, never int
All application Eloquent models use UUIDv7 primary keys via the `HasUuids` trait, not auto-increment integers. Any code that type-hints a model's id must use `string`, never `int` — this includes validation rule helpers, route bindings, and action/service signatures.
