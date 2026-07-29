# Task Completion

- Every source change needs programmatic coverage in its owning runner: Pest for Laravel Unit/Feature, Vitest for Vue/TypeScript units/components, Playwright only for critical cross-stack journeys.
- Run the smallest affected tests first, then the full runner for each changed surface.
- PHP touched: run `vendor/bin/pint --dirty --format agent`, focused Pest, and applicable Larastan/Rector checks.
- Vue/TypeScript/CSS touched: run Vitest, formatting, ESLint, type-check, and build.
- E2E/setup touched: run Playwright on fresh isolated SQLite twice and confirm no run-server remains; exercise failure/signal cleanup when process lifecycle changes.
- Agent context touched: run Boost publication twice for idempotence and both Serena memory reference checks.
- Before handoff, run mandatory `composer run ci:check`; do not replace it with an equivalent subset.
- Review status and complete diff, preserve unrelated changes, run `git diff --check`, and report exact validation outcomes.