# Agent Workflow

## Production posture

- Treat every change as production-affecting. Make the smallest reversible change, preserve unrelated work, and report only verification actually run.
- Scope is authorization. Dependencies, security-sensitive surfaces, shipped migrations, CI/release configuration, destructive operations, history rewrites, and scope growth require explicit developer approval.
- A red required gate blocks completion. Fix it within scope or stop when the fix requires unavailable authority.

## Bootstrap and routing

- Start with application information when available. Before coding, activate Serena, read its instructions, then read `mem:core` and only the focused memories it routes to. Inspect the working tree before editing.
- Claude Code and Codex are the repository's only supported agents. They are peers: either may investigate, plan when useful, and implement an authorized request directly. Do not add another agent or a planner/implementor tier.
- Use Serena for code navigation, references, diagnostics, precise edits, and project memory. For Vue orientation, read the component directly; its TypeScript gate is authoritative over Serena diagnostics.
- Use Laravel Boost for installed-version Laravel context, framework docs, schema, read-only data, URLs, and recent logs. Use Context7 for current non-Laravel library docs, then web search only when those tools are unhelpful.
- Use repository Composer scripts when one exists. Read `.ai/rules/index.md` and every matching Project Rule before editing an affected path.
- Discover skills dynamically, activate only matching skills, and read each selected `SKILL.md` through EOF before acting.

## Task and verification loop

1. Ground the request in repository state, focused context, nearby patterns, and version-specific documentation.
2. Separate confirmed evidence from inference. Validate a proposed root cause with authoritative documentation or a reproducing experiment.
3. Make the smallest coherent change and add or update a programmatic test.
4. Run focused tests, then the checks required by the affected surface.
5. Review the complete diff and classify any durable finding exactly once.

- PHP or frontend source changed: run `composer run fix` before the gate.
- Agent infrastructure changed: run `composer run agents:update` twice and confirm the second run leaves no further tracked diff.
- CI, release, coverage, installer, or Playwright configuration changed: also run `composer run ci:full`.
- Always finish with `composer run ci:check`.

## Durable knowledge

Classify before writing; one finding has one owner:

| Finding | Canonical destination |
| --- | --- |
| Always-on workflow or verification rule | `.ai/guidelines/` |
| Trigger-scoped procedure | `.ai/skills/` |
| Stable constraint selected by file glob | `.ai/rules/` via `record-rule` |
| Orientation map or invariant needed before paths are known | Serena memory |
| Code-visible, tooling-owned, generic, volatile, or task-local | Nowhere |

- More-specific ownership wins. Never mirror a finding between stores.
- `.ai/rules/boost/` is regenerated; never edit it or record a custom rule in it. Use `record-rule` for custom Project Rules.
- Before a memory write, read `mem:memory_maintenance`. Keep `mem:core` as the graph root and run `serena memories check`; during maintenance also run the unmarked and fuzzy audit.
