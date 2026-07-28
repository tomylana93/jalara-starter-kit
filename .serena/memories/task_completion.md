# Task Completion

- Every source change must have programmatic coverage: add/update a Pest test, then run the smallest relevant test command, normally `php artisan test --compact <test-file>` or a focused `--filter`.
- PHP touched: run `vendor/bin/pint --dirty --format agent`; run focused Pest tests; add `composer run types:check` when type-sensitive/backend scope warrants it.
- Vue/TypeScript/CSS touched: run `pnpm run format`, `pnpm run lint:check`, `pnpm run types:check`, plus relevant backend feature tests for the end-to-end behavior.
- Route/controller contract changed: regenerate/use Wayfinder through the configured Vite integration and verify frontend type checks; no hardcoded route fallback.
- Agent context touched: validate custom skill structure, run `php artisan boost:update --no-interaction` twice to verify publication is idempotent, and run Serena reference checks when memories changed.
- Before every task handoff, `composer run ci:check` is mandatory after focused checks pass. Do not replace it with an equivalent subset of commands; report and resolve or explicitly surface every failure before completion.
- Frontend build-affecting change: run `pnpm run build` when proportionate; Vite manifest errors specifically require `pnpm run build` or an active dev server.
- Before handoff, inspect `git diff`/status, preserve unrelated user changes, and report the exact checks run and their outcomes.
- Do not create ad-hoc verification scripts when the test suite can demonstrate the behavior.