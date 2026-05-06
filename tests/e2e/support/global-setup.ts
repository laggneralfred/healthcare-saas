import { execFileSync } from 'node:child_process';
import path from 'node:path';

export default async function globalSetup() {
    execFileSync('php', [path.resolve('tests/e2e/support/ensure-e2e-user.php')], {
        env: process.env,
        stdio: 'inherit',
    });
}
