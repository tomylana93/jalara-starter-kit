---
paths:
  - 'app/**'
---

# App

## Model ids are UUIDv7 strings, never int
All application Eloquent models use UUIDv7 primary keys via the `HasUuids` trait, not auto-increment integers. Any code that type-hints a model's id must use `string`, never `int` — this includes validation rule helpers, route bindings, and action/service signatures.

## No repository layer; data objects only at the action boundary
Actions and Tables query Eloquent directly. Do not introduce a repository layer.

Structured data crosses the action boundary as a readonly object in `app/Data`, not as an array: an action takes a `*Data` object and returns a `*Result` object. Everywhere else — inside an action, between an action and a presenter's arguments, in a table row — a typed array shape still carries the data. Do not add an object merely to name an array that never leaves one class. See `.ai/rules/data.md`.
