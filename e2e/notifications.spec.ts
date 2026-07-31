import { execFileSync } from 'node:child_process';
import { expect, test  } from '@playwright/test';
import type {Page} from '@playwright/test';

/**
 * Send the sample notification through the same environment the server uses, so
 * it lands in the isolated database and broadcasts over the test Reverb port.
 */
const sendTestNotification = (
    environment: Record<string, string>,
    email: string,
): void => {
    execFileSync('php', ['artisan', 'notification:test', email], {
        env: { ...process.env, ...environment },
        stdio: 'inherit',
    });
};

/**
 * Set up a listener for the user's private notification channel subscription success.
 * Call this BEFORE page.goto() or page.reload() to register the WebSocket listener early enough,
 * and await the returned Promise after page.goto()/page.reload() (or before sending the notification).
 */
const setupSubscriptionTracker = (page: Page): Promise<void> => {
    return new Promise<void>((resolve, reject) => {
        const timeout = setTimeout(() => {
            reject(new Error('Timed out waiting for private channel subscription success'));
        }, 30_000);

        page.on('websocket', (ws) => {
            ws.on('framereceived', (event) => {
                try {
                    const payloadStr = typeof event.payload === 'string'
                        ? event.payload
                        : event.payload.toString('utf-8');
                    const data = JSON.parse(payloadStr);

                    if (
                        data.event === 'pusher_internal:subscription_succeeded' &&
                        typeof data.channel === 'string' &&
                        data.channel.startsWith('private-App.Models.User.')
                    ) {
                        clearTimeout(timeout);
                        resolve();
                    }
                } catch {
                    // Ignore non-JSON or other frames
                }
            });
        });
    });
};

test('delivers a notification to the bell without a page refresh', async ({
    page,
}, testInfo) => {
    const environment = testInfo.config.metadata
        .applicationEnvironment as Record<string, string>;

    /*
     * The bundle under test must be the one built by e2e/run-tests.sh, so the
     * browser has to reach Reverb on the isolated test port rather than the
     * port a local development session uses.
     */
    const socketUrls: string[] = [];
    page.on('websocket', (socket) => socketUrls.push(socket.url()));

    const subscriptionPromise = setupSubscriptionTracker(page);

    await page.goto('/dashboard');

    await expect
        .poll(() => socketUrls, { timeout: 30_000 })
        .toEqual(
            expect.arrayContaining([
                expect.stringContaining(
                    `${environment.REVERB_HOST}:${environment.REVERB_PORT}`,
                ),
            ]),
        );

    const bell = page.locator('[data-test="notification-bell"]');
    await expect(bell).toBeVisible();
    await expect(
        page.locator('[data-test="notification-badge"]'),
    ).toHaveCount(0);

    // Wait for the subscription to succeed before sending the notification
    await subscriptionPromise;

    /*
     * Nothing reloads after this point: the badge may only appear because the
     * queued broadcast reached the browser over the WebSocket connection.
     */
    sendTestNotification(environment, environment.SUPER_ADMIN_EMAIL);

    await expect(page.locator('[data-test="notification-badge"]')).toHaveText(
        '1',
        { timeout: 30_000 },
    );

    await bell.click();
    await expect(page.getByText('Test notification')).toBeVisible();
});

test('lists the notification on its own page and marks it as read', async ({
    page,
}, testInfo) => {
    const environment = testInfo.config.metadata
        .applicationEnvironment as Record<string, string>;

    const subscriptionPromise = setupSubscriptionTracker(page);

    await page.goto('/notifications');

    // Wait for the subscription to succeed before sending the notification
    await subscriptionPromise;

    /*
     * The notification is queued, so the worker — not the command above —
     * writes its database row. The bell updates over the WebSocket the moment
     * that row exists, which is the only safe point to start marking things as
     * read; acting earlier would leave the late row unread.
     */
    const badge = page.locator('[data-test="notification-badge"]');
    const unreadBefore =
        (await badge.count()) > 0 ? Number(await badge.innerText()) : 0;

    sendTestNotification(environment, environment.SUPER_ADMIN_EMAIL);

    await expect(badge).toHaveText(String(unreadBefore + 1), {
        timeout: 30_000,
    });

    /* The list is server-rendered, so it needs a fetch to pick the row up. */
    await page.reload();

    const rows = page.locator('[data-test="notification-list"] > li');
    await expect(rows.first()).toBeVisible();
    await expect(rows.first()).toHaveAttribute('data-unread', 'true');

    await page.locator('[data-test="notification-filter-unread"]').click();
    await expect(page).toHaveURL(/\/notifications\?filter=unread$/);
    await expect(rows.first()).toBeVisible();

    await page.locator('[data-test="notification-mark-all"]').click();

    /* Nothing is unread any more, so the unread filter empties out. */
    await expect(
        page.locator('[data-test="notification-empty"]'),
    ).toBeVisible();

    await page.locator('[data-test="notification-filter-all"]').click();
    await expect(
        page.locator('[data-test="notification-list"] > li').first(),
    ).toHaveAttribute('data-unread', 'false');
});
