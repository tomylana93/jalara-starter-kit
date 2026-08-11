# Frontend

- Inertia Vue entrypoint: `resources/js/app.ts`; pages: `resources/js/pages`; layouts: `resources/js/layouts`; shared components: `resources/js/components`; composables: `resources/js/composables`.
- Layout selection is centralized in `resources/js/app.ts`: auth pages use AuthLayout, account pages nest AppLayout + AccountLayout, and settings pages use AppLayout with PageWrapper.
- Vue SFCs use `<script setup lang="ts">`, a single root, strict TypeScript, and the `@/*` alias.
- Use Inertia Link/Form/hooks and generated Wayfinder imports under `@/actions` or `@/routes`; do not hardcode backend URLs in application code.
- Reuse UI primitives under `resources/js/components/ui`. Tailwind CSS v4 is global through `resources/css/app.css`; `useAppearance` owns dark/light initialization.
- `resources/js/components/ui/**` is registry-owned and CLI-managed, configured by `components.json` (style `new-york-v4`, base color `neutral`, alias `@/components/ui`, lucide icons). The read-only/editing constraint itself is a Project Rule on that glob.
- Application UI glyphs under `resources/js` use individually imported components from `@lucide/vue`, matching `components.json` iconLibrary `lucide`; branding artwork and non-icon decorative SVG patterns are exempt, and registry-owned `resources/js/components/ui/**` remains CLI-managed.
- The reusable Tiptap surface is `resources/js/components/editor/RichTextEditor.vue`; it consumes `RichTextDocument` from `resources/js/types/editor.ts`, the generic `editor.*` translation domain, and `.rich-text-content` styling shared with the read-only renderer. Keep it free of docs-specific names; docs management is one consumer and its server-valid node schema still bounds what the toolbar may produce (`mem:documentation`).
- `useEditor` from `@tiptap/vue-3` returns a bare shallow ref, so transactions do not invalidate computeds. Toolbar state derived from `isActive()`/`can()` must depend on a counter bumped in the editor's `onTransaction` callback.
- Color preset contract, brand-accent scope, nested draft previews, and auth/chrome coverage: `mem:frontend/color_theme`.
- Generic server-driven table (TanStack controlled state, domain-local `columns.ts`, URL query state): `mem:frontend/data_table`.
- Frontend forms use Laravel/Inertia validation. Avoid native constraint attributes; preserve input type/inputmode semantics and bind errors with `:aria-invalid="Boolean(errors.field)"`.
- Colocate Vitest component tests with pages/components. Use the shared `resources/js/test/setup.ts` Inertia/translation/browser stubs and assert rendered output or submitted controls rather than internal refs.
- Media upload field placement, approved registry primitives, no-helper-text convention, required branding assets, and public fallback paths: `mem:frontend/media_uploads`.
- Chat surfaces: which layer owns paging vs viewport vs realtime, the message registry primitives and their test stubs, and the desktop-only widget: `mem:frontend/chat`.