<?php

use App\Filament\Pages\CommunicationsDashboard;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\MessageLog;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\User;
use App\Services\AI\AIService;
use App\Services\AI\AIUnavailableException;
use App\Services\PracticeContext;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

function createReminderDraftAppointment(Practice $practice, array $patientOverrides = []): Appointment
{
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $patient = Patient::factory()->create([
        'practice_id' => $practice->id,
        'first_name' => $patientOverrides['first_name'] ?? 'Maya',
        'last_name' => $patientOverrides['last_name'] ?? 'Rivera',
        'email' => $patientOverrides['email'] ?? 'maya@example.com',
    ]);
    $practitioner = Practitioner::factory()->create([
        'practice_id' => $practice->id,
        'user_id' => $user->id,
    ]);
    $appointmentType = AppointmentType::factory()->create(['practice_id' => $practice->id]);

    return Appointment::withoutPracticeScope()->create([
        'practice_id' => $practice->id,
        'patient_id' => $patient->id,
        'practitioner_id' => $practitioner->id,
        'appointment_type_id' => $appointmentType->id,
        'status' => 'scheduled',
        'start_datetime' => now()->addDay()->setTime(10, 0),
        'end_datetime' => now()->addDay()->setTime(11, 0),
    ]);
}

it('AIService returns a reminder draft from minimal context', function () {
    config([
        'services.ai.provider' => 'openai',
        'services.ai.openai.api_key' => 'test-key',
        'services.ai.openai.model' => 'gpt-test',
    ]);

    Http::fake([
        'api.openai.com/v1/responses' => Http::response([
            'output_text' => 'Hi Maya, this is a friendly reminder of your appointment tomorrow at 10:00 AM with Serenity Clinic.',
        ]),
    ]);

    $result = app(AIService::class)->draftReminderMessage([
        'patient_first_name' => 'Maya',
        'practice_name' => 'Serenity Clinic',
        'appointment_datetime' => 'Monday, April 27, 2026 at 10:00 AM',
        'reminder_reason' => 'appointment reminder',
    ]);

    expect($result)->toContain('Hi Maya');

    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer test-key')
        && $request['model'] === 'gpt-test'
        && str_contains($request['instructions'], 'patient communication assistant')
        && str_contains($request['instructions'], 'Do not include diagnosis')
        && str_contains($request['input'], 'patient_first_name')
        && str_contains($request['input'], 'Serenity Clinic')
        && ! str_contains($request['input'], 'diagnosis'));
});

it('creates reminder draft suggestion and usage log without sending a message', function () {
    Mail::fake();
    Queue::fake();

    $practice = Practice::factory()->create(['name' => 'Serenity Clinic']);
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $appointment = createReminderDraftAppointment($practice);

    app()->instance(AIService::class, new class extends AIService {
        public function draftReminderMessage(array $context): string
        {
            return 'Hi Maya, this is a friendly reminder of your upcoming appointment at Serenity Clinic.';
        }
    });

    $this->actingAs($user);

    Livewire::test(CommunicationsDashboard::class)
        ->set('selectedAppointmentId', $appointment->id)
        ->set('reminderReason', '24-hour appointment reminder')
        ->call('draftAIReminder')
        ->assertSet('aiReminderDraft', 'Hi Maya, this is a friendly reminder of your upcoming appointment at Serenity Clinic.');

    expect(MessageLog::withoutPracticeScope()->where('practice_id', $practice->id)->count())->toBe(0);
    Mail::assertNothingSent();
    Queue::assertNothingPushed();

    $this->assertDatabaseHas('ai_suggestions', [
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'patient_id' => $appointment->patient_id,
        'appointment_id' => $appointment->id,
        'feature' => 'reminder_draft',
        'suggested_text' => 'Hi Maya, this is a friendly reminder of your upcoming appointment at Serenity Clinic.',
        'status' => 'pending',
    ]);

    $this->assertDatabaseHas('ai_usage_logs', [
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'feature' => 'reminder_draft',
        'status' => 'success',
    ]);
});

it('uses selected practice context for reminder drafts by super admin', function () {
    $practice = Practice::factory()->create(['name' => 'Selected Practice']);
    $otherPractice = Practice::factory()->create(['name' => 'Other Practice']);
    $superAdmin = User::factory()->create(['practice_id' => null]);
    $appointment = createReminderDraftAppointment($practice);

    app()->instance(AIService::class, new class extends AIService {
        public function draftReminderMessage(array $context): string
        {
            return 'Hi Maya, reminder draft for the selected practice.';
        }
    });

    $this->actingAs($superAdmin);
    PracticeContext::setCurrentPracticeId($practice->id);

    Livewire::test(CommunicationsDashboard::class)
        ->set('selectedAppointmentId', $appointment->id)
        ->set('reminderReason', 'appointment reminder')
        ->call('draftAIReminder');

    $this->assertDatabaseHas('ai_suggestions', [
        'practice_id' => $practice->id,
        'user_id' => $superAdmin->id,
        'feature' => 'reminder_draft',
        'appointment_id' => $appointment->id,
    ]);

    $this->assertDatabaseMissing('ai_suggestions', [
        'practice_id' => $otherPractice->id,
        'user_id' => $superAdmin->id,
        'feature' => 'reminder_draft',
    ]);
});

it('logs failed reminder drafts cleanly without sending', function () {
    Mail::fake();
    Queue::fake();

    $practice = Practice::factory()->create();
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $appointment = createReminderDraftAppointment($practice);

    app()->instance(AIService::class, new class extends AIService {
        public function draftReminderMessage(array $context): string
        {
            throw new AIUnavailableException('Reminder AI offline');
        }
    });

    $this->actingAs($user);

    Livewire::test(CommunicationsDashboard::class)
        ->set('selectedAppointmentId', $appointment->id)
        ->set('reminderReason', 'appointment reminder')
        ->call('draftAIReminder');

    expect(MessageLog::withoutPracticeScope()->where('practice_id', $practice->id)->count())->toBe(0);
    Mail::assertNothingSent();
    Queue::assertNothingPushed();

    $this->assertDatabaseHas('ai_suggestions', [
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'feature' => 'reminder_draft',
        'appointment_id' => $appointment->id,
        'suggested_text' => null,
        'status' => 'failed',
    ]);

    $this->assertDatabaseHas('ai_usage_logs', [
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'feature' => 'reminder_draft',
        'status' => 'failed',
        'error_message' => 'Reminder AI offline',
    ]);
});
