<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Process\Process;

/**
 * migrate:safe — production-friendly migration runner.
 *
 * Usage
 * ─────
 *   php artisan migrate:safe               # run migrations with logging
 *   php artisan migrate:safe --snapshot    # dump DB first, then migrate
 *
 * On failure the command prints the exact psql restore command so the
 * operator knows how to roll back to the pre-migration snapshot.
 */
class MigrateSafeCommand extends Command
{
    protected $signature = 'migrate:safe
                            {--snapshot : Dump the database to storage/snapshots/ before migrating}
                            {--force : Force migrations to run in production without an interactive prompt}';

    protected $description = 'Run migrations safely: optional pre-migration snapshot, captured output, and restore instructions on failure';

    public function handle(): int
    {
        $snapshotPath = null;

        // ── Optional snapshot ──────────────────────────────────────────────────
        if ($this->option('snapshot')) {
            $snapshotPath = $this->takeSnapshot();

            if ($snapshotPath === null) {
                $this->error('Snapshot failed — aborting. No migrations were run.');
                return self::FAILURE;
            }

            $this->info("Snapshot written to: {$snapshotPath}");
        }

        // ── Run migrations ─────────────────────────────────────────────────────
        $this->info('Running migrations…');

        $buffer   = new BufferedOutput();
        $exitCode = Artisan::call('migrate', ['--force' => true], $buffer);
        $output   = $buffer->fetch();

        // Echo captured output so the operator sees what happened.
        $this->output->write($output);

        // ── Result handling ────────────────────────────────────────────────────
        if ($exitCode !== self::SUCCESS) {
            $this->newLine();
            $this->error('Migration failed!');

            if ($snapshotPath !== null) {
                $this->newLine();
                $this->line('<fg=yellow>Restore the database to its pre-migration state with:</>');
                $this->line($this->buildRestoreCommand($snapshotPath));
                $this->newLine();
                $this->comment("Snapshot file: {$snapshotPath}");
            } else {
                $this->comment('Tip: re-run with --snapshot next time to get an automatic restore command.');
            }

            return self::FAILURE;
        }

        $this->info('Migrations completed successfully.');
        return self::SUCCESS;
    }

    // ── Snapshot ───────────────────────────────────────────────────────────────

    private function takeSnapshot(): ?string
    {
        $config = $this->dbConfig();

        if ($config === null) {
            $this->error('migrate:safe --snapshot only supports PostgreSQL connections.');
            return null;
        }

        $dir = storage_path('snapshots');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $timestamp    = now()->format('Y-m-d_His');
        $snapshotPath = "{$dir}/pre-migration-{$timestamp}.sql";

        $this->info("Taking database snapshot ({$config['database']})…");

        $process = new Process(
            command: [
                'pg_dump',
                '--host',     $config['host'],
                '--port',     (string) ($config['port'] ?? 5432),
                '--username', $config['username'],
                '--dbname',   $config['database'],
                '--file',     $snapshotPath,
            ],
            env: ['PGPASSWORD' => $config['password']]
        );

        $process->setTimeout(300);  // 5 minutes for large databases
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('pg_dump failed: ' . $process->getErrorOutput());
            return null;
        }

        return $snapshotPath;
    }

    private function buildRestoreCommand(string $snapshotPath): string
    {
        $config = $this->dbConfig() ?? [];

        return sprintf(
            '  PGPASSWORD=%s psql --host=%s --port=%s --username=%s --dbname=%s < %s',
            escapeshellarg((string) ($config['password'] ?? '')),
            escapeshellarg((string) ($config['host'] ?? '127.0.0.1')),
            escapeshellarg((string) ($config['port'] ?? 5432)),
            escapeshellarg((string) ($config['username'] ?? '')),
            escapeshellarg((string) ($config['database'] ?? '')),
            escapeshellarg($snapshotPath)
        );
    }

    /** Returns the active PostgreSQL connection config, or null for other drivers. */
    private function dbConfig(): ?array
    {
        $connection = config('database.default');
        $config     = config("database.connections.{$connection}", []);

        if (($config['driver'] ?? '') !== 'pgsql') {
            return null;
        }

        return $config;
    }
}
