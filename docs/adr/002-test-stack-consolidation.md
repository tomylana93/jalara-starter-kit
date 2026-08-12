# ADR-002: Consolidate the test stack on Pest 5

- Status: accepted
- Date: 2026-08-12

## Context

The repository is maintained by a solo developer, but its test stack was sized
for a team. Three runners each owned a slice of the suite: Pest for PHP
behaviour, Vitest for TypeScript and Vue components, and Playwright Test for
cross-stack journeys. Each carried its own configuration, its own fixtures, its
own authentication setup, and its own vocabulary for the same ideas.

The cost was not the runtime. It was that a single behaviour could plausibly be
tested in three places, and deciding which one was right had become a judgement
call made fresh every time. Fifty-two Vitest files had accumulated, most of
which mounted a Vue single-file component and asserted on the rendered DOM —
re-proving through a jsdom approximation what a real browser or a server-side
props assertion could prove directly. Six Playwright specs carried a bespoke
runner: an isolated SQLite database, a dedicated Vite build directory, a
per-run storage root, a storage-state authentication project, and a shell
script to orchestrate it.

Pest 5 ships a browser plugin that drives Playwright while keeping Laravel's
testing API — factories, `actingAs()`, `RefreshDatabase`, event fakes — inside
the same test. That closed the gap that had originally justified a separate
runner.

## Decision

Authoring consolidates on Pest 5. Playwright remains, but only as the runtime
underneath Pest's browser plugin; `@playwright/test` and the bespoke runner are
gone.

Each layer now answers a question the others cannot:

- **Vitest** keeps the modules whose subject is logic, including composables
  that manipulate `document`, `window`, or storage. There the DOM is what is
  being tested, not a rendering surface.
- **Feature tests** own the server contract — Inertia props, authorization,
  redirects — reached through `actingAs()` rather than a captured browser
  session.
- **Browser tests** own what only a real browser can show: that each page boots
  and renders the content it should.

The per-page browser specs collapse into one smoke sweep. A page that only
needed proving "renders with the right props" moves to a Feature test.

The sweep pairs `assertNoSmoke()` with a positive `assertSee()` per page, and
that pairing is not decoration. `assertNoSmoke()` asserts only the absence of
console logs and JavaScript errors, and a page that renders nothing produces
neither — so a blank page passes it. Writing the sweep the other way would have
traded 44 deleted Vitest files for a guarantee weaker than the one they gave.
This was not hypothetical: the first version of the suite inherited
`withoutVite()` from `Tests\TestCase`, served no bundle, rendered blank on every
page, and passed.

## What this layer does not yet prove

The browser layer currently proves rendering only. Multi-step flow coverage does
not exist. The two flows this consolidation intended to keep — the upload guard
and chat — could not be written against the new stack, and the reason is the
same in both cases rather than two separate accidents:

**The Pest browser plugin's interaction fidelity against a headless component
library (reka-ui) is unproven for this application.**

Concretely, the plugin exposes no request interception, so the upload guard's
in-flight state — which the old spec held open with `page.route()` — cannot be
observed at all. And driving `fill()` and `click()` against reka-ui wrappers
leaves the DOM showing a recipient and a message body that Vue's reactive state
does not hold: the send reaches the server with every field of the request
absent. The same flows passed under the previous runner, so the application
works; what is unproven is the new harness.

That single question gates any proposal to widen the browser suite. If further
investigation also stalls, the honest conclusion may be that interactive flows
in this stack need a different tool — a decision to be taken later on evidence,
not now on a guess.

## Consequences

The rendering-focused Vitest files are deleted rather than migrated. Their
assertions were about jsdom output, and jsdom computes no layout — a class that
renders invisibly and a class that renders correctly are indistinguishable
there. Whatever those tests were protecting is better protected by the smoke
sweep or by a Feature-level props assertion.

Removing the bespoke Playwright runner also removes the asset-isolation
machinery it required: a dedicated build directory, hot file, storage root, and
the configuration on both the Vite and the Laravel side that had to stay in
sync. That coupling was a recurring source of subtle breakage and it now has no
reason to exist.

The upload-guard and chat browser tests are therefore not part of this change.
The upload guard's logic is still held by the ten tests in
`useUploadGuard.test.ts`, and chat's server contract by its Feature tests; what
is missing in both cases is end-to-end wiring proof.

Two structural CI tests are dropped with it. `WorkflowContractTest` asserted the
shape of workflow YAML, and `GateOutcomeTest` covered a lookup helper; both
described tooling rather than application behaviour. The release-governance
tests stay, because those decide whether a commit may be tagged — that is
executable policy with a security consequence, and it is the one part of the CI
surface where a wrong answer is not merely inconvenient.

Database compatibility is no longer proved by the CI gate on every pull
request. It moves to ephemeral podman containers driven by `composer run
test:pgsql` and `test:mysql` locally, and to a `main`-only job in CI. A pull
request is therefore green without ever having run against PostgreSQL or MySQL.
That is accepted: the alternative charged every change for a guarantee that
only the merge needs.
