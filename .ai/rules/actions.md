---
paths:
  - 'app/Actions/**'
---

# Actions

## Actions expose a single handle() method
Business mutations live in single-purpose Action classes under a domain subdirectory, and the entry point is always `handle()`. Do not use `execute()` or `__invoke()`.
