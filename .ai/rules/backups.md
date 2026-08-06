---
paths:
  - 'app/Actions/Backups/**'
---

# Backups

## A restored dump must be settled before the run records itself
Every archive this application writes was dumped while a backup run held the single-flight lock (`RunBackup` claims the row before calling `backup:run`), so replaying any archive resurrects a `backup_runs` row with `lock_key` set and `status = running`. Left alone it holds the lock forever: every later backup/restore hits the unique index and is told one is already running, and the page shows an `activeRun` that never ends. Anything that replays a dump must run `SettleRestoredDatabase` afterwards - it also clears queue, session and cache tables, which describe in-flight work rather than data.

Two consequences to keep in mind: writing run state after a wipe must go through `RestoreBackup::writeRunState()` (the row's table may not exist yet, and a plain `save()` updates nothing silently), and `manage backups` is effectively arbitrary SQL - `IsBackupArchive` validates entry paths only, and anything under `db-dumps/` is later fed to `PDO::exec()`. Never widen that permission beyond Super Admin.
