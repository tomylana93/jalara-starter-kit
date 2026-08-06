---
paths:
  - 'resources/js/components/**'
---

# Components

## Never root a form field wrapper in display:contents
A wrapper component whose root is `class="contents"` is skipped by the box tree. A parent's `space-y-*` still *selects* it — the selector is `> * + *` — but the margin never renders, so the field collides with whatever follows it. Flex/grid `gap` on the parent keeps working, which is why the bug hides: it appears only on the pages that space their children with `space-y-*`, not on the ones using a `FieldGroup`.

Give such a wrapper a real box (a plain `<div>`). It still becomes a single flex item inside a gap-based parent, so both layouts stay correct.

jsdom computes no layout, so Vitest passes either way. `BooleanField.vue` shipped this bug and only a rendered screenshot caught it. When changing a shared field wrapper's root element, look at a real page.
