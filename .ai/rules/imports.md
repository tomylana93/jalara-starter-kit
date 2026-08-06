---
paths:
  - 'app/Imports/**'
---

# Imports

## Imports create only, all or nothing
Import never updates. `UserPolicy::update()` authorizes per target user; a bulk operation authorizes once and cannot honour that. `StoreUserRequest` also marks `status` prohibited while `UpdateUserRequest` accepts it, so an upsert path would quietly make status mass-writable. Roles are validated per row against the actor's `assignableRoleValues()`, not the whole enum.

Validate the entire sheet, then write inside one transaction. Partial success plus create-only semantics traps the operator: re-uploading the corrected file turns every previously successful row into a duplicate-email error.

`CreateUser` is idempotent — an existing email returns the existing user with no error — so duplicates must be caught in the validation phase or they pass silently as successes.

`UserImportSheet::MAX_ROWS` is a time budget, not a round number: each row costs one bcrypt hash. Never reuse a single hash across rows to raise it; per-user salt is deliberate.
