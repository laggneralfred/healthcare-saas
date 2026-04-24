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

function createReminderTranslationAppointment(Practice $practice): Appointment
{
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $patient = Patient::factory()->create([
        'practice_id' => $practice->id,
        'first_name' => 'Maya',
        'last_name' => 'Rivera',
        'email' => 'maya@example.com',
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

it('AIService translates reminder text with target language context', function () {
    config([
        'services.ai.provider' => 'openai',
        'services.ai.openai.api_key' => 'test-key',
        'services.ai.openai.model' => 'gpt-test',
    ]);

    Http::fake([
        'api.openai.com/v1/responses' => Http::response([
            'output_text' => 'Hola Maya, este es un recordatorio amable de su cita mañana a las 10:00 AM.',
        ]),
    ]);

    $result = app(AIService::class)->translateText(
        'Hi Maya, this is a friendly reminder of your appointment tomorrow at 10:00 AM.',
        'Spanish',
        ['practice_name' => 'Serenity Clinic']
    );

    expect($result)->toContain('Hola Maya');

    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer test-key')
        && $request['model'] === 'gpt-test'
        && str_contains($request['instructions'], 'medical office communication translator')
        && str_contains($request['instructions'], 'Do not mention AI')
        && str_contains($request['input'], 'target_language')
        && str_contains($request['input'], 'Spanish')
        && str_contains($request['input'], 'message_text'));
});

it('creates translation suggestion and usage log while preserving English draft and sending nothing', function () {
    Mail::fake();
    Queue::fake();

    $practice = Practice::factory()->create(['name' => 'Serenity Clinic']);
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $appointment = createReminderTranslationAppointment($practice);
    $englishDraft = 'Hi Maya, this is a friendly reminder of your appointment tomorrow at 10:00 AM.';

    app()->instance(AIService::class, new class extends AIService {
        public function translateText(string $text, string $targetLanguage, array $context = []): string
        {
            return 'Hola Maya, este es un recordatorio amable de su cita mañana a las 10:00 AM.';
        }
    });

    $this->actingAs($user);

    Livewire::test(CommunicationsDashboard::class)
        ->set('selectedAppointmentId', $appointment->id)
        ->set('aiReminderDraft', $englishDraft)
        ->set('targetLanguage', 'Spanish')
        ->call('translateReminderDraft')
        ->assertSet('aiReminderDraft', $englishDraft)
        ->assertSet('translatedReminderDraft', 'Hola Maya, este es un recordatorio amable de su cita mañana a las 10:00 AM.');

    expect(MessageLog::withoutPracticeScope()->where('practice_id', $practice->id)->count())->toBe(0);
    Mail::assertNothingSent();
    Queue::assertNothingPushed();

    $this->assertDatabaseHas('ai_suggestions', [
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'patient_id' => $appointment->patient_id,
        'appointment_id' => $appointment->id,
        'feature' => 'translation',
        'suggested_text' => 'Hola Maya, este es un recordatorio amable de su cita mañana a las 10:00 AM.',
        'status' => 'pending',
    ]);

    $this->assertDatabaseHas('ai_usage_logs', [
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'feature' => 'translation',
        'status' => 'success',
    ]);
});

it('uses selected practice context for translations by super admin', function () {
    $practice = Practice::factory()->create(['name' => 'Selected Practice']);
    $otherPractice = Practice::factory()->create(['name' => 'Other Practice']);
    $superAdmin = User::factory()->create(['practice_id' => null]);
    $appointment = createReminderTranslationAppointment($practice);

    app()->instance(AIService::class, new class extends AIService {
        public function translateText(string $text, string $targetLanguage, array $context = []): string
        {
            return 'Bonjour Maya, ceci est un rappel amical.';
        }
    });

    $this->actingAs($superAdmin);
    PracticeContext::setCurrentPracticeId($practice->id);

    Livewire::test(CommunicationsDashboard::class)
        ->set('selectedAppointmentId', $appointment->id)
        ->set('aiReminderDraft', 'Hi Maya, this is a friendly reminder.')
        ->set('targetLanguage', 'French')
        ->call('translateReminderDraft');

    $this->assertDatabaseHas('ai_suggestions', [
        'practice_id' => $practice->id,
        'user_id' => $superAdmin->id,
        'feature' => 'translation',
        'appointment_id' => $appointment->id,
    ]);

    $this->assertDatabaseMissing('ai_suggestions', [
        'practice_id' => $otherPractice->id,
        'user_id' => $superAdmin->id,
        'feature' => 'translation',
    ]);
});

it('logs failed translations cleanly without sending', function () {
    Mail::fake();
    Queue::fake();

    $practice = Practice::factory()->create();
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $appointment = createReminderTranslationAppointment($practice);

    app()->instance(AIService::class, new class extends AIService {
        public function translateText(string $text, string $targetLanguage, array $context = []): string
        {
            throw new AIUnavailableException('Translation AI offline');
        }
    });

    $this->actingAs($user);

    Livewire::test(CommunicationsDashboard::class)
        ->set('selectedAppointmentId', $appointment->id)
        ->set('aiReminderDraft', 'Hi Maya, this is a friendly reminder.')
        ->set('targetLanguage', 'German')
        ->call('translateReminderDraft')
        ->assertSet('aiReminderDraft', 'Hi Maya, this is a friendly reminder.');

    expect(MessageLog::withoutPracticeScope()->where('practice_id', $practice->id)->count())->toBe(0);
    Mail::assertNothingSent();
    Queue::assertNothingPushed();

    $this->assertDatabaseHas('ai_suggestions', [
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'feature' => 'translation',
        'appointment_id' => $appointment->id,
        'suggested_text' => null,
        'status' => 'failed',
    ]);

    $this->assertDatabaseHas('ai_usage_logs', [
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'feature' => 'translation',
        'status' => 'failed',
        'error_message' => 'Translation AI offline',
    ]);
});
