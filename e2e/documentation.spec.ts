import { expect, test } from '@playwright/test';

test('creates, publishes, reads, and finds internal documentation', async ({
    page,
}) => {
    const title = 'Playwright Guide';
    const breadcrumb = page.getByRole('navigation', { name: 'breadcrumb' });

    await page.goto('/documentation/manage');
    await expect(breadcrumb).toContainText('Documentation');
    await expect(breadcrumb).toContainText('Manage documentation');

    await page.locator('#category-name').fill('Testing');
    await page.getByRole('button', { name: 'Add category' }).click();
    await expect(page.getByText('Testing', { exact: true })).toBeVisible();

    await page.locator('[data-test="create-documentation"]').click();
    await expect(page).toHaveURL(/\/documentation\/manage\/create$/);
    await expect(breadcrumb).toContainText('New documentation');

    await page.locator('#title').fill(title);
    await page.locator('[data-test="documentation-category-trigger"]').click();
    await page
        .locator('[data-test="documentation-category-option"]')
        .filter({ hasText: 'Testing' })
        .click();
    await page.locator('[data-test="documentation-status-trigger"]').click();
    await page.locator('[data-test="documentation-status-published"]').click();
    const editor = page.locator('[data-test="rich-text-editor"] [contenteditable="true"]');
    await editor.fill('This document can be found through the global search.');
    await editor.focus();

    // Select all text to format it
    await page.keyboard.press('Control+A');

    const boldButton = page.locator('[data-test="rich-text-toggle-bold"]');
    await boldButton.click();

    // Positive case: Assert Bold button has data-state="on"
    await expect(boldButton).toHaveAttribute('data-state', 'on');

    // Toggle Bold off
    await boldButton.click();
    await expect(boldButton).toHaveAttribute('data-state', 'off');

    // Negative case: Toggle Code Block on and assert Bold button is disabled
    const codeBlockButton = page.locator('[data-test="rich-text-toggle-codeBlock"]');
    await codeBlockButton.click();
    await expect(boldButton).toBeDisabled();

    // Toggle Code Block off
    await codeBlockButton.click();

    await page.locator('[data-test="save-documentation"]').click();

    /* A saved document returns the author to the list it came from. */
    await expect(page).toHaveURL(/\/documentation\/manage$/);
    await expect(page.getByText('The documentation has been created.')).toBeVisible();

    const row = page.locator('[data-test="documentation-row"]', {
        hasText: title,
    });
    await expect(row).toBeVisible();
    await expect(row).toContainText('Testing');
    await expect(row).toContainText('Published');

    await page.goto('/documentation');
    await expect(page.getByRole('heading', { name: title })).toBeVisible();
    await page
        .locator('a[href="/documentation/playwright-guide"]')
        .click();
    await expect(page.getByRole('heading', { name: title })).toBeVisible();
    await expect(breadcrumb).toContainText(title);
    await expect(
        page.getByText(
            'This document can be found through the global search.',
            { exact: true },
        ),
    ).toBeVisible();

    await page.keyboard.press('Control+K');
    const search = page.getByPlaceholder('Search navigation or documentation…');
    await search.fill('Playwright');
    await page
        .getByRole('dialog')
        .getByText(title, { exact: true })
        .click();

    await expect(page).toHaveURL(/\/documentation\/playwright-guide$/);
});

/** A genuinely valid 1x1 PNG the upload endpoint will accept. */
const imageFixture = {
    name: 'diagram.png',
    mimeType: 'image/png',
    buffer: Buffer.from(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
        'base64',
    ),
};

test('uploads an editor image and serves it to a reader', async ({ page }) => {
    const title = 'Playwright Image Guide';
    const alt = 'Approval flow diagram';

    await page.goto('/documentation/manage');
    await page.locator('#category-name').fill('Screenshots');
    await page.getByRole('button', { name: 'Add category' }).click();
    await expect(page.getByText('Screenshots', { exact: true })).toBeVisible();

    await page.locator('[data-test="create-documentation"]').click();
    await page.locator('#title').fill(title);
    await page.locator('[data-test="documentation-category-trigger"]').click();
    await page
        .locator('[data-test="documentation-category-option"]')
        .filter({ hasText: 'Screenshots' })
        .click();
    await page.locator('[data-test="documentation-status-trigger"]').click();
    await page.locator('[data-test="documentation-status-published"]').click();

    /* Alt text is collected before the file, so the picker is the second step. */
    await page.locator('[data-test="rich-text-image-trigger"]').click();
    await page.locator('[data-test="rich-text-image-alt"]').fill(alt);
    await page.locator('[data-test="rich-text-image-submit"]').click();
    await page
        .locator('[data-test="rich-text-image-input"]')
        .setInputFiles(imageFixture);

    /* The node only appears once the queue has published a URL to point at. */
    const inserted = page.locator('[data-test="rich-text-editor"] img');
    await expect(inserted).toBeVisible({ timeout: 30_000 });
    await expect(inserted).toHaveAttribute('alt', alt);

    await page.locator('[data-test="save-documentation"]').click();
    await expect(page).toHaveURL(/\/documentation\/manage$/);
    await expect(
        page.getByText('The documentation has been created.'),
    ).toBeVisible();

    await page.goto('/documentation/playwright-image-guide');
    const rendered = page.locator('.rich-text-content img');
    await expect(rendered).toBeVisible();
    await expect(rendered).toHaveAttribute('alt', alt);
});
