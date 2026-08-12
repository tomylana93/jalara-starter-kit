# Release Process

Commit, pull-request, branching, and release *authoring* policy is owned by `.ai/guidelines/release-workflow.md` and loads on every task. This memory holds only the automation internals a diagnosis would otherwise have to rediscover. Cost-model rationale: `docs/adr/003-ci-cost-model.md`. Local command names: `mem:suggested_commands`.

## Workflow topology

- `.github/workflows/_ci.yml` is the only gate implementation. Callers pass `scope`: `pull-request` | `main` | `release`.
- **pull-request** (two jobs): `verify` (static + vitest + pest sqlite with PCOV `--min=80 --parallel`; also runs `dorny/paths-filter` and exposes `frontend` output) + `browser` only when that output is true. Path filter is job-level so browser appears *skipped*, not missing. Browser caches `~/.cache/ms-playwright` keyed on the locked Playwright version; no `--parallel` on browser.
- **main**: `compat` (pest `--parallel` on pgsql:17 + mysql:8.4) + `drift` (pest sqlite `--parallel`) + release-eligibility on the caller. No browser on `main`.
- **release**: `installer` only (packaging contract for the release PR).
- **weekly** (`.github/workflows/weekly.yml`): dependency audit + opt-in installer-smoke (`STARTER_KIT_MODE`). Both non-hermetic; never colour release eligibility.
- Both release workflows key on a successful conclusion of the `main` workflow.
- `.github/scripts/release-commit.sh` tells a release merge from any other commit by checking the associated merged pull request's head ref. Automatic publication addresses the triggering `main` run SHA; manual reconciliation requires an explicit full release SHA.
- `pull-request.yml`: `gates` records the gate conclusion for one revision; `required` re-reads live pull-request policy on every accepted event. Metadata-only and current runs are excluded when reusing a `gates` result for the live head SHA. `GateOutcome` / `ci:gate-outcome` own that lookup.
- Concurrency: pull-request runs cancel (per PR *and* per event name); `main` and both release workflows queue.
- Every workflow grants `permissions: {}` at the top and asks per job. A job that calls the reusable gate must hold `contents: read` itself (and `pull-requests: read` when the callee needs paths-filter on a PR).

## Release eligibility

- The security boundary, not the gate. `.github/scripts/provenance-report.sh` gathers per-commit provenance; `php artisan ci:release-eligibility` judges it.
- Effective baseline = `baseline` in `.github/release-provenance.json`, advanced to the newest `v*` tag when that tag descends from it.
- Trust follows the head repository: only an account with write access can push a branch inside the repository. `.github/scripts/pull-request-reviews.sh` resolves each reviewer's repository permission through the collaborators API; an approval counts only for the exact head sha and only for `admin`, `maintain` or `write`. Unreadable permission → `unknown`, reported distinctly from a missing approval.
- Remediation evidence is gathered with Git (commit order + exact inverse-patch). `ci:release-eligibility` accepts only evidence tied to the same ledger entry; entries behind the effective released baseline are reported closed.

## Release Please

- Release type `simple` because its `version.txt` updater uses `createIfMissing: false`: with no `version.txt` committed, nothing extra is generated and `version.json` stays the only runtime version file.
- A single root `simple` manifest release is componentless: do not set `package-name` in `release-please-config.json`. A configured package component does not match the componentless `release-please--branches--main` branch and can leave a merged release PR at `autorelease: pending` without ever publishing.
- Release Please only creates or updates the release pull request. `release-publish.yml` reads metadata from the verified release commit, decides tag/release work before mutation, creates the Git ref at that exact SHA, and verifies the published non-draft GitHub Release afterward.

## Credentials

- Guards are workflow *steps*, not job-level `if`s (`secrets` unavailable in job-level conditions).
- `.github/actions/release-credentials` resolves: GitHub App (`RELEASE_APP_CLIENT_ID` + `RELEASE_APP_PRIVATE_KEY`) → installation token, else `RELEASE_TOKEN`, else `none`. Creator gets contents/pull-requests/issues write; publisher only contents write. Mode `none` reports in the job summary and never fails the run.

## Runtime version

- `config('app.version')` parses `version.json` defensively and falls back to `0.0.0`; `HandleInertiaRequests::share` publishes it as the `version` shared prop; `AppFooter` renders `v<version>` even when branding footer text is absent.
- `.gitignore` excludes `/.vscode/*` and re-includes `!/.vscode/settings.json`. Excluding the directory itself would make the negation unreachable.
