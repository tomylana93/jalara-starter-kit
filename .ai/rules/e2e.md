---
paths:
  - 'e2e/**'
---

# E2E

## run-server.sh is the only writer of the e2e database schema
`e2e/run-server.sh` owns `migrate:fresh` and superadmin setup, because it must migrate before it starts `queue:work`. Never migrate, seed, or truncate the SQLite file from `global-setup.ts`, a spec, or a fixture: Playwright starts the web server before `globalSetup`, so a second writer drops tables underneath the live worker. The worker re-reads `illuminate:queue:restart` every loop through the database cache store, dies on `no such table: cache`, and only the queue-driven specs fail — intermittently, and never on CI, where `retries: 2` hides it. Need seed data? Add it to `run-server.sh`.
