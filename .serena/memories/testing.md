# Testing

- Pest owns Laravel behavior under `tests/Unit`, `tests/Feature`, and — since the test-stack consolidation — browser coverage under `tests/Browser`. See `mem:testing/browser`.
- Vitest owns TypeScript modules and composables, including ones that manipulate `document`, `window`, or storage: there the DOM is the subject under test, not a rendering surface. Vitest does not render application `.vue` SFCs. The boundary is a Project Rule on `resources/js/**`.
- A page that only needs proving "renders with the right props" is tested at the Feature level through `actingAs()`; its JavaScript errors are covered by the browser smoke sweep. Browser tests are reserved for multi-step flows.
- Keep one canonical test per behavior and do not duplicate backend contracts across layers.
- Tests only guard application behavior or build artifacts the running application consumes. Do not test development tooling, agent/skill infrastructure, Composer/CI wiring, README/LICENSE content, or dev-only dependency registration; those surfaces are proven by the command that owns them (`composer run ci:check`, the linters, the agent-context generator), not by a Pest assertion. The one exception is the release-governance surface: `app/Console/Commands/Ci` decides merge policy and release eligibility, and its cases live in `tests/Feature/Ci`; That is executable policy with a security consequence, not wiring. The structural workflow-shape and gate-outcome tests were dropped in the consolidation: they described tooling rather than application behavior.
- Coverage is enforced at 80% via `composer run coverage:check`, reached through `ci:pest:coverage`; locally it is part of `composer run ci:full` only.
- `tests/Pest.php` calls `pest()->tia()->locally()`, so TIA is active on local runs and skipped automatically on CI, where the full suite always runs. Pest's own docs forbid `--tia` in a test pipeline.
- No global test timezone is configured; the suite passes identically under any machine zone because tests that render browser-local instants pin it themselves through `resources/js/test/timeZone.ts`. The obligation is a Project Rule on `resources/js/**`.
- Vitest keeps per-file isolation on purpose. `isolate: false` tears down the jsdom globals after the first file in a worker, so later files fail with `document is not defined`, and `resources/js/test/setup.ts` exposes mutable module state (`inertiaPageProps`, `formState`, `httpState`, `echoState`, `inertiaClientState`) with no global reset hook. GitHub CI loads PCOV. Dependency advisories run in the GitHub Actions gate through the shared `composer run audit:check` primitive; local `ci:check` and `ci:full` exclude the non-hermetic audit.
- Prefer stable public outcomes over collaborator wiring, internal call sequences, or class-shape assertions.
- `laravel/pao` must stay at `>= 1.1.3`. PAO 1.1.2 repeated Collision's
  `--no-output` flag, so `php artisan test` exited 1 while reporting
  `result: passed`; 1.1.3 fixes the duplicate flag and focused
  `php artisan test --compact` runs green with PAO enabled.
- The repo has no two-factor support: no `two_factor_*` columns, and `fortify.features` omits it. Do not write tests against Fortify 2FA.
- `resources/views/app.blade.php` passes the page component to `@vite`, so a Pest feature test hitting a *new* Inertia page fails with "Unable to locate file in Vite manifest" until `pnpm run build` runs. Build after adding a page, before running feature tests.
- Browser-suite layout, build prerequisite, and focused commands: `mem:testing/browser`.