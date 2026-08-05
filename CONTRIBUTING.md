# Contributing & Releases Guidelines

This document outlines the contribution guidelines, branching workflows, testing requirements, and release process for **Jalara Starter Kit**.

The full internal conventions live in [`.ai/guidelines/release-workflow.md`](.ai/guidelines/release-workflow.md). The summary below is what a contributor needs day-to-day.

## Branching and CI Workflow

Work happens on the `dev` branch, and `main` receives updates through a pull request that is merged with a merge commit.

Continuous integration is tiered so routine feedback stays fast:

| Context | Gate | Contents |
| --- | --- | --- |
| Draft pull request, push to an ordinary branch | `composer run ci:check` | Frontend lint, format, types, and Vitest; PHP Rector, Pint, Larastan, and Pest |
| Ready pull request, push to `main` | `composer run ci:full` | Everything above plus 80% coverage enforcement and the Playwright journeys (the public starter-kit installer check runs in parallel on GitHub Actions) |

* Both commands can be run locally.
* Superseded runs for the same branch or pull request are cancelled automatically.

---

## Commit and Pull Request Conventions

Every non-merge commit and every pull-request title must use an English Conventional Commit with an optional scope:
`<type>(<optional scope>): <description>`

Allowed types:
*   `feat` — A new feature
*   `fix` — A bug fix
*   `perf` — Performance improvements
*   `refactor` — Code change that neither fixes a bug nor adds a feature
*   `docs` — Documentation only changes
*   `test` — Adding missing tests or correcting existing tests
*   `build` — Changes that affect the build system or external dependencies
*   `ci` — Changes to our CI configuration files and scripts
*   `chore` — Other changes that don't modify src or test files
*   `revert` — Reverts a previous commit

English is mandatory authoring guidance; CI programmatically validates Conventional Commit structure, allowed types, and scopes. Merge commits are exempt, and a CI job enforces the rest. Release Please reads these commit messages to decide the next version bump and to generate the changelog automatically.

### Pull-Request Descriptions
Every pull-request description must contain exactly three sections:
1.  **Summary**: What changed and why.
2.  **Testing**: The commands that were run and their results.
3.  **Release impact**: The version bump the commits imply (major, minor, patch, or none) and anything an operator has to do after the release.

---

## Releases

Releases are automated with [Release Please](https://github.com/googleapis/release-please). It targets the `main` branch, runs only after the full gate has succeeded there, starts at `0.1.0`, and follows standard SemVer. 

It maintains:
*   `CHANGELOG.md`
*   The `.release-please-manifest.json` version manifest
*   The Git tag and GitHub Release
*   `version.json` (the runtime version the application footer renders)

Publishing a release means merging the release pull request that Release Please opens.

For detailed steps on how to configure the required personal access token and variables to enable release automation, see the [Release Automation Setup in setup.md](docs/setup.md#release-automation-setup).
