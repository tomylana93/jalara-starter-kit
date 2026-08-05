---
paths:
  - 'resources/js/**'
---

# Js

## Serena symbol overview is unreliable for .vue files
For single-file Vue components Serena exposes template nodes instead of `script setup` symbols. Read the .vue file directly to orient, then use targeted find_symbol once the name is known. Treat Serena Vue/TS diagnostics as hints only; `pnpm run types:check` is authoritative.
