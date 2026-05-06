import { expect, test } from '@playwright/test';
import { loginAsStaff, setupData } from './support/helpers';

test('patient submits assigned portal form and staff reviews without overwriting demographics', async ({ page }) => {
    const data = setupData('forms');

    await page.goto(String(data.portalFormsUrl));
    await expect(page).toHaveURL(/\/patient\/forms/);
    await expect(page.getByRole('heading', { name: /Forms/i })).toBeVisible();
    await expect(page.getByText('New Patient Intake')).toBeVisible();

    await page.getByRole('link', { name: /Complete form/i }).click();
    await expect(page.getByRole('heading', { name: /New Patient Intake/i })).toBeVisible();
    await page.getByLabel(/Date of birth/i).fill('1984-03-02');
    await page.getByLabel(/Main reason for visit/i).fill('E2E portal form submission');
    await page.getByLabel(/I agree the clinic may contact me/i).check();
    await page.getByRole('button', { name: /Submit form/i }).click();

    await expect(page).toHaveURL(/\/patient\/forms/);
    await expect(page.getByText(/submitted|reviewed by staff/i).first()).toBeVisible();

    await loginAsStaff(page);
    await page.goto(`/admin/patients/${data.patientId}`);

    await expect(page.getByText(String(data.patientName)).first()).toBeVisible();
    await expect(page.getByText('New Patient Intake').first()).toBeVisible();
    await expect(page.getByText(/Submitted/i).first()).toBeVisible();
    await expect(page.getByRole('button', { name: /Mark Form Reviewed/i })).toBeVisible();

    await page.getByRole('button', { name: /Mark Form Reviewed/i }).evaluate((button) => {
        (button as HTMLButtonElement).click();
    });

    await expect(page.getByText(/Reviewed/i).first()).toBeVisible();
    await expect(page.getByText(String(data.patientEmail)).first()).toBeVisible();
});
