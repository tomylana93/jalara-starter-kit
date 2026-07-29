# Frontend

- Inertia Vue entrypoint: `resources/js/app.ts`; pages: `resources/js/pages`; layouts: `resources/js/layouts`; shared components: `resources/js/components`; composables: `resources/js/composables`.
- Layout selection is centralized in `resources/js/app.ts`: auth pages use AuthLayout, account pages nest AppLayout + AccountLayout, and settings pages use AppLayout with PageWrapper.
- Vue SFCs use `<script setup lang="ts">`, a single root, strict TypeScript, and the `@/*` alias.
- Use Inertia Link/Form/hooks and generated Wayfinder imports under `@/actions` or `@/routes`; do not hardcode backend URLs in application code.
- Reuse UI primitives under `resources/js/components/ui`. Tailwind CSS v4 is global through `resources/css/app.css`; `useAppearance` owns dark/light initialization.
- `resources/js/components/ui/**` is registry-owned and read-only: never hand-edit, hand-patch, or hand-author files there, and never reimplement a registry primitive elsewhere. Add or update primitives only through the locally installed shadcn-vue CLI (`pnpm exec shadcn-vue add|update <component>`; the `shadcn-vue` package is a devDependency, do not use `pnpm dlx`), configured by `components.json` (style `new-york-v4`, base color `neutral`, alias `@/components/ui`, lucide icons). Registry additions require the same approval as any dependency change.
- Resolve registry incompatibilities outside `ui`: run an official CLI update, or adapt in consumer/bootstrap code (`resources/js/app.ts`, wrapper components). If a needed behavior cannot be expressed that way, build a separate non-`ui` component instead of forking the primitive.
- Color preset contract, brand-accent scope, nested draft previews, and auth/chrome coverage: `mem:frontend/color_theme`.
- Frontend forms use Laravel/Inertia validation. Avoid native constraint attributes; preserve input type/inputmode semantics and bind errors with `:aria-invalid="Boolean(errors.field)"`.
- Colocate Vitest component tests with pages/components. Use the shared `resources/js/test/setup.ts` Inertia/translation/browser stubs and assert rendered output or submitted controls rather than internal refs.
- Media upload field placement, approved registry primitives, no-helper-text convention, required branding assets, and public fallback paths: `mem:frontend/media_uploads`.