import { execFileSync } from 'node:child_process';
import { expect, test } from '@playwright/test';

const PEER_NAME = 'Playwright Peer';

/**
 * Create the Active user the direct message is sent to, in the same isolated
 * environment the server under test uses.
 */
const createPeer = (environment: Record<string, string>): void => {
    execFileSync(
        'php',
        [
            'artisan',
            'tinker',
            '--execute',
            `App\\Models\\User::firstOrCreate(['email' => 'peer@example.test'], ['name' => '${PEER_NAME}', 'password' => 'Playwright-Test-Password-123!', 'email_verified_at' => now()]);`,
        ],
        { env: { ...process.env, ...environment }, stdio: 'inherit' },
    );
};

test('opens a direct message from the recipient search and stores it', async ({
    page,
}, testInfo) => {
    createPeer(
        testInfo.config.metadata.applicationEnvironment as Record<
            string,
            string
        >,
    );

    await page.goto('/chat');

    await expect(
        page.locator('[data-test="chat-conversations-empty"]'),
    ).toBeVisible();

    /* A conversation only exists once a valid message is actually sent. */
    await page.locator('[data-test="chat-recipient-search"]').fill('Playwright');
    await expect(
        page.locator('[data-test="chat-search-results"]'),
    ).toBeVisible();
    await page.getByText(PEER_NAME, { exact: true }).first().click();

    await page
        .locator('[data-test="chat-image-input"]')
        .setInputFiles('public/assets/images/branding/icon.png');
    await expect(page.locator('[data-test="chat-image-draft"]')).toBeVisible();

    await page
        .locator('[data-test="chat-composer-input"]')
        .fill('First line\nSecond line');
    await page.locator('[data-test="chat-send-button"]').click();

    await expect(
        page.locator('[data-test="chat-message-list"]'),
    ).toContainText('First line');
    await expect(page.locator('[data-test="chat-message-image"]')).toBeVisible();
    await expect(
        page.locator('[data-test="chat-conversation-list"]'),
    ).toContainText(PEER_NAME);

    /* The transcript is server-owned, so it survives a full reload. */
    await page.reload();
    await expect(
        page.locator('[data-test="chat-conversation-list"]'),
    ).toContainText(PEER_NAME);
});

test('closes and restores the chat surface from chat settings', async ({
    page,
}) => {
    await page.goto('/settings/chat');
    await page.locator('[data-test="chat-enabled-switch"]').click();
    await page.locator('[data-test="update-chat-settings-button"]').click();
    await expect(page.getByText('Chat settings updated.')).toBeVisible();

    await page.goto('/dashboard');
    await expect(page.getByRole('link', { name: 'Chat', exact: true })).toHaveCount(
        0,
    );

    const closed = await page.goto('/chat');
    expect(closed?.status()).toBe(403);

    await page.goto('/settings/chat');
    await page.locator('[data-test="chat-enabled-switch"]').click();
    await page.locator('[data-test="update-chat-settings-button"]').click();
    await expect(page.getByText('Chat settings updated.')).toBeVisible();

    await page.goto('/chat');
    await expect(page.locator('[data-test="chat-recipient-search"]')).toBeVisible();
});

test('records a Super Admin audit access without disturbing the participants', async ({
    page,
}) => {
    await page.goto('/chat/audit');

    await expect(page.locator('[data-test="chat-audit-notice"]')).toBeVisible();

    /* The audit surface has to be searchable by participant. */
    await page.locator('[data-test="chat-audit-search"]').fill('Zeno');
    await expect(page.locator('[data-test="chat-audit-empty"]')).toBeVisible();

    await page.locator('[data-test="chat-audit-search"]').fill(PEER_NAME);
    await expect(
        page.locator('[data-test="chat-audit-list"]'),
    ).toContainText(PEER_NAME);

    const firstRow = page.locator('[data-test^="chat-audit-open-"]').first();
    await expect(firstRow).toBeVisible();
    await firstRow.click();

    await expect(page.locator('[data-test="chat-audit-messages"]')).toBeVisible();
    await expect(page.locator('[data-test="chat-audit-logs"]')).toBeVisible();
});
