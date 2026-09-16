---
paths:
    - '**/*'
---

# Tools

## Use Composer as the tooling entry point

Run project tooling through scripts defined in composer.json instead of invoking vendor/bin executables or package.json scripts directly.
Before reporting any task complete, run composer ci:check. If it fails, report the failing stage and do not claim completion.
