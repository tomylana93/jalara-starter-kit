# Project Core

- Laravel Vue starter kit; server-driven Inertia SPA with authentication and account settings.
- Backend source map and request lifecycle: `mem:backend/core`.
- Frontend source map, layouts, UI, and route integration: `mem:frontend/core`.
- Agent guideline/skill sources, generated outputs, and Serena maintenance invariants: `mem:agent_context`.
- Installed languages/frameworks/toolchain: `mem:tech_stack`.
- Development/setup/check commands: `mem:suggested_commands`.
- Cross-codebase style and architecture constraints: `mem:conventions`.
- Locale layout and the natural Indonesian translation voice/technical-term policy: `mem:localization`.
- Stable test boundaries and domain-oriented suite organization: `mem:testing`.
- Internal documentation data model, authorization, editor, search, and navigation invariants: `mem:documentation`.
- Scheduled backup configuration, single-flight run lock, queue-connection and archive-addressing invariants: `mem:backend/backups`.
- Required task verification sequence: `mem:task_completion`.
- Commit-message, pull-request, application-version, and release automation conventions: `mem:release_process`.
- Top-level paths: `app/` backend application code; `routes/` web/console routes; `database/` migrations/factories/seeders; `resources/js/` Inertia Vue application; `resources/css/app.css` Tailwind entrypoint; `tests/` Pest suites.
- This memory is the graph root and top-level source map only. Always-on workflow lives in `.ai/guidelines/`, path-selectable constraints in `.ai/rules/` (found via `.ai/rules/index.md`), and focused procedures in `.ai/skills/`; do not restate any of them here.