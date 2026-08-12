# Suggested Commands

- Local setup: `composer run setup`; GitHub CI dependency/environment setup without an early build: `composer run ci:setup`, or `composer run ci:setup:php` for the Node-free Pest job; development: `composer run dev`; frontend dev: `pnpm run dev`.
- Vitest: `pnpm test:unit`; watch: `pnpm test:unit:watch`.
- Pest focused: `php artisan test --compact <file>` or `--filter=<name>`; full backend: `composer test`.
- Pest browser: `composer run ci:browser`; focused: `vendor/bin/pest tests/Browser/<file> --filter='<name>'`. Requires a current `pnpm run build`.
- Database compatibility locally: `composer run test:pgsql` and `composer run test:mysql` (ephemeral podman containers, torn down after the run).
- Combined auto-fix before the gate: `composer run fix` (Rector, Pint on dirty
  files, ESLint, Prettier). Frontend-only auto-fix: `pnpm run lint`, `pnpm run
  format`; checks: `pnpm run lint:check`, `pnpm run format:check`, `pnpm run
  types:check`, `pnpm run test:unit`. PHP-only auto-fix: `composer run rector`, `composer run
  format:agent`; repository-wide formatting: `composer run lint`. Checks:
  `composer run rector:check`, `composer run lint:check`, `composer run
  types:check`.
- Laravel Doctor: `composer run doctor:check` (read-only) and `composer run doctor:fix` (mutating; manual only). Deliberately outside `ci:check` and the workflow: CI runs on `:memory:` SQLite with no migrated schema, so migrations/cache/queue/session diagnostics fail there.
- Two-tier gate composed from one script per CI job, so the local command runs
  the same set as CI. `ci:static` = frontend lint/format/types, then
  `config:clear`, Pint, Larastan. `ci:vitest`, `ci:pest`, `ci:pest:coverage`,
  `ci:browser` are the rest. `composer run ci:check` (fast) = `ci:static`,
  `ci:vitest`, `ci:pest`. `composer run ci:full` (promotion) swaps in coverage
  and adds `ci:browser`, which needs a current production build. Pest runs once
  per tier. Rector is in neither gate: it is a local-only auto-fixer run through
  `composer run fix`. Pest TIA is active locally and skipped on CI through
  `pest()->tia()->locally()`.
- `.github/workflows/_ci.yml` is the single implementation of the gate that both
  `pull-request.yml` and `main.yml` call. First stage in parallel: `static`,
  `vitest`, `pest` (SQLite and PostgreSQL, coverage on PostgreSQL only), and
  `audit`. Second stage, only after all four pass: `e2e` and `installer`. The
  `pest` job installs no Node toolchain because `Tests\TestCase` calls
  `withoutVite()`; the `static` and `vitest` jobs both need PHP because the Vue
  sources import gitignored Wayfinder modules that only Artisan generates. A
  draft pull request never calls this workflow: it runs the merge policy, plus
  `composer run ci:workflows` when `.github/**` changed. Marking the pull request
  ready is what asks for the gate, and converting it back to a draft cancels the
  run through the shared per-pull-request concurrency group.
- Canonical install command (the only supported one): `laravel new <name> --using=<repo URL> --database=sqlite --pnpm --no-boost --no-interaction`. Only `--pnpm` and `--no-interaction` are load-bearing. With Laravel Installer 5.31 `--boost` is `VALUE_NONE` and is only enabled inside `interact()`, so `--no-interaction` already skips Boost; `--no-boost` stays in the command purely as an explicit safeguard against reinitializing Jalara's preconfigured Boost and agent context (`boost.json`, `.ai/`), and against a future change to the noninteractive default. Boost remains a dependency of the installed application regardless. `--no-interaction` is what suppresses the installer's Pest scaffolding - `interact()` otherwise forces `pest`/`boost` on, and `installPest` runs `composer remove phpunit`, an unpinned `composer update`, `pest --init`, and `pest --drift` over the committed suite. `--pnpm` is required because the installer deletes every non-selected package manager's lock file and only installs/builds when a manager is passed explicitly; it also rewrites the `dev`/`dev:ssr`/`setup` Composer scripts through `str_replace(['npm','npx','ppnpm'], ['pnpm','pnpm dlx','pnpm'])`, so those scripts must round-trip that substitution unchanged.
- Installer ordering: `npx tiged <url> && composer install` (so `post-create-project-cmd` never fires), then `composer run post-root-package-install`, `key:generate`, the `extra.laravel.installer.post-create-project` hooks, then database configuration and migration, then the Node install/build. Package discovery, key generation, and the hooks all boot the application before `.env`, the SQLite file, and the schema exist; hooks must stay DB-free, noninteractive, and idempotent.
- The `installer` CI job reproduces every material stage of that command from `git archive HEAD` with no `APP_ENV` set (tiged would fetch the default branch, not the code under review). The ordinary gate jobs cannot detect these failures because they export `APP_ENV=testing`.
