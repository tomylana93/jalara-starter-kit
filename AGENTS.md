<laravel-boost-guidelines>
=== .ai/agent-workflow rules ===

# Agent Workflow

## Production posture

- Treat every change as production-affecting. Make the smallest reversible change, preserve unrelated work, and report only verification actually run.
- Scope is authorization. Dependencies, security-sensitive surfaces, shipped migrations, CI/release configuration, destructive operations, history rewrites, and scope growth require explicit developer approval.
- A red required gate blocks completion. Fix it within scope or stop when the fix requires unavailable authority.

## Bootstrap and routing

- Start with application information when available. Before coding, activate Serena, read its instructions, then read `mem:core` and only the focused memories it routes to. Inspect the working tree before editing.
- Every AI coding agent configured for this repository is a peer: each may investigate, plan when useful, and implement an authorized request directly.
- Use Serena for code navigation, references, diagnostics, precise edits, and project memory. For Vue orientation, read the component directly; its TypeScript gate is authoritative over Serena diagnostics.
- Use Laravel Boost for installed-version Laravel context, framework docs, schema, read-only data, URLs, and recent logs. Use Context7 for current non-Laravel library docs, then web search only when those tools are unhelpful.
- Use repository Composer scripts when one exists. Read `.ai/rules/index.md` and every matching Project Rule before editing an affected path.
- Discover skills dynamically, activate only matching skills, and read each selected `SKILL.md` through EOF before acting.

## Task and verification loop

1. Ground the request in repository state, focused context, nearby patterns, and version-specific documentation.
2. Separate confirmed evidence from inference. Validate a proposed root cause with authoritative documentation or a reproducing experiment.
3. Make the smallest coherent change and add or update a programmatic test.
4. Run focused tests, then the checks required by the affected surface.
5. Review the complete diff and classify any durable finding exactly once.

- PHP or frontend source changed: run `composer run fix` before the gate.
- Agent infrastructure changed: run `composer run agents:update` twice and confirm the second run leaves no further tracked diff.
- CI, release, coverage, installer, or Playwright configuration changed: also run `composer run ci:full`.
- Always finish with `composer run ci:check`.

## `.gitignore` changes and staging

Removing an ignore line exposes files it was hiding. After changing `.gitignore`, never `git add -A` or `git add .`; run `git status --porcelain` first, stage per path, and confirm nothing newly un-hidden came along. An ignore line that looks dead must be proven dead by inspecting the directory's contents, not inferred from the name of the feature being removed.

## Durable knowledge

Classify before writing; one finding has one owner:

| Finding | Canonical destination |
| --- | --- |
| Always-on workflow or verification rule | `.ai/guidelines/` |
| Trigger-scoped procedure | `.ai/skills/` |
| Stable constraint selected by file glob | `.ai/rules/` via `record-rule` |
| Orientation map or invariant needed before paths are known | Serena memory |
| Code-visible, tooling-owned, generic, volatile, or task-local | Nowhere |

- More-specific ownership wins. Never mirror a finding between stores.
- `.ai/rules/boost/` is regenerated; never edit it or record a custom rule in it. Use `record-rule` for custom Project Rules.
- Before a memory write, read `mem:memory_maintenance`. Keep `mem:core` as the graph root and run `serena memories check`; during maintenance also run the unmarked and fuzzy audit.

=== .ai/release-workflow rules ===

# Release Workflow

This is the authoring policy for commits, pull requests, and releases. Read `mem:release_process` only when diagnosing release automation internals.

## Branching and CI

- `main` is the only permanent branch. Work on a short-lived branch off `main` and merge it back with a **squash merge**; merge commits and rebase merges are disabled.
- The pull-request title is the unit of the changelog and of SemVer. Commits inside a pull request are not release units.
- Open a working-branch pull request as a draft to run the cheap checks (merge policy). Marking it ready for review runs the pull-request gate (`verify`, plus `browser` when frontend paths change). Branch pushes alone run no CI.
- Every push to `main` runs the main-scope gate (database compat + drift) on the commit that landed, then decides release eligibility.
- Merge a ready pull request with:

  ```shell
  gh pr merge <number> --squash
  ```

- A pull request from a fork needs one approving review from an account whose repository permission carries write access, and that approval lapses when the head revision changes. A branch inside the repository already carries write access, so it needs no self-approval — only the checks.

## GitHub Free, private

The plan provides no protected branches, no rulesets, no merge queue, and no required checks, so nothing prevents a direct push or a merge over a red gate. Release eligibility is the boundary instead: an invalid commit is allowed to make `main` red, and while it is, the release pull request is not refreshed and no tag or GitHub Release is created. There is no one-click bypass.

Remediate by reverting through a pull request, then record the commit in `.github/release-provenance.json` with a reason and the sha that reverted it. The entry is checked rather than trusted: the remediating commit must follow the offending commit, be releasable itself, and have the exact inverse patch. Replacement labels are not accepted because their semantics cannot be proven mechanically. Do not rewrite history as routine practice.

## Commits and pull requests

- Every pull-request title is an English Conventional Commit: `<type>(<optional scope>): <description>`.
- Allowed types are `feat`, `fix`, `perf`, `refactor`, `docs`, `test`, `build`, `ci`, `chore`, `revert`. Mark breaking changes with `!` or a `BREAKING CHANGE:` footer. Dependabot uses `chore(deps):`.
- Every pull-request description contains exactly `## Summary`, `## Testing`, and `## Release impact`; the last names the implied bump and operator action.

## Releases

- Releasing is split in two. `release-pr.yml` opens and refreshes the release pull request after a successful `main` run; `release-publish.yml` writes the tag and the GitHub Release after a human merges it. Neither does the other's half, and the release pull request never auto-merges.
- Automation stays off until `RELEASE_ENABLED=true` and one credential mode is complete: a GitHub App (`RELEASE_APP_CLIENT_ID` plus the `RELEASE_APP_PRIVATE_KEY` secret, preferred) or the `RELEASE_TOKEN` fine-grained PAT. Without them CI stays green and the release job reports in its summary that it is inactive.
- Publication is idempotent. If a tag exists but its GitHub Release does not, re-run `release-publish.yml`: it finishes that same version rather than raising a new one.

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
