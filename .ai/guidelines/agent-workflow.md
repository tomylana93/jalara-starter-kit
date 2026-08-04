# Agent Workflow

## Bootstrap

- Start each chat by inspecting the application with the framework-specific
  application information tool when it is available.
- Before coding, use Serena when available: read its instructions, then read
  `mem:core` and only the focused memories relevant to the task.
- When investigation reveals the affected domain, follow the corresponding
  focused memory reference from `mem:core` before deeper exploration.
- Inspect the working tree before editing and preserve unrelated user changes.

## Implementor Routing

- Codex plans first and closes every analysis with a `## HANDOFF` block, then
  asks the developer which implementor takes it.
- Claude Code is the default implementor and runs `/apply-plan`. agy is the
  light implementor for small, well-specified changes and applies a handoff
  through its `apply-plan` skill. Codex implements its own plan only as the
  fallback when Claude is rate-limited and the change exceeds agy's scope.
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
  Fall back to local inspection when an optional MCP tool is unavailable;
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
2. Retrieve version-specific documentation before making framework-sensitive
   changes.
3. Make the smallest coherent change using existing project patterns.
4. For diagnosis, separate confirmed evidence from inference. Validate a
   proposed root cause with authoritative documentation or a reproducing
   experiment; otherwise label it as an unverified hypothesis.
5. Run focused tests first, then the required format, lint, type, or build
   checks for the affected surface.
6. Review the diff and update Serena memory only when the task revealed stable,
   non-obvious project knowledge.

## Verification Boundaries

- After changing PHP, run Rector before Pint: use `composer run rector` for
  automated structural refactoring, then `composer run format:agent` to format
  the resulting dirty files with agent-readable output. Reserve
  `composer run lint` for an explicitly requested repository-wide formatting
  pass.
- Rector scripts must use JSON output without a progress bar. If
  `composer run rector:check` blocks CI by reporting transformable code, run
  `composer run rector` as the required first fix; do not reproduce Rector's
  changes manually. Then run `composer run format:agent`, recheck Rector and
  Pint, and rerun the full gate.
- For frontend auto-fixes, run `pnpm run lint` before `pnpm run format` because
  ESLint may change source structure and Prettier should normalize the final
  output. If `lint:check` or `format:check` blocks CI, the matching auto-fix
  script is mandatory before manual edits; manually address only issues the
  tool leaves unresolved. Type-check, unit-test, build, and E2E failures have no
  general auto-fixer and require focused diagnosis.
- Keep the CI gate ordered from fast static checks to broader execution:
  frontend lint, format, types, and unit tests; PHP Rector, Pint, Larastan, and
  Pest; then the single production build performed by the Playwright command.
- Always run the required `composer run ci:check` final gate. If it exposes a
  failure outside the original task scope, treat that failure as required
  follow-up work: isolate its cause, make the smallest safe fix while
  preserving unrelated user changes, rerun the focused check, and continue
  until the full gate passes. Stop only when the fix requires user approval,
  destructive action, external coordination, or another unavailable authority.

## Memory Discipline

- Read `mem:memory_maintenance` before every memory write.
- Record durable invariants and discovery shortcuts, not secrets, logs,
  transient state, obvious facts, or task-local notes.
- Keep `mem:core` as the graph root and place focused knowledge in topic
  memories linked with marked `mem:` references.
- After memory changes, check referential integrity with
  `serena memories check`; use the unmarked and fuzzy audit flags during
  maintenance work.
