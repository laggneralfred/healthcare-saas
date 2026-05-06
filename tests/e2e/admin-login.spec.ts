import { expect, test } from '@playwright/test';

test('admin login page loads', async ({ page }) => {
    await page.goto('/admin/login');

    await expect(page).toHaveURL(/\/admin\/login/);
    await expect(page.locator('input[type="email"], input[name="email"]').first()).toBeVisible();
    await expect(page.locator('input[type="password"], input[name="password"]').first()).toBeVisible();
});
