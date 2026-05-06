import { expect, test } from '@playwright/test';
import { loginAsStaff, setupData } from './support/helpers';

test('preferred-practitioner appointment request opens calendar first and can be scheduled manually', async ({ page }) => {
    const data = setupData('preferred-request');
    const before = setupData('appointment-count', ['preferred-request']);

    await loginAsStaff(page);
    await page.goto('/admin/front-desk');

    const createFromRequest = page
        .locator(`a[href*="appointment_request_id=${data.appointmentRequestId}"]`)
        .first();

    await expect(createFromRequest).toHaveAttribute('href', /\/admin\/schedule/);
    await createFromRequest.click();

    await expect(page).toHaveURL(/\/admin\/schedule/);
    await expect(page).toHaveURL(new RegExp(`appointment_request_id=${data.appointmentRequestId}`));
    await expect(page).toHaveURL(new RegExp(`practitioner_id=${data.practitionerId}`));
    await expect(page.getByRole('grid').first()).toBeVisible();

    await page.locator('.fc-timegrid-slot-lane[data-time="09:00:00"]').first().click({ force: true });

    await expect(page).toHaveURL(/\/admin\/appointments\/create/);
    await expect(page).toHaveURL(new RegExp(`appointment_request_id=${data.appointmentRequestId}`));
    await expect(page).toHaveURL(new RegExp(`patient_id=${data.patientId}`));
    await expect(page).toHaveURL(new RegExp(`appointment_type_id=${data.appointmentTypeId}`));
    await expect(page).toHaveURL(new RegExp(`practitioner_id=${data.practitionerId}`));
    await expect(page).toHaveURL(/start_datetime=/);
    await expect(page.getByRole('heading', { name: /Appointment Request/i })).toBeVisible();

    await page.goto(String(data.createUrl));
    await expect(page.getByRole('heading', { name: /Appointment Request/i })).toBeVisible();
    await expect(page.getByText('Schedule Context')).toBeVisible();

    await page.getByRole('button', { name: /Save & add another/i }).click();

    await expect
        .poll(() => setupData('appointment-count', ['preferred-request']).appointmentCount)
        .toBe(Number(before.appointmentCount) + 1);
});
