# Agent Context Infrastructure

- Custom Boost source-of-truth: `.ai/guidelines/` for always-loaded rules; `.ai/skills/` for on-demand procedures.
- Generated, tracked outputs: `AGENTS.md`, `CLAUDE.md`, `.agents/skills/`, and `.claude/skills/`. Regenerate with `php artisan boost:update --no-interaction`; do not maintain custom changes only in generated files.
- `boost.json` selects supported agents and Boost-provided resources. Non-Boost MCP client configuration remains client-specific and must not be assumed identical across agents.
- Serena project bootstrap/configuration: `.serena/project.yml`; durable project memory graph: `.serena/memories/`.
- Skill availability is dynamic. Discover current skill metadata at runtime; never persist an installed-skill enumeration in guidelines or memories.
- Agent-context validation: validate custom skill structure, run Boost update twice for idempotence, run Serena reference checks after memory changes, then run `git diff --check`.