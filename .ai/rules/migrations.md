---
paths:
  - 'database/migrations/**'
---

# Migrations

## App tables use UUID primary and foreign keys
Declare primary keys as `$table->uuid('id')->primary()`. Relations to an application model use `foreignUuid()` or `foreignIdFor()` (the latter auto-detects UUID/ULID/int from the related model's traits). Infrastructure tables (`jobs`, `failed_jobs`, `cache`, `migrations`, `sessions.id`) keep their default Laravel types; only foreign keys referencing an app model (e.g. `sessions.user_id`) become UUID.
