# Suggested Commands

- First install: `composer run setup` (creates local .env when absent, generates key, migrates, installs pnpm packages, builds assets).
- Local development: `composer run dev` delegates to `php artisan dev`; frontend-only: `pnpm run dev`.
- Build assets: `pnpm run build`; SSR build: `pnpm run build:ssr`.
- PHP formatting: `vendor/bin/pint --dirty --format agent` after PHP edits; repo lint / lint check: `composer run lint`, `composer run lint:check`.
- Frontend lint / formatter: `pnpm run check`, auto-fix `pnpm run check:fix`; Vue types: `pnpm run types:check`.
- PHP static analysis: `composer run types:check`. Tests: narrow `php artisan test --compact <path-or-filter>`; full project gate: `composer test` or `composer run ci:check`.