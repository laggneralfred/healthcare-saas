import { expect, test } from '@playwright/test';
import { expectAdminShell, expectLoginPage, loginAsStaff } from './support/helpers';

test('admin login page loads', async ({ page }) => {
    await page.goto('/admin/login');

    await expectLoginPage(page);
});

test('staff can log in, navigate core pages, and log out', async ({ page }) => {
    await loginAsStaff(page);
    await expectAdminShell(page);

    await page.goto('/admin/dashboard');
    await expect(page).toHaveURL(/\/admin\/dashboard/);
    await expectAdminShell(page);

    await page.goto('/admin/front-desk');
    await expect(page).toHaveURL(/\/admin\/front-desk/);
    await expect(page.getByRole('heading', { name: /Today/i }).first()).toBeVisible();
    await expect(page.getByText('Here is what needs your attention today.')).toBeVisible();

    for (const label of ['Today', 'Calendar', 'Patients', 'Visits', 'Follow-Up']) {
        await expect(page.getByRole('link', { name: new RegExp(label, 'i') }).first()).toBeVisible();
    }
    await expect(page.getByRole('button', { name: /Settings/i }).first()).toBeVisible();

    await page.goto('/admin/patients');
    await expect(page).toHaveURL(/\/admin\/patients/);
    await expect(page.getByRole('heading', { name: /Patients/i }).first()).toBeVisible();

    await page.goto('/admin/schedule');
    await expect(page).toHaveURL(/\/admin\/schedule/);
    await expect(page.getByRole('grid').first()).toBeVisible();
    await expect(page.getByRole('button', { name: 'week', exact: true })).toBeVisible();

    await page.goto('/admin/encounters');
    await expect(page).toHaveURL(/\/admin\/encounters/);
    await expect(page.getByRole('heading', { name: /Visits/i }).first()).toBeVisible();

    await page.goto('/admin/communications-dashboard');
    await expect(page).toHaveURL(/\/admin\/communications-dashboard/);
    await expect(page.getByRole('heading', { name: /Follow-Up/i }).first()).toBeVisible();
    await expect(page.getByText('Patients who may need a gentle follow-up will appear here.')).toBeVisible();

    await page.goto('/admin/practices');
    await expect(page).toHaveURL(/\/admin\/practices/);

    const practiceAction = page.getByRole('link', { name: /edit|view/i }).first();
    await expect(practiceAction).toBeVisible();
    await practiceAction.click();

    await expect(page).toHaveURL(/\/admin\/practices\/\d+(\/edit)?/);
    await expect(page.getByText(/Practice Name|Website Links|URL Slug/i).first()).toBeVisible();

    await page.getByRole('button', { name: /User menu/i }).click();
    await page.getByRole('button', { name: /sign out|log out|logout/i }).click();

    await expect(page).toHaveURL(/\/admin\/login/);
});
