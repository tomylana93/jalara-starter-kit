# Suggested Commands

- Setup: `composer run setup`; development: `composer run dev`; frontend dev: `pnpm run dev`.
- Vitest: `pnpm test:unit`; watch: `pnpm test:unit:watch`.
- Pest focused: `php artisan test --compact <file>` or `--filter=<name>`; full backend: `composer test`.
- Pest TIA (requires PCOV/Xdebug): `composer test:tia`; rebuild baseline: `vendor/bin/pest --tia --fresh`.
- Playwright E2E: `pnpm test:e2e`; focused: `pnpm exec playwright test <spec> --grep '<name>'`.
- Frontend checks: `pnpm run lint:check`, `pnpm run format:check`, `pnpm run types:check`, `pnpm run build`.
- PHP checks: `composer run lint:check`, `composer run rector:check`, `composer run types:check`; PHP formatting: `vendor/bin/pint --dirty --format agent`.
- Mandatory final gate: `composer run ci:check` (frontend checks, Vitest, Pest, build, Playwright).
- Publish agent context: `php artisan boost:update --no-interaction`; memory checks: `serena memories check` and `serena memories check --include-unmarked --fuzzy-matching`.