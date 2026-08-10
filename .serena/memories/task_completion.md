# Task Completion

Routing anchor only; this memory holds no policy of its own.

- The required verification sequence, the auto-fix ordering, the two-tier gate composition, and the rule that `composer run ci:full` is required after CI/release/coverage/installer/Playwright configuration changes are owned by the `Verification Boundaries` section of `.ai/guidelines/agent-workflow.md`, which loads on every task.
- Which runner owns which coverage, and the Pest/Vitest/Playwright boundaries: `mem:testing`.
- Playwright run isolation, the twice-run idempotence check, and run-server cleanup: `mem:testing/browser`.
- Concrete command invocations and CI job layout: `mem:suggested_commands`.
