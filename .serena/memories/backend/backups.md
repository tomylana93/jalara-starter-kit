# Backups

## Configuration

- `spatie/laravel-backup` v10. `config/backup.php` is trimmed to the options this
  project decides on and carries an extra top-level `schedule` key (Spatie ignores
  unknown top-level keys), so the whole feature lives in one file.
- Trimming has two traps. `notifications.slack` must stay: unlike `discord` and
  `webhook`, Spatie reads it unguarded and requires all four sub-keys. And every
  notification class must appear in `notifications.notifications`, muted ones with
  an empty channel list - `BaseNotification::via()` indexes the map by class name,
  so an absent key is an undefined-key error at send time.
- `notifications.mail.to` must be a valid email. Spatie validates it while building
  its `Config`, which `EventHandler` resolves at boot, so a null value throws for
  every command and request in the application. "No recipient" is therefore
  expressed as muted notifications plus a placeholder address, never an empty one.
- `backup.name` reads raw `env('APP_NAME')`, not `config('app.name')`: the app name
  is a runtime setting, and following it would strand existing archives under the
  previous destination folder.
- Archive contents: database plus `storage/app/public` and
  `storage/app/private/chat`. The staging prefix `app/private/image-uploads` is
  excluded - transient bytes deleted after the publishing transaction commits.
  `relative_path` is `base_path()` so a restore unpacks over the project root.
- Destination is the `backups` disk (`storage/backups`), rooted outside
  `storage/app` so archives are neither inside the backed-up tree nor mixed with
  media. Never the `public` disk: it is symlinked from `public/storage`.
- Env knobs: `BACKUP_SCHEDULE_TIME`, `BACKUP_SCHEDULE_TIMEZONE`,
  `BACKUP_MAX_STORAGE_MB`, `BACKUP_NOTIFICATION_EMAIL`, `BACKUP_DISKS` (comma
  separated). The megabyte ceiling overrides the whole retention ladder - Spatie
  deletes oldest until the total fits - so it is the value that actually decides
  how much history survives.

## Scheduling

- `routes/console.php` reads times through `config()`, never `env()`: outside a
  config file `env()` returns null under `config:cache`, silently unscheduling
  every backup. The timezone is explicit because `app.timezone` is UTC.
- Order is cleanup (-30m) then backup then monitor (+30m). Cleaning first keeps
  peak disk usage before the new archive lands.
- The scheduled backup dispatches `StartBackupRun`, not `Schedule::command('backup:run')`,
  so scheduled and manual runs share one lock and one history.
- `model:prune` names its models explicitly; a prunable model missing from that
  list is never pruned. `BackupRun` is in it.

## Execution

- `RunBackupJob` runs on the `database-long` queue connection (`retry_after` 3600),
  `tries = 1`, `timeout` 1800. `retry_after` is per-connection, so a slow job on the
  default `database` connection (90s) is claimed a second time while the first is
  still running - two concurrent dumps. Workers must process `database-long`
  explicitly or backups never run: the job sits unclaimed, the run stays pending,
  and nothing fails to explain it. `DevCommands::registerDefaults()` starts
  `queue:listen` with no connection argument, so `AppServiceProvider::register()`
  adds a `queue-long` dev process; production deployments need the equivalent
  worker.
- `BackupRun.lock_key` is a unique column holding one fixed value
  (`BackupRun::ACTIVE_LOCK_KEY`) while a run is active. The insert either wins or
  violates the constraint; every terminal transition clears it. Duplicate NULLs are
  allowed on every supported driver.
- `RunBackup` claims with a conditional `UPDATE ... WHERE status = pending`, so a
  redelivered job cannot restart a finished run. A command that exits zero without
  producing an archive is recorded as failed, not completed.
- `error_code` is a translation key suffix under `backup.error.*`, never an
  exception message.

## Restore and upload

- `backup_runs` carries both directions: `BackupRunType::Backup` and `Restore`
  share the table, the lock and the history page. A restore run's `filename` is
  the archive it replays, not one it produced.
- `StartRestoreRun` -> `RestoreBackupJob` (`database-long`, `tries = 1`) ->
  `RestoreBackup`: copy the archive local, inspect it with
  `BackupArchiveContents`, dump the current database next to the archive folder
  (`<name>-pre-restore/`, last three kept), `db:wipe`, replay each dump whole
  through `PDO::exec`, settle, merge the archived media back over `base_path()`.
  Media is merged, never mirrored - files newer than the archive stay.
- `BackupArchiveContents` is the security boundary for both upload and restore:
  entries must sit under `db-dumps/` or a configured media prefix, and it is
  path-shape only. Uploads land on the first configured destination disk.
- Compressed dumps are refused rather than replayed, before anything is wiped.

## HTTP surface

- `manage backups` is a separate permission from `manage settings`: the download
  endpoint hands out a full copy of the database. Super Admin gets it via
  `AuthorizationCatalog::permissionsFor()`; Admin does not. Existing installations
  need `auth:sync-authorization` after deploy - it is deliberately not called from
  a migration, since it prunes catalog drift.
- Routes live under the `settings/backups` prefix but in their own group; the
  settings index is a hub answering to `manage settings|manage backups` with cards
  filtered per ability, so a backup-only holder can reach it. The whole backup
  group also requires `RequirePassword` (a three-hour window, not a per-download
  check).
- Archives are addressed by basename and resolved against
  `BackupArchives::find()`, which matches the real listing. No client input ever
  reaches a filesystem path, so traversal is a name that matches nothing rather
  than a string to sanitise. Downloads stream via `Storage::download()`.
- The page polls with `router.reload({ only: [...] })` only while `activeRun` is
  set, and stops when it clears; an idle page issues no requests.
