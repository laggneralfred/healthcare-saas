<?php

namespace Tests\Feature;

use App\Models\AcupunctureEncounter;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\CheckoutSession;
use App\Models\ConsentRecord;
use App\Models\Encounter;
use App\Models\IntakeSubmission;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\ServiceFee;
use App\Models\User;
use App\Services\PracticeContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Proves that the BelongsToPractice global scope prevents any user from
 * seeing records that belong to a different practice.
 *
 * Fixture layout
 * ──────────────
 * Practice A  →  one complete tree of records (patient, practitioner, type, fee,
 *                appointment, intake, consent, encounter, acupuncture-encounter,
 *                checkout-session).
 * Practice B  →  minimal positive-control records (one patient, one practitioner).
 *
 * Each isolation test authenticates as a Practice-B user and asserts that
 * Practice A's records are invisible.  Positive-control tests confirm that
 * Practice B's own data remains accessible.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    // ── Practices ──────────────────────────────────────────────────────────────

    private Practice $practiceA;
    private Practice $practiceB;

    // ── Users ──────────────────────────────────────────────────────────────────

    private User $userA;
    private User $userB;

    // ── Practice A fixtures ────────────────────────────────────────────────────

    private Practitioner        $practitionerA;
    private Patient             $patientA;
    private ServiceFee          $feeA;
    private AppointmentType     $typeA;
    private Appointment         $appointmentA;
    private IntakeSubmission    $intakeA;
    private ConsentRecord       $consentA;
    private Encounter           $encounterA;
    private AcupunctureEncounter $acuA;
    private CheckoutSession     $checkoutA;

    // Practice B has no records by default.  Positive-control tests create their
    // own local fixtures so that isolation tests can assert count = 0 cleanly.

    // ── Setup ──────────────────────────────────────────────────────────────────

    protected function setUp(): void
    {
        parent::setUp();

        $this->practiceA = Practice::factory()->create();
        $this->practiceB = Practice::factory()->create();

        $this->userA = User::factory()->create(['practice_id' => $this->practiceA->id]);
        $this->userB = User::factory()->create(['practice_id' => $this->practiceB->id]);

        // Build a full record tree for Practice A.
        $this->practitionerA = Practitioner::factory()->create([
            'practice_id' => $this->practiceA->id,
        ]);

        $this->patientA = Patient::factory()->create([
            'practice_id' => $this->practiceA->id,
        ]);

        $this->feeA = ServiceFee::factory()->create([
            'practice_id' => $this->practiceA->id,
        ]);

        $this->typeA = AppointmentType::factory()->create([
            'practice_id' => $this->practiceA->id,
        ]);

        $this->appointmentA = Appointment::factory()->create([
            'practice_id'         => $this->practiceA->id,
            'patient_id'          => $this->patientA->id,
            'practitioner_id'     => $this->practitionerA->id,
            'appointment_type_id' => $this->typeA->id,
        ]);

        $this->intakeA = IntakeSubmission::factory()->create([
            'practice_id'    => $this->practiceA->id,
            'patient_id'     => $this->patientA->id,
            'appointment_id' => $this->appointmentA->id,
        ]);

        $this->consentA = ConsentRecord::factory()->create([
            'practice_id'    => $this->practiceA->id,
            'patient_id'     => $this->patientA->id,
            'appointment_id' => $this->appointmentA->id,
        ]);

        // EncounterFactory::definition() intentionally omits FK columns;
        // provide them explicitly so the row is correctly owned by Practice A.
        $this->encounterA = Encounter::factory()->create([
            'practice_id'     => $this->practiceA->id,
            'patient_id'      => $this->patientA->id,
            'appointment_id'  => $this->appointmentA->id,
            'practitioner_id' => $this->practitionerA->id,
        ]);

        // AcupunctureEncounter has no practice_id column; it is a 1-to-1
        // extension of Encounter and is isolated through that parent.
        $this->acuA = AcupunctureEncounter::factory()->withClinicalData()->create([
            'encounter_id' => $this->encounterA->id,
        ]);

        $this->checkoutA = CheckoutSession::factory()->open()->create([
            'practice_id'     => $this->practiceA->id,
            'patient_id'      => $this->patientA->id,
            'appointment_id'  => $this->appointmentA->id,
            'practitioner_id' => $this->practitionerA->id,
        ]);

        // Practice B starts with zero records.  Positive-control tests add
        // their own fixtures inline so that isolation tests can assert count = 0.
    }

    // ── Isolation: Patient ─────────────────────────────────────────────────────

    public function test_practice_b_user_cannot_see_practice_a_patients(): void
    {
        $this->actingAs($this->userB);

        $this->assertCount(0, Patient::all());
        $this->assertNull(Patient::find($this->patientA->id));
    }

    // ── Isolation: Practitioner ────────────────────────────────────────────────

    public function test_practice_b_user_cannot_see_practice_a_practitioners(): void
    {
        $this->actingAs($this->userB);

        $this->assertCount(0, Practitioner::all());
        $this->assertNull(Practitioner::find($this->practitionerA->id));
    }

    // ── Isolation: AppointmentType ─────────────────────────────────────────────

    public function test_practice_b_user_cannot_see_practice_a_appointment_types(): void
    {
        $this->actingAs($this->userB);

        $this->assertCount(0, AppointmentType::all());
        $this->assertNull(AppointmentType::find($this->typeA->id));
    }

    // ── Isolation: ServiceFee ──────────────────────────────────────────────────

    public function test_practice_b_user_cannot_see_practice_a_service_fees(): void
    {
        $this->actingAs($this->userB);

        $this->assertCount(0, ServiceFee::all());
        $this->assertNull(ServiceFee::find($this->feeA->id));
    }

    // ── Isolation: Appointment ─────────────────────────────────────────────────

    public function test_practice_b_user_cannot_see_practice_a_appointments(): void
    {
        $this->actingAs($this->userB);

        $this->assertCount(0, Appointment::all());
        $this->assertNull(Appointment::find($this->appointmentA->id));
    }

    // ── Isolation: IntakeSubmission ────────────────────────────────────────────

    public function test_practice_b_user_cannot_see_practice_a_intake_submissions(): void
    {
        $this->actingAs($this->userB);

        $this->assertCount(0, IntakeSubmission::all());
        $this->assertNull(IntakeSubmission::find($this->intakeA->id));
    }

    // ── Isolation: ConsentRecord ───────────────────────────────────────────────

    public function test_practice_b_user_cannot_see_practice_a_consent_records(): void
    {
        $this->actingAs($this->userB);

        $this->assertCount(0, ConsentRecord::all());
        $this->assertNull(ConsentRecord::find($this->consentA->id));
    }

    // ── Isolation: Encounter ───────────────────────────────────────────────────

    public function test_practice_b_user_cannot_see_practice_a_encounters(): void
    {
        $this->actingAs($this->userB);

        $this->assertCount(0, Encounter::all());
        $this->assertNull(Encounter::find($this->encounterA->id));
    }

    // ── Isolation: AcupunctureEncounter ───────────────────────────────────────

    /**
     * AcupunctureEncounter has no practice_id column and therefore carries no
     * BelongsToPractice global scope of its own.  Instead, it is isolated
     * structurally: the only legitimate ORM path to an AcupunctureEncounter is
     * through its parent Encounter, which IS scoped.
     *
     * This test verifies that:
     *   (a) The parent Encounter is invisible to Practice B users.
     *   (b) Traversing the relationship from a scoped Encounter collection
     *       therefore yields zero AcupunctureEncounter records.
     *   (c) The null-safe accessor on a hidden Encounter returns null.
     */
    public function test_practice_b_user_cannot_reach_practice_a_acupuncture_encounter_via_encounter(): void
    {
        $this->actingAs($this->userB);

        // (a) Parent is scoped away.
        $encounter = Encounter::find($this->encounterA->id);
        $this->assertNull($encounter, 'Practice A Encounter must be invisible to Practice B user');

        // (b) Null-safe chain on a missing parent returns null.
        $this->assertNull($encounter?->acupunctureEncounter);

        // (c) Eager-loading through the scoped collection yields nothing.
        $reachable = Encounter::with('acupunctureEncounter')
            ->get()
            ->pluck('acupunctureEncounter')
            ->filter()
            ->values();

        $this->assertCount(
            0,
            $reachable,
            'No AcupunctureEncounter should be reachable through scoped Encounter collection'
        );
    }

    // ── Isolation: CheckoutSession ─────────────────────────────────────────────

    public function test_practice_b_user_cannot_see_practice_a_checkout_sessions(): void
    {
        $this->actingAs($this->userB);

        $this->assertCount(0, CheckoutSession::all());
        $this->assertNull(CheckoutSession::find($this->checkoutA->id));
    }

    // ── Positive control: Practice B user sees their own data ─────────────────

    /**
     * Guards against a "scope too broad" false positive where the scope
     * accidentally hides everything — including the authenticated practice's
     * own records.
     */
    public function test_practice_b_user_can_see_own_patients(): void
    {
        $patientB = Patient::factory()->create(['practice_id' => $this->practiceB->id]);

        $this->actingAs($this->userB);

        $results = Patient::all();

        $this->assertCount(1, $results);
        $this->assertEquals($patientB->id, $results->first()->id);
    }

    public function test_practice_b_user_can_see_own_practitioners(): void
    {
        $practitionerB = Practitioner::factory()->create(['practice_id' => $this->practiceB->id]);

        $this->actingAs($this->userB);

        $results = Practitioner::all();

        $this->assertCount(1, $results);
        $this->assertEquals($practitionerB->id, $results->first()->id);
    }

    // ── Unauthenticated context has no scope ───────────────────────────────────

    /**
     * Without an authenticated user (console commands, seeders, public routes
     * that use their own token/slug checks), PracticeContext::currentPracticeId()
     * returns null and the global scope is a deliberate no-op.  All rows are
     * visible — each code path in that context is responsible for its own
     * access control.
     */
    public function test_unauthenticated_context_applies_no_scope(): void
    {
        // No actingAs() call.
        $this->assertNull(PracticeContext::currentPracticeId());

        // All existing patients are visible — scope is inactive.
        // setUp only creates patientA; that is sufficient to prove no filtering.
        $all = Patient::all();
        $this->assertTrue($all->contains('id', $this->patientA->id));

        // withoutPracticeScope() is a no-op when the scope is already inactive;
        // verify it does not crash and returns the same result.
        $this->assertEquals($all->count(), Patient::withoutPracticeScope()->count());
    }

    // ── Super-admin scoped to Practice B cannot see Practice A data ───────────

    /**
     * A super-admin (practice_id = null) with their session scoped to Practice B
     * via PracticeContext::setCurrentPracticeId() should see exactly the same
     * isolation as a regular Practice B user.
     */
    public function test_super_admin_scoped_to_practice_b_cannot_see_practice_a_data(): void
    {
        $superAdmin = User::factory()->create(['practice_id' => null]);

        // Mirrors what PracticeSwitchController does when the super-admin
        // switches to Practice B from the top-bar dropdown.
        PracticeContext::setCurrentPracticeId($this->practiceB->id);

        $this->actingAs($superAdmin);

        // Confirm the context resolves to Practice B, not A.
        $this->assertEquals(
            $this->practiceB->id,
            PracticeContext::currentPracticeId(),
            'PracticeContext must resolve to Practice B for this super-admin session'
        );

        // Every scoped model must hide Practice A's records.
        $this->assertCount(0, Patient::all());
        $this->assertNull(Patient::find($this->patientA->id));

        $this->assertCount(0, Practitioner::all());
        $this->assertNull(Practitioner::find($this->practitionerA->id));

        $this->assertCount(0, AppointmentType::all());
        $this->assertNull(AppointmentType::find($this->typeA->id));

        $this->assertCount(0, ServiceFee::all());
        $this->assertNull(ServiceFee::find($this->feeA->id));

        $this->assertCount(0, Appointment::all());
        $this->assertNull(Appointment::find($this->appointmentA->id));

        $this->assertCount(0, IntakeSubmission::all());
        $this->assertNull(IntakeSubmission::find($this->intakeA->id));

        $this->assertCount(0, ConsentRecord::all());
        $this->assertNull(ConsentRecord::find($this->consentA->id));

        $this->assertCount(0, Encounter::all());
        $this->assertNull(Encounter::find($this->encounterA->id));

        $this->assertCount(0, CheckoutSession::all());
        $this->assertNull(CheckoutSession::find($this->checkoutA->id));
    }

    /**
     * Positive control: when scoped to Practice B, a super-admin CAN still
     * see Practice B's own records.
     */
    public function test_super_admin_scoped_to_practice_b_can_see_practice_b_data(): void
    {
        $patientB      = Patient::factory()->create(['practice_id' => $this->practiceB->id]);
        $practitionerB = Practitioner::factory()->create(['practice_id' => $this->practiceB->id]);

        $superAdmin = User::factory()->create(['practice_id' => null]);

        PracticeContext::setCurrentPracticeId($this->practiceB->id);

        $this->actingAs($superAdmin);

        $patients = Patient::all();
        $this->assertCount(1, $patients);
        $this->assertEquals($patientB->id, $patients->first()->id);

        $practitioners = Practitioner::all();
        $this->assertCount(1, $practitioners);
        $this->assertEquals($practitionerB->id, $practitioners->first()->id);
    }

    // ── withoutPracticeScope() bypass ─────────────────────────────────────────

    /**
     * withoutPracticeScope() lets privileged code (e.g. API controllers that
     * receive the practice via route model binding, reporting jobs) query across
     * tenants even while authenticated.
     */
    public function test_without_practice_scope_bypasses_isolation(): void
    {
        $patientB = Patient::factory()->create(['practice_id' => $this->practiceB->id]);

        $this->actingAs($this->userB);

        // Scoped: Practice B sees only its own patient (not Practice A's).
        $scoped = Patient::all();
        $this->assertCount(1, $scoped);
        $this->assertFalse($scoped->contains('id', $this->patientA->id));

        // Bypassed: all records from both practices are visible.
        $all = Patient::withoutPracticeScope()->get();
        $this->assertCount(2, $all);
        $this->assertTrue($all->contains('id', $this->patientA->id));
        $this->assertTrue($all->contains('id', $patientB->id));
    }
}
