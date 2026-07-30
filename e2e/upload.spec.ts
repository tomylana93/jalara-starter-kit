import { expect, test  } from '@playwright/test';
import type {Page} from '@playwright/test';

/** A genuinely valid 1x1 PNG, which satisfies the square icon rule. */
const pngFixture = {
    name: 'icon.png',
    mimeType: 'image/png',
    buffer: Buffer.from(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
        'base64',
    ),
};

/**
 * Hold the upload response open so the in-flight state can be observed.
 * Returns a function that lets the request finish.
 */
async function stallUpload(page: Page): Promise<() => void> {
    let release: () => void = () => undefined;
    const gate = new Promise<void>((resolve) => {
        release = resolve;
    });

    await page.route('**/settings/branding/assets/**', async (route) => {
        if (route.request().method() !== 'POST') {
            await route.fallback();

            return;
        }

        await gate;
        await route.continue();
    });

    return release;
}

const startUpload = async (page: Page) => {
    await page
        .locator('[data-test="branding-icon-input"]')
        .setInputFiles(pngFixture);
};

test('locks the page while an upload is in flight and releases it afterwards', async ({
    page,
}) => {
    await page.goto('/settings/branding');

    const release = await stallUpload(page);

    await startUpload(page);

    const field = page.locator('[data-test="branding-icon"]');
    await expect(field).toHaveAttribute('data-state', /uploading|processing/);

    // The guard modal is shown and the upload controls are locked.
    await expect(page.getByText('Upload in progress')).toBeVisible();
    await expect(
        page.locator('[data-test="branding-icon-input"]'),
    ).toBeDisabled();

    release();

    await expect(page.getByText('Upload in progress')).toBeHidden();
    await expect(field).toHaveAttribute('data-state', 'done');

    // Navigation works normally again once the upload has settled.
    await page.goto('/settings/general');
    await expect(page).toHaveURL(/\/settings\/general$/);
});

test('blocks in-app navigation while uploading', async ({ page }) => {
    await page.goto('/settings/branding');

    const release = await stallUpload(page);

    await startUpload(page);
    await expect(page.getByText('Upload in progress')).toBeVisible();

    /*
     * A real in-app link is an Inertia visit, so clicking one mid-upload must be
     * refused and leave the URL where it was.
     */
    await page
        .locator('nav a[href$="/settings"]')
        .first()
        .click({ force: true });

    await page.waitForTimeout(500);
    await expect(page).toHaveURL(/\/settings\/branding$/);

    release();
    await expect(page.getByText('Upload in progress')).toBeHidden();
});

test('warns the browser before a reload discards an upload', async ({
    page,
}) => {
    await page.goto('/settings/branding');

    const release = await stallUpload(page);

    await startUpload(page);
    await expect(page.getByText('Upload in progress')).toBeVisible();

    /*
     * Dispatching the event directly is the deterministic way to prove the
     * handler is installed: whether Chromium then renders its native dialog
     * depends on prior user interaction, which is outside our control.
     */
    const prevented = await page.evaluate(() => {
        const event = new Event('beforeunload', { cancelable: true });

        window.dispatchEvent(event);

        return event.defaultPrevented;
    });

    expect(prevented).toBe(true);

    release();
    await expect(page.getByText('Upload in progress')).toBeHidden();

    // With no upload running the page must be free to unload again.
    const preventedAfter = await page.evaluate(() => {
        const event = new Event('beforeunload', { cancelable: true });

        window.dispatchEvent(event);

        return event.defaultPrevented;
    });

    expect(preventedAfter).toBe(false);
});

test('abandons the upload rather than faking progress when history moves', async ({
    page,
}) => {
    await page.goto('/settings/general');
    await page.goto('/settings/branding');

    const release = await stallUpload(page);

    await startUpload(page);
    await expect(page.getByText('Upload in progress')).toBeVisible();

    /*
     * A popstate cannot be reliably cancelled, so the guarantee under test is
     * the honest one: the upload is cancelled and the lock is lifted rather
     * than left looking as though it were still running.
     */
    await page.goBack();

    await expect(page.getByText('Upload in progress')).toBeHidden();

    release();
});
