# Conventions

- HTTP routes are named and map to Inertia pages; use `Route::inertia` for simple page endpoints and controllers for settings mutations.
- Controllers return `Inertia::render()` or named redirects (`to_route()`); success feedback uses `Inertia::flash('toast', ...)`.
- Auth/settings request validation is delegated to Form Requests. User uses Laravel attributes for fillable/hidden fields, protected `casts(): array`, plus Fortify 2FA and passkey traits.
- Route-bound Vue code imports generated Wayfinder functions from `@/routes` or `@/actions`, and submits with Inertia Vue `<Form v-bind="action.form()">`; do not hand-code backend URLs.
- Vue pages use `<script setup lang="ts">`, `defineProps`, and layout assignment with `defineOptions({ layout: ... })`; one root element. Aliases include `@/components`, `@/composables`, `@/lib`, `@/components/ui`.
- Feature tests use Pest closures, User factories, `actingAs()`, named routes, Inertia component/prop assertions, and test authenticated plus rejected/error paths.