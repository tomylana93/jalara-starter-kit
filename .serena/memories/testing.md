# Testing

- Pest owns Laravel Unit/Feature behavior under `tests/Unit` and `tests/Feature`; it does not own browser or Vue component coverage.
- Vitest owns TypeScript utilities and Vue component behavior. Colocate `*.test.ts` with the component/page and assert rendered user-visible output, DOM attributes, emitted behavior, and form payload controls rather than private Vue state.
- Playwright Test owns only critical cross-stack Chromium flows. E2E proves wiring and representative user journeys; authorization, persistence edge cases, validation detail, and permission matrices stay in Pest Feature tests.
- Keep one canonical test per behavior and do not duplicate backend contracts across Pest and Playwright.
- Organize Pest feature tests by observable domain under `tests/Feature/{Domain}`; do not mirror implementation-layer folders.
- Put database-free object contracts in `tests/Unit`; bind `Tests\TestCase` only when the service container is needed.
- Prefer stable public outcomes over collaborator wiring, internal call sequences, or class-shape assertions.
- E2E isolation and focused commands: `mem:testing/browser`.