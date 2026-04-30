<?php

namespace Tests\Feature;

use App\Livewire\Public\AppointmentRequestForm;
use App\Models\AppointmentRequest;
use App\Models\Patient;
use App\Models\PatientCommunication;
use App\Models\Practice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AppointmentRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_request_link_renders_simple_form_without_login_or_patient_details(): void
    {
        [$request, $token] = $this->appointmentRequestLink();

        $this->get(route('appointment-request.show', $token))
            ->assertSuccessful()
            ->assertSee('We’re glad to hear from you.')
            ->assertSee('Let us know when you’d like to come in.')
            ->assertSee('Preferred days or times')
            ->assertDontSee($request->patient->name);
    }

    public function test_public_request_form_records_preferred_times_and_note(): void
    {
        [$request, $token] = $this->appointmentRequestLink();

        Livewire::test(AppointmentRequestForm::class, ['token' => $token])
            ->set('preferred_times', 'Tuesday morning or Thursday after 2')
            ->set('note', 'Prefer the same practitioner if possible.')
            ->call('submit')
            ->assertSet('submitted', true)
            ->assertSee('Appointment request received');

        $request->refresh();

        $this->assertSame(AppointmentRequest::STATUS_PENDING, $request->status);
        $this->assertSame('Tuesday morning or Thursday after 2', $request->preferred_times);
        $this->assertSame('Prefer the same practitioner if possible.', $request->note);
        $this->assertNotNull($request->submitted_at);
    }

    public function test_invalid_request_token_returns_not_found(): void
    {
        $this->get('/appointment-request/not-a-real-token')->assertNotFound();
    }

    private function appointmentRequestLink(): array
    {
        $practice = Practice::factory()->create();
        $patient = Patient::factory()->create([
            'practice_id' => $practice->id,
            'first_name' => 'Nora',
            'last_name' => 'Request',
            'name' => 'Nora Request',
        ]);
        $communication = PatientCommunication::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'type' => PatientCommunication::TYPE_INVITE_BACK,
            'channel' => PatientCommunication::CHANNEL_EMAIL,
            'language' => 'en',
            'subject' => 'Checking in',
            'body' => 'Hi Nora,',
            'status' => PatientCommunication::STATUS_SENT,
        ]);

        return AppointmentRequest::createLinkFor($patient, $communication);
    }
}
