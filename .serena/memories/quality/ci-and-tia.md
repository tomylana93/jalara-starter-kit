# Quality, CI, and TIA

- Composer requires PHP `^8.5`. Quality dependencies include Rector 2.6, Larastan/PHPStan, Pest 5, and the Pest type-coverage, PHPStan, and Rector plugins.
- `composer run ci:check` is the complete local/GitHub gate: frontend formatting/lint and Vue types, Pint, PHPStan (including Pest tests), Rector dry-run, then `pest --ci --type-coverage`. It must run the full test suite, not TIA.
- `rector.php` targets PHP 8.5, includes the Pest coding-style set, and checks app, bootstrap/app.php, config, database, routes, and tests. `phpstan.neon` includes Larastan, Carbon, and the Pest PHPStan extension and scans `tests/` as well.
- `composer run test:tia` is the local fast path. It activates the installed-but-CLI-disabled Xdebug only for that process with `XDEBUG_MODE=coverage php -d zend_extension=xdebug`, so TIA works without a privileged host change.
- `.github/workflows/tests.yml` calls the full Composer CI gate. `.github/workflows/tia-baseline.yml` is a separate main/scheduled/manual job that configures Xdebug, records `pest --parallel --tia --fresh`, and uploads the `pest-tia-baseline` artifact. Baseline fetching is opt-in through `PEST_TIA_BASELINED=1` after that artifact exists; do not make normal local TIA depend on an artifact that has not yet been published.
