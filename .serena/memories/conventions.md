# Conventions

- Follow sibling files for structure and naming; names are descriptive, classes/components TitleCase, PHP methods/variables camelCase.
- PHP: explicit parameter and return types; constructor property promotion where dependencies exist; curly braces for every control structure; no empty public constructors; Enum cases TitleCase.
- PHP documentation: prefer PHPDoc blocks and array-shape/generic annotations where types cannot be expressed natively; inline comments only for exceptional complexity.
- Laravel: thin controllers, validation in Form Requests or reusable concerns, authorization in policies, Eloquent over raw queries, named routes and `route()`/`to_route()` for URLs.
- API work defaults to versioned endpoints and Eloquent API Resources unless established routes show another convention.
- Vue: Composition API with `<script setup lang="ts">`, single root template, strict types, `@/` imports, shared components before new one-off UI.
- ESLint enforces type-only imports, alphabetized import groups, all control braces, 1TBS brace style, and blank lines around control statements.
- Prettier: 4-space tabs, semicolons, single quotes, 80 columns; Tailwind plugin sorts utility classes and recognizes `clsx`, `cn`, and `cva`.
- Wayfinder-generated `resources/js/actions`, `resources/js/routes`, and `resources/js/wayfinder` are generated artifacts; call their typed functions from frontend code rather than hardcoded URLs. They are gitignored and normally produced by the `wayfinder()` Vite plugin, which sets `formVariants: true`. Regenerating from the CLI must therefore pass `php artisan wayfinder:generate --with-form`; a plain run silently strips every `.form` helper and breaks type-checking across all pages that submit forms.
- Pest tests use function-style `test()`/expectations and factories; feature tests use database refresh globally. Every behavior change requires a new or updated programmatic test.
- All application Eloquent models use UUIDv7 primary keys, not auto-increment integers: migrations declare `$table->uuid('id')->primary()`, models use the `HasUuids` trait (never set the id manually in factories), and relations use `foreignUuid()` or `foreignIdFor()` (the latter auto-detects UUID/ULID/int from the related model's traits). Any code that type-hints a model's id as `int` (e.g. validation rule helpers, route bindings) must use `string` instead. Infrastructure tables (`jobs`, `failed_jobs`, `cache`, `migrations`, `sessions.id`) keep their default Laravel types; only foreign keys referencing an app model (e.g. `sessions.user_id`) become UUID.
- Private-method retention, extraction, inlining, removal, and placement rules are owned by `mem:architecture/private_methods`.
