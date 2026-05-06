import { expect, test } from '@playwright/test';
import { loginAsStaff, setupData } from './support/helpers';

test('public new-patient interest is reviewed by staff and does not create a patient automatically', async ({ page }) => {
    const data = setupData('public-new-patient');
    const slug = String(data.practiceSlug);
    const email = `public-new-patient-${Date.now()}@example.test`;

    await page.goto(`/p/${slug}/new-patient`);
    await page.getByLabel(/First name/i).fill('Public');
    await page.getByLabel(/Last name/i).fill('Newpatient');
    await page.getByLabel(/Email/i).fill(email);
    await page.getByLabel(/Phone/i).fill('555-555-0101');
    await page.getByLabel(/Preferred service/i).fill('Acupuncture');
    await page.getByLabel(/Preferred days\/times/i).fill('Wednesday morning');
    await page.getByLabel(/Message/i).fill('E2E public new patient request');
    await page.getByLabel(/I understand the clinic may contact me/i).check();
    await page.getByRole('button', { name: /Send request/i }).click();

    await expect(page).toHaveURL(/\/new-patient\/thanks/);
    await expect(page.getByText(/received your request/i)).toBeVisible();

    await loginAsStaff(page);
    await page.goto('/admin/new-patient-interests');
    await expect(page.getByText(email)).toBeVisible();

    const countData = setupData('new-interest-count', ['public-new-patient', email]);
    expect(countData.patientCount).toBe(0);
});
