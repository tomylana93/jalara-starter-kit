# Data Initialization

- Reference/bootstrap data is owned by idempotent Artisan commands, never `DatabaseSeeder`; the seeder remains a no-op for tooling compatibility.
- Destructive synchronization commands provide a fully read-only dry-run that reports creates, removals, attachments, and detachments before execution.
- Bootstrap secrets enter through environment-backed configuration; application and command code consume `config()`, never `env()` directly.
- `auth:sync-authorization` owns the guard `web` authorization catalog and prunes catalog drift while preserving other guards.
- `auth:init-superadmin` is the operator path for the sole protected system user and composes authorization synchronization.