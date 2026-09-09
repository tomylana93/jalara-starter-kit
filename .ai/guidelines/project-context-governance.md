# Project Context Governance

This project uses two complementary knowledge systems:

- **Boost Project Rules** (`.ai/rules`) for durable project-specific constraints, conventions, and decisions that **must be followed**.
- **Serena Memory** for durable repository knowledge that helps agents **understand the project**, such as architecture, domains, workflows, relationships, and non-obvious implementation context.

## Before Working

- Read `.ai/rules/index.md` and all rules relevant to files in scope.
- Use Serena when broader repository, architecture, domain, legacy, or historical context is needed.
- When Serena context is needed, read `mem:core` first and then only relevant referenced memories.
- Do not load all memories unnecessarily.

## Knowledge Placement

Use:

- **Must be followed** → Boost Project Rule
- **Useful to understand** → Serena Memory
- **Temporary, obvious, generic, task-specific, or speculative** → Store nowhere

Do not duplicate the same knowledge across both systems.

## Maintaining Knowledge

Create or update a **Project Rule** only for verified, durable project conventions or decisions.

Create or update **Serena Memory** only for verified, stable, non-obvious, reusable repository knowledge that would otherwise be costly to rediscover.

Do not update either system merely because code changed.

After substantial work, update project knowledge only when a durable rule or understanding has actually changed.

## Conflict Resolution

Priority:

1. Explicit user instruction
2. Boost Project Rules
3. Verified current source code, configuration, and tests
4. Serena Memory
5. Inferred patterns

If source code conflicts with a Project Rule, investigate before assuming either is outdated.

If Serena Memory conflicts with verified current implementation, update the stale memory.

References to avoiding "native memory" in Boost rules do not apply to Serena project memories. Do not convert descriptive Serena knowledge into Boost Project Rules solely to persist it.