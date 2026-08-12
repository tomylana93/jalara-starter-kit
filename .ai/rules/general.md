---
paths:
  - composer.json
  - README.md
  - '**/*.php'
---

# General

## Script or extra edits change the lock content hash
Editing the `scripts` or `extra` sections changes the lock content hash. Rerun `composer update --lock` and confirm `composer validate --strict --no-check-publish` passes with no dependency version changes.

## Never publish a hard-coded aggregate test count
README and badges must not publish a hard-coded aggregate test count. Pest datasets and runner parameterization make source-declaration counts differ from executed cases. Use the `tests` workflow status badge for repository health; if an exact metric is ever required, derive it from machine-readable runtime reports from Pest, Vitest, and Playwright rather than counting `test()` or `it()` declarations.

## PHPDoc must add information
Add PHPDoc only when it conveys information native PHP types and descriptive names cannot express, such as array shapes, generics, framework magic, public contracts, or non-obvious invariants. “Prefer PHPDoc over inline comments” governs the form of a necessary comment, not comment frequency; omit PHPDoc that merely restates a signature or obvious behavior.
