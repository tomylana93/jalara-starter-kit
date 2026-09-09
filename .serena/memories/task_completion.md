# Task Completion

- PHP change: run `vendor/bin/pint --dirty --format agent`, then the narrowest affected `php artisan test --compact` target. Run `composer run types:check` when app/bootstrap/config/database/routes PHP types may be affected.
- Vue/TypeScript change: run `pnpm run check` and `pnpm run types:check`; run `pnpm run build` for bundling-sensitive changes.
- Cross-stack change: run the relevant commands above, then `composer run ci:check` (frontend check + Vue types + Composer test script) when a full integration gate is warranted.
- Do not treat `composer test` as tests alone: it clears config, checks Pint, runs PHPStan, then Laravel tests.