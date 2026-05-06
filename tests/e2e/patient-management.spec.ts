import { expect, test } from '@playwright/test';
import { loginAsStaff, setupData } from './support/helpers';

test('staff can open a patient and reach edit patient information', async ({ page }) => {
    const data = setupData('patient-management');

    await loginAsStaff(page);
    await page.goto(`/admin/patients/${data.patientId}`);

    await expect(page).toHaveURL(new RegExp(`/admin/patients/${data.patientId}`));
    await expect(page.getByRole('link', { name: /Edit Patient Information/i })).toBeVisible();
    await expect(page.getByRole('link', { name: 'New Visit', exact: true })).toBeVisible();
    await expect(page.getByRole('link', { name: /New Appointment/i })).toBeVisible();
    await expect(page.getByRole('button', { name: /Send Portal Link/i })).toBeVisible();
    await expect(page.getByRole('button', { name: /Send Forms/i })).toBeVisible();

    await page.getByRole('link', { name: /Edit Patient Information/i }).click();

    await expect(page).toHaveURL(new RegExp(`/admin/patients/${data.patientId}/edit`));
    await expect(page).not.toHaveURL(/\/admin\/dashboard/);
    await expect(page.getByRole('heading', { name: /Edit Patient|Patient/i }).first()).toBeVisible();
});
