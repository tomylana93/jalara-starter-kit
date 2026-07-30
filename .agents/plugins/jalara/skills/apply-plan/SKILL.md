---
name: apply-plan
description: Implement a Codex HANDOFF plan in jalara-starter-kit under an explicit, scoped authorization. Use when the developer pastes a "## HANDOFF" block and asks agy to implement it, or mentions apply-plan, applying a Codex plan, or executing a handoff.
---

# Apply Plan

Codex produced the plan; the developer reviewed it and chose agy as the
implementor. That choice authorizes **the scope described in the handoff, and
nothing else**.

If the pasted text is missing, truncated, or is not a `## HANDOFF` block, stop
and say so instead of guessing at the intent.

## Step 0 — Confirm the plan fits this implementor

Read `.agents/plugins/jalara/rules/implementor-scope.md`. If the plan is a new
feature, a migration, a security-surface change, a dependency change, or a
destructive operation, stop and recommend Claude Code instead. Being chosen does
not widen the scope.

## Step 1 — Freshness check, before editing anything

Run `git rev-parse HEAD`, `git status --short`, and `git branch --show-current`.
Compare against the handoff's base commit, working-tree snapshot, and branch.

- HEAD matches the base commit — proceed.
- HEAD moved, but no commit since the base touches the files, symbols, routes,
  schema, or contracts named in the plan — report the drift in one line and
  proceed.
- HEAD moved and a commit since the base changed a contract, schema, route, or a
  symbol the plan depends on — the plan is stale. Stop and report what changed.

For the working tree:

- Dirty files already recorded in the handoff snapshot are expected. Leave them.
- Dirty files that appeared since the snapshot are fine on their own — report
  them, do not revert them, do not stage them.
- If any dirty file overlaps a file or symbol the plan edits, stop and report.
  Do not merge your change into someone else's uncommitted work.

## Step 2 — Ground the plan in the current code

Use the plan's symbol anchors (`Class::method`) as edit targets, not its line
numbers; line numbers in a handoff are investigation evidence and drift. Locate
each target with Serena and read it before editing. Use Laravel Boost for
version-specific framework behaviour and Context7 for non-Laravel libraries.

## Step 3 — Classify every mismatch you hit

**Nonmaterial — adapt, keep going, report at the end.** Naming, line drift, a
slightly different local pattern, a moved file, an import path, test placement
within the existing convention.

**Material — stop and report before implementing.** Acceptance criteria cannot
be met as written; a public contract, route signature, or schema differs from
what the plan assumed; authorization or security behaviour is affected; a new
dependency or destructive migration would be needed; the change spreads to files
the plan did not list; or the plan's stated root cause turns out to be wrong.

When in doubt, it is material. A wrong stop costs one message; a wrong
adaptation ships.

## Step 4 — Implement and test

Make the smallest coherent change satisfying the acceptance criteria. Honour the
plan's "Di luar scope" list literally. Write or update the tests the handoff
names, covering both the positive and the negative/authorization case, using
existing factories and their states. Run the focused command first:

```
php artisan test --compact --filter=...
```

## Step 5 — Verification gate

1. PHP changed: `composer run rector`, then `composer run format:agent`
2. Frontend changed: `pnpm run lint`, then `pnpm run format`
3. Always: `composer run ci:check`

Do not report success on a red gate. Paste the failing output instead.

## Step 6 — Report back

- Acceptance criteria: met / not met, each one.
- Nonmaterial adaptations made, and why.
- Anything deliberately left out, and why.
- Focused test result and `ci:check` result.
- Files touched — confirm the diff stayed inside the approved scope.

## Step 7 — Memory

Only after a green gate, and only if the work revealed a durable, non-obvious
project invariant. Read `mem:memory_maintenance` first. Do not record
task-local notes or anything already visible in the code or git history.
