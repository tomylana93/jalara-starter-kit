---
paths:
  - 'routes/**'
---

# Routes

## Wayfinder CLI regeneration must pass --with-form
This repository runs the `wayfinder()` Vite plugin with `formVariants: true`, so generated output always includes `.form` helpers. Regenerating from the CLI must therefore pass `php artisan wayfinder:generate --with-form`; a plain run silently strips every `.form` helper and breaks type-checking across all pages that submit forms. The installed wayfinder skill presents the flag as optional — here it is mandatory.
