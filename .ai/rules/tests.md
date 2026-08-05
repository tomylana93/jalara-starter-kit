---
paths:
  - 'tests/**'
---

# Tests

## Narrow untyped test helpers before fluent assertions
`$this->artisan()` returns `PendingCommand|int`. Narrow it through the `pendingCommand()` helper in `tests/Pest.php` before fluent command assertions; `inertiaRows()` does the same for untyped `viewData('page')` props feeding `collect()`. Put database-free object contracts in `tests/Unit`; bind `Tests\TestCase` only when the service container is needed.
