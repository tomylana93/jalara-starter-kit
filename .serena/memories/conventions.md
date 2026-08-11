# Conventions

Routing anchor only; this memory holds no local rules of its own.

- Always-on Laravel, PHP, Vue, Inertia, Pint, Wayfinder, and test policies are published into `AGENTS.md`/`CLAUDE.md` by Laravel Boost from `.ai/guidelines/` and the package guideline set. Read them there; they are already in context on every task.
- Constraints that only bind once a specific path is in scope live in `.ai/rules/`. Match the file you are about to touch against `.ai/rules/index.md`, including the Boost-managed `.ai/rules/boost/` rows.
- Architectural placement of behavior across Actions, Data objects, Support, presenters, and models: `mem:backend/core`.
- Frontend component, layout, and route-integration structure: `mem:frontend/core`.
