import { execFileSync } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';
import path from 'node:path';

const databasePath = path.resolve(
    'storage/framework/testing/playwright.sqlite',
);
const environment = {
    ...process.env,
    APP_ENV: 'testing',
    APP_URL: 'http://127.0.0.1:8010',
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: databasePath,
    SESSION_DRIVER: 'file',
    SETTINGS_CACHE_ENABLED: 'false',
    SUPER_ADMIN_NAME: 'Playwright Admin',
    SUPER_ADMIN_EMAIL: 'playwright@example.test',
    SUPER_ADMIN_PASSWORD: 'Playwright-Test-Password-123!',
};

export default function globalSetup(): void {
    mkdirSync(path.dirname(databasePath), { recursive: true });
    mkdirSync(path.resolve('e2e/.auth'), { recursive: true });
    writeFileSync(databasePath, '');

    execFileSync(
        'php',
        ['artisan', 'migrate:fresh', '--force', '--no-interaction'],
        {
            env: environment,
            stdio: 'inherit',
        },
    );
    execFileSync(
        'php',
        [
            'artisan',
            'auth:init-superadmin',
            '--reset-password',
            '--no-interaction',
        ],
        {
            env: environment,
            stdio: 'inherit',
        },
    );
}
