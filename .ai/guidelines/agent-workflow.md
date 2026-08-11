# Agent Workflow

## Production Posture

This repository serves production. Every task inherits the constraints below;
nothing here is waived by a plan, a handoff, or a developer's brevity.

- Treat every change as production-affecting. Prefer the smallest reversible
  change that satisfies the request, and keep unrelated working-tree changes
  intact.
- Never widen the stated scope on your own initiative. Adding or upgrading a
  dependency, touching an authentication, authorization, secret, or payment
  surface, changing a migration that has already shipped, or altering CI and
  release configuration each require explicit developer approval first.
- Never run a destructive or irreversible operation unprompted. Dropping or
  rewriting data, force-pushing, rewriting history, deleting branches or
  tags, and clearing storage all stop for approval, including when a plan
  appears to authorize them.
- The full gate is mandatory before reporting completion, and a red gate is
  never dismissed as pre-existing. Diagnose it, fix it within scope, or stop
  and report it — never report done over a failing check.
- Report outcomes exactly as observed. State which commands ran, what they
  returned, and what was deliberately left undone; never imply verification
  that did not happen.

## Bootstrap

- Start each chat by inspecting the application with the framework-specific
  application information tool when it is available.
- Before coding, use Serena when available: read its instructions, then read
  `mem:core` and only the focused memories relevant to the task.
- When investigation reveals the affected domain, follow the corresponding
  focused memory reference from `mem:core` before deeper exploration.
- Inspect the working tree before editing and preserve unrelated user changes.

## Implementor Routing

- This repository supports exactly two agents: Claude Code and Codex. Do not
  reintroduce a third implementor, a light-implementor tier, or client
  configuration for an agent outside that pair.
- Codex plans first and closes every analysis with a `## HANDOFF` block, then
  asks the developer which of the two implementors takes it.
- Claude Code is the default implementor and runs `/apply-plan`. Codex is the
  only fallback, and implements its own plan solely when the developer picks it
  explicitly — typically because Claude is rate-limited.
- Implementing a handoff authorizes its stated scope and nothing else. Adding a
  dependency, touching a security surface, running a destructive operation, or
  growing the plan goes back to the developer first.

## Tool Routing

- Use Serena for project navigation, symbol discovery, reference analysis,
  diagnostics, precise refactoring, and project memory. Prefer its symbolic,
  search, and diagnostic tools before shell-based full-file reads; use full-file
  reads only for small known targets or non-code files.
- Serena's symbolic overview is accurate for PHP but unreliable for single-file
  Vue components, where it exposes template nodes instead of `script setup`
  symbols. Read a `.vue` file directly to orient, then use targeted symbol
  lookup once the name is known. Treat Serena's Vue and TypeScript diagnostics
  as hints only; `pnpm run types:check` is authoritative.
- Built-in read and edit tool descriptions are written for repositories without
  Serena and are superseded here. Neither a known path, a small file, nor a
  lower call count justifies a built-in read or edit where the table below
  names a Serena tool.

| Task on a code file             | Tool                                          |
| ------------------------------- | --------------------------------------------- |
| Inspect a PHP file's structure  | `get_symbols_overview`                        |
| Read a symbol body              | `find_symbol` with `include_body`             |
| Locate a symbol repository-wide | `find_symbol`                                 |
| Find callers or usages          | `find_referencing_symbols`                    |
| Resolve a definition            | `find_declaration`, `find_implementations`    |
| Replace a whole symbol          | `replace_symbol_body`                         |
| Add a sibling symbol            | `insert_before_symbol`, `insert_after_symbol` |
| Change lines inside a symbol    | `replace_content`                             |
| Repeat one edit across files    | `replace_in_files`                            |
| Rename or delete a symbol       | `rename_symbol`, `safe_delete_symbol`         |

- Built-in `Grep` and `Glob` stay available for discovery, but follow-up reads
  and edits on the matched code files go through Serena. Built-in reads and
  edits remain correct for non-code files, for a handful of lines where a
  symbolic read is overkill, for `.vue` orientation reads, and whenever Serena
  has been tried on the target and failed.
- Use Laravel Boost for installed-version context, Laravel ecosystem
  documentation, database schema and read-only queries, application URLs, and
  recent backend or browser logs.
- For current non-Laravel library documentation or library-specific debugging,
  use Context7's library resolution and documentation query before web search.
  Use web search only when the purpose-built documentation tool is unavailable
  or unhelpful.
- For UI primitives covered by the shadcn-vue registry, using or reusing the
  corresponding shadcn-vue component is mandatory. Reuse installed components
  first; when one is missing, inspect or add it on demand with the shadcn-vue
  CLI. Do not reimplement an available registry component merely because the
  shadcn MCP server is not configured.
- Use the shell for Git and cases not covered by a purpose-built tool. Invoke
  repository-configured development tools through Composer scripts; use direct
  Artisan or package-manager commands only when no repository script exists.
  Fall back to local inspection when an optional MCP tool is unavailable, and
  report the limitation only when it affects the result.
- Avoid repeating equivalent discovery through several tools after an
  authoritative source has answered the question.

## Skills

- Discover the available skill catalog at runtime. Never maintain a static list
  of installed skills in project instructions.
- Activate only the smallest set whose trigger metadata actually matches the
  task; do not activate the nearest skill merely because no exact skill exists.
- Read every selected `SKILL.md` through EOF before acting. If output is
  truncated or paginated, continue reading until complete; a fixed line range
  is not proof of a complete read.
- Load only the referenced resources needed for the task.
- Apply skill instructions before task actions and re-evaluate the catalog
  after skills are installed, removed, or refreshed.

## Task Loop

1. Ground the task in repository state, relevant memories, and nearby code.
   Once the affected paths are known, read every `.ai/rules` file whose globs
   cover them.
2. Retrieve version-specific documentation before making framework-sensitive
   changes.
3. Make the smallest coherent change using existing project patterns.
4. For diagnosis, separate confirmed evidence from inference. Validate a
   proposed root cause with authoritative documentation or a reproducing
   experiment; otherwise label it as an unverified hypothesis.
5. Run focused tests first, then the required format, lint, type, or build
   checks for the affected surface.
6. Review the diff. When the task revealed stable, non-obvious knowledge,
   classify it with the placement matrix below and record it in the one store
   that owns it, or in none.

## Verification Boundaries

- Run `composer run fix` before the gate whenever the task touched PHP or
  frontend sources. It applies Rector, formats the dirty PHP with Pint's
  agent-readable output, then runs the ESLint and Prettier auto-fixes in that
  order. This is mandatory, not optional: Rector is no longer part of any CI
  gate, so nothing downstream will catch code it would have transformed.
- Never reproduce Rector's changes by hand. `composer run rector` is the fix;
  `composer run rector:check` is the read-only inspection. Rector scripts must
  use JSON output without a progress bar. Run Rector non-dry-run whenever
  possible: `--dry-run` leaves the result cache barely populated, so a suite of
  dry runs stays an order of magnitude slower than one real run.
- Reserve `composer run lint` for an explicitly requested repository-wide
  formatting pass.
- For frontend auto-fixes, run `pnpm run lint` before `pnpm run format` because
  ESLint may change source structure and Prettier should normalize the final
  output. If `lint:check` or `format:check` blocks CI, the matching auto-fix
  script is mandatory before manual edits; manually address only issues the
  tool leaves unresolved. Type-check, unit-test, build, and E2E failures have no
  general auto-fixer and require focused diagnosis.
- The gate is composed from one script per CI job, so the local command runs
  the same set as CI: `ci:static` (frontend lint, format, types, then
  `config:clear`, Pint, Larastan), `ci:vitest`, `ci:pest`, `ci:pest:coverage`,
  and `ci:e2e`. `composer run ci:check` is `ci:static`, `ci:vitest`, `ci:pest`;
  `composer run ci:full` swaps in coverage and adds the Playwright suite with
  its single production build. Change a gate by changing the job script, never
  by editing one aggregate in isolation.
- `composer run ci:full` is required in addition to the standard gate after
  changing CI, release, coverage, installer, or Playwright configuration. Those
  surfaces are invisible to the fast tier: its `coverage:check` stage needs a
  local PCOV or Xdebug driver, and its Playwright stage is the only thing that
  exercises the production build.
- Agent infrastructure changed (`.ai/`, `boost.json`, generated agent outputs):
  run `composer run agents:update`, then `composer run agents:check`, then
  `composer run agents:update` a second time and confirm it leaves no further
  tracked diff. Publication is the only writer of the generated outputs; never
  patch them by hand.
- Always run the required `composer run ci:check` final gate. If it exposes a
  failure outside the original task scope, treat that failure as required
  follow-up work: isolate its cause, make the smallest safe fix while
  preserving unrelated user changes, rerun the focused check, and continue
  until the full gate passes. Stop only when the fix requires user approval,
  destructive action, external coordination, or another unavailable authority.

## Durable Knowledge Boundary

Every durable finding has exactly one canonical destination. Classify it before
recording it, and never mirror the same knowledge into a second store.

Run the classification before the write, not after it. Naming the destination is
a precondition for recording anything durable: if you cannot say which single row
below owns the finding and why the other rows do not, you do not yet know enough
to record it. A finding that seems to fit two rows fits the more specific one —
a glob-selectable constraint is a Project Rule even when a memory already
mentions the domain.

| The finding is                                                            | Destination                    |
| ------------------------------------------------------------------------- | ------------------------------ |
| An always-on workflow, routing, or verification rule binding every task    | `.ai/guidelines/`              |
| A focused procedure worth loading only when its trigger metadata matches   | `.ai/skills/`                  |
| A stable, non-obvious constraint selectable from an affected file glob     | `.ai/rules/` via `record-rule` |
| Orientation knowledge needed before paths are known: source maps, invariants | Serena memory                |
| Visible in the code, tooling-owned, generic framework knowledge, task-local | nowhere                        |

- Tie-break: knowledge already stated in a guideline or enforced by configured
  tooling is already owned. Do not restate it as a Project Rule or a memory.
- A Project Rule that would apply regardless of which file is in scope is
  misfiled; it belongs in a guideline. A memory that only matters once a
  specific glob is in scope is misfiled; it belongs in `.ai/rules/`.
- `.ai/rules/` holds path-scoped rules: settled decisions, non-obvious traps,
  and standing constraints that bind whoever edits a given glob. An agent finds
  them by matching the file it is about to touch against `.ai/rules/index.md`.
- Serena memory holds the project knowledge graph: source maps, domain
  invariants, and discovery shortcuts that orient an agent before it knows
  which files are in scope. An agent finds them by traversing `mem:` references
  from `mem:core`.
- Boost's generated instruction to always prefer `record-rule` over "native
  memory" targets personal, session-scoped assistant memory. It does not apply
  to Serena memory here, which is committed under `.serena/memories/` and
  shared with the team. Both stores are in the repository and both are
  authoritative.
- Write a rule with the `record-rule` tool, never by hand: it owns file
  placement, frontmatter, and the index. Pass a `glob`, a short `title`, and a
  few-line `note`.
- Path-scoped framework guidance is in use. `composer run agents:update` runs
  Boost with `BOOST_RULES_SCOPED_GUIDELINES=true`, so package guidance that
  applies only to specific globs is extracted into the Boost-managed
  `.ai/rules/boost/` subtree and listed in `.ai/rules/index.md` instead of being
  duplicated inline in the generated agent files. That subtree is regenerated on
  every publication: never hand-edit it, and never record a custom rule inside
  it. Rules written with `record-rule` live beside it, outside `boost/`, and
  publication leaves them alone.

## Memory Discipline

- Read `mem:memory_maintenance` before every memory write.
- Record durable invariants and discovery shortcuts, not secrets, logs,
  transient state, obvious facts, or task-local notes. A constraint the
  placement matrix assigns to `.ai/rules/` never also becomes a memory.
- When a finding already lives in the wrong store, relocate it in this order:
  record it in the owning store, verify it is reachable through that store's own
  retrieval path, then remove the duplicate. Never delete the source first, and
  never leave the same knowledge live in two stores once the relocation is
  finished.
- Keep `mem:core` as the graph root and place focused knowledge in topic
  memories linked with marked `mem:` references.
- After memory changes, check referential integrity with
  `serena memories check`; use the unmarked and fuzzy audit flags during
  maintenance work.
