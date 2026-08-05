---
paths:
  - 'tests/Feature/**'
---

# Feature

## Organize feature tests by observable domain
Group Pest feature tests under `tests/Feature/{Domain}` by observable domain; do not mirror implementation-layer folders. Prefer stable public outcomes over collaborator wiring, internal call sequences, or class-shape assertions.
