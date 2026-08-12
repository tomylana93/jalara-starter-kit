# Contributing & Releases Guidelines

This document outlines the contribution guidelines, branching workflows, testing requirements, and release process for **Jalara Starter Kit**.

The full internal conventions live in [`.ai/guidelines/release-workflow.md`](.ai/guidelines/release-workflow.md). The summary below is what a contributor needs day-to-day.

## Branching and CI Workflow

`main` is the only permanent branch. Work happens on a short-lived branch off `main` and returns to it through a **squash-merged** pull request; merge commits and rebase merges are disabled on the repository. The pull-request title becomes the single commit on `main`, which is also the unit the changelog and the version bump are derived from.

Continuous integration is tiered so routine feedback stays fast:

| Context | Contents |
| --- | --- |
| Draft pull request | The merge policy, plus the workflow contract when `.github/**` changed |
| Ready pull request | Frontend lint, format, types, Vitest; PHP Pint, Larastan, Pest on SQLite and on PostgreSQL with 80% coverage; the dependency audit; then the Playwright journeys and the installer contract |
| Push to `main` | The same gate again, on the commit that actually landed, followed by the release-eligibility check |

* Locally, `composer run ci:check` runs the static analysis, Vitest and Pest; `composer run ci:full` adds the coverage threshold and the Playwright journeys. The PostgreSQL leg, the dependency audit and the installer contract run in CI only — the first needs a database service and the other two are not hermetic. The audit is `composer run audit:check`, and CI runs that same script rather than its own copy of the commands.
* A pull request and `main` call one reusable workflow, so the two can never drift apart.
* Superseded runs for the same pull request are cancelled automatically, including when a ready pull request is converted back to a draft.
* Every check is aggregated into one status named `CI / required`. It is reported again whenever an approval, a dismissal or an edited title changes the answer, and it re-reads the merge policy each time rather than trusting an earlier verdict.

### Reviews

A pull request from a fork needs one approving review from an account with write access, and that approval is dropped as soon as the head revision changes. Write access is read from the reviewer's repository permission rather than from the badge GitHub shows beside their name, because that badge covers read-only collaborators too. A branch pushed inside the repository already required write access, so it needs no self-approval — only the checks.

### What this repository can and cannot enforce

On the GitHub Free plan a private repository has no protected branches, no rulesets, no merge queue, and no required checks. Nothing here can stop a direct push or a merge over a red gate.

What is enforced is the release: a commit that reached `main` outside a squashed, policy-satisfying pull request makes the branch ineligible, and while it is, the release pull request is not refreshed and no tag or GitHub Release is produced.

The fix is a revert through a pull request, recorded in `.github/release-provenance.json`. That file is not an escape hatch: the named commit must follow the offending commit, pass the same release policy, and carry its exact inverse patch. Replacement labels are not accepted because the workflow cannot prove their semantics. The ledger changes through a pull request like everything else, and there is no bypass switch.

---

## Commit and Pull Request Conventions

Every pull-request title must use an English Conventional Commit with an optional scope. Under squash merges the title is the commit that lands on `main`, so it is the only subject Release Please ever reads:
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

English is mandatory authoring guidance; CI programmatically validates Conventional Commit structure, allowed types, and scopes on the pull-request title. Commits inside the branch are not release units and are not validated. Release Please reads the resulting subjects on `main` to decide the next version bump and to generate the changelog automatically.

### Pull-Request Descriptions
Every pull-request description must contain exactly three sections:
1.  **Summary**: What changed and why.
2.  **Testing**: The commands that were run and their results.
3.  **Release impact**: The version bump the commits imply (major, minor, patch, or none) and anything an operator has to do after the release.

---

## Releases

Releases are automated with [Release Please](https://github.com/googleapis/release-please). It targets the `main` branch, starts at `0.1.0`, and follows standard SemVer.

It maintains:
*   `CHANGELOG.md`
*   The `.release-please-manifest.json` version manifest
*   The Git tag and GitHub Release
*   `version.json` (the runtime version the application footer renders)

Releasing happens in two halves, and a human sits between them:

1. **Propose.** After a successful run on `main`, `release-pr.yml` opens or refreshes the release pull request. It never tags.
2. **Publish.** Once you merge that pull request, `release-publish.yml` writes the tag and the GitHub Release for it. It never opens pull requests, and it never auto-merges.

The release pull request is checked for what is genuinely unverified about it — that it touches release files only, that the manifest, `version.json`, and the changelog agree on one new version, and that the application still installs, builds, and boots. It does not re-run the test suite, because the commit it describes already passed the full gate on `main`.

Publication is idempotent. If the tag was written but the GitHub Release was not, re-run `release-publish.yml`: it finishes that same version instead of raising a new one.

Automation stays switched off until it is configured. For the credentials and repository variables, see the [Release Automation Setup in setup.md](docs/setup.md#release-automation-setup).
