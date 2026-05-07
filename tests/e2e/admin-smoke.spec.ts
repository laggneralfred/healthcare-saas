import { expect, test } from '@playwright/test';
import { expectAdminShell, expectLoginPage, loginAsStaff, setupData } from './support/helpers';

test('admin login page loads', async ({ page }) => {
    await page.goto('/admin/login');

    await expectLoginPage(page);
});

test('staff can log in, navigate core pages, and log out', async ({ page }) => {
    const data = setupData('admin-smoke');

    await loginAsStaff(page);
    await expectAdminShell(page);

    await page.goto('/admin/dashboard');
    await expect(page).toHaveURL(/\/admin\/dashboard/);
    await expectAdminShell(page);

    await page.goto('/admin/front-desk');
    await expect(page).toHaveURL(/\/admin\/front-desk/);
    await expect(page.getByRole('heading', { name: /Today/i }).first()).toBeVisible();
    await expect(page.getByText('Here is what needs your attention today.')).toBeVisible();
    await expect(page.getByText('Setup Checklist').first()).toBeVisible();
    await expect(page.getByText(/Practice profile|Public website links/i).first()).toBeVisible();

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

    await page.goto(`/admin/practices/${data.practiceId}/edit`);
    await expect(page).toHaveURL(/\/admin\/practices\/\d+(\/edit)?/);
    await expect(page.getByText(/Practice Name|Website Links|URL Slug/i).first()).toBeVisible();

    await page.goto('/admin/practitioner-review');
    await expect(page).toHaveURL(/\/admin\/practitioner-review/);
    await expect(page.getByText('Founding Practitioner Review Program')).toBeVisible();
    await page.getByLabel('Practice type').fill('Acupuncture');
    await page.getByLabel('Clinic size').fill('Solo practitioner');
    await page.getByLabel('Current system / tools').fill('Google Calendar');
    await page.getByLabel('First impression').fill('Clear enough to test.');
    await page.getByLabel('Was it clear what to do first?').selectOption('5');
    await page.getByLabel('Did website links make sense?').fill('Yes');
    await page.getByLabel('Online scheduling preference').fill('Staff-reviewed requests');
    await page.getByLabel('Online intake forms feedback').fill('Useful');
    await page.getByLabel('Most useful part').fill('Website links');
    await page.getByLabel('What would make the first week easier?').fill('A shorter setup path');
    await page.getByLabel('What would make you more likely to subscribe?').fill('Confidence in setup');
    await page.getByLabel(/I understand the review discount/i).check();
    await page.getByRole('button', { name: 'Submit review' }).click();
    await expect(page.getByText('Latest submitted response')).toBeVisible();

    await page.getByRole('button', { name: /User menu/i }).click();
    await page.getByRole('button', { name: /sign out|log out|logout/i }).click();

    await expect(page).toHaveURL(/\/admin\/login/);
});
