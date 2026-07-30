# Testing

- Pest owns Laravel Unit/Feature behavior under `tests/Unit` and `tests/Feature`; it does not own browser or Vue component coverage.
- Vitest owns TypeScript utilities and Vue component behavior. Colocate `*.test.ts` with the component/page and assert rendered user-visible output, DOM attributes, emitted behavior, and form payload controls rather than private Vue state.
- Playwright Test owns only critical cross-stack Chromium flows. E2E proves wiring and representative user journeys; authorization, persistence edge cases, validation detail, and permission matrices stay in Pest Feature tests.
- Keep one canonical test per behavior and do not duplicate backend contracts across Pest and Playwright.
- GitHub CI loads PCOV and enforces at least 80% application coverage via `composer run coverage:check`; dependency advisories are part of `composer run ci:check`.
- Organize Pest feature tests by observable domain under `tests/Feature/{Domain}`; do not mirror implementation-layer folders (e.g. `tests/Feature/Documentation/ReadmeTest.php` checks README/LICENSE presence and content rules).
- Put database-free object contracts in `tests/Unit`; bind `Tests\TestCase` only when the service container is needed.
- Prefer stable public outcomes over collaborator wiring, internal call sequences, or class-shape assertions.
- `resources/views/app.blade.php` passes the page component to `@vite`, so a Pest feature test hitting a *new* Inertia page fails with "Unable to locate file in Vite manifest" until `pnpm run build` runs. Build after adding a page, before running feature tests.
- Shared Vitest stubs in `resources/js/test/setup.ts` must stay faithful to the real primitive: `Input` emits `update:modelValue` (needed for `v-model`) and `PageWrapper` renders both the `actions` and default slots. `inertiaPageProps` is a plain object, so a `computed` over it does not re-evaluate — set permissions before the first read instead of mutating mid-test.
- E2E isolation and focused commands: `mem:testing/browser`.