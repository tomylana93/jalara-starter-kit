# Release Process

Commit, pull-request, branching, and release policy is owned by `.ai/guidelines/release-workflow.md` and loads on every task. This memory holds only the automation internals that are not stated there and that a diagnosis would otherwise have to rediscover.

## Workflow topology

- `.github/workflows/_ci.yml` is the only implementation of the gate. Callers pass `scope`: `pull-request` | `main` | `release`. Rationale: `docs/adr/003-ci-cost-model.md`.
- **pull-request**: `changes` (paths-filter) + `verify` (static + vitest + pest sqlite with PCOV `--min=80 --parallel`) + `browser` only when frontend paths match. Browser is job-level filtered so it appears *skipped*, not missing.
- **main**: `compat` (pest pgsql:17 + mysql:8.4, no parallel) + `drift` (pest sqlite --parallel) + release-eligibility on the caller. No browser on `main`.
- **release**: `installer` only (packaging contract for the release PR).
- **weekly** (`.github/workflows/weekly.yml`): dependency audit + opt-in installer-smoke (`STARTER_KIT_MODE`). Both non-hermetic; never colour release eligibility.
- Both release workflows key on a successful conclusion of the `main` workflow, so nothing releases from a commit whose main-scope gate did not pass.
- `.github/scripts/release-commit.sh` tells a release merge from any other commit by checking the associated merged pull request's head ref. Automatic publication addresses the triggering `main` run SHA; manual reconciliation requires an explicit full release SHA.
- `pull-request.yml` splits the aggregate in two. `gates` records the gate conclusion for one revision; `required` re-reads the live pull request policy on every accepted event. Metadata-only and current runs are excluded when it reuses a `gates` result for the live head SHA. `GateOutcome` / `ci:gate-outcome` still own that lookup.
- Concurrency: pull-request runs cancel (grouped per pull request *and* per event name, so an approval cannot cancel a running gate), `main` and both release workflows queue.
- Every workflow grants `permissions: {}` at the top and asks per job. A job that calls the reusable gate must therefore hold `contents: read` itself (and `pull-requests: read` when the callee needs paths-filter on a PR), because a called workflow can only narrow what the caller has.

## Release eligibility

- The security boundary, not the gate. `.github/scripts/provenance-report.sh` gathers per-commit provenance; `php artisan ci:release-eligibility` judges it. Splitting them is what makes the boundary testable.
- Effective baseline = `baseline` in `.github/release-provenance.json`, advanced to the newest `v*` tag when that tag descends from it.
- Trust follows the head repository: only an account with write access can push a branch inside the repository. `.github/scripts/pull-request-reviews.sh` resolves each reviewer's repository permission through the collaborators API; an approval counts only for the exact head sha and only for `admin`, `maintain` or `write`. A permission the token could not read becomes `unknown` and is reported distinctly from a missing approval, so a token problem never reads as a policy violation.
- `.github/scripts/provenance-report.sh` gathers remediation evidence with Git: an open entry includes commit order and an exact inverse-patch comparison. `ci:release-eligibility` accepts only evidence tied to the same ledger entry; entries behind the effective released baseline are reported closed.

## Release Please

- Release type `simple` is chosen because its `version.txt` updater uses `createIfMissing: false`: with no `version.txt` committed, nothing extra is generated and `version.json` stays the only runtime version file, updated through the generic JSON extra-file updater.
- A single root `simple` manifest release is componentless: do not set `package-name` in `release-please-config.json`. A configured package component does not match the componentless `release-please--branches--main` branch and can leave a merged release PR at `autorelease: pending` without ever publishing.
- Release Please only creates or updates the release pull request. `release-publish.yml` reads metadata from the verified release commit, decides tag/release work before mutation, creates the Git ref at that exact SHA, and verifies the published non-draft GitHub Release afterward.

## Credentials

- Guards are workflow *steps*, not job-level `if`s, because `secrets` is unavailable in a job-level condition.
- `.github/actions/release-credentials` resolves the mode: GitHub App (`RELEASE_APP_CLIENT_ID` + `RELEASE_APP_PRIVATE_KEY`) → installation token, else `RELEASE_TOKEN`, else `none`. Permission subsets are per scope: the creator gets contents/pull-requests/issues write, the publisher only contents write. Mode `none` reports in the job summary and never fails the run.

## Runtime version

- `config('app.version')` parses `version.json` defensively and falls back to `0.0.0`; `HandleInertiaRequests::share` publishes it as the `version` shared prop, and `AppFooter` renders it as plain `v<version>` text that stays visible when the branding footer text is absent.
- `.gitignore` excludes `/.vscode/*` and re-includes `!/.vscode/settings.json`. Excluding the directory itself instead would make the negation unreachable.
- CI gate composition and local commands: `mem:suggested_commands`.
