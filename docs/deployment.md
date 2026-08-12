# Manual VPS Deployment

This document is the runbook for deploying Jalara to a single VPS from a
published release tag, using the scripts in `deploy/`.

The scripts themselves carry the operational detail: `deploy/bootstrap.sh` opens
with the full server prerequisite list and the copy-pasteable nginx, Supervisor,
and cron snippets, kept next to the code that assumes them so the two cannot
drift apart. This document covers what the scripts cannot: why the flow is shaped
this way, and what to do when a deployment goes wrong.

## What gets deployed

The unit of deployment is a **published release tag** — a tag that also has a
non-draft GitHub Release behind it. A tag is only written after the main-scope
gate has passed and every commit behind it was found eligible for release,
so a published tag is the project's only marker for "this code was verified and
released". `deploy/deploy.sh` refuses a tag whose release is missing or still
draft, which catches the case where the tag landed but publication failed; re-run
`release-publish.yml` to finish that same version.

Deploying the branch tip instead would silently ship commits that no release
covers, with a `version.json` that disagrees with what is running.

## Layout on the server

```
/srv/jalara/
├── releases/
│   ├── 20260807141522-v1.2.0/   ← complete, still bootable, kept for rollback
│   └── 20260807163011-v1.3.0/
├── shared/
│   ├── .env                     ← the only copy; releases symlink to it
│   ├── storage/                 ← uploads, logs, sessions, cache, backups
│   ├── repo/                    ← bare clone the server fetches tags into
│   ├── pnpm-store/              ← content-addressed store; releases hardlink from it
│   └── deploy-backups/          ← pre-migration pg_dump files
└── current -> releases/20260807163011-v1.3.0
```

Release directories are named `<UTC timestamp>-<tag>` so that a lexical sort is
also a chronological one — sorting by tag would place `v1.9.0` after `v1.10.0`.

Every release keeps its own `vendor/` and `node_modules/`. That is what makes
rollback a symlink flip instead of a rebuild: the previous release is not a
reference to something, it is a complete installation that was serving traffic
minutes ago. The cost is low because pnpm hardlinks from the shared store.

`current` is what the nginx vhost points at, and what Supervisor's commands
reference. Nothing else should ever be referenced by path.

## Where each artifact is built

| Artifact | Built | Why |
| --- | --- | --- |
| PHP source | On the server, from the tag | The server fetches the tag into `shared/repo` and unpacks it. Nothing from your working tree can leak into a release. |
| PHP dependencies | On the server | `composer install --no-dev --optimize-autoloader` |
| Frontend bundle | On your machine | Built in a dedicated worktree pinned to the tag, then rsynced |
| Node dependencies | On the server | `pnpm install --prod`; `puppeteer` is a runtime dependency for the PDF exports |

The frontend bundle is built locally, so the deployment script runs from your
machine and drives the server over SSH.

Two consequences follow from building assets locally, and both are handled:

1. **Assets are built from the tag, not from your working tree.** A persistent
   git worktree at `BUILD_WORKTREE` is checked out to the tag being deployed.
   Your working directory is never touched, so you can deploy a hotfix without
   stashing whatever you are in the middle of.
2. **`VITE_*` values come from the server.** `VITE_APP_NAME` and the
   `VITE_REVERB_*` variables are inlined into the bundle at build time by
   `resources/js/app.ts`. Building with a local `.env` would bake
   `localhost:8080` into production's websocket client — which does not fail at
   build time, does not fail at deploy time, and shows up as realtime features
   quietly not working. The script reads those keys out of `shared/.env` over SSH
   and exports them into the build, where Vite lets them override the `.env`
   files it loaded.

## First deployment

1. Provision the server. See the prerequisite block at the top of
   `deploy/bootstrap.sh`; it lists the packages, the nginx vhost, the three
   Supervisor programs, and the cron entry, with snippets.

2. Configure the target:

   ```bash
   cp deploy/config.example.sh deploy/config.sh
   $EDITOR deploy/config.sh
   ```

   `deploy/config.sh` is gitignored. A private fork of this repository only fills
   in this one file; the flow itself is inherited.

3. Prepare the server:

   ```bash
   ./deploy/bootstrap.sh
   ```

   This is idempotent and safe to re-run. It creates the directory skeleton, a
   production `shared/.env` with a freshly generated `APP_KEY`, a read-only
   deploy key, and the bare repository. It ends by printing the public key and
   the remaining manual steps.

4. Register the printed deploy key on GitHub under **Settings → Deploy keys**,
   **without** write access. The server never needs to push. Use one key per
   repository — GitHub rejects the same public key as a deploy key twice, so a
   private fork generates its own.

5. Fill in `shared/.env` on the server: database credentials, `APP_URL`,
   `REVERB_*`, `MAIL_*`, `LARAVEL_PDF_CHROME_PATH`, `SUPER_ADMIN_*`.

6. Create the PostgreSQL database and role. Production refuses to run on SQLite,
   and the application puts sessions, cache, and queue in the database, so a
   single-writer engine would contend with the queue workers on every request.

7. Deploy:

   ```bash
   ./deploy/deploy.sh
   ```

## Routine deployment

```bash
./deploy/deploy.sh              # latest published tag
./deploy/deploy.sh v1.2.0       # a specific tag
./deploy/deploy.sh --dry-run    # preflight and summary only, changes nothing
```

Flags: `--yes` skips the confirmation, `--skip-env-check` allows deploying when
the release introduces `.env` keys the server does not have, `--dry-run` stops
after the summary.

There is deliberately no flag to skip the pre-migration dump or the health check.
Both are cheap, and the moment you want to skip them is the moment you need them.

### The confirmation prompt

Before anything is modified, the script prints a summary and waits for you to
type `yes`. The line worth reading is the migration count and, when there are
any, the list of migration files. That is your only opportunity to see that a
release touches the schema **before** it does — and the schema is the part that
an automatic rollback cannot undo.

### What happens, in order

```
PREFLIGHT   resolve tag → verify GitHub Release → compare .env keys
            → read production VITE_* → summary → confirm
PREPARE     build assets from the tag → unpack tag into a new release
            → composer install → pnpm install → rsync assets
            → optimize → storage:link
WINDOW      artisan down → pg_dump → migrate → auth:sync-authorization
            → flip current → artisan up
VERIFY      GET /up, with retries → automatic rollback on failure
FINISH      queue:restart → reverb:restart → prune old releases
```

Everything expensive happens before the maintenance window. The window itself
contains only the steps that genuinely cannot straddle two versions, so it is
seconds rather than minutes.

Three details in that sequence are easy to overlook and each exists for a
specific reason:

- **`artisan down` is issued from the currently running release**, not the new
  one, whose `vendor/` may not be complete yet. The maintenance marker lives in
  shared storage, so it applies to whichever release is being served.
- **`auth:sync-authorization` is a required step.** It is deliberately not driven
  from a migration, because it prunes catalog drift. Skip it and permissions
  introduced by the release are never attached to any role: the feature ships,
  and nobody can reach it.
- **Pruning old releases happens last**, after the restart signals. Deleting a
  directory out from under a running queue worker kills it in a way that is hard
  to trace back to the deployment.

### Background processes

Workers are restarted with `queue:restart` and `reverb:restart` — graceful
signals, not process control. Each worker finishes its current job, exits, and
Supervisor starts it again from `current`, which now resolves to the new release.
No job is interrupted, and the deployment account never needs `sudo`.

The scheduler needs nothing: it is a fresh process every minute, so it picks up
`current` on its own.

### The one prerequisite that fails silently

The nginx vhost must resolve the release path:

```nginx
fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
fastcgi_param DOCUMENT_ROOT   $realpath_root;
```

Without it, PHP-FPM keeps serving the previous release out of its realpath and
opcode caches after `current` is flipped. The deployment reports success, the
health check passes, and the site runs old code. This is solved in the vhost
rather than by reloading PHP-FPM on every deployment, because the alternative
means the deployment account holds `sudo` forever and every deployment throws
away a warm OPcache.

## Rollback

Automatic rollback happens when the health check fails: the symlink goes back,
workers restart, and the script exits non-zero with an explicit report.

For anything the health check cannot catch — a bug found an hour later — roll
back deliberately:

```bash
./deploy/rollback.sh --list                          # what is available
./deploy/rollback.sh                                 # previous release
./deploy/rollback.sh 20260807141522-v1.2.0           # a specific one
```

This takes seconds and runs no build, no `composer install`, and no migration.

**Rollback returns code, not data.** Migrations that already ran stay applied.
For additive migrations this is harmless — the old code does not know the new
columns exist. For destructive ones (`dropColumn`, `renameColumn`), the restored
release is running against a schema it does not recognise, and the pre-migration
dump in `shared/deploy-backups/` is the way back. Both the automatic and the
manual path print this, along with the path to the relevant dump.

### Why a separate pre-deployment dump

The application already has scheduled backups through `spatie/laravel-backup`,
but the deployment does not use them. Those archives are long-term retention with
their own history, quota, and UI, and `BACKUP_MAX_STORAGE_MB` deletes the oldest
archives until the total fits. Writing a deployment dump into that pool would
mean deploying frequently quietly erases backup history.

`pg_dump` into `shared/deploy-backups/` is a safety net for one operation, with
its own retention (`KEEP_DEPLOY_DUMPS`), no interaction with the backup run lock,
and no dependency on a queue worker being alive.

## Verification

These scripts are the one part of the repository that the test suite cannot
cover: they are only fully exercised against a real server. `--dry-run` validates
the preflight and the summary, but not the paths that matter most — the `trap`
that brings the site back up, and the automatic rollback.

Prove those once, on the new server, before it carries traffic: deploy a tag
whose migration deliberately fails and confirm that the site comes back up on the
previous release. After that you can trust the recovery path.

## Troubleshooting

| Symptom | Cause |
| --- | --- |
| Deployment succeeds but old code is served | nginx vhost missing `$realpath_root` |
| `git clone` fails during bootstrap | Deploy key not registered on GitHub yet; register it and re-run |
| Preflight rejects the tag | No GitHub Release, or it is still a draft — publication did not complete |
| Preflight lists missing `.env` keys | The release introduces configuration the server lacks; add it to `shared/.env`, or use `--skip-env-check` if those keys are genuinely unused |
| Realtime features silent, no errors | Bundle built with the wrong `VITE_REVERB_*`; confirm those keys in `shared/.env` and redeploy |
| Backups never run, nothing fails | Supervisor is missing the `database-long` worker; `RunBackupJob` uses that connection and the job sits unclaimed |
| Site stuck behind the maintenance page | Run `./deploy/rollback.sh`, which issues `artisan up` unconditionally |
