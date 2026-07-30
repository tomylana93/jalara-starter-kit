import { expect, test as setup } from '@playwright/test';

setup('logs in as the superadmin', async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel('Email address').fill('playwright@example.test');
    await page
        .getByLabel('Password', { exact: true })
        .fill('Playwright-Test-Password-123!');
    await page.locator('[data-test="login-button"]').click();

    await expect(page).toHaveURL(/\/dashboard$/);
    await expect(
        page.getByText('Dashboard', { exact: true }).first(),
    ).toBeVisible();

    await page.goto('/settings/authentication');
    await expect(page).toHaveURL(/\/confirm-password$/);
    await page
        .getByLabel('Password', { exact: true })
        .fill('Playwright-Test-Password-123!');
    await page.locator('[data-test="confirm-password-button"]').click();
    await expect(page).toHaveURL(/\/settings\/authentication$/);

    await page.context().storageState({
        path: 'e2e/.auth/superadmin.json',
    });
});
