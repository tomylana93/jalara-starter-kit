# Release Workflow

This is the authoring policy for commits, pull requests, and releases. Read `mem:release_process` only when diagnosing release automation internals.

## Branching and CI

- `main` is the only permanent branch. Work on a short-lived branch off `main` and merge it back with a **squash merge**; merge commits and rebase merges are disabled.
- The pull-request title is the unit of the changelog and of SemVer. Commits inside a pull request are not release units.
- Open a working-branch pull request as a draft to run the cheap checks: the merge policy, and the workflow contract when `.github/**` changed. Marking it ready for review runs the full gate. Branch pushes alone run no CI.
- Every push to `main` re-runs the same full gate on the commit that landed, then decides release eligibility.
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
