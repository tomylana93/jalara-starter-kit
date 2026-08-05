# Frontend

- Inertia Vue entrypoint: `resources/js/app.ts`; pages: `resources/js/pages`; layouts: `resources/js/layouts`; shared components: `resources/js/components`; composables: `resources/js/composables`.
- Layout selection is centralized in `resources/js/app.ts`: auth pages use AuthLayout, account pages nest AppLayout + AccountLayout, and settings pages use AppLayout with PageWrapper.
- Vue SFCs use `<script setup lang="ts">`, a single root, strict TypeScript, and the `@/*` alias.
- Use Inertia Link/Form/hooks and generated Wayfinder imports under `@/actions` or `@/routes`; do not hardcode backend URLs in application code.
- Reuse UI primitives under `resources/js/components/ui`. Tailwind CSS v4 is global through `resources/css/app.css`; `useAppearance` owns dark/light initialization.
- `resources/js/components/ui/**` is registry-owned and read-only: never hand-edit, hand-patch, or hand-author files there, and never reimplement a registry primitive elsewhere. Add or update primitives only through the locally installed shadcn-vue CLI (`pnpm exec shadcn-vue add|update <component>`; the `shadcn-vue` package is a devDependency, do not use `pnpm dlx`), configured by `components.json` (style `new-york-v4`, base color `neutral`, alias `@/components/ui`, lucide icons). Registry additions require the same approval as any dependency change.
- Application UI glyphs under `resources/js` use individually imported components from `@lucide/vue`, matching `components.json` iconLibrary `lucide`; branding artwork and non-icon decorative SVG patterns are exempt, and registry-owned `resources/js/components/ui/**` remains CLI-managed.
- Resolve registry incompatibilities outside `ui`: run an official CLI update, or adapt in consumer/bootstrap code (`resources/js/app.ts`, wrapper components). If a needed behavior cannot be expressed that way, build a separate non-`ui` component instead of forking the primitive.
- The reusable Tiptap surface is `resources/js/components/editor/RichTextEditor.vue`; it consumes `RichTextDocument` from `resources/js/types/editor.ts`, the generic `editor.*` translation domain, and `.rich-text-content` styling shared with the read-only renderer. Keep it free of documentation-specific names; documentation management is one consumer and its server-valid node schema still bounds what the toolbar may produce (`mem:documentation`).
- `useEditor` from `@tiptap/vue-3` returns a bare shallow ref, so transactions do not invalidate computeds. Toolbar state derived from `isActive()`/`can()` must depend on a counter bumped in the editor's `onTransaction` callback.
- Color preset contract, brand-accent scope, nested draft previews, and auth/chrome coverage: `mem:frontend/color_theme`.
- Generic server-driven table (TanStack controlled state, domain-local `columns.ts`, URL query state): `mem:frontend/data_table`.
- Frontend forms use Laravel/Inertia validation. Avoid native constraint attributes; preserve input type/inputmode semantics and bind errors with `:aria-invalid="Boolean(errors.field)"`.
- Registry dialog action components (`AlertDialogAction`, and any primitive that
  forwards `@click` as a fallthrough attribute) run their own close handler
  BEFORE the consumer's listener, because Vue merges the component's own props
  ahead of inherited attrs. A confirm flow whose `open` binding also clears the
  pending target therefore reads null in its confirm handler and silently sends
  nothing - no error, no request. Keep "which record is pending" and "is the
  dialog open" in separate refs, and clear the target only after dispatching.
  jsdom can order these the other way, so a passing component test is not
  evidence; drive the close explicitly (`findComponent(AlertDialog).vm.$emit('update:open', false)`)
  before clicking confirm.
- Colocate Vitest component tests with pages/components. Use the shared `resources/js/test/setup.ts` Inertia/translation/browser stubs and assert rendered output or submitted controls rather than internal refs.
- Media upload field placement, approved registry primitives, no-helper-text convention, required branding assets, and public fallback paths: `mem:frontend/media_uploads`.
- Chat surfaces: which layer owns paging vs viewport vs realtime, the message registry primitives and their test stubs, and the desktop-only widget: `mem:frontend/chat`.