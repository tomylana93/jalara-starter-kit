# Playwright E2E

- E2E specs live under `e2e/` and run with `pnpm test:e2e`; Playwright builds assets, provisions Chromium, starts Laravel, and uses one worker.
- Use the isolated SQLite database at `storage/framework/testing/playwright.sqlite`, file sessions, and testing environment values. Global setup recreates migrations and initializes the non-production superadmin from process environment.
- Authentication is a setup project that writes `e2e/.auth/superadmin.json`; Chromium settings specs consume that storage state. Never commit credentials or auth state.
- Keep E2E to critical user journeys and observable browser output. Do not add testing endpoints or duplicate detailed Feature-test ownership.
- Iterate with `pnpm exec playwright test <spec> --grep '<name>'`; run the full suite twice when changing database/setup behavior to prove idempotence.
- CI installs Chromium with `pnpm exec playwright install --with-deps chromium`.
- After normal, failed, interrupted, or terminated runs, verify no Playwright run-server remains with `pgrep -af '[p]laywright.*run-server'`.