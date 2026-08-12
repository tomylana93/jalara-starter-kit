---
paths:
  - 'tests/**'
---

# Tests

## The smoke sweep must assert content, not just the absence of errors
`assertNoSmoke()` only proves there were no console logs and no JavaScript errors. A page that renders nothing at all produces neither, so a blank page passes it — which is exactly what happened when `tests/Browser` still inherited `withoutVite()`. Every page in the sweep therefore carries a positive `assertSee()` for text only a rendered page shows. The sweep proves each page boots *and renders its expected content*.

## What the browser layer proves today
Browser tests prove each page boots and renders its expected content. Multi-step flows are not yet covered at this layer; see `docs/adr/002-test-stack-consolidation.md`. Do not widen the browser suite's scope before the interaction-fidelity question recorded there is settled.

A page that only needs proving "renders with the right props" is tested in a Feature test. Feature tests verify the server contract — Inertia props, authorization, redirects — through `actingAs()`, never a browser storage state.

## Browser tests must not inherit withoutVite()
`Tests\TestCase` calls `withoutVite()`, which is right for a Feature test and fatal for a browser test: the `@vite` directive is stubbed, no bundle is served, and the browser renders a blank page. `tests/Browser` binds `Tests\BrowserTestCase` instead, and needs a current `pnpm run build`.

## Narrow untyped test helpers before fluent assertions
`$this->artisan()` returns `PendingCommand|int`. Narrow it through the `pendingCommand()` helper in `tests/Pest.php` before fluent command assertions; `inertiaRows()` does the same for untyped `viewData('page')` props feeding `collect()`. Put database-free object contracts in `tests/Unit`; bind `Tests\TestCase` only when the service container is needed.
