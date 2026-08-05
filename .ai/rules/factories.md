---
paths:
  - 'database/factories/**'
---

# Factories

## Never set a UUID id manually in a factory
Application models use the `HasUuids` trait, which generates the UUIDv7 primary key. Do not assign `id` in a factory definition or state; let the trait produce it.

## A factory names every column of its table
`definition()` must set every column, using an explicit `null` where that is the intended default. Exempt: the primary key, timestamps, and anything already in the model's `$attributes`.

`Model::shouldBeStrict()` is on outside production, so a column the factory never set is missing from the in-memory model rather than null — reading it throws, and an `#[Appends]` accessor turns that into a 500 during serialization far from its cause.

`tests/Feature/FactoryCoverageTest.php` enforces this against the live schema. Feature tests cannot: they only fail for columns some assertion happens to touch.
