<?php

namespace Tests\Feature;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Appointments\Pages\CreateAppointment;
use App\Models\Appointment;
use App\Models\AppointmentRequest;
use App\Models\AppointmentType;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\PractitionerTimeBlock;
use App\Models\PractitionerWorkingHour;
use App\Models\User;
use App\Support\PracticeAccessRoles;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AppointmentSchedulingFromRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        PracticeAccessRoles::ensureRoles();
    }

    public function test_create_page_shows_request_context_and_preserves_prefill_values(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);
        $appointmentType = AppointmentType::factory()->create([
            'practice_id' => $practice->id,
            'name' => 'Follow-up Acupuncture',
        ]);
        $practitioner = $this->practitionerFor($practice, 'Dr. Preference');
        $request = $this->appointmentRequestFor($practice, $patient, [
            'appointment_type_id' => $appointmentType->id,
            'practitioner_id' => $practitioner->id,
            'requested_service' => 'Acupuncture follow-up',
            'preferred_times' => "Tuesday morning\nThursday after 2",
            'note' => 'Patient prefers a quiet room.',
            'submitted_at' => now()->setDate(2026, 5, 1)->setTime(9, 15),
        ]);

        $this->actingAs($admin);

        Livewire::withQueryParams([
            'appointment_request_id' => $request->id,
            'patient_id' => $patient->id,
            'appointment_type_id' => $appointmentType->id,
            'practitioner_id' => $practitioner->id,
            'return_url' => '/admin/front-desk',
        ])
            ->test(CreateAppointment::class)
            ->assertSee('Appointment Request')
            ->assertSee('Follow-up Acupuncture')
            ->assertSee('Dr. Preference')
            ->assertSee('Tuesday morning')
            ->assertSee('Thursday after 2')
            ->assertSee('Patient prefers a quiet room.')
            ->assertSee('Submitted May 1, 2026 9:15 AM')
            ->assertSee('Pending')
            ->assertFormSet([
                'patient_id' => $patient->id,
                'appointment_type_id' => $appointmentType->id,
                'practitioner_id' => $practitioner->id,
            ]);
    }

    public function test_cross_practice_request_cannot_be_used_on_create_page(): void
    {
        [, $admin] = $this->practiceWithAdmin();
        $otherPractice = Practice::factory()->create();
        $otherPatient = Patient::factory()->create(['practice_id' => $otherPractice->id]);
        $otherRequest = $this->appointmentRequestFor($otherPractice, $otherPatient, [
            'preferred_times' => 'Other practice details',
        ]);

        $this->actingAs($admin);

        $this->get(AppointmentResource::getUrl('create') . '?appointment_request_id=' . $otherRequest->id)
            ->assertNotFound();
    }

    public function test_create_page_shows_practitioner_schedule_context_for_request(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();
        $patient = Patient::factory()->create([
            'practice_id' => $practice->id,
            'first_name' => 'Schedule',
            'last_name' => 'Patient',
            'name' => 'Schedule Patient',
        ]);
        $otherPractice = Practice::factory()->create();
        $otherPatient = Patient::factory()->create([
            'practice_id' => $otherPractice->id,
            'name' => 'Other Practice Patient',
        ]);
        $appointmentType = AppointmentType::factory()->create([
            'practice_id' => $practice->id,
            'name' => 'Initial Treatment',
        ]);
        $otherAppointmentType = AppointmentType::factory()->create([
            'practice_id' => $otherPractice->id,
            'name' => 'Other Practice Treatment',
        ]);
        $practitioner = $this->practitionerFor($practice, 'Dr. Calendar');
        $otherPractitioner = $this->practitionerFor($otherPractice, 'Dr. Elsewhere');
        $request = $this->appointmentRequestFor($practice, $patient, [
            'appointment_type_id' => $appointmentType->id,
            'practitioner_id' => $practitioner->id,
        ]);

        PractitionerWorkingHour::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'practitioner_id' => $practitioner->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '13:00',
            'is_active' => true,
        ]);
        PractitionerTimeBlock::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'practitioner_id' => $practitioner->id,
            'starts_at' => Carbon::parse('2026-05-04 11:00', 'America/Los_Angeles'),
            'ends_at' => Carbon::parse('2026-05-04 11:30', 'America/Los_Angeles'),
            'block_type' => PractitionerTimeBlock::TYPE_ADMIN,
            'reason' => 'Staff meeting',
        ]);
        Appointment::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'appointment_type_id' => $appointmentType->id,
            'status' => 'scheduled',
            'start_datetime' => '2026-05-04 10:00:00',
            'end_datetime' => '2026-05-04 10:45:00',
        ]);
        Appointment::withoutPracticeScope()->create([
            'practice_id' => $otherPractice->id,
            'patient_id' => $otherPatient->id,
            'practitioner_id' => $otherPractitioner->id,
            'appointment_type_id' => $otherAppointmentType->id,
            'status' => 'scheduled',
            'start_datetime' => '2026-05-04 10:00:00',
            'end_datetime' => '2026-05-04 10:45:00',
        ]);

        $this->actingAs($admin);

        Livewire::withQueryParams([
            'appointment_request_id' => $request->id,
            'patient_id' => $patient->id,
            'appointment_type_id' => $appointmentType->id,
            'practitioner_id' => $practitioner->id,
            'start_datetime' => '2026-05-04 09:30:00',
            'return_url' => '/admin/front-desk',
        ])
            ->test(CreateAppointment::class)
            ->assertSee('Schedule Context')
            ->assertSee('Dr. Calendar')
            ->assertSee('Monday, May 4, 2026')
            ->assertSee('9:00 AM - 1:00 PM')
            ->assertSee('11:00 AM - 11:30 AM')
            ->assertSee('Staff meeting')
            ->assertSee('10:00 AM - 10:45 AM')
            ->assertSee('Schedule Patient')
            ->assertSee('Initial Treatment')
            ->assertDontSee('Other Practice Patient')
            ->assertSee('Open Calendar for this Practitioner')
            ->assertSee('appointment_request_id=' . $request->id, false)
            ->assertSee('patient_id=' . $patient->id, false)
            ->assertSee('appointment_type_id=' . $appointmentType->id, false)
            ->assertSee('practitioner_id=' . $practitioner->id, false)
            ->assertSee('return_url=%2Fadmin%2Ffront-desk', false);
    }

    private function practiceWithAdmin(): array
    {
        $practice = Practice::factory()->create(['timezone' => 'America/Los_Angeles']);
        $admin = User::factory()->create(['practice_id' => $practice->id]);
        PracticeAccessRoles::assignOwner($admin);

        return [$practice, $admin];
    }

    private function practitionerFor(Practice $practice, string $name): Practitioner
    {
        $user = User::factory()->create([
            'practice_id' => $practice->id,
            'name' => $name,
        ]);
        $user->assignRole(User::ROLE_PRACTITIONER);

        return Practitioner::factory()->create([
            'practice_id' => $practice->id,
            'user_id' => $user->id,
            'is_active' => true,
        ]);
    }

    private function appointmentRequestFor(Practice $practice, Patient $patient, array $attributes = []): AppointmentRequest
    {
        return AppointmentRequest::withoutPracticeScope()->create(array_merge([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'token_hash' => hash('sha256', 'request-' . $practice->id . '-' . $patient->id . '-' . uniqid()),
            'status' => AppointmentRequest::STATUS_PENDING,
            'preferred_times' => 'Any afternoon',
            'submitted_at' => now(),
        ], $attributes));
    }
}
