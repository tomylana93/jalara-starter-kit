---
description: Implement a Codex HANDOFF plan under an explicit, scoped authorization
argument-hint: paste the full "## HANDOFF -> Claude" block
---

You are the implementor. Codex produced the plan below; the developer reviewed it and
is now authorizing implementation by running this command.

## Handoff

$ARGUMENTS

## What running this command authorizes

Invoking `/apply-plan` means the developer approves implementing **the scope described
in this handoff, and nothing else**. It is specifically NOT approval to:

- add, remove, or upgrade any dependency;
- run destructive operations (`migrate:fresh`, `db:wipe`, dropping columns or tables,
  force-pushing, rewriting history, deleting tests);
- modify files outside the plan's scope, including unrelated dirty files;
- widen the plan when the code turns out to be more tangled than Codex assumed.

Any of those requires going back to the developer first.

If the pasted text is missing, truncated, or is not a HANDOFF block, stop and say so
instead of guessing at the intent.

## Step 1 — Freshness check (before editing anything)

Run `git rev-parse HEAD`, `git status --short`, and `git branch --show-current`.
Compare against the handoff's base commit, working-tree snapshot, and branch.

Resolve as follows:

- **HEAD matches the base commit** — proceed.
- **HEAD moved, but no commit since the base touches the files, symbols, routes,
  schema, or contracts named in the plan** — report the drift in one line and proceed.
- **HEAD moved and a commit since the base changed a contract, schema, route, or a
  symbol the plan depends on** — the plan is stale. Stop and report what changed.

For the working tree:

- Dirty files already recorded in the handoff snapshot are expected. Leave them alone.
- Dirty files that appeared since the snapshot are also fine on their own — report them,
  do not revert them, do not stage them.
- **If any dirty file overlaps a file or symbol the plan edits, stop and report.** Do not
  merge your change into someone else's uncommitted work.

Never run `git checkout --`, `git restore`, `git stash`, or `git clean` to get a clean
tree. Preserving unrelated user changes outranks a tidy diff.

## Step 2 — Ground the plan in the current code

Use the plan's symbol anchors (`Class::method`), not its line numbers, as edit targets;
line numbers in the handoff are investigation evidence and drift. Locate each target with
Serena (`find_symbol`, `find_referencing_symbols`) and read it before editing.

Follow the repo's normal routing: Serena for navigation and precise edits, Boost
`search-docs` for version-specific framework behaviour, Context7 for non-Laravel
libraries. Activate the skills the affected domain calls for.

## Step 3 — Classify every mismatch you hit

The plan was written from a read-only investigation. Where it does not match reality:

**Nonmaterial — adapt, keep going, report at the end.** Variable or helper naming, line
drift, a slightly different local pattern, a file that moved, an import path, test
placement within the existing convention.

**Material — stop and report before implementing.** Acceptance criteria cannot be met as
written; a public contract, route signature, or schema differs from what the plan assumed;
authorization or security behaviour is affected; a new dependency would be needed; a
destructive migration would be needed; the change spreads to files the plan did not list;
or the plan's stated root cause turns out to be wrong.

When in doubt, it is material. A wrong stop costs one message; a wrong adaptation ships.

## Step 4 — Implement

Make the smallest coherent change that satisfies the acceptance criteria, following
existing project patterns and reusing existing components. Honour the plan's "Out of
scope" list literally.

## Step 5 — Tests

Write or update the tests named in the handoff, covering both the positive case and the
negative/authorization case. Use factories and their existing states. Run the focused
command first:

```
php artisan test --compact --filter=...
```

## Step 6 — Verification gate

In order, and only for the surfaces you touched:

1. Source changed (PHP or frontend): `composer run fix`
2. Agent infrastructure changed: `composer run agents:update`, then
   `composer run agents:check`, then `composer run agents:update` again with no
   further tracked diff
3. Memory changed: `serena memories check`, and
   `serena memories check --include-unmarked --fuzzy-matching`
4. Always: `composer run ci:check`

If `ci:check` surfaces a failure outside the plan's scope, treat it as required follow-up:
isolate the cause, make the smallest safe fix, preserve unrelated changes, rerun. Stop only
if the fix would need approval, a destructive action, or authority you do not have — the
scope limits above still apply.

Do not report success on a red gate. Paste the failing output instead.

## Step 7 — Report back

- Acceptance criteria: met / not met, each one.
- Nonmaterial adaptations made, and why.
- Anything deliberately left out, and why.
- Focused test result and `ci:check` result.
- Files touched — confirm the diff stayed inside the approved scope.

## Step 8 — Memory

Only after a green gate, and only if the work revealed a durable, non-obvious project
invariant. Read `mem:memory_maintenance` first. Do not record task-local notes, or
anything already visible in the code or git history.
