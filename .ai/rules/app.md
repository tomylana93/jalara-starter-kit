---
paths:
  - 'app/**'
---

# App

## Direct Eloquent persistence
Use Eloquent models directly for persistence; do not introduce repository or dedicated query-object layers.

## Immutable dates
Treat application dates as immutable; the Date facade is configured with CarbonImmutable.
