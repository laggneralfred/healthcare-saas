import { expect, test } from '@playwright/test';
import { loginAsStaff, setupData } from './support/helpers';

test('patient portal appointment request is submitted as a request, not a booking', async ({ page }) => {
    const data = setupData('portal');

    await page.goto(String(data.portalUrl));
    await expect(page).toHaveURL(/\/patient\/dashboard/);

    await page.getByRole('link', { name: /^Request appointment$/i }).last().click();
    await expect(page.getByRole('heading', { name: /Request an appointment/i })).toBeVisible();
    await expect(page.getByLabel(/What kind of visit/i)).toContainText(String(data.appointmentTypeName));

    await page.getByLabel(/What kind of visit/i).selectOption(String(data.appointmentTypeId));
    await expect(page).toHaveURL(new RegExp(`appointment_type_id=${data.appointmentTypeId}`));
    await expect(page.getByLabel(/Do you prefer a practitioner/i)).toContainText(String(data.practitionerName));

    await page.getByLabel(/Do you prefer a practitioner/i).selectOption(String(data.practitionerId));
    await page.getByLabel(/Preferred days and times/i).fill('Monday morning works best');
    await page.getByLabel(/Message/i).fill('E2E portal request');
    await page.getByRole('button', { name: /Send request/i }).click();

    await expect(page).toHaveURL(/\/patient\/dashboard/);
    await expect(page.getByText(/Your request was sent/i)).toBeVisible();
    await expect(page.getByText(/does not book an appointment automatically|request/i).first()).toBeVisible();

    await loginAsStaff(page);
    await page.goto('/admin/front-desk');
    await expect(page.getByText('Monday morning works best').first()).toBeVisible();

    const countData = setupData('appointment-count', ['portal']);
    expect(countData.appointmentCount).toBe(0);
});
