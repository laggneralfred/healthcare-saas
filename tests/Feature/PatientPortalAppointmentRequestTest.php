<?php

namespace Tests\Feature;

use App\Filament\Pages\FrontDeskDashboard;
use App\Models\Appointment;
use App\Models\AppointmentRequest;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\User;
use App\Services\PatientPortalTokenService;
use App\Support\PracticeAccessRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientPortalAppointmentRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        PracticeAccessRoles::ensureRoles();
    }

    public function test_portal_patient_can_open_request_appointment_page(): void
    {
        [, $admin, $patient] = $this->practiceAdminAndPatient();
        $this->openPortalSessionFor($patient, $admin);

        $this->get(route('patient.appointment-request.create'))
            ->assertOk()
            ->assertSee('Request an appointment')
            ->assertSee('Preferred days and times')
            ->assertSee('Requested service');
    }

    public function test_portal_patient_can_submit_appointment_request(): void
    {
        [$practice, $admin, $patient] = $this->practiceAdminAndPatient();
        $this->openPortalSessionFor($patient, $admin);

        $this->post(route('patient.appointment-request.store'), [
            'requested_service' => 'Follow-up acupuncture',
            'preferred_days_times' => 'Tuesday morning or Thursday after 2',
            'message' => 'Prefer the same practitioner if possible.',
        ])->assertRedirect(route('patient.dashboard'));

        $request = AppointmentRequest::withoutPracticeScope()->firstOrFail();

        $this->assertSame($practice->id, $request->practice_id);
        $this->assertSame($patient->id, $request->patient_id);
        $this->assertSame(AppointmentRequest::STATUS_PENDING, $request->status);
        $this->assertSame('Follow-up acupuncture', $request->requested_service);
        $this->assertSame('Tuesday morning or Thursday after 2', $request->preferred_times);
        $this->assertSame('Prefer the same practitioner if possible.', $request->note);
        $this->assertNotNull($request->submitted_at);
        $this->assertNotNull($request->token_hash);
        $this->assertSame(0, Appointment::withoutPracticeScope()->count());
    }

    public function test_dashboard_shows_own_request_status_only(): void
    {
        [$practice, $admin, $patient] = $this->practiceAdminAndPatient();
        $otherPatient = Patient::factory()->create([
            'practice_id' => $practice->id,
            'first_name' => 'Other',
            'last_name' => 'Patient',
        ]);

        AppointmentRequest::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'token_hash' => hash('sha256', 'own-request'),
            'status' => AppointmentRequest::STATUS_PENDING,
            'requested_service' => 'Massage therapy',
            'preferred_times' => 'Friday afternoon',
            'submitted_at' => now(),
        ]);

        AppointmentRequest::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'patient_id' => $otherPatient->id,
            'token_hash' => hash('sha256', 'other-request'),
            'status' => AppointmentRequest::STATUS_PENDING,
            'requested_service' => 'Private other request',
            'preferred_times' => 'Monday morning',
            'submitted_at' => now(),
        ]);

        $this->openPortalSessionFor($patient, $admin);

        $this->get(route('patient.dashboard'))
            ->assertOk()
            ->assertSee('Massage therapy')
            ->assertSee('Pending')
            ->assertSee('Friday afternoon')
            ->assertDontSee('Private other request')
            ->assertDontSee('Monday morning');
    }

    public function test_unauthenticated_visitor_cannot_access_request_page(): void
    {
        $this->get(route('patient.appointment-request.create'))
            ->assertRedirect(route('patient.portal.invalid'));

        $this->post(route('patient.appointment-request.store'), [
            'preferred_days_times' => 'Tuesday morning',
        ])->assertRedirect(route('patient.portal.invalid'));
    }

    public function test_staff_can_see_portal_created_request_on_today_dashboard(): void
    {
        [$practice, $admin, $patient] = $this->practiceAdminAndPatient();
        AppointmentRequest::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'token_hash' => hash('sha256', 'portal-created'),
            'status' => AppointmentRequest::STATUS_PENDING,
            'requested_service' => 'Follow-up acupuncture',
            'preferred_times' => 'Tuesday morning',
            'note' => 'Portal request note',
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin);

        $this->get(FrontDeskDashboard::getUrl())
            ->assertOk()
            ->assertSee($patient->name)
            ->assertSee('Follow-up acupuncture')
            ->assertSee('Tuesday morning')
            ->assertSee('Portal request note');
    }

    public function test_cross_practice_scoping_is_enforced_for_portal_requests(): void
    {
        [$practice, $admin, $patient] = $this->practiceAdminAndPatient();
        $otherPractice = Practice::factory()->create(['name' => 'Other Clinic']);
        $otherPatient = Patient::factory()->create(['practice_id' => $otherPractice->id]);

        AppointmentRequest::withoutPracticeScope()->create([
            'practice_id' => $otherPractice->id,
            'patient_id' => $otherPatient->id,
            'token_hash' => hash('sha256', 'other-practice-request'),
            'status' => AppointmentRequest::STATUS_PENDING,
            'requested_service' => 'Other clinic service',
            'preferred_times' => 'Wednesday morning',
            'submitted_at' => now(),
        ]);

        $this->openPortalSessionFor($patient, $admin);

        $this->post(route('patient.appointment-request.store'), [
            'requested_service' => 'Own clinic service',
            'preferred_days_times' => 'Thursday morning',
        ])->assertRedirect(route('patient.dashboard'));

        $ownRequest = AppointmentRequest::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->firstOrFail();

        $this->assertSame($patient->id, $ownRequest->patient_id);
        $this->assertSame('Own clinic service', $ownRequest->requested_service);

        $this->get(route('patient.dashboard'))
            ->assertOk()
            ->assertSee('Own clinic service')
            ->assertDontSee('Other clinic service');
    }

    private function openPortalSessionFor(Patient $patient, User $admin): void
    {
        [, $plainToken] = app(PatientPortalTokenService::class)->createForExistingPatient($patient, $admin);

        $this->get(route('patient.magic-link', ['token' => $plainToken]))
            ->assertRedirect(route('patient.dashboard'));
    }

    private function practiceAdminAndPatient(): array
    {
        $practice = Practice::factory()->create(['name' => 'Portal Clinic']);
        $admin = User::factory()->create(['practice_id' => $practice->id]);
        $admin->assignRole(User::ROLE_ADMINISTRATOR);
        $patient = Patient::factory()->create([
            'practice_id' => $practice->id,
            'first_name' => 'Nora',
            'last_name' => 'Portal',
            'email' => 'nora@example.test',
        ]);

        return [$practice, $admin, $patient];
    }
}
