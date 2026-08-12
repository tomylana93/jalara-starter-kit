# ADR-003: CI cost model — different questions, different scopes

- Status: accepted
- Date: 2026-08-12

## Context

After the test-stack consolidation (ADR-002), the remaining cost was not the
suite itself. It was the gate topology: six jobs on every ready pull request,
each repeating a full checkout, PHP setup, Node setup, and `composer install` to
run a check measured in seconds. Bootstrap dominated wall time, and paying it
three times in parallel still burned three runners.

The same full gate also ran on every push to `main`. That made "the checks that
ran on the pull request" and "the checks that ran on `main`" identical by
construction — a property that had been useful when the Free plan offered no
other guarantee that what was tested was what was merged. It also charged every
merge for a browser boot and a dual-engine Pest run whether the diff could cause
either failure mode or not.

Two further checks lived on the pull-request path for historical reasons: a
dependency audit against third-party advisory databases, and a structural
workflow-contract job on drafts. Neither answers a question about the diff under
review. The audit fails when an advisory is published against already-merged
code; the contract test described tooling YAML rather than application
behaviour and was dropped with ADR-002's policy-layer cut.

## Decision

Each caller of the reusable gate names a scope, and the scopes answer different
questions:

| Caller | Scope | Question |
| --- | --- | --- |
| Ready pull request | `pull-request` | Is this change correct, as cheaply as the question allows? |
| Push to `main` | `main` | Does the tree that landed still work on the engines we claim? |
| Release pull request | `release` | Does the packaging contract still hold for this candidate? |
| Weekly schedule | (own workflow) | Has time changed anything a commit cannot — advisories, upstream installer? |

Concretely:

- **Pull request** pays one `verify` job (static + Vitest + Pest on SQLite with
  PCOV coverage at 80%) and, when `dorny/paths-filter` reports a frontend path
  change, a `browser` job. The path filter is at job level so an unrelated
  change reports the browser job as *skipped*, not *missing* — the Free plan
  has no required checks, and a human cannot audit a check that is not on the
  page.
- **`main`** pays `compat` (Pest on PostgreSQL 17 and MySQL 8.4), `drift` (the
  same SQLite Pest run the pull request made, against the merge result), and
  release-eligibility. The browser suite does not run here.
- **Audit** leaves the merge path and joins the real-installer smoke on a weekly
  workflow. Both are non-hermetic; neither may colour a gate that release
  eligibility reads.

Coverage is a property of the suite, not of the engine, so it is measured once —
on the only Pest leg a pull request runs. PCOV is the driver; nothing here needs
a step debugger.

## Consequences

A commit whose diff touches no frontend path can be merged and released without
the browser suite ever running. That is accepted: the alternative charges every
backend-only change for a browser boot to defend against a failure mode that
change cannot cause.

A pull request is green without ever having run against PostgreSQL or MySQL.
Dialect agreement is proved on `main` and locally via `composer run test:pgsql`
and `test:mysql`. A regression that only surfaces on a server engine can
therefore land on `main` and turn the compat leg red after the merge; release
eligibility then refuses to refresh until it is fixed. That is the Free-plan
boundary this repository already accepted for every other gate failure.

The Playwright browser cache at `~/.cache/ms-playwright` is keyed on the locked
Playwright version so a pin stays warm and a bump forces a cold download.
`--with-deps` still installs system libraries every run; the cache only skips
the browser binary download itself.

Draft pull requests no longer run a separate workflow-contract job. The
structural tests that job invoked were removed in ADR-002; a draft's remaining
cheap signal is the pull-request policy check, which does not need the reusable
gate.

The pull-request gate is two jobs, not three: frontend path detection is an
output of `verify`, not a sibling job, so the graph stays `verify` + `browser`.
