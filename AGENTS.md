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
- Avoid repeating equivalent discovery through several tools after an
  authoritative source has answered the question.

## MCP Tool Usage and Error Mitigation

- **Validation Error Mitigation (call_mcp_tool & mcp_ prefixes)**:
  - If `call_mcp_tool` is not in the declared tools schema for the current session (such as in some client-Gemini sessions), do NOT attempt to use it or direct eager tools (e.g. `mcp_serena_execute_shell_command`).
  - When `call_mcp_tool` is unavailable, fall back cleanly to native tools (`run_command` for terminal commands, `grep_search` / `view_file` for exploration, and `replace_file_content` / `multi_replace_file_content` for edits) instead of failing.
- **Artifact Path Verification**:
  - Never specify `ArtifactMetadata` when writing or editing repository code files (e.g., PHP, JS, Vue, CSS files) using `write_to_file`.
  - Only specify `ArtifactMetadata` when explicitly creating or editing user-facing reports, analysis documents, or checklists inside the designated conversation brain directory (e.g. `/home/tomylana93/.gemini/antigravity-cli/brain/<conversation-id>/`).

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
   record it in the store that matches how it must be found: `record-rule` for
   a constraint bound to a path, Serena memory for orientation knowledge.

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

## Durable Knowledge Boundary

Two committed stores hold durable knowledge. They are complementary, not
alternatives; choose by how the knowledge must be found, and never mirror the
same rule into both.

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
- Path-scoped framework guidance under `.ai/rules/boost` is not in use;
  `boost.rules.scoped_guidelines` stays disabled, so package guidelines remain
  inline in the generated agent files. Do not expect that directory to exist.

## Memory Discipline

- Read `mem:memory_maintenance` before every memory write.
- Record durable invariants and discovery shortcuts, not secrets, logs,
  transient state, obvious facts, or task-local notes.
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
- Draft pull requests and pushes to an ordinary branch run the fast CI gate.
  Ready pull requests and pushes to `main` run the full gate.

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

- This project keeps committed, area-grouped rules in `.ai/rules` (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule.
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

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `pnpm run build` or ask the user to run `pnpm run dev` or `composer run dev`.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== inertia-vue/core rules ===

# Inertia + Vue

Vue components must have a single root element.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

</laravel-boost-guidelines>
