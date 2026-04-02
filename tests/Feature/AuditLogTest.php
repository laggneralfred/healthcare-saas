<?php

namespace Tests\Feature;

use App\Http\Middleware\RequiresActiveSubscription;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\CheckoutSession;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private Practice $practice;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->practice = Practice::factory()->create();
        $this->user     = User::factory()->create(['practice_id' => $this->practice->id]);
    }

    // ── record() creates a correct entry ───────────────────────────────────────

    public function test_record_creates_activity_log_with_correct_fields(): void
    {
        $this->actingAs($this->user);

        $patient = Patient::withoutEvents(fn () => Patient::factory()->create([
            'practice_id' => $this->practice->id,
            'name'        => 'Jane Doe',
        ]));

        $log = ActivityLog::record('viewed', $patient);

        $this->assertDatabaseHas('activity_logs', [
            'action'          => 'viewed',
            'auditable_type'  => Patient::class,
            'auditable_id'    => $patient->id,
            'auditable_label' => 'Jane Doe',
            'practice_id'     => $this->practice->id,
            'user_id'         => $this->user->id,
            'user_email'      => $this->user->email,
        ]);

        $this->assertSame('viewed', $log->action);
        $this->assertSame($patient->id, $log->auditable_id);
    }

    // ── Patient creation triggers an audit log ─────────────────────────────────

    public function test_patient_creation_triggers_created_audit_log(): void
    {
        $this->actingAs($this->user);

        $patient = Patient::factory()->create([
            'practice_id' => $this->practice->id,
            'first_name'  => 'Alice',
            'last_name'   => 'Audit',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action'         => 'created',
            'auditable_type' => Patient::class,
            'auditable_id'   => $patient->id,
            'practice_id'    => $this->practice->id,
        ]);

        $log = ActivityLog::where('auditable_type', Patient::class)
            ->where('auditable_id', $patient->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($log->new_values);
        $this->assertSame('Alice Audit', $log->new_values['name']);
    }

    // ── Sensitive fields never appear in logs ──────────────────────────────────

    public function test_sensitive_fields_are_never_logged(): void
    {
        $this->actingAs($this->user);

        $user = User::factory()->create([
            'practice_id' => $this->practice->id,
            'password'    => bcrypt('super-secret'),
        ]);

        // Directly exercise AuditLogger::created() with a user that has sensitive attrs
        AuditLogger::created($user);

        $logs = ActivityLog::where('auditable_type', User::class)
            ->where('auditable_id', $user->id)
            ->where('action', 'created')
            ->get();

        foreach ($logs as $log) {
            $newValues = $log->new_values ?? [];
            $this->assertArrayNotHasKey('password', $newValues, 'password must not appear in audit log');
            $this->assertArrayNotHasKey('remember_token', $newValues, 'remember_token must not appear');
            $this->assertArrayNotHasKey('stripe_id', $newValues, 'stripe_id must not appear');
            $this->assertArrayNotHasKey('pm_type', $newValues, 'pm_type must not appear');
            $this->assertArrayNotHasKey('pm_last_four', $newValues, 'pm_last_four must not appear');
        }
    }

    // ── AuditLogger::updated() captures old and new values ────────────────────

    public function test_updated_audit_captures_old_and_new_values(): void
    {
        $this->actingAs($this->user);

        $patient = Patient::withoutEvents(fn () => Patient::factory()->create([
            'practice_id' => $this->practice->id,
            'name'        => 'Before Name',
        ]));

        // Simulate an update: manually set dirty state and call AuditLogger
        $patient->name = 'After Name';
        AuditLogger::updated($patient);

        $log = ActivityLog::where('auditable_type', Patient::class)
            ->where('auditable_id', $patient->id)
            ->where('action', 'updated')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('Before Name', $log->old_values['name']);
        $this->assertSame('After Name', $log->new_values['name']);
    }

    // ── stateChanged audit is logged correctly ─────────────────────────────────

    public function test_state_changed_audit_captures_from_and_to_state(): void
    {
        $this->actingAs($this->user);

        $practitioner = Practitioner::withoutEvents(fn () => Practitioner::factory()->create([
            'practice_id' => $this->practice->id,
        ]));
        $appointment = Appointment::withoutEvents(fn () => Appointment::factory()->create([
            'practice_id'     => $this->practice->id,
            'practitioner_id' => $practitioner->id,
        ]));
        $session = CheckoutSession::withoutEvents(fn () => CheckoutSession::factory()->open()->create([
            'practice_id'    => $this->practice->id,
            'appointment_id' => $appointment->id,
            'patient_id'     => $appointment->patient_id,
            'practitioner_id'=> $practitioner->id,
        ]));

        AuditLogger::stateChanged($session, 'open', 'paid', ['tender_type' => 'cash']);

        $log = ActivityLog::where('auditable_type', CheckoutSession::class)
            ->where('auditable_id', $session->id)
            ->where('action', 'state_changed')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('open', $log->old_values['state']);
        $this->assertSame('paid', $log->new_values['state']);
        $this->assertSame('cash', $log->metadata['tender_type']);
    }

    // ── Super-admin can access audit log resource ──────────────────────────────

    public function test_super_admin_can_access_activity_log_index(): void
    {
        $superAdmin = User::factory()->create(['practice_id' => null]);
        $this->actingAs($superAdmin);

        $response = $this->get('/admin/activity-logs');

        $response->assertOk();
    }

    // ── Regular user can access their own practice's audit log ─────────────────

    public function test_regular_user_can_access_activity_log_index(): void
    {
        // Bypass subscription check: in testing env the practice has no Stripe subscription
        $this->withoutMiddleware(RequiresActiveSubscription::class);
        $this->actingAs($this->user);

        $response = $this->get('/admin/activity-logs');

        $response->assertOk();
    }
}
