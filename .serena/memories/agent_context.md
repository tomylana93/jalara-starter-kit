# Agent Context Infrastructure

- Custom Boost source-of-truth: `.ai/guidelines/` for always-loaded rules; `.ai/skills/` for on-demand procedures.
- Generated, tracked outputs: `AGENTS.md`, `CLAUDE.md`, `.agents/skills/`, and `.claude/skills/`. Regenerate with `composer run agents:update`; do not maintain custom changes only in generated files.
- `boost.json` selects supported agents and Boost-provided resources. Non-Boost MCP client configuration remains client-specific and must not be assumed identical across agents.
- Serena project bootstrap/configuration: `.serena/project.yml`; durable project memory graph: `.serena/memories/`.
- Composer scripts are the repository-owned entry points for dependency-backed development and agent-publication tools. Prefer them over direct `vendor/bin/*`, Artisan, or package-manager calls whenever a matching script exists; Serena memory operations remain Serena-native.
- Skill availability is dynamic. Discover current skill metadata at runtime; never persist an installed-skill enumeration in guidelines or memories.
- Agent-context validation: validate custom skill structure, run Boost update twice for idempotence, run Serena reference checks after memory changes, then run `git diff --check`.