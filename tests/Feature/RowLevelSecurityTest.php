<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AcupunctureEncounter;
use App\Models\CheckoutSession;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Verifies that PostgreSQL Row Level Security policies correctly enforce
 * practice-level tenant isolation at the database level.
 *
 * Test architecture
 * ─────────────────
 * The test suite runs as the `postgres` superuser (configured in phpunit.xml)
 * which has BYPASSRLS, so Laravel fixtures work normally.
 *
 * We use DatabaseTruncation instead of RefreshDatabase so that each test's
 * data is committed to the database immediately.  Without committed data, a
 * second PDO connection running at READ COMMITTED isolation cannot see the
 * rows created inside RefreshDatabase's wrapping transaction.
 *
 * RLS verification is done via a raw PDO connection opened as the `healthcare`
 * application user.  That user has no BYPASSRLS privilege and is therefore
 * fully subject to every practice_isolation RLS policy.
 */
class RowLevelSecurityTest extends TestCase
{
    use DatabaseTruncation;

    private Practice $practiceA;
    private Practice $practiceB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->practiceA = Practice::factory()->create();
        $this->practiceB = Practice::factory()->create();
    }

    /**
     * DatabaseTruncation truncates BEFORE each test (via setUp), so the last
     * test's committed data would otherwise leak into subsequent test classes
     * that use RefreshDatabase.  Override tearDown to always truncate on the
     * way out so this class leaves no committed data behind.
     */
    protected function tearDown(): void
    {
        $this->truncateDatabaseTables();
        parent::tearDown();
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    /**
     * Open a raw PDO connection as the `healthcare` application user.
     * This user has no BYPASSRLS privilege and is therefore fully subject
     * to every practice_isolation RLS policy.
     */
    private function appConnection(): \PDO
    {
        return new \PDO(
            sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                env('DB_HOST', '127.0.0.1'),
                env('DB_PORT', '5432'),
                env('DB_DATABASE', 'healthcare_saas_test')
            ),
            'healthcare',
            'secret',
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
    }

    /**
     * COUNT(*) from $table as the healthcare user with app.practice_id set to
     * the given value.  Returns only the rows that RLS allows that user to see.
     */
    private function countAs(string $table, int $practiceId): int
    {
        $pdo = $this->appConnection();
        $pdo->exec("SELECT set_config('app.practice_id', '{$practiceId}', false)");
        return (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    }

    // ── Policy existence ───────────────────────────────────────────────────

    public function test_rls_policies_exist_on_all_protected_tables(): void
    {
        $tables = [
            'appointments', 'patients', 'practitioners', 'encounters',
            'acupuncture_encounters', 'intake_submissions', 'consent_records',
            'checkout_sessions', 'checkout_lines', 'appointment_types',
            'service_fees', 'subscriptions',
        ];

        $policies = DB::table('pg_policies')
            ->where('schemaname', 'public')
            ->where('policyname', 'practice_isolation')
            ->pluck('tablename')
            ->all();

        foreach ($tables as $table) {
            $this->assertContains(
                $table,
                $policies,
                "Missing RLS policy 'practice_isolation' on table '{$table}'"
            );
        }
    }

    public function test_rls_is_forced_on_all_protected_tables(): void
    {
        $tables = [
            'appointments', 'patients', 'practitioners', 'encounters',
            'acupuncture_encounters', 'intake_submissions', 'consent_records',
            'checkout_sessions', 'checkout_lines', 'appointment_types',
            'service_fees', 'subscriptions',
        ];

        $forcedTables = DB::table('pg_class')
            ->join('pg_namespace', 'pg_namespace.oid', '=', 'pg_class.relnamespace')
            ->where('pg_namespace.nspname', 'public')
            ->where('pg_class.relrowsecurity', true)
            ->where('pg_class.relforcerowsecurity', true)
            ->pluck('pg_class.relname')
            ->all();

        foreach ($tables as $table) {
            $this->assertContains(
                $table,
                $forcedTables,
                "FORCE ROW LEVEL SECURITY not enabled on table '{$table}'"
            );
        }
    }

    // ── No-context blocks all rows ─────────────────────────────────────────

    public function test_no_rows_visible_when_practice_id_not_set(): void
    {
        Patient::factory()->create(['practice_id' => $this->practiceA->id]);

        // healthcare connection with no app.practice_id set — NULLIF returns
        // NULL → practice_id = NULL is always false → zero rows visible.
        $pdo   = $this->appConnection();
        $count = (int) $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();

        $this->assertSame(0, $count);
    }

    // ── Cross-tenant isolation ─────────────────────────────────────────────

    public function test_practice_a_user_sees_only_practice_a_patients(): void
    {
        Patient::factory()->count(3)->create(['practice_id' => $this->practiceA->id]);
        Patient::factory()->count(5)->create(['practice_id' => $this->practiceB->id]);

        $this->assertSame(3, $this->countAs('patients', $this->practiceA->id));
        $this->assertSame(5, $this->countAs('patients', $this->practiceB->id));
    }

    public function test_switching_context_switches_visible_rows(): void
    {
        Patient::factory()->count(2)->create(['practice_id' => $this->practiceA->id]);
        Patient::factory()->count(7)->create(['practice_id' => $this->practiceB->id]);

        // Same connection, two different app.practice_id settings.
        $pdo = $this->appConnection();

        $pdo->exec("SELECT set_config('app.practice_id', '{$this->practiceA->id}', false)");
        $countA = (int) $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();

        $pdo->exec("SELECT set_config('app.practice_id', '{$this->practiceB->id}', false)");
        $countB = (int) $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();

        $this->assertSame(2, $countA);
        $this->assertSame(7, $countB);
    }

    public function test_rls_covers_appointments(): void
    {
        Appointment::factory()->count(4)->create(['practice_id' => $this->practiceA->id]);

        $this->assertSame(4, $this->countAs('appointments', $this->practiceA->id));
        $this->assertSame(0, $this->countAs('appointments', $this->practiceB->id));
    }

    public function test_rls_covers_practitioners(): void
    {
        Practitioner::withoutEvents(function () {
            Practitioner::factory()->count(2)->create(['practice_id' => $this->practiceA->id]);
        });

        $this->assertSame(2, $this->countAs('practitioners', $this->practiceA->id));
        $this->assertSame(0, $this->countAs('practitioners', $this->practiceB->id));
    }

    public function test_rls_covers_encounters(): void
    {
        // encounters.appointment_id has a UNIQUE constraint (1:1 with appointments),
        // so each encounter needs its own appointment.  practitioner_id is also
        // NOT NULL, so we create one practitioner to share across all encounters.
        $patient      = Patient::factory()->create(['practice_id' => $this->practiceA->id]);
        $practitioner = Practitioner::withoutEvents(
            fn () => Practitioner::factory()->create(['practice_id' => $this->practiceA->id])
        );
        $apptA1 = Appointment::factory()->create(['practice_id' => $this->practiceA->id, 'patient_id' => $patient->id]);
        $apptA2 = Appointment::factory()->create(['practice_id' => $this->practiceA->id, 'patient_id' => $patient->id]);
        $apptA3 = Appointment::factory()->create(['practice_id' => $this->practiceA->id, 'patient_id' => $patient->id]);

        foreach ([$apptA1, $apptA2, $apptA3] as $appt) {
            Encounter::factory()->create([
                'practice_id'     => $this->practiceA->id,
                'patient_id'      => $patient->id,
                'practitioner_id' => $practitioner->id,
                'appointment_id'  => $appt->id,
            ]);
        }

        $this->assertSame(3, $this->countAs('encounters', $this->practiceA->id));
        $this->assertSame(0, $this->countAs('encounters', $this->practiceB->id));
    }

    public function test_rls_covers_acupuncture_encounters_via_subquery(): void
    {
        // acupuncture_encounters has no practice_id — the policy joins through
        // the parent encounters table.
        $patient      = Patient::factory()->create(['practice_id' => $this->practiceA->id]);
        $practitioner = Practitioner::withoutEvents(
            fn () => Practitioner::factory()->create(['practice_id' => $this->practiceA->id])
        );
        $appointment = Appointment::factory()->create([
            'practice_id' => $this->practiceA->id,
            'patient_id'  => $patient->id,
        ]);
        $encounter = Encounter::factory()->create([
            'practice_id'     => $this->practiceA->id,
            'patient_id'      => $patient->id,
            'practitioner_id' => $practitioner->id,
            'appointment_id'  => $appointment->id,
        ]);
        AcupunctureEncounter::factory()->create(['encounter_id' => $encounter->id]);

        $this->assertSame(1, $this->countAs('acupuncture_encounters', $this->practiceA->id));
        $this->assertSame(0, $this->countAs('acupuncture_encounters', $this->practiceB->id));
    }

    public function test_rls_covers_checkout_sessions(): void
    {
        // Let the factory create its own appointments — each session gets a
        // distinct appointment so no unique constraint is violated.
        CheckoutSession::factory()->count(2)->create(['practice_id' => $this->practiceA->id]);

        $this->assertSame(2, $this->countAs('checkout_sessions', $this->practiceA->id));
        $this->assertSame(0, $this->countAs('checkout_sessions', $this->practiceB->id));
    }

    // ── Superuser bypass ───────────────────────────────────────────────────

    public function test_superuser_bypasses_rls(): void
    {
        // Laravel DB facade uses the postgres superuser (BYPASSRLS).
        // It should see all rows across both practices.
        Patient::factory()->count(4)->create(['practice_id' => $this->practiceA->id]);
        Patient::factory()->count(6)->create(['practice_id' => $this->practiceB->id]);

        $this->assertSame(10, (int) DB::table('patients')->count());
    }
}
