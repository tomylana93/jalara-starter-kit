---
name: maintaining-agent-context
description: Maintain repository agent infrastructure, including always-on guidelines, on-demand skills, MCP routing rules, Laravel Boost publication, and Serena project memories. Use when onboarding an agent, auditing or changing AGENTS.md/CLAUDE.md generation, editing `.ai/`, adding/updating/removing skills, changing MCP instructions or configuration, reorganizing Serena memories, repairing memory references, or refreshing Boost resources.
---

# Maintaining Agent Context

Keep agent context portable, concise, dynamically discoverable, and safe to
regenerate.

## Establish the Source of Truth

- Inspect the working tree, `boost.json`, `.ai/`, `.serena/`, and the generated
  agent outputs before editing.
- Treat `.ai/guidelines/` and `.ai/skills/` as the custom Boost sources.
  Treat `AGENTS.md`, `CLAUDE.md`, `.agents/skills/`, and `.claude/skills/` as
  generated outputs.
- Change generated outputs only through
  `php artisan boost:update --no-interaction`; do not patch them independently.
- Keep MCP client configuration outside Boost publication. Change it only when
  the user explicitly includes that client and server in scope.

## Maintain Guidelines and Skills

- Put short, broad, always-applicable rules in a guideline.
- Put focused procedures that should load on demand in a skill.
- Discover skills from the current runtime catalog and filesystem. Never copy a
  list of installed skill names into static instructions.
- Give every skill a lowercase hyphenated directory name and a `SKILL.md` whose
  frontmatter contains only `name` and a precise trigger-rich `description`.
- Keep the body imperative and concise. Add scripts, references, or assets only
  when they remove repeated work or large optional context.
- Validate custom skills before publication and inspect trigger overlap so a
  routine task activates only the minimum relevant set.

## Maintain Serena Memory

1. Activate the project and read Serena's instructions.
2. Read `mem:memory_maintenance`, then `mem:core` and the focused memories
   affected by the change.
3. Write only stable, non-obvious knowledge that avoids meaningful future
   rediscovery. Exclude secrets, logs, volatile state, obvious file contents,
   and task-local history.
4. Keep `mem:core` as the graph root. Store focused topics separately and use
   marked `mem:` references with clear routing descriptions.
5. Use Serena memory tools for writes and reference-aware maintenance. Rename
   or delete memory only with explicit user authorization.
6. Run `serena memories check` after writes. During an audit, also run
   `serena memories check --include-unmarked --fuzzy-matching` and review every
   reported candidate.

## Publish and Verify

1. Run `php artisan boost:update --no-interaction`.
2. Confirm the custom guideline and skills appear in every configured agent
   output without manually enumerating unrelated installed skills.
3. Run the update again and confirm it produces no further tracked diff.
4. Run the Serena memory checks when memories changed.
5. Run `git diff --check`, review the complete diff, and report exactly which
   validation commands ran.
