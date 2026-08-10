---
name: grill-me
description: Interview the developer relentlessly about a draft plan, one question at a time, until every branch of the decision tree is resolved, then emit the developer handoff. Use only on explicit `$grill-me` invocation when the user asks to grill, stress-test, poke holes in, pressure-test, or challenge a plan, design, or approach before implementation, or asks for a handoff that survives scrutiny. Interrogates evidence, decisions, scope, unknowns, tests, verification gate, freshness, and implementor routing, then assembles the handoff.
---

# Grill Me

Interview the developer relentlessly about every aspect of the plan until you
reach shared understanding, then emit the handoff. This skill is plan-only: it
never edits the repository, never writes Serena memory, and never treats the
resulting handoff as authorization to implement.

Activate only when invoked explicitly as `$grill-me`. Ordinary planning must not
pull this skill in.

## Ground First

- Re-read the current repository state for every file, symbol, route, and schema
  the draft plan names. A plan grounded in memory is ungrounded.
- Record the base commit, branch, and working-tree state now; the handoff's
  freshness check depends on them.
- Retrieve version-specific documentation for any framework-sensitive claim
  before accepting it.

## Interview Loop

Walk down each branch of the decision tree, resolving dependencies between
decisions one by one. For every branch:

- Ask **one question at a time** and wait for the answer. Never batch questions
  or emit a numbered list of open items in place of an interview.
- Give your recommended answer with each question, and say why it wins over the
  alternative you considered.
- If a question can be answered by exploring the codebase or the documentation,
  explore instead of asking.
  Never ask a question whose answer is already in the repository.
- Let the answer reshape the tree: a resolved decision may open, close, or
  reorder the branches below it. Re-derive the next question from the plan as it
  now stands.
- Keep going until every branch is resolved or the developer stops the
  interview. Do not short-circuit to the handoff while a material branch is open.

The grills below are the source of the questions. Work them in order; each one
either resolves against the repository or becomes the next interview question.

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
- Bring the survivors to the developer through the interview loop, one at a
  time, recommendation first.
- Always interview, never assume, when an unknown changes acceptance criteria, a
  public contract, an authorization or security surface, a dependency, or a
  destructive operation.
- Record an unknown as a stated assumption only after the developer has declined
  to decide it or confirmed the recommendation.

## Scope Grill

- Confirm every planned edit traces to an acceptance criterion. Cut the rest.
- Name what the plan deliberately excludes, including adjacent work a reader
  would expect. Ask whether each exclusion is intended.
- Verify no step adds a dependency, widens a security surface, or performs a
  destructive operation. If one does, it goes back to the developer.

## Test Grill

- Every acceptance criterion needs a test that fails without the change.
- Require both a positive case and a negative or authorization case.
- Name the focused command that runs them.
- Confirm the verification gate matches the surfaces actually touched.

## Assemble the Handoff

Only after the interview is done, emit the handoff using
`assets/handoff-template.md` verbatim in structure: every section, in order,
with its checkboxes and gates intact. Fill each field from the grilled plan; do
not leave placeholder text behind. Every answer the developer gave in the
interview must be reflected in the handoff, not silently dropped.

Close by asking the developer which of the two implementors takes it: Claude
Code (the default) or Codex (the only fallback).

## Boundaries

- Stop at the handoff. Do not implement, stage, or commit.
- Higher-priority developer instructions always win over this skill.
- Do not grow the plan while grilling it; surface the growth as a question.
