# Conventions

- Laravel: thin controllers, validation in Form Requests or reusable concerns, authorization in policies, Eloquent over raw queries, named routes and `route()`/`to_route()` for URLs.
- Vue: Composition API with `<script setup lang="ts">`, single root template, strict types, `@/` imports, shared components before new one-off UI.
- Wayfinder-generated `resources/js/actions`, `resources/js/routes`, and `resources/js/wayfinder` are generated artifacts; call their typed functions from frontend code rather than hardcoded URLs. They are gitignored and normally produced by the `wayfinder()` Vite plugin, which sets `formVariants: true`; the CLI-regeneration constraint that follows from that is a Project Rule on `routes/**`.
- Private-method retention, extraction, inlining, removal, and placement rules are owned by `mem:architecture/private_methods`.
