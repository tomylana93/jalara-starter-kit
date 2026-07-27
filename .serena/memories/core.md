# Project Core

- Laravel Vue starter kit; server-driven Inertia SPA with authentication and account settings.
- Backend source map and request lifecycle: `mem:backend/core`.
- Frontend source map, layouts, UI, and route integration: `mem:frontend/core`.
- Agent guideline/skill sources, generated outputs, and Serena maintenance invariants: `mem:agent_context`.
- Installed languages/frameworks/toolchain: `mem:tech_stack`.
- Development/setup/check commands: `mem:suggested_commands`.
- Cross-codebase style and architecture constraints: `mem:conventions`.
- Required task verification sequence: `mem:task_completion`.
- Top-level paths: `app/` backend application code; `routes/` web/console routes; `database/` migrations/factories/seeders; `resources/js/` Inertia Vue application; `resources/css/app.css` Tailwind entrypoint; `tests/` Pest suites.
- Preserve the existing directory structure and reuse existing components/patterns; do not add dependencies or new base folders without user approval.
- Custom agent rules originate in `.ai/` and are published into agent-specific outputs by Laravel Boost; Laravel ecosystem code changes require version-specific Boost documentation lookup.