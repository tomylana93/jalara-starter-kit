import { expect, test } from '@playwright/test';

const provisionedEmail = 'master-data-user@example.test';

/**
 * Provisioning reads the configured default password, so the journey starts by
 * making sure one exists.
 */
test.beforeAll(async ({ browser }) => {
    const context = await browser.newContext({
        storageState: 'e2e/.auth/superadmin.json',
    });
    const page = await context.newPage();

    await page.goto('/settings/user-provisioning');
    await page.locator('#defaultPassword').fill('Playwright-Default-123!');
    await page
        .locator('#defaultPassword_confirmation')
        .fill('Playwright-Default-123!');
    await page.locator('[data-test="update-default-password-button"]').click();

    await expect(
        page.getByText('The default password was updated.'),
    ).toBeVisible();

    await context.close();
});

test('creates a user that starts active and then disables it', async ({
    page,
}) => {
    await page.goto('/master-data/users');
    await page.locator('[data-test="create-user-button"]').click();

    await expect(page).toHaveURL(/\/master-data\/users\/create$/);
    /* Creation offers no status control at all. */
    await expect(page.locator('#status')).toHaveCount(0);

    await page.locator('#name').fill('Playwright Managed');
    await page.locator('#email').fill(provisionedEmail);
    await page.locator('#role').click();
    await page.locator('[data-test="role-option-user"]').click();
    await page.locator('[data-test="save-user-button"]').click();

    await expect(page.getByText('The user has been created.')).toBeVisible();
    await expect(page).toHaveURL(/\/master-data\/users$/);

    await page.locator('[data-test="table-search"]').fill(provisionedEmail);
    await expect(page).toHaveURL(/search=master-data-user/);

    const row = page.locator('tbody tr', { hasText: 'Playwright Managed' });
    await expect(row).toBeVisible();
    await expect(row.getByText('Active', { exact: true })).toBeVisible();

    await row.getByRole('button', { name: 'Open row actions' }).click();
    await page.getByRole('menuitem', { name: 'Edit' }).click();

    await expect(page).toHaveURL(/\/master-data\/users\/[^/]+\/edit$/);

    await page.locator('#status').click();
    await page.locator('[data-test="status-option-disabled"]').click();
    await page.locator('[data-test="save-user-button"]').click();

    await expect(page.getByText('The user has been updated.')).toBeVisible();

    await page.locator('[data-test="table-search"]').fill(provisionedEmail);
    await expect(page).toHaveURL(/search=master-data-user/);
    await expect(
        page
            .locator('tbody tr', { hasText: 'Playwright Managed' })
            .getByText('Disabled', { exact: true }),
    ).toBeVisible();
});

/**
 * The one place a document is really rendered.
 *
 * Every other PDF assertion runs against `Pdf::fake()` in Pest, which proves
 * the template and its data but never opens a browser. Chromium only exists in
 * this job, so this is what would notice that the renderer itself is broken -
 * a missing browser, an unbuilt print stylesheet, a template Chromium refuses.
 */
test('downloads the selected users as a real pdf', async ({ page }) => {
    await page.goto('/master-data/users');

    await page.locator('[data-test="table-select-all"]').click();
    await page.locator('[data-test="export-users-button"]').click();

    const [download] = await Promise.all([
        page.waitForEvent('download'),
        page.locator('[data-test="export-users-pdf"]').click(),
    ]);

    expect(download.suggestedFilename()).toBe('users.pdf');

    const path = await download.path();
    const { readFileSync } = await import('node:fs');
    const contents = readFileSync(path);

    /* Every PDF starts with this signature; anything else is not a document. */
    expect(contents.subarray(0, 4).toString('latin1')).toBe('%PDF');
    expect(contents.byteLength).toBeGreaterThan(1000);
});
