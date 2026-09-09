---
paths:
  - 'tests/**'
---

# Tests

## Feature database isolation
Feature tests use the globally configured RefreshDatabase trait for database isolation.

## Factory-backed test data
Create test-owned model data with factories and their states.

## Immutable dates
Treat application dates as immutable; the Date facade is configured with CarbonImmutable.
