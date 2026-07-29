# Frontend

- Inertia Vue entrypoint: `resources/js/app.ts`; pages: `resources/js/pages`; layouts: `resources/js/layouts`; shared components: `resources/js/components`; composables: `resources/js/composables`.
- Layout selection is centralized in `resources/js/app.ts`: auth pages use AuthLayout, account pages nest AppLayout + AccountLayout, and settings pages use AppLayout with PageWrapper.
- Vue SFCs use `<script setup lang="ts">`, a single root, strict TypeScript, and the `@/*` alias.
- Use Inertia Link/Form/hooks and generated Wayfinder imports under `@/actions` or `@/routes`; do not hardcode backend URLs in application code.
- Reuse UI primitives under `resources/js/components/ui`. Tailwind CSS v4 is global through `resources/css/app.css`; `useAppearance` owns dark/light initialization.
- Frontend forms use Laravel/Inertia validation. Avoid native constraint attributes; preserve input type/inputmode semantics and bind errors with `:aria-invalid="Boolean(errors.field)"`.
- Colocate Vitest component tests with pages/components. Use the shared `resources/js/test/setup.ts` Inertia/translation/browser stubs and assert rendered output or submitted controls rather than internal refs.
- Media upload field placement, approved registry primitives, no-helper-text convention, required branding assets, and public fallback paths: `mem:frontend/media_uploads`.