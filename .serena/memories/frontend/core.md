# Frontend

- Inertia Vue 3 application entrypoint: `resources/js/app.ts`; pages: `resources/js/pages`; shared layouts: `resources/js/layouts`; shared app components: `resources/js/components`; composables: `resources/js/composables`; types: `resources/js/types`.
- Page layout selection is centralized in `resources/js/app.ts`: Welcome has no layout, `auth/*` uses AuthLayout, `account/*` nests AppLayout + AccountLayout (`resources/js/layouts/account/Layout.vue`), `settings/*` nests AppLayout + SettingsLayout (`resources/js/layouts/settings/Layout.vue`), other pages use AppLayout. `account/*` is per-user account management (profile/security/appearance); `settings/*` is application-wide administration.
- Vue SFCs use `<script setup lang="ts">`, a single template root, strict TypeScript, and the `@/*` alias for `resources/js/*`.
- Use Inertia `<Link>`, `<Form>`, hooks, and router APIs. Axios is not installed; Inertia v3's built-in HTTP facilities are the supported path.
- Backend links/actions must use generated Wayfinder imports under `@/actions` or `@/routes`; never hardcode backend URLs. Generated Wayfinder folders are not hand-edited and are excluded from ESLint.
- Reusable UI primitives live under `resources/js/components/ui` (Reka UI/shadcn-style); check these before adding a component.
- Tailwind CSS v4 is loaded through Vite; global stylesheet is `resources/css/app.css`. Dark/light initialization is handled by `useAppearance`.
- Inertia page metadata/layout props use `defineOptions({ layout: ... })`; page titles use `<Head>`.
- Frontend forms rely on Laravel/Inertia validation for every field, including passwords. Do not add HTML constraint-validation attributes such as `required`, `min`, `max`, or `pattern`; use input type/inputmode only for keyboard and value-entry semantics.
- Bind server errors with `:aria-invalid="Boolean(errors.field)"`. The shared `Input`, `Textarea`, and `PasswordInput` components already provide their base presentation; do not add per-usage classes or modify those components for field error state.