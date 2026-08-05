---
paths:
  - 'app/Http/Requests/**'
---

# Requests

## All validation lives in Form Requests
Every validated endpoint has a Form Request under a domain subdirectory. Never call `$request->validate()` or `Validator::make()` in a controller. Share repeated rule sets through a trait in `app/Concerns`.
