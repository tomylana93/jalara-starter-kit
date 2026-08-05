---
paths:
  - 'tests/PHPStan/**'
---

# P H P Stan

## Add stubs for genuine vendor typing gaps, never baselines
PHPStan level 7 analyses `tests/` and loads stubs from this directory. `Settings.stub` corrects `Spatie\LaravelSettings\Settings::fill/save/refresh`, which vendor declares `: self`; without it every `app(XSettings::class)->refresh()->property` chain reports `property.notFound` (~104 errors). Add a stub here for a genuine vendor typing gap — never a baseline and never an ignore.
