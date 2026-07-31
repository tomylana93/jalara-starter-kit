## HANDOFF

**Task:** <one sentence, imperative, in the developer's language>
**Repository:** `<repo>`
**Branch:** `<branch>`
**Base commit:** `<full sha>`
**Working tree at analysis:** `clean` | `<list of dirty files>`

### Tujuan dan kriteria penerimaan
- [ ] <observable, testable outcome>
- [ ] <one line per criterion; no implementation detail>

### Konteks terverifikasi
- `<path>:<line>` / `<symbol or section>` - <what it proves>
- <external documentation source> - <version-specific fact it establishes>

### Asumsi dan unknowns
- Assumption: <stated assumption the implementor may rely on>
- Unverified: <inference not yet proven, and how it would be proven>

### Rencana perubahan
1. `<path>`
   - Symbol/area: `<Class::method or section>`
   - Change: <what changes>
   - Reason: <why, tied to an acceptance criterion>
   - Contract to preserve: <what must not break>

### Di luar scope
- <adjacent work deliberately excluded>
- Do not add dependencies.
- Do not touch unrelated dirty files.

### Tes
- Add/update: `<test file>`
- Positive case: <what proves the feature works>
- Negative/authorization case: <what proves the guard holds>
- Focused command: `<command>`

### Verification gate
- PHP changed: `composer run rector`, then `composer run format:agent`
- Frontend changed: `pnpm run lint`, then `pnpm run format`
- Final: `composer run ci:check`

### Freshness check
- Compare HEAD against the base commit above.
- Compare the working tree against the snapshot above.
- Nonmaterial adaptation (variable names, line drift, small local pattern
  differences) is allowed and must be reported at the end.
- Material deviation (acceptance criteria, public contract, authorization or
  security surface, new dependency, destructive migration, scope growth) must
  go back to the developer before implementing.

### Kriteria selesai
- [ ] All acceptance criteria met
- [ ] Focused tests pass
- [ ] `composer run ci:check` passes
- [ ] Diff touches only the approved scope
- [ ] Memory updated only after a green gate, and only for new invariants
