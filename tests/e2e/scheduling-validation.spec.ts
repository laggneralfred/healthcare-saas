import { expect, test } from '@playwright/test';
import { loginAsStaff, setupData } from './support/helpers';

test('appointment create blocks invalid practitioner times and saves a valid working-hour appointment', async ({ page }) => {
    test.setTimeout(60_000);

    const data = setupData('validation');

    await loginAsStaff(page);

    const initial = setupData('appointment-count', ['validation']);

    await page.goto(String(data.outsideCreateUrl));
    await expect(page.getByRole('heading', { name: /Appointment Request/i })).toBeVisible();
    await page.getByRole('button', { name: /Save & add another/i }).click();
    await expect(page).toHaveURL(/\/admin\/appointments\/create/);
    const afterOutsideAttempt = setupData('appointment-count', ['validation']);
    expect(afterOutsideAttempt.appointmentCount).toBe(initial.appointmentCount);

    await page.goto(String(data.blockedCreateUrl));
    await expect(page.getByRole('heading', { name: /Appointment Request/i })).toBeVisible();
    await page.getByRole('button', { name: /Save & add another/i }).click();
    await expect(page).toHaveURL(/\/admin\/appointments\/create/);
    const afterBlockedAttempt = setupData('appointment-count', ['validation']);
    expect(afterBlockedAttempt.appointmentCount).toBe(initial.appointmentCount);

    await page.goto(String(data.validCreateUrl));
    await expect(page.getByText('Schedule Context')).toBeVisible();
    await page.getByRole('button', { name: /Save & add another/i }).click();

    await expect
        .poll(() => setupData('appointment-count', ['validation']).appointmentCount, {
            intervals: [2_000],
            timeout: 45_000,
        })
        .toBe(Number(initial.appointmentCount) + 1);
});
