---
paths:
  - 'database/migrations/**'
---

# Migrations

## App tables use UUID primary and foreign keys
Declare primary keys as `$table->uuid('id')->primary()`. Relations to an application model use `foreignUuid()` or `foreignIdFor()` (the latter auto-detects UUID/ULID/int from the related model's traits). Infrastructure tables (`jobs`, `failed_jobs`, `cache`, `migrations`, `sessions.id`) keep their default Laravel types; only foreign keys referencing an app model (e.g. `sessions.user_id`) become UUID.

## Edit the existing migration instead of adding an add/alter migration
Changing an existing table means editing the migration that created it, then resetting the database (`php artisan migrate:fresh --seed`). Do not add a follow-up `add_*_to_*` or `alter_*` migration for a column, index, or constraint change.

A brand-new table is the exception: it gets its own new migration file, as does a vendor-published migration.

This keeps the schema readable as one file per table. It is only safe because the kit is installed fresh by consumers rather than upgraded in place — never apply it to a deployed database that holds real data.
