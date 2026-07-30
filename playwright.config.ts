import { defineConfig, devices } from '@playwright/test';

const databasePath = `${process.cwd()}/storage/framework/testing/playwright.sqlite`;
const baseURL = 'http://127.0.0.1:8010';
const applicationEnvironment = {
    APP_ENV: 'testing',
    APP_URL: baseURL,
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: databasePath,
    SESSION_DRIVER: 'file',
    SETTINGS_CACHE_ENABLED: 'false',
    SUPER_ADMIN_NAME: 'Playwright Admin',
    SUPER_ADMIN_EMAIL: 'playwright@example.test',
    SUPER_ADMIN_PASSWORD: 'Playwright-Test-Password-123!',
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
