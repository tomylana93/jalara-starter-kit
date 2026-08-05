---
paths:
  - composer.json
  - README.md
  - vite.config.ts
---

# General

## Script or extra edits change the lock content hash
Editing the `scripts` or `extra` sections changes the lock content hash. Rerun `composer update --lock` and confirm `composer validate --strict --no-check-publish` passes with no dependency version changes.

## Never publish a hard-coded aggregate test count
README and badges must not publish a hard-coded aggregate test count. Pest datasets and runner parameterization make source-declaration counts differ from executed cases. Use the `tests` workflow status badge for repository health; if an exact metric is ever required, derive it from machine-readable runtime reports from Pest, Vitest, and Playwright rather than counting `test()` or `it()` declarations.

## Vitest must stay on the forks pool
The `test.env.TZ = 'Asia/Jakarta'` setting only applies under Vitest's default `forks` pool. Under `pool: 'threads'` the worker thread inherits the process timezone and the setting is silently ignored, so every browser-timezone date test fails on any machine not already in that zone — including CI, which runs UTC. A developer whose system clock is on Asia/Jakarta will see the suite pass locally either way, so this cannot be caught by running the gate. Do not set `pool`.
