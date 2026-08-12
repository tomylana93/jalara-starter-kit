---
paths:
  - 'app/Actions/**'
---

# Actions

## Actions expose a single handle() method
Business mutations live in single-purpose Action classes under a domain subdirectory, and the entry point is always `handle()`. Do not use `execute()` or `__invoke()`.

## Wrap an insert whose unique violation you catch in its own transaction
PostgreSQL aborts the entire transaction on a constraint violation and rejects every later statement in it with SQLSTATE 25P02. So the insert-and-catch idiom used as an atomic gate — `StageImageUpload`, `StartBackupRun`, `StartRestoreRun` — breaks as soon as any caller has a transaction open: the catch block's own read, or the caller's next query, fails instead of recovering. On SQLite nothing goes wrong, so the suite hid it until it ran on PostgreSQL.

Wrap the save in `DB::transaction(fn () => $model->save())`. Nested it is a savepoint to roll back to; standalone it is an ordinary transaction around one insert. The gate semantics are unchanged either way.
