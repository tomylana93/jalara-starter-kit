# Suggested Commands

- Local setup: `composer run setup`; GitHub CI dependency/environment setup without an early build: `composer run ci:setup`; development: `composer run dev`; frontend dev: `pnpm run dev`.
- Vitest: `pnpm test:unit`; watch: `pnpm test:unit:watch`.
- Pest focused: `php artisan test --compact <file>` or `--filter=<name>`; full backend: `composer test`.
- Pest TIA (requires PCOV/Xdebug): `composer run test:tia`; rebuild baseline: `composer run test:tia:fresh`.
- Playwright E2E: `pnpm test:e2e`; focused: `pnpm exec playwright test <spec> --grep '<name>'`.
- Frontend auto-fix order: `pnpm run lint`, then `pnpm run format`; checks: `pnpm run lint:check`, `pnpm run format:check`, `pnpm run types:check`, `pnpm run test:unit`. Use `pnpm run test:e2e` for the production build plus Playwright.
- PHP auto-fix order: `composer run rector`, then `composer run format:agent`; repository-wide formatting: `composer run lint`. Checks run Rector before Pint: `composer run rector:check`, then `composer run lint:check` and `composer run types:check`. Rector apply/check output JSON without a progress bar.
- Mandatory final gate: `composer run ci:check` orders frontend lint, format, types, Vitest; PHP Rector, Pint, Larastan, Pest; then the build-backed Playwright suite.
- Publish agent context: `composer run agents:update`; memory checks remain Serena-native: `serena memories check` and `serena memories check --include-unmarked --fuzzy-matching`.