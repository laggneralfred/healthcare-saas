import { expect, test } from '@playwright/test';
import { loginAsStaff, setupData } from './support/helpers';

test('no-preference appointment request opens form first and suggestions only fill the form', async ({ page }) => {
    const data = setupData('no-preference-request');
    const before = setupData('appointment-count', ['no-preference-request']);

    await loginAsStaff(page);
    await page.goto('/admin/front-desk');

    const createFromRequest = page
        .locator(`a[href*="appointment_request_id=${data.appointmentRequestId}"]`)
        .first();

    await expect(createFromRequest).toHaveAttribute('href', /\/admin\/appointments\/create/);
    await createFromRequest.click();

    await expect(page).toHaveURL(/\/admin\/appointments\/create/);
    await expect(page).toHaveURL(new RegExp(`appointment_request_id=${data.appointmentRequestId}`));
    await expect(page.getByRole('heading', { name: /Appointment Request/i })).toBeVisible();
    await expect(page.getByText('Schedule Context')).toBeVisible();
    await expect(page.getByText('Suggested Openings')).toBeVisible();

    await page.getByRole('link', { name: /Use this time/i }).first().click();

    await expect(page).toHaveURL(/\/admin\/appointments\/create/);
    await expect(page).toHaveURL(new RegExp(`appointment_request_id=${data.appointmentRequestId}`));
    await expect(page).toHaveURL(/start_datetime=/);
    await expect(page).toHaveURL(/practitioner_id=/);
    await expect(page.getByRole('heading', { name: /Appointment Request/i })).toBeVisible();

    const after = setupData('appointment-count', ['no-preference-request']);
    expect(after.appointmentCount).toBe(before.appointmentCount);
});
