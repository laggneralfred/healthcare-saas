<?php

use App\Filament\Resources\Encounters\Pages\EditEncounter;
use App\Models\AIUsageLog;
use App\Models\AISuggestion;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\User;
use App\Services\AI\AIService;
use App\Services\AI\AIUnavailableException;
use App\Services\PracticeContext;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function createEncounterForPractice(Practice $practice, array $attributes = []): Encounter
{
    $patient = Patient::factory()->create(['practice_id' => $practice->id]);
    $practitioner = Practitioner::factory()->create(['practice_id' => $practice->id]);
    $appointmentType = AppointmentType::factory()->create(['practice_id' => $practice->id]);
    $appointment = Appointment::factory()->create([
        'practice_id' => $practice->id,
        'patient_id' => $patient->id,
        'practitioner_id' => $practitioner->id,
        'appointment_type_id' => $appointmentType->id,
    ]);

    return Encounter::factory()->create([
        'practice_id' => $practice->id,
        'patient_id' => $patient->id,
        'practitioner_id' => $practitioner->id,
        'appointment_id' => $appointment->id,
        'discipline' => 'acupuncture',
        'visit_notes' => 'pt says neck tight better after tx',
        ...$attributes,
    ]);
}

it('AIService returns an improved note from OpenAI when configured', function () {
    config([
        'services.ai.provider' => 'openai',
        'services.ai.openai.api_key' => 'test-key',
        'services.ai.openai.model' => 'gpt-test',
    ]);

    Http::fake([
        'api.openai.com/v1/responses' => Http::response([
            'output_text' => 'Patient reports neck tightness improved after treatment.',
        ]),
    ]);

    $result = app(AIService::class)->improveNote('neck tight better after tx');

    expect($result)->toBe('Patient reports neck tightness improved after treatment.');

    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer test-key')
        && $request['model'] === 'gpt-test'
        && str_contains($request['instructions'], 'Do not diagnose')
        && str_contains($request['input'], 'neck tight better after tx'));
});

it('AIService is unavailable when no API key is configured', function () {
    config(['services.ai.openai.api_key' => null]);

    expect(fn () => app(AIService::class)->improveNote('rough note'))
        ->toThrow(AIUnavailableException::class);
});

it('creates a practice-scoped AI suggestion and preserves the original note until accepted', function () {
    $practice = Practice::factory()->create();
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $encounter = createEncounterForPractice($practice);

    app()->instance(AIService::class, new class extends AIService {
        public function improveNote(string $note, array $context = []): string
        {
            return 'Patient reports neck tightness improved after treatment.';
        }
    });

    $this->actingAs($user);

    Livewire::test(EditEncounter::class, ['record' => $encounter->id])
        ->set('data.visit_notes', 'neck tight better after tx')
        ->call('improveNote');

    $encounter->refresh();
    expect($encounter->visit_notes)->toBe('pt says neck tight better after tx');

    $this->assertDatabaseHas('ai_suggestions', [
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'encounter_id' => $encounter->id,
        'feature' => 'improve_note',
        'original_text' => 'neck tight better after tx',
        'suggested_text' => 'Patient reports neck tightness improved after treatment.',
        'status' => 'pending',
    ]);

    $this->assertDatabaseHas('ai_usage_logs', [
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'feature' => 'improve_note',
        'status' => 'success',
    ]);
});

it('uses selected practice context for a super admin with no practice_id', function () {
    $practice = Practice::factory()->create();
    $otherPractice = Practice::factory()->create();
    $superAdmin = User::factory()->create(['practice_id' => null]);
    $encounter = createEncounterForPractice($practice);

    app()->instance(AIService::class, new class extends AIService {
        public function improveNote(string $note, array $context = []): string
        {
            return 'Improved selected-practice note.';
        }
    });

    $this->actingAs($superAdmin);
    PracticeContext::setCurrentPracticeId($practice->id);

    Livewire::test(EditEncounter::class, ['record' => $encounter->id])
        ->set('data.visit_notes', 'selected practice note')
        ->call('improveNote');

    $this->assertDatabaseHas('ai_suggestions', [
        'practice_id' => $practice->id,
        'user_id' => $superAdmin->id,
        'encounter_id' => $encounter->id,
        'suggested_text' => 'Improved selected-practice note.',
    ]);

    $this->assertDatabaseMissing('ai_suggestions', [
        'practice_id' => $otherPractice->id,
        'user_id' => $superAdmin->id,
    ]);
});

it('copies the suggestion into the note only after explicit accept', function () {
    $practice = Practice::factory()->create();
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $encounter = createEncounterForPractice($practice, [
        'visit_notes' => 'original rough note',
    ]);

    $suggestion = AISuggestion::create([
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'patient_id' => $encounter->patient_id,
        'appointment_id' => $encounter->appointment_id,
        'encounter_id' => $encounter->id,
        'feature' => 'improve_note',
        'original_text' => 'original rough note',
        'suggested_text' => 'Improved note text.',
        'status' => 'pending',
    ]);

    $this->actingAs($user);

    Livewire::test(EditEncounter::class, ['record' => $encounter->id])
        ->set('data.ai_suggestion', 'Improved note text.')
        ->set('data.ai_suggestion_id', $suggestion->id)
        ->call('acceptAISuggestion')
        ->call('saveDraft');

    $encounter->refresh();
    $suggestion->refresh();

    expect($encounter->visit_notes)->toBe('Improved note text.');
    expect($suggestion->status)->toBe('accepted');
    expect($suggestion->accepted_text)->toBe('Improved note text.');
    expect($suggestion->accepted_at)->not->toBeNull();
});

it('logs failed AI calls and stores failed suggestions', function () {
    $practice = Practice::factory()->create();
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $encounter = createEncounterForPractice($practice);

    app()->instance(AIService::class, new class extends AIService {
        public function improveNote(string $note, array $context = []): string
        {
            throw new AIUnavailableException('AI offline');
        }
    });

    $this->actingAs($user);

    Livewire::test(EditEncounter::class, ['record' => $encounter->id])
        ->set('data.visit_notes', 'rough failed note')
        ->call('improveNote');

    $this->assertDatabaseHas('ai_suggestions', [
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'encounter_id' => $encounter->id,
        'original_text' => 'rough failed note',
        'suggested_text' => null,
        'status' => 'failed',
    ]);

    $this->assertDatabaseHas('ai_usage_logs', [
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'feature' => 'improve_note',
        'status' => 'failed',
        'error_message' => 'AI offline',
    ]);
});
