---
paths:
  - 'database/factories/**'
---

# Factories

## Never set a UUID id manually in a factory
Application models use the `HasUuids` trait, which generates the UUIDv7 primary key. Do not assign `id` in a factory definition or state; let the trait produce it.
