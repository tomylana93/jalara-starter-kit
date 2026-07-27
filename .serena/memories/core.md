# Project Core

- Laravel Vue starter kit; server-driven Inertia SPA with authentication and account settings.
- Backend source map and request lifecycle: `mem:backend/core`.
- Frontend source map, layouts, UI, and route integration: `mem:frontend/core`.
- Installed languages/frameworks/toolchain: `mem:tech_stack`.
- Development/setup/check commands: `mem:suggested_commands`.
- Cross-codebase style and architecture constraints: `mem:conventions`.
- Required task verification sequence: `mem:task_completion`.
- Top-level paths: `app/` backend application code; `routes/` web/console routes; `database/` migrations/factories/seeders; `resources/js/` Inertia Vue application; `resources/css/app.css` Tailwind entrypoint; `tests/` Pest suites.
- Preserve the existing directory structure and reuse existing components/patterns; do not add dependencies or new base folders without user approval.
- Project-specific agent rules are supplied in AGENTS.md/context; Laravel Boost version-specific documentation lookup is required before code changes.