import { defineConfig, devices } from '@playwright/test';

/*
 * `e2e/run-tests.sh` owns a per-run temporary Laravel storage root and removes
 * it afterwards, so nothing a run writes lands in the application's real
 * storage. A direct `pnpm exec playwright test` invocation has no runner, and
 * falls back to the repository storage path it has always used.
 */
const storageRoot =
    process.env.LARAVEL_STORAGE_PATH ?? `${process.cwd()}/storage`;
const databasePath = `${storageRoot}/framework/testing/playwright.sqlite`;
const baseURL = 'http://127.0.0.1:8010';

/*
 * Set only when the runner created a uniquely named public symlink into the
 * isolated public storage root; otherwise the public disk keeps its usual
 * `APP_URL/storage` URL.
 */
const publicStorageLink = process.env.E2E_PUBLIC_STORAGE_LINK;

/*
 * Reverb runs on its own port so a development server started with
 * `composer run dev` (which uses 8080) never collides with a test run. The
 * matching VITE_REVERB_* values are baked into the bundle by the `test:e2e`
 * script, because the browser reads them at build time.
 */
const reverbPort = '8081';
const reverbKey = 'playwright-reverb-key';

const applicationEnvironment = {
    APP_ENV: 'testing',
    APP_URL: baseURL,
    LARAVEL_STORAGE_PATH: storageRoot,
    ...(publicStorageLink
        ? { FILESYSTEM_PUBLIC_URL: `${baseURL}/${publicStorageLink}` }
        : {}),
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: databasePath,
    SESSION_DRIVER: 'file',
    SETTINGS_CACHE_ENABLED: 'false',
    SUPER_ADMIN_NAME: 'Playwright Admin',
    SUPER_ADMIN_EMAIL: 'playwright@example.test',
    SUPER_ADMIN_PASSWORD: 'Playwright-Test-Password-123!',
    /* Queued broadcasts are drained by the worker started in run-server.sh. */
    BROADCAST_CONNECTION: 'reverb',
    QUEUE_CONNECTION: 'database',
    REVERB_APP_ID: 'playwright',
    REVERB_APP_KEY: reverbKey,
    REVERB_APP_SECRET: 'playwright-reverb-secret',
    REVERB_HOST: '127.0.0.1',
    REVERB_PORT: reverbPort,
    REVERB_SCHEME: 'http',
    REVERB_SERVER_HOST: '127.0.0.1',
    REVERB_SERVER_PORT: reverbPort,
};

export default defineConfig({
    testDir: './e2e',
    fullyParallel: false,
    workers: 1,
    retries: process.env.CI ? 2 : 0,
    reporter: process.env.CI ? 'github' : 'list',
    globalSetup: './e2e/global-setup.ts',
    use: {
        baseURL,
        trace: 'retain-on-failure',
    },
    webServer: {
        command: 'exec e2e/run-server.sh',
        url: baseURL,
        reuseExistingServer: false,
        env: {
            ...process.env,
            ...applicationEnvironment,
        },
    },
    projects: [
        {
            name: 'setup',
            testMatch: /auth\.setup\.ts/,
            use: { ...devices['Desktop Chrome'] },
        },
        {
            name: 'chromium',
            dependencies: ['setup'],
            testIgnore: /auth\.setup\.ts/,
            use: {
                ...devices['Desktop Chrome'],
                storageState: 'e2e/.auth/superadmin.json',
            },
        },
    ],
    metadata: { applicationEnvironment },
});
