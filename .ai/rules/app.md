---
paths:
  - 'app/**'
---

# App

## Run Rector before Pint on PHP changes
After changing PHP, run `composer run rector` first, then `composer run format:agent`. Rector performs structural refactoring that Pint must format afterwards; doing it in the other order leaves a dirty tree. Never reproduce Rector changes by hand when `rector:check` blocks CI.
