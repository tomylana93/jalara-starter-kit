# Playwright E2E

- E2E specs live under `e2e/` and run with `pnpm test:e2e`; Playwright builds assets, provisions Chromium, starts Laravel, and uses one worker.
- Use the isolated SQLite database at `storage/framework/testing/playwright.sqlite`, file sessions, and testing environment values. Global setup recreates migrations and initializes the non-production superadmin from process environment.
- Authentication is a setup project that writes `e2e/.auth/superadmin.json`; Chromium settings specs consume that storage state. Never commit credentials or auth state.
- Keep E2E to critical user journeys and observable browser output. Do not add testing endpoints or duplicate detailed Feature-test ownership.
- Iterate with `pnpm exec playwright test <spec> --grep '<name>'`; run the full suite twice when changing database/setup behavior to prove idempotence.
- CI installs Chromium with `pnpm exec playwright install --with-deps chromium`.
- After normal, failed, interrupted, or terminated runs, verify no Playwright run-server remains with `pgrep -af '[p]laywright.*run-server'`.
- Playwright owns dedicated Vite paths: `E2E_ASSET_ISOLATION=true` (exported by `e2e/run-tests.sh`) selects `public/build-e2e` and `public/hot-e2e` in both `vite/asset-output.ts` and `config/app.php` (applied by `AppServiceProvider::configureVite`). The runner must never snapshot, delete, or restore `public/build`/`public/hot`; those belong to the developer's Vite session, and the two sides' paths must always be derived together. To prevent Laravel from serving cached developer paths while `pnpm test:e2e` builds `public/build-e2e`, `e2e/run-tests.sh` must clear Laravel's cached configuration after exporting the flag and before any build or server boot.
- Vitest also loads `vite.config.ts`, and the Laravel Vite plugin registers a process-exit handler that deletes its configured hot file, so `pnpm run test:unit` gets its own `public/hot-vitest` path; without it a unit run erases a live development marker.
- Laravel's `withoutVite()` stub ignores `useHotFile`, so feature tests still see a real `public/hot`. Tests that depend on Vite not running hot must tolerate the hot code path instead of configuring it away.
- A green E2E/CI gate no longer rebuilds `public/build`; preview frontend changes through an active Vite session or a fresh local build.