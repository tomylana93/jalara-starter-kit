---
name: grill-me
description: Critique and harden a draft plan before emitting a developer handoff. Use only on explicit `$grill-me` invocation when the user asks to grill, stress-test, poke holes in, pressure-test, or challenge a plan, design, or approach before implementation, or asks for a handoff that survives scrutiny. Audits evidence, acceptance criteria, scope, unknowns, tests, verification gate, freshness, and implementor routing, then assembles the handoff.
---

# Grill Me

Interrogate the plan, not the developer. This skill is plan-only: it never edits
the repository, never writes Serena memory, and never treats the resulting
handoff as authorization to implement.

Activate only when invoked explicitly as `$grill-me`. Ordinary planning must not
pull this skill in.

## Ground First

- Re-read the current repository state for every file, symbol, route, and schema
  the draft plan names. A plan grounded in memory is ungrounded.
- Record the base commit, branch, and working-tree state now; the handoff's
  freshness check depends on them.
- Retrieve version-specific documentation for any framework-sensitive claim
  before accepting it.

## Evidence Grill

For each claim in the plan, ask and answer:

- Is this confirmed evidence or inference? Label inference as unverified.
- What file and symbol proves it? A claim without an anchor is not evidence.
- Does the cited anchor still exist and still say what the plan assumes?
- Is the stated root cause validated by documentation or a reproducing
  experiment, or merely plausible?

Drop or downgrade every claim that fails. Do not carry an unproven root cause
into the handoff as fact.

## Decision Grill

- List the decisions the plan silently made. Name the alternative each one beat
  and why.
- Investigate open questions yourself first: read the code, read the docs, run a
  read-only check.
- Escalate to the developer only when an unknown changes acceptance criteria, a
  public contract, an authorization or security surface, a dependency, or a
  destructive operation. Everything else becomes a stated assumption.
- Never ask a question whose answer is already in the repository.

## Scope Grill

- Confirm every planned edit traces to an acceptance criterion. Cut the rest.
- Name what the plan deliberately excludes, including adjacent work a reader
  would expect.
- Verify no step adds a dependency, widens a security surface, or performs a
  destructive operation. If one does, it goes back to the developer.

## Test Grill

- Every acceptance criterion needs a test that fails without the change.
- Require both a positive case and a negative or authorization case.
- Name the focused command that runs them.
- Confirm the verification gate matches the surfaces actually touched.

## Assemble the Handoff

Emit the handoff using `assets/handoff-template.md` verbatim in structure:
every section, in order, with its checkboxes and gates intact. Fill each field
from the grilled plan; do not leave placeholder text behind.

Close by asking the developer which implementor takes it: Claude Code, agy, or
Codex.

## Boundaries

- Stop at the handoff. Do not implement, stage, or commit.
- Higher-priority developer instructions always win over this skill.
- Do not grow the plan while grilling it; surface the growth as a question.
