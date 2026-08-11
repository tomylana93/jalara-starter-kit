import { mkdirSync } from 'node:fs';
import path from 'node:path';

/*
 * Database preparation deliberately does NOT happen here.
 *
 * `run-server.sh` owns it, because it has to: the queue worker it starts can
 * only run against an already-migrated schema. Playwright starts the web server
 * before this hook, so migrating here as well dropped and recreated every table
 * underneath a worker that was already polling. The worker re-reads the
 * `illuminate:queue:restart` key every loop through the *database* cache store,
 * so a check landing inside that window died on `no such table: cache` and took
 * the worker down with it. The server itself survived, which is why this
 * surfaced only as the three queue-driven specs (chat images, notifications,
 * documentation images) failing, intermittently and never on CI, where retries
 * hid it.
 *
 * Anything a test needs from the database belongs in `run-server.sh`, so the
 * schema has exactly one writer.
 */
export default function globalSetup(): void {
    /* `auth.setup.ts` writes the shared storage state here. */
    mkdirSync(path.resolve('e2e/.auth'), { recursive: true });
}
