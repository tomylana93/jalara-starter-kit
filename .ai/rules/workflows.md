---
paths:
  - '.github/workflows/**'
---

# Workflows

## pg_dump client major must match the PostgreSQL server major
`pg_dump` refuses to dump a server newer than itself and exits 1 with "aborting because of server version mismatch". The backup suite surfaces that as nothing but "Expected status code 0 but received 1", which reads like an application bug and is not.

The `compat` job pins `postgres:17-alpine` and installs `postgresql-client-17` in the same job. Bumping the service image requires installing the matching client in the same change. The same trap applies to a production restore drill: the deployment host's client must match its server.

## Path filters for optional jobs stay at job level
A path filter on `on.push.paths` / `on.pull_request.paths` makes the job *missing* when the path does not match. On the Free plan there are no required checks, so a missing check is indistinguishable from one that never ran. Filter with `dorny/paths-filter` inside the job graph instead, so the job appears as skipped.

## Do not cache vendor/ or node_modules/
Only the Composer download cache and the pnpm store are cached. The install still runs so platform checks keep validating the tree; a stale install cache can make a run slow, never wrong.
