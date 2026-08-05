---
paths:
  - 'config/**'
---

# Config

## Cast env() values to their expected type
Wrap `env()` in the type the config value needs — `(string)`, `(int)`, `(bool)` — before passing it to anything typed. `env()` returns `bool|string|null`, so an uncast value fails static analysis. This applies to vendor-published config files too.
