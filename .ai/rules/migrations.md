---
paths:
  - 'database/migrations/**'
---

# Migrations

## App tables use UUID primary and foreign keys
Declare primary keys as `$table->uuid('id')->primary()`. Relations to an application model use `foreignUuid()` or `foreignIdFor()` (the latter auto-detects UUID/ULID/int from the related model's traits). Infrastructure tables (`jobs`, `failed_jobs`, `cache`, `migrations`, `sessions.id`) keep their default Laravel types; only foreign keys referencing an app model (e.g. `sessions.user_id`) become UUID.

## Migration lifecycle follows the README project status
`README.md` owns the canonical project lifecycle flag. While it is **Development / Pre-adoption** and no persistent consumer database exists, change an existing table by editing the migration that created it, then reset the development database (`php artisan migrate:fresh --seed`). Do not add a follow-up `add_*_to_*` or `alter_*` migration for a column, index, or constraint change in this state.

A brand-new table is the exception: it gets its own new migration file, as does a vendor-published migration.

Before the first real deployment, external adoption, or supported in-place upgrade, change the README status to **Stable / Adopted**. From that point every existing migration is immutable: evolve the schema with new, forward-only migrations and never reset a deployed database that holds real data. A Git tag alone does not change the lifecycle state.
