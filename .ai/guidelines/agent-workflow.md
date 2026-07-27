# Agent Workflow

## Bootstrap

- Start each chat by inspecting the application with the framework-specific
  application information tool when it is available.
- Before coding, use Serena when available: read its instructions, then read
  `mem:core` and only the focused memories relevant to the task.
- When investigation reveals the affected domain, follow the corresponding
  focused memory reference from `mem:core` before deeper exploration.
- Inspect the working tree before editing and preserve unrelated user changes.

## Tool Routing

- Use Serena for project navigation, symbol discovery, reference analysis,
  diagnostics, precise refactoring, and project memory. Prefer its symbolic,
  search, and diagnostic tools before shell-based full-file reads; use full-file
  reads only for small known targets or non-code files.
- Use Laravel Boost for installed-version context, Laravel ecosystem
  documentation, database schema and read-only queries, application URLs, and
  recent backend or browser logs.
- For current non-Laravel library documentation or library-specific debugging,
  use Context7's library resolution and documentation query before web search.
  Use web search only when the purpose-built documentation tool is unavailable
  or unhelpful.
- Use shadcn tools for registry-backed UI components when available.
- Use the shell for Git, Artisan, tests, builds, and cases not covered by a
  purpose-built tool. Fall back to local inspection when an optional MCP tool
  is unavailable; report the limitation only when it affects the result.
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

## Memory Discipline

- Read `mem:memory_maintenance` before every memory write.
- Record durable invariants and discovery shortcuts, not secrets, logs,
  transient state, obvious facts, or task-local notes.
- Keep `mem:core` as the graph root and place focused knowledge in topic
  memories linked with marked `mem:` references.
- After memory changes, check referential integrity with
  `serena memories check`; use the unmarked and fuzzy audit flags during
  maintenance work.
