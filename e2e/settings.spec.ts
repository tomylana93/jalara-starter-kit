import { expect, test } from '@playwright/test';

test('smokes and navigates every settings screen', async ({ page }) => {
    await page.goto('/settings');

    const cards = [
        'general',
        'authentication',
        'user-provisioning',
        'mail',
        'security',
        'branding',
    ];

    for (const card of cards) {
        await expect(
            page.locator(`[data-test="settings-card-${card}"]`),
        ).toBeVisible();
    }

    await page.locator('[data-test="settings-card-general"]').click();
    await expect(page).toHaveURL(/\/settings\/general$/);

    for (const path of cards.slice(1)) {
        await page.goto(`/settings/${path}`);
        await expect(page.locator('main')).toBeVisible();
    }
});

test('updates general settings and precognizes invalid input', async ({
    page,
}) => {
    await page.goto('/settings/general');
    await page.locator('#applicationName').fill('');
    await page.locator('#dateFormat').click();

    await expect(
        page.getByText('The application name field is required.'),
    ).toBeVisible();
    await expect(page.locator('#applicationName')).toHaveAttribute(
        'aria-invalid',
        'true',
    );

    await page.locator('#applicationName').fill('Jalara Playwright');
    await page.locator('[data-test="date-format-option-d/m/Y"]').click();
    await page.locator('[data-test="update-general-settings-button"]').click();

    await expect(page.getByText('General settings updated.')).toBeVisible();
    await expect(page).toHaveTitle(/Jalara Playwright/);
});

test('updates the authentication switch', async ({ page }) => {
    await page.goto('/settings/authentication');
    const switchControl = page.locator('#requireEmailVerification');
    const previousState = await switchControl.getAttribute('data-state');

    await switchControl.click();
    await page
        .locator('[data-test="update-authentication-settings-button"]')
        .click();

    await expect(
        page.getByText('Authentication settings updated.'),
    ).toBeVisible();
    await expect(switchControl).not.toHaveAttribute(
        'data-state',
        previousState ?? '',
    );
});

test('updates branding and preserves its attributes in dark mode', async ({
    page,
}) => {
    await page.goto('/settings/branding');
    await page.locator('#companyName').fill('Jalara E2E');
    await page.locator('#colorTheme-emerald').click();
    await page.locator('#fontPreset-system-serif').click();
    await page.locator('#appLayout-header').click();
    await page.locator('[data-test="update-branding-settings-button"]').click();

    await expect(page.getByText('Branding settings updated.')).toBeVisible();
    await expect(page.locator('html')).toHaveAttribute(
        'data-color-theme',
        'emerald',
    );
    await expect(page.locator('html')).toHaveAttribute(
        'data-font-preset',
        'system-serif',
    );

    await page.evaluate(() => localStorage.setItem('appearance', 'dark'));
    await page.reload();

    await expect(page.locator('html')).toHaveClass(/dark/);
    await expect(page.locator('html')).toHaveAttribute(
        'data-color-theme',
        'emerald',
    );
});

test('adds and removes the default password through confirmation', async ({
    page,
}) => {
    await page.goto('/settings/user-provisioning');
    await page.locator('#defaultPassword').fill('Jalara-Def4ult!');
    await page.locator('#defaultPassword_confirmation').fill('Jalara-Def4ult!');
    await page.locator('[data-test="update-default-password-button"]').click();

    await expect(
        page.locator('[data-test="default-password-status"]'),
    ).toContainText('Configured');

    await page.locator('[data-test="remove-default-password-button"]').click();
    await expect(page.getByText('Remove the default password?')).toBeVisible();
    await page
        .locator('[data-test="confirm-remove-default-password-button"]')
        .click();

    await expect(
        page.locator('[data-test="default-password-status"]'),
    ).toContainText('Not configured');
});
