import { defineConfig, devices } from '@playwright/test';

const baseURL = process.env.PLAYWRIGHT_BASE_URL ?? 'http://127.0.0.1:8002';
const parsedBaseURL = new URL(baseURL);
const localHosts = new Set(['127.0.0.1', 'localhost']);
const shouldStartLocalServer = localHosts.has(parsedBaseURL.hostname);
const serverHost = parsedBaseURL.hostname;
const serverPort = parsedBaseURL.port || (parsedBaseURL.protocol === 'https:' ? '443' : '80');

if (/practiqapp\.com/i.test(baseURL)) {
    throw new Error('Playwright e2e tests must not run against production Practiq hosts.');
}

export default defineConfig({
    testDir: './tests/e2e',
    globalSetup: './tests/e2e/support/global-setup.ts',
    timeout: 30_000,
    workers: 1,
    expect: {
        timeout: 5_000,
    },
    use: {
        baseURL,
        trace: 'on-first-retry',
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
    webServer: shouldStartLocalServer
        ? {
            command: `php artisan serve --host=${serverHost} --port=${serverPort}`,
            url: baseURL,
            reuseExistingServer: true,
            timeout: 30_000,
        }
        : undefined,
});
