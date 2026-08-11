---
paths:
  - 'database/migrations/**'
---

# Migrations

## App tables use UUID primary and foreign keys
Declare primary keys as `$table->uuid('id')->primary()`. Relations to an application model use `foreignUuid()` or `foreignIdFor()` (the latter auto-detects UUID/ULID/int from the related model's traits). Infrastructure tables (`jobs`, `failed_jobs`, `cache`, `migrations`, `sessions.id`) keep their default Laravel types; only foreign keys referencing an app model (e.g. `sessions.user_id`) become UUID.

## Project lifecycle status: Stable / Adopted
This file owns the canonical project lifecycle flag. It is deliberately internal to agents and developers: do not restate it in `README.md` or any other adopter-facing document.

The current status is **Stable / Adopted** — the repository serves production and persistent consumer databases exist. Every existing migration is therefore immutable. Evolve the schema with new, forward-only migrations; never edit a shipped migration, and never reset a deployed database that holds real data (`migrate:fresh` is for local development only).

A brand-new table gets its own new migration file, as does a vendor-published migration.

The earlier **Development / Pre-adoption** state, in which existing migrations could be edited in place and the database rebuilt from scratch, no longer applies. Returning to it is not a routine change: it discards the guarantee adopters rely on and needs explicit developer approval. A Git tag alone does not change the lifecycle state.
