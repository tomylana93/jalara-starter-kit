# Task Completion

- Every source change needs programmatic coverage in its owning runner: Pest for Laravel Unit/Feature, Vitest for Vue/TypeScript units/components, Playwright only for critical cross-stack journeys.
- E2E/setup touched: run Playwright on fresh isolated SQLite twice and confirm no run-server remains; exercise failure/signal cleanup when process lifecycle changes.
- `composer run ci:full` is required in addition to the standard gate after changes to CI, release, coverage, installer, or Playwright configuration; its `coverage:check` stage needs a local PCOV/Xdebug driver.