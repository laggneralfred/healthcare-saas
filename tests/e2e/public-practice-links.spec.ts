import { expect, test } from '@playwright/test';
import { loginAsStaff, setupData } from './support/helpers';

test('practice settings exposes public website links and snippets', async ({ page }) => {
    const data = setupData('public-links');
    const slug = String(data.practiceSlug);

    await loginAsStaff(page);
    await page.goto(`/admin/practices/${data.practiceId}/edit`);

    await expect(page.getByText('Website Links')).toBeVisible();
    await expect(page.getByText('New Patient Request').first()).toBeVisible();
    await expect(page.getByText('Existing Patient Access').first()).toBeVisible();
    await expect(page.getByText('Request Appointment').first()).toBeVisible();
    await expect(page.getByText(`/p/${slug}/new-patient`).first()).toBeVisible();
    await expect(page.getByText(`/p/${slug}/existing-patient`).first()).toBeVisible();
    await expect(page.getByText(`/p/${slug}/request-appointment`).first()).toBeVisible();
    await expect(page.getByText(`target="_blank" rel="noopener"`).first()).toBeVisible();

    await page.goto(`/p/${slug}/new-patient`);
    await expect(page.getByRole('heading', { name: /Request to become a new patient/i })).toBeVisible();

    await page.goto(`/p/${slug}/existing-patient`);
    await expect(page.getByRole('heading', { name: /Existing patient access/i })).toBeVisible();

    await page.goto(`/p/${slug}/request-appointment`);
    await expect(page).toHaveURL(new RegExp(`/p/${slug}/existing-patient`));
});
