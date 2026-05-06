import { execFileSync } from 'node:child_process';
import path from 'node:path';
import { expect, Page } from '@playwright/test';

export const adminEmail = process.env.E2E_ADMIN_EMAIL ?? 'admin@healthcare.test';
export const adminPassword = process.env.E2E_ADMIN_PASSWORD ?? 'password';

export type E2EData = Record<string, string | number | null>;

export function setupData(scenario = 'base', extraArgs: string[] = []): E2EData {
    const output = execFileSync(
        'php',
        [path.resolve('tests/e2e/support/setup-data.php'), scenario, ...extraArgs],
        {
            env: process.env,
            encoding: 'utf8',
        },
    );

    return JSON.parse(output);
}

export async function expectLoginPage(page: Page) {
    await expect(page).toHaveURL(/\/admin\/login/);
    await expect(page.locator('input[type="email"], input[name="email"]').first()).toBeVisible();
    await expect(page.locator('input[type="password"], input[name="password"]').first()).toBeVisible();
}

export async function loginAsStaff(page: Page) {
    await page.goto('/admin/login');
    await expectLoginPage(page);

    await page.locator('input[type="email"], input[name="email"]').first().fill(adminEmail);
    await page.locator('input[type="password"], input[name="password"]').first().fill(adminPassword);
    await page.getByRole('button', { name: /sign in|log in|login/i }).click();

    await expect(page).not.toHaveURL(/\/admin\/login/);
}

export async function expectAdminShell(page: Page) {
    await expect(page.getByRole('navigation').first()).toBeVisible();
    await expect(page.getByRole('link', { name: /Today/i }).first()).toBeVisible();
}

