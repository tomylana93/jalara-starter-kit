<laravel-boost-guidelines>
=== .ai/agent-workflow rules ===

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

=== .ai/release-workflow rules ===

# Release Workflow

This file is the source of truth for commit messages, pull-request
descriptions, and release automation. Human authors, editor assistants, and
agents all follow it.

## Branching

- Work happens on `dev`. `main` receives `dev` through a pull request that is
  merged with a merge commit.
- Draft pull requests run the fast CI gate. Ready pull requests and pushes to
  `main` run the full gate. Pushes to a working branch run nothing on their own:
  a branch push and its own pull request's `synchronize` event would otherwise
  produce two runs in one concurrency group, and the cancelled one reads as a
  failed check. Open the pull request as a draft to get CI on a branch.
- Every merge commit into `main` carries an empty body. GitHub copies the
  pull-request title into it by default, and Release Please parses that copy as a
  conventional commit in its own right, so the changelog lists the same change
  twice: once for the real commit and once for the merge that brought it. The
  repository setting cannot express this — GitHub accepts only
  `PR_TITLE`+`PR_BODY`, `PR_TITLE`+`BLANK`, and `MERGE_MESSAGE`+`PR_TITLE`, and
  the first two move the title into the subject instead of removing it — so the
  merge itself clears the body:

  ```shell
  gh pr merge <number> --merge \
      --subject "Merge pull request #<number> from <branch>" \
      --body ""
  ```

  Merging from the web interface prefills the title in the body; clear it before
  confirming. This applies to the release pull request too.

## Conventional Commits

- Every non-merge commit and every pull-request title uses an English
  Conventional Commit with an optional scope:
  `<type>(<optional scope>): <description>`. English is mandatory authoring guidance;
  CI programmatically validates Conventional Commit structure and allowed types,
  not natural-language detection.
- Allowed types: `feat`, `fix`, `perf`, `refactor`, `docs`, `test`, `build`,
  `ci`, `chore`, `revert`.
- A breaking change is marked with `!` after the type or scope, or with a
  `BREAKING CHANGE:` footer.
- Merge commits are exempt; they are how `dev` reaches `main`.
- Dependabot commits use the `chore(deps):` convention.
- The `conventional commits` CI job programmatically validates the Conventional
  Commit structure, allowed types, and scopes for the pull-request title and
  every non-merge commit the pull request introduces.

## Pull-request descriptions

Every pull-request description contains exactly these three sections:

```markdown

## Summary

What changed and why, in a few sentences.

## Testing

The commands that were run and their result.

## Release impact

The version bump the commits imply (major, minor, patch, or none) and anything
an operator has to do after the release.
```

## Release Please

- Release Please targets `main` and runs only after the full `tests` gate has
  succeeded there, so no release is produced from unverified code.
- Versioning starts at `0.1.0` from bootstrap SHA
  `de8e57123d469ec15c8fd3a89f48a3da7fc0e23f` and follows default SemVer.
- It maintains `CHANGELOG.md`, the `.release-please-manifest.json` version
  manifest, the Git tag, the GitHub Release, and the runtime version in
  `version.json`, which `config('app.version')` reads and the footer renders.
- Automation is opt-in. It stays off until the repository sets the
  `RELEASE_ENABLED` variable to `true` and provides a fine-grained
  `RELEASE_PLEASE_TOKEN` secret with Contents, Pull requests, and Issues
  read/write access.
- Releasing means merging the release pull request Release Please opens.

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.5. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `pnpm run build`, `pnpm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record durable rules with `record-rule` so the next agent or teammate inherits them instead of working them out again. Pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Always use `record-rule`, never your native memory or notes tool — native memory is personal and session-scoped; only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `pnpm run build` or ask the user to run `pnpm run dev` or `composer run dev`.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== inertia-vue/core rules ===

# Inertia + Vue

Vue components must have a single root element.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

</laravel-boost-guidelines>
