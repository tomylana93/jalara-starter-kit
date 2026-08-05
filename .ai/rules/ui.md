---
paths:
  - 'resources/js/components/ui/**'
---

# Ui

## Registry components are CLI-owned and read-only
Never hand-edit, hand-patch, or hand-author files here, and never reimplement a registry primitive elsewhere. Add or update only through the locally installed shadcn-vue CLI (`pnpm exec shadcn-vue add|update <component>` — the package is a devDependency, do not use `pnpm dlx`), configured by `components.json`. Resolve registry incompatibilities outside this directory: run an official CLI update, or adapt in consumer/bootstrap code (`resources/js/app.ts`, wrapper components). If a needed behavior cannot be expressed that way, build a separate non-`ui` component instead of forking the primitive. Registry additions require the same approval as any dependency change.
