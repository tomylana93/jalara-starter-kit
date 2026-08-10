## HANDOFF

**Task:** <one sentence, imperative>
**Repository:** `<repo>`
**Branch:** `<branch>`
**Base commit:** `<full sha>`
**Working tree at analysis:** `clean` | `<list of dirty files>`

### Goals and acceptance criteria
- [ ] <observable, testable outcome>
- [ ] <behaviour that must not change>

### Verified context
- `<path>:<line>` / `<symbol or section>` - <what it proves and why it matters>
- <package version or documentation source> - <version-specific fact it establishes>

### Assumptions and unknowns
- Assumption: <stated assumption the implementor may rely on>
- Unverified: <inference not yet proven, and how it would be proven>

### Change plan
1. `<path>`
   - Symbol/area: `<Class::method or section>`   (symbol anchors, not line numbers)
   - Change: <what changes>
   - Reason: <why, tied to an acceptance criterion>
   - Contract to preserve: <what must not break>

### Out of scope
- <adjacent work deliberately excluded>
- Do not add dependencies.
- Do not touch unrelated dirty files.

### Tests
- Add/update: `<test file>`
- Positive case: <what proves the feature works>
- Negative/authorization case: <what proves the guard holds>
- Focused command: `<command>`

### Verification gate
- Source changed (PHP or frontend): `composer run fix`
- Agent infrastructure changed: `composer run agents:update`, then
  `composer run agents:check`, then `composer run agents:update` again with no
  further tracked diff
- Memory changed: `serena memories check`, and
  `serena memories check --include-unmarked --fuzzy-matching`
- Final: `composer run ci:check`

### Memory/context update
- Target: `mem:<name>` or `<path>`   (name it; do not write "if relevant")
- Invariant to record: <the rule, verbatim enough to write without re-deriving>
- Or state: "No new durable invariant; no memory update."

### Freshness check
- Compare HEAD against the base commit above.
- Compare the working tree against the snapshot above.
- Nonmaterial adaptation (variable names, line drift, small local pattern
  differences) is allowed and must be reported at the end.
- Material deviation (acceptance criteria, public contract, authorization or
  security surface, new dependency, destructive migration, scope growth) must
  go back to the developer before implementing.

### Completion criteria
- [ ] All acceptance criteria met
- [ ] Focused tests pass
- [ ] `composer run ci:check` passes
- [ ] Diff touches only the approved scope
- [ ] Named memory/context target updated after the green gate, or explicitly
      declared unnecessary
