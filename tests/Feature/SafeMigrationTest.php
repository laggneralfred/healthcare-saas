<?php

namespace Tests\Feature;

use App\Database\SafeMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Tests for the SafeMigration guard system.
 *
 * Migration fixture classes are defined at the bottom of this file.
 * SafeMigration::safeUpSource() reads the lines of the safeUp() method
 * using reflection, so the text must literally appear in this file.
 */
class SafeMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure a clean env-var state before each test.
        putenv('ALLOW_DESTRUCTIVE_MIGRATIONS=');
        unset($_ENV['ALLOW_DESTRUCTIVE_MIGRATIONS']);
    }

    protected function tearDown(): void
    {
        // Restore testing environment and clean env var.
        $this->app['env'] = 'testing';
        putenv('ALLOW_DESTRUCTIVE_MIGRATIONS=');
        unset($_ENV['ALLOW_DESTRUCTIVE_MIGRATIONS']);
        parent::tearDown();
    }

    // ── Guard: destructive without $destructive flag ────────────────────────────

    /**
     * A migration whose safeUp() calls dropColumn() without $destructive = true
     * must throw before any schema work is attempted — in every environment.
     */
    public function test_destructive_migration_is_blocked_when_flag_not_set(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/\$destructive is not set to true/');

        (new UnflaggedDropColumnMigration())->up();
    }

    public function test_destructive_migration_blocked_when_flag_not_set_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/\$destructive is not set to true/');

        (new UnflaggedDropColumnMigration())->up();
    }

    public function test_rename_column_is_also_blocked_without_flag(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/\$destructive is not set to true/');

        (new UnflaggedRenameColumnMigration())->up();
    }

    // ── Guard: $destructive = true in production, no env var ──────────────────

    /**
     * Even when $destructive = true, a production deploy without the env var
     * must be blocked — the guard is the last line of defense against accidents.
     */
    public function test_flagged_destructive_migration_blocked_in_production_without_env_var(): void
    {
        $this->app['env'] = 'production';
        putenv('ALLOW_DESTRUCTIVE_MIGRATIONS=');
        unset($_ENV['ALLOW_DESTRUCTIVE_MIGRATIONS']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Destructive migration .* is blocked in production/');

        (new FlaggedDestructiveMigration())->up();
    }

    // ── Guard: $destructive = true in production WITH env var ─────────────────

    /**
     * When the operator sets ALLOW_DESTRUCTIVE_MIGRATIONS=true, the migration
     * must be allowed to run.
     */
    public function test_flagged_destructive_migration_allowed_in_production_with_env_var(): void
    {
        $this->app['env'] = 'production';
        putenv('ALLOW_DESTRUCTIVE_MIGRATIONS=true');
        $_ENV['ALLOW_DESTRUCTIVE_MIGRATIONS'] = 'true';

        // FlaggedDestructiveMigration::safeUp() is a no-op — it only tests the guard,
        // not actual schema work.
        $this->expectNotToPerformAssertions();

        (new FlaggedDestructiveMigration())->up();
    }

    /**
     * Outside production the env var is not required — developers can run
     * destructive migrations locally without setting it.
     */
    public function test_flagged_destructive_migration_allowed_in_non_production_without_env_var(): void
    {
        // Default env is 'testing' in this suite.
        $this->app['env'] = 'testing';

        $this->expectNotToPerformAssertions();

        (new FlaggedDestructiveMigration())->up();
    }

    // ── migrations_log table is written to ────────────────────────────────────

    /**
     * A successful migration must write a row to migrations_log with
     * success = true and a non-null duration.
     */
    public function test_migrations_log_is_written_on_successful_up(): void
    {
        $migration = new SafeAddTableMigration();
        $migration->up();

        $log = DB::table('migrations_log')
            ->where('direction', 'up')
            ->where('success', true)
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($log, 'Expected a migrations_log row for the up() run');
        $this->assertSame('up', $log->direction);
        $this->assertTrue((bool) $log->success);
        $this->assertNotNull($log->finished_at);
        $this->assertNotNull($log->duration_ms);
        $this->assertNull($log->error);

        // Cleanup — the migration created a table.
        Schema::dropIfExists('safe_migration_smoke_test');
    }

    /**
     * A failed migration must write success = false with the error message.
     */
    public function test_migrations_log_captures_failure(): void
    {
        $this->expectException(\Throwable::class);

        try {
            (new SafeAlwaysFailingMigration())->up();
        } finally {
            $log = DB::table('migrations_log')
                ->where('success', false)
                ->orderByDesc('id')
                ->first();

            $this->assertNotNull($log, 'Expected a failure row in migrations_log');
            $this->assertFalse((bool) $log->success);
            $this->assertNotNull($log->error, 'Error message must be stored');
        }
    }

    /**
     * Running safeDown() also writes a migrations_log row.
     */
    public function test_migrations_log_is_written_on_down(): void
    {
        $migration = new SafeAddTableMigration();
        $migration->up();
        $migration->down();

        $downLog = DB::table('migrations_log')
            ->where('direction', 'down')
            ->where('success', true)
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($downLog);
        $this->assertSame('down', $downLog->direction);
        $this->assertTrue((bool) $downLog->success);
    }

    // ── Safe migrations (no destructive ops) always pass the guard ─────────────

    public function test_safe_migration_without_destructive_ops_runs_without_flag(): void
    {
        // Should not throw — no destructive calls in safeUp().
        $migration = new SafeAddTableMigration();
        $migration->up();

        $this->assertTrue(Schema::hasTable('safe_migration_smoke_test'));

        $migration->down();
        $this->assertFalse(Schema::hasTable('safe_migration_smoke_test'));
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Migration fixtures (defined here so ReflectionMethod::getFileName() resolves
// to this file — SafeMigration reads the source lines of safeUp() to detect
// destructive operations).
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Has dropColumn() in safeUp() but $destructive is false (default).
 * Must be blocked by the guard.
 */
class UnflaggedDropColumnMigration extends SafeMigration
{
    public function safeUp(): void
    {
        // Guard should throw before this body executes.
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
}

/**
 * Has renameColumn() in safeUp() but $destructive is false.
 * Must be blocked by the guard.
 */
class UnflaggedRenameColumnMigration extends SafeMigration
{
    public function safeUp(): void
    {
        // Guard should throw before this body executes.
        Schema::table('patients', function (Blueprint $table) {
            $table->renameColumn('email', 'contact_email');
        });
    }
}

/**
 * Has $destructive = true but safeUp() is intentionally a no-op.
 * Used to test the env-var guard in isolation (avoids real schema changes).
 */
class FlaggedDestructiveMigration extends SafeMigration
{
    protected bool $destructive = true;

    public function safeUp(): void
    {
        // Intentionally empty — this fixture only exercises the guard logic.
    }
}

/**
 * A perfectly safe migration: adds a temp table in safeUp(), drops it in safeDown().
 * Used to verify that migrations_log is written and Schema::create() is not blocked.
 */
class SafeAddTableMigration extends SafeMigration
{
    public function safeUp(): void
    {
        Schema::create('safe_migration_smoke_test', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->timestamps();
        });
    }

    public function safeDown(): void
    {
        Schema::dropIfExists('safe_migration_smoke_test');
    }
}

/**
 * Throws unconditionally in safeUp() — exercises the failure logging path.
 */
class SafeAlwaysFailingMigration extends SafeMigration
{
    public function safeUp(): void
    {
        throw new \RuntimeException('Intentional test failure for migrations_log capture.');
    }
}
