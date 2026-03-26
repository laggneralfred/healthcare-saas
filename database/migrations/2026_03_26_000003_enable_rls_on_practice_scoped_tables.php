<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Enables PostgreSQL Row Level Security (RLS) on every practice-scoped table.
 *
 * Design
 * ──────
 * Each table gets two statements and one policy:
 *
 *   ENABLE ROW LEVEL SECURITY — activates the RLS engine for the table.
 *   FORCE ROW LEVEL SECURITY  — includes the table owner in enforcement
 *                               (required because the app DB user also owns
 *                               the tables in this environment).
 *   POLICY practice_isolation — USING expression that restricts every SELECT,
 *                               INSERT, UPDATE and DELETE to rows whose
 *                               practice_id equals the current_setting value.
 *
 * Setting propagation
 * ───────────────────
 * The web app sets `app.practice_id` at the start of every request via
 * SetPostgresTenantContext middleware.  The NULLIF wrapper converts an
 * absent or empty setting to NULL, making the USING expression false for
 * all rows and thus safe when no context is established.
 *
 * Superuser bypass
 * ────────────────
 * PostgreSQL superusers have BYPASSRLS by default.  Migrations and the test
 * suite run as the `postgres` superuser and are never subject to these
 * policies.
 *
 * acupuncture_encounters
 * ──────────────────────
 * This table has no practice_id column.  Its policy joins through the parent
 * `encounters` table using a correlated subquery.
 */
return new class extends Migration
{
    /** Tables with a direct practice_id column. */
    private array $directTables = [
        'appointments',
        'patients',
        'practitioners',
        'encounters',
        'intake_submissions',
        'consent_records',
        'checkout_sessions',
        'checkout_lines',
        'appointment_types',
        'service_fees',
        'subscriptions',
    ];

    public function up(): void
    {
        // ── Tables with a direct practice_id column ───────────────────────────
        foreach ($this->directTables as $table) {
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");
            DB::statement("
                CREATE POLICY practice_isolation ON {$table}
                    USING (
                        practice_id = NULLIF(current_setting('app.practice_id', true), '')::int
                    )
            ");
        }

        // ── acupuncture_encounters — no practice_id, join through encounters ──
        DB::statement("ALTER TABLE acupuncture_encounters ENABLE ROW LEVEL SECURITY");
        DB::statement("ALTER TABLE acupuncture_encounters FORCE ROW LEVEL SECURITY");
        DB::statement("
            CREATE POLICY practice_isolation ON acupuncture_encounters
                USING (
                    encounter_id IN (
                        SELECT id FROM encounters
                        WHERE practice_id =
                            NULLIF(current_setting('app.practice_id', true), '')::int
                    )
                )
        ");
    }

    public function down(): void
    {
        foreach ($this->directTables as $table) {
            DB::statement("DROP POLICY IF EXISTS practice_isolation ON {$table}");
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
        }

        DB::statement("DROP POLICY IF EXISTS practice_isolation ON acupuncture_encounters");
        DB::statement("ALTER TABLE acupuncture_encounters DISABLE ROW LEVEL SECURITY");
    }
};
