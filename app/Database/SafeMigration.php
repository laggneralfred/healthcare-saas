<?php

namespace App\Database;

use Carbon\CarbonInterface;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Safe migration base class.
 *
 * All migrations after 2026_03_26_000005_create_migrations_log_table should
 * extend this class instead of Migration.
 *
 * Usage
 * ─────
 *   return new class extends SafeMigration
 *   {
 *       public function safeUp(): void { ... }   // required
 *       public function safeDown(): void { ... } // optional
 *   };
 *
 * For migrations that contain dropColumn / renameColumn / Schema::drop():
 *   return new class extends SafeMigration
 *   {
 *       protected bool $destructive = true;
 *       public function safeUp(): void { ... }
 *   };
 *
 * Guard rules
 * ───────────
 * 1. If safeUp() source contains a destructive call AND $destructive is false
 *    → throws immediately, migration never runs.
 * 2. If $destructive is true AND APP_ENV=production AND
 *    ALLOW_DESTRUCTIVE_MIGRATIONS is not set → throws.
 * 3. If a migration takes > 30 seconds, a warning is written to STDERR
 *    (migration still succeeds; check for table locks).
 */
abstract class SafeMigration extends Migration
{
    /** Set to true on migrations that intentionally drop columns / tables / rename. */
    protected bool $destructive = false;

    private const WARN_AFTER_SECONDS = 30;

    /** Method calls in safeUp() that signal a destructive migration. */
    private const DESTRUCTIVE_CALLS = [
        'dropColumn(',
        'renameColumn(',
        'Schema::drop(',
        'Schema::dropIfExists(',
    ];

    // ── Template methods ───────────────────────────────────────────────────────

    /** All schema work goes here — implement this instead of up(). */
    abstract public function safeUp(): void;

    /** Rollback work goes here — implement this instead of down(). */
    public function safeDown(): void {}

    // ── Final lifecycle methods ────────────────────────────────────────────────

    final public function up(): void
    {
        $this->guardDestructive();

        $startedAt = now();
        $logId     = $this->logStart('up', $startedAt);

        try {
            $this->safeUp();
            $this->logFinish($logId, $startedAt, true, null);
        } catch (\Throwable $e) {
            $this->logFinish($logId, $startedAt, false, $e->getMessage());
            throw $e;
        }
    }

    final public function down(): void
    {
        $startedAt = now();
        $logId     = $this->logStart('down', $startedAt);

        try {
            $this->safeDown();
            $this->logFinish($logId, $startedAt, true, null);
        } catch (\Throwable $e) {
            $this->logFinish($logId, $startedAt, false, $e->getMessage());
            throw $e;
        }
    }

    // ── Destructive-operation guard ────────────────────────────────────────────

    private function guardDestructive(): void
    {
        // When the dev has not opted in, scan safeUp() body for destructive calls.
        if (! $this->destructive) {
            $source = $this->safeUpSource();
            $found  = array_filter(
                self::DESTRUCTIVE_CALLS,
                fn (string $call) => str_contains($source, $call)
            );

            if (! empty($found)) {
                throw new \RuntimeException(sprintf(
                    "Migration [%s] contains destructive operation(s) [%s] "
                    . "but \$destructive is not set to true.\n"
                    . "Add 'protected bool \$destructive = true;' to the migration class to proceed.",
                    $this->migrationName(),
                    implode(', ', array_values($found))
                ));
            }

            return;
        }

        // When the dev has opted in, block in production unless explicitly allowed.
        if (app()->environment('production') && ! env('ALLOW_DESTRUCTIVE_MIGRATIONS')) {
            throw new \RuntimeException(sprintf(
                "Destructive migration [%s] is blocked in production.\n"
                . "Set ALLOW_DESTRUCTIVE_MIGRATIONS=true in your .env to proceed.\n"
                . "Remove the variable again immediately after the deployment.",
                $this->migrationName()
            ));
        }
    }

    // ── Logging ────────────────────────────────────────────────────────────────

    /**
     * Insert an in-flight row and return its ID (null if the table is not yet
     * created — happens for the very first migrations on a fresh install).
     */
    private function logStart(string $direction, CarbonInterface $startedAt): ?int
    {
        try {
            if (! Schema::hasTable('migrations_log')) {
                return null;
            }

            return (int) DB::table('migrations_log')->insertGetId([
                'migration'   => $this->migrationName(),
                'direction'   => $direction,
                'started_at'  => $startedAt,
                'finished_at' => null,
                'duration_ms' => null,
                'success'     => null,
                'error'       => null,
            ]);
        } catch (\Throwable) {
            return null;  // logging must never break migrations
        }
    }

    private function logFinish(
        ?int $logId,
        CarbonInterface $startedAt,
        bool $success,
        ?string $error
    ): void {
        if ($logId === null) {
            return;
        }

        try {
            $finishedAt = now();
            $durationMs = (int) $startedAt->diffInMilliseconds($finishedAt);

            DB::table('migrations_log')->where('id', $logId)->update([
                'finished_at' => $finishedAt,
                'duration_ms' => $durationMs,
                'success'     => $success,
                'error'       => $error,
            ]);

            if ($durationMs > self::WARN_AFTER_SECONDS * 1000) {
                fwrite(STDERR, sprintf(
                    "\n  ⚠  Migration [%s] took %ss (>%ss threshold). "
                    . "Check for table locks or consider a non-locking approach.\n",
                    $this->migrationName(),
                    round($durationMs / 1000, 1),
                    self::WARN_AFTER_SECONDS
                ));
            }
        } catch (\Throwable) {
            // silently skip — logging must never break migrations
        }
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Human-readable name for this migration, derived from the file that defines it.
     * For anonymous classes (the standard pattern), ReflectionClass resolves to the
     * file where `return new class extends SafeMigration { ... }` is written.
     * Includes the start line so multiple anonymous classes in the same file are distinct.
     */
    private function migrationName(): string
    {
        $rc = new \ReflectionClass($this);
        return pathinfo((string) $rc->getFileName(), PATHINFO_FILENAME)
            . '@L' . $rc->getStartLine();
    }

    /**
     * Read only the lines of safeUp() body to avoid false positives from
     * other methods or comments elsewhere in the same file.
     */
    private function safeUpSource(): string
    {
        try {
            $method = new \ReflectionMethod($this, 'safeUp');
            $file   = (string) $method->getFileName();
            $start  = $method->getStartLine() - 1;   // 0-indexed
            $length = $method->getEndLine() - $start;
            $lines  = file($file) ?: [];
            return implode('', array_slice($lines, $start, $length));
        } catch (\Throwable) {
            return '';
        }
    }
}
