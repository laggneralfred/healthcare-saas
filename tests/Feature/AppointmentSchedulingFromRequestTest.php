<?php

namespace Tests\Feature;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Appointments\Pages\CreateAppointment;
use App\Models\AppointmentRequest;
use App\Models\AppointmentType;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\User;
use App\Support\PracticeAccessRoles;
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
