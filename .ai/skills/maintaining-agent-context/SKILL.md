---
name: maintaining-agent-context
description: Maintain repository agent infrastructure, including always-on guidelines, on-demand skills, MCP routing rules, Laravel Boost publication, and Serena project memories. Use when onboarding an agent, changing AGENTS.md/CLAUDE.md generation, editing `.ai/`, adding/updating/removing skills, changing MCP instructions or configuration, reorganizing Serena memories, repairing memory references, or refreshing Boost resources.
---

# Maintaining Agent Context

Keep agent context portable, concise, dynamically discoverable, and safe to
regenerate.

## Establish the Source of Truth

- Inspect the working tree, `boost.json`, `.ai/`, `.serena/`, and the generated
  agent outputs before editing.
- Keep `boost.json` aligned with the AI coding agents the repository currently
  supports. Add or remove a client only when the developer explicitly includes
  that agent in scope.
- Treat `.ai/guidelines/` and `.ai/skills/` as the custom Boost sources, and
  `AGENTS.md`, `CLAUDE.md`, plus their published skills as generated outputs.
  Third-party project skills are installer-owned instead: canonical files live
  in `.agents/skills/`, Claude Code receives `.claude/skills/` symlinks, and
  `skills-lock.json` owns their provenance. Publication must preserve them.
- Treat `.ai/rules/` as a third, tool-owned store: committed, path-scoped rules
  written only through the `record-rule` MCP tool, which owns file placement,
  frontmatter, and `index.md`. Recorded rules are neither a Boost source you
  hand-author nor a generated output you regenerate, so never edit them by hand.
- `.ai/rules/boost/` is the one part of that store publication owns. Because
  `composer run agents:update` sets `BOOST_RULES_SCOPED_GUIDELINES=true`, Boost
  extracts path-scoped package guidance into that managed subtree and rewrites
  `.ai/rules/index.md` to list it, instead of duplicating it inline in the
  generated agent files. Regenerate it, never hand-edit it, and never record a
  custom rule inside it.
- Boost exposes no update or delete operation for a recorded rule. Removing or
  rewriting one is therefore a direct-edit exception that requires explicit
  per-change developer approval, and it must end by regenerating `index.md`
  through `RuleRepository::writeIndex` rather than by hand. An index reading
  `No rules recorded yet.` is valid.
- Change generated outputs only through
  `composer run agents:update`; do not patch them independently.
- Invoke dependency-backed repository tooling through Composer scripts. Add or
  update the Composer entry point before documenting a direct vendor binary or
  Artisan command; keep Serena memory operations Serena-native.
- Keep MCP client configuration outside Boost publication. Change it only when
  the user explicitly includes that client and server in scope.

## Maintain Guidelines and Skills

- Put short, broad, always-applicable rules in a guideline.
- Put focused procedures that should load on demand in a skill.
- Discover skills from the current runtime catalog and filesystem. Never copy a
  list of installed skill names into static instructions.
- Select a skill only when its trigger metadata actually matches the task; do
  not substitute the closest available skill for a missing capability.
- Give every custom skill a lowercase hyphenated directory name and a `SKILL.md` whose
  frontmatter contains only `name` and a precise trigger-rich `description`.
- Install third-party project skills for exactly `claude-code` and `codex`, not
  an all-agent wildcard. Let the installer manage `skills-lock.json` and agent
  symlinks; do not copy or patch those assets by hand.
- Read a selected `SKILL.md` through EOF. Continue after truncated or paginated
  output; never treat a fixed line range as a complete read.
- Keep the body imperative and concise. Add scripts, references, or assets only
  when they remove repeated work or large optional context.
- Validate custom skills before publication and inspect trigger overlap so a
  routine task activates only the minimum relevant set.

## Place Durable Knowledge

- Classify every candidate against the placement matrix in the agent-workflow
  guideline before recording it anywhere. Name the single destination and the
  reason it is not one of the others.
- Reject a candidate that is already stated in a guideline, already enforced by
  configured tooling, visible in the code, generic framework knowledge, or
  task-local. "Nowhere" is a valid outcome.
- Never bulk-migrate between stores. Audit read-only first, report candidates
  grouped as keep, migrate, relocate, remove, or conflict, and let the developer
  approve them individually.
- Per approved candidate, follow audit, confirm, record, remove in that order:
  record the destination entry first, verify it is discoverable through its own
  retrieval path, then remove the source entry. Never leave the same knowledge
  live in two stores between steps, and never remove a source before its
  replacement exists.

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

1. Run `composer run agents:update`.
2. Confirm the custom guideline and skills appear in both configured agent
   outputs without manually enumerating unrelated installed skills.
3. Run the update again and confirm it produces no further tracked diff.
4. Confirm `boost.json` selects the intended agents and inspect generated output
   for stale client-specific assumptions.
5. Run the Serena memory checks when memories changed.
6. Run `git diff --check`, review the complete diff, and report exactly which
   validation commands ran.
