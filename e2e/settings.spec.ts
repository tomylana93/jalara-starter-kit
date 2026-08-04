import { expect, test } from '@playwright/test';

test('previews and persists branding in light and dark authentication layouts', async ({
    page,
}) => {
    await page.goto('/settings/branding');
    const storedTheme = await page.locator('html').getAttribute(
        'data-color-theme',
    );
    await page.locator('#companyName').fill('Jalara E2E');
    await page.locator('#colorTheme-teal').click();
    await page.locator('#fontPair-playfair-display-source-sans').click();
    await page.locator('#appLayout-header').click();

    for (const preview of [
        'identity-preview',
        'auth-preview',
        'app-preview',
    ]) {
        await expect(page.locator(`[data-test="${preview}"]`).first()).toHaveAttribute(
            'data-color-theme',
            'teal',
        );
    }

    await expect(page.locator('html')).toHaveAttribute(
        'data-color-theme',
        storedTheme ?? 'neutral',
    );

    await page.locator('[data-test="update-branding-settings-button"]').click();

    await expect(page.getByText('Branding settings updated.')).toBeVisible();
    await expect(page.locator('html')).toHaveAttribute(
        'data-color-theme',
        'teal',
    );
    await expect(page.locator('html')).toHaveAttribute(
        'data-font-pair',
        'playfair-display-source-sans',
    );

    await page.evaluate(() => localStorage.setItem('appearance', 'dark'));
    await page.reload();

    await expect(page.locator('html')).toHaveClass(/dark/);
    await expect(page.locator('html')).toHaveAttribute(
        'data-color-theme',
        'teal',
    );

    await page.context().clearCookies();
    await page.goto('/login');
    await expect(page.locator('html')).toHaveClass(/dark/);
    await expect(page.locator('html')).toHaveAttribute(
        'data-color-theme',
        'teal',
    );
    const darkBackground = await page.locator('body').evaluate(
        (element) => getComputedStyle(element).backgroundColor,
    );

    await page.evaluate(() => localStorage.setItem('appearance', 'light'));
    await page.reload();

    await expect(page.locator('html')).not.toHaveClass(/dark/);
    await expect(page.locator('html')).toHaveAttribute(
        'data-color-theme',
        'teal',
    );
    await expect(page.locator('body')).not.toHaveCSS(
        'background-color',
        darkBackground,
    );
});
