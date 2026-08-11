# Release Process

Commit, pull-request, branching, and release policy is owned by `.ai/guidelines/release-workflow.md` and load on every task. This memory holds only the automation internals that are not stated there and that a diagnosis would otherwise have to rediscover.

- Release type `simple` is chosen because its `version.txt` updater uses `createIfMissing: false`: with no `version.txt` committed, nothing extra is generated and `version.json` stays the only runtime version file, updated through the generic JSON extra-file updater.
- A single root `simple` manifest release is componentless: do not set `package-name` in `release-please-config.json`. A configured package component does not match the componentless `release-please--branches--main` branch and can leave a merged release PR at `autorelease: pending` without ever publishing.
- Treat a successful Release Please workflow as complete only after the expected Git tag and a non-draft GitHub Release exist and the merged release PR reads `autorelease: tagged`; a green job may still have logically aborted publication.
- The `RELEASE_PLEASE_TOKEN` guard is a workflow *step*, not a job-level `if`, because `secrets` is unavailable in a job-level condition.
- `.github/workflows/release-please.yml` is triggered by `workflow_run` on a successful `tests` run, which is the mechanism behind "nothing is released from unverified code".
- The `conventional commits` job in `.github/workflows/tests.yml` is shell-only with no third-party action; it validates the pull-request title plus `git log --no-merges base..head`. Dependabot satisfies it through `commit-message.prefix: chore(deps)` without `include: scope`.
- `config('app.version')` parses `version.json` defensively and falls back to `0.0.0`; `HandleInertiaRequests::share` publishes it as the `version` shared prop, and `AppFooter` renders it as plain `v<version>` text that stays visible when the branding footer text is absent.
- `.gitignore` excludes `/.vscode/*` and re-includes `!/.vscode/settings.json`. Excluding the directory itself instead would make the negation unreachable.
- CI tiering and gate composition: `mem:suggested_commands`.
