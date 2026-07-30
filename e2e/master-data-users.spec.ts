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

test('navigates from master data into the user table', async ({ page }) => {
    await page.goto('/master-data');

    await expect(
        page.locator('[data-test="master-data-card-users"]'),
    ).toBeVisible();

    await page.locator('[data-test="master-data-card-users"]').click();

    await expect(page).toHaveURL(/\/master-data\/users$/);
    await expect(page.locator('table')).toBeVisible();
    await expect(page.locator('[data-test="table-search"]')).toBeVisible();
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

    await row.getByRole('link', { name: 'Edit' }).click();

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

test('drives the search filter through the server', async ({ page }) => {
    await page.goto('/master-data/users');

    await page.locator('[data-test="table-search"]').fill('no-such-user-here');

    await expect(page).toHaveURL(/search=no-such-user-here/);
    await expect(page.locator('[data-test="table-empty"]')).toBeVisible();
    await expect(page.getByText('No users found')).toBeVisible();
});

test('drives the page size through the server', async ({ page }) => {
    await page.goto('/master-data/users');

    await page.locator('[data-test="table-per-page"]').click();
    await page.locator('[data-test="table-per-page-option-25"]').click();

    await expect(page).toHaveURL(/perPage=25/);
    await expect(page.locator('[data-test="table-per-page"]')).toContainText(
        '25',
    );
});
