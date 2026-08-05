---
paths:
  - resources/js/test/setup.ts
---

# Test

## Shared Vitest stubs must stay faithful to the real primitives
Stubs must match the real primitive: `Input` emits `update:modelValue` (needed for `v-model`) and `PageWrapper` renders both the `actions` and default slots. `inertiaPageProps` is a plain object, so a `computed` over it does not re-evaluate — set permissions before the first read instead of mutating mid-test. This file must also carry the runtime pieces `resources/js/app.ts` provides, because Vitest never boots the Inertia app: `config.global.components` registers `Primitive` (registry files reference it globally without importing it), and `@inertiajs/core` is mocked so `http.getClient().request` records into `inertiaClientState` instead of opening a jsdom XHR. Without the mock, `chatClient`/`imageUploads` calls print `connect ECONNREFUSED 127.0.0.1:3000` after the run with no test attribution.
