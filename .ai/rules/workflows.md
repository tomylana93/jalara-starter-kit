---
paths:
  - '.github/workflows/**'
---

# Workflows

## pg_dump client major must match the PostgreSQL server major
`pg_dump` refuses to dump a server newer than itself and exits 1 with "aborting because of server version mismatch". The backup suite surfaces that as nothing but "Expected status code 0 but received 1", which reads like an application bug and is not.

The `pest` job pins `postgres:16-alpine` because ubuntu-latest ships the 16 client. Bumping the service image requires installing the matching client in the same change. The same trap applies to a production restore drill: the deployment host's client must match its server.
