<?php

use App\Filament\Resources\Encounters\Pages\EditEncounter;
use App\Filament\Resources\Encounters\Pages\CreateEncounter;
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

it('AIService reads text from nested OpenAI responses output content', function () {
    config([
        'services.ai.provider' => 'openai',
        'services.ai.openai.api_key' => 'test-key',
        'services.ai.openai.model' => 'gpt-test',
    ]);

    Http::fake([
        'api.openai.com/v1/responses' => Http::response([
            'output' => [
                [
                    'type' => 'message',
                    'role' => 'assistant',
                    'content' => [
                        [
                            'type' => 'output_text',
                            'text' => 'Patient reports neck tightness improved after treatment.',
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $result = app(AIService::class)->improveNote('neck tight better after tx');

    expect($result)->toBe('Patient reports neck tightness improved after treatment.');
});

it('AIService improves a specific encounter field with field context', function () {
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

    $result = app(AIService::class)->improveField('neck tight better after tx', 'Subjective');

    expect($result)->toBe('Patient reports neck tightness improved after treatment.');

    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer test-key')
        && $request['model'] === 'gpt-test'
        && str_contains($request['instructions'], 'provided Subjective text')
        && str_contains($request['instructions'], 'Do not assign billing codes')
        && str_contains($request['input'], 'Subjective text to improve:')
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
        ->call('improveNote')
        ->assertSet('data.ai_suggestion', 'Patient reports neck tightness improved after treatment.')
        ->assertSet('data.visit_notes', 'neck tight better after tx');

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

it('can improve and accept an unsaved note on the create encounter screen', function () {
    $practice = Practice::factory()->create();
    $user = User::factory()->create(['practice_id' => $practice->id]);

    app()->instance(AIService::class, new class extends AIService {
        public function improveNote(string $note, array $context = []): string
        {
            return 'Patient reports neck tightness improved after treatment.';
        }
    });

    $this->actingAs($user);

    Livewire::test(CreateEncounter::class)
        ->set('data.visit_notes', 'neck tight better after tx')
        ->set('data.discipline', 'acupuncture')
        ->call('improveNote')
        ->assertSet('data.ai_suggestion', 'Patient reports neck tightness improved after treatment.')
        ->call('acceptAISuggestion')
        ->assertSet('data.visit_notes', 'Patient reports neck tightness improved after treatment.');

    expect(Encounter::withoutPracticeScope()->where('practice_id', $practice->id)->count())->toBe(0);

    $this->assertDatabaseHas('ai_suggestions', [
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'encounter_id' => null,
        'feature' => 'improve_note',
        'original_text' => 'neck tight better after tx',
        'suggested_text' => 'Patient reports neck tightness improved after treatment.',
        'accepted_text' => 'Patient reports neck tightness improved after treatment.',
        'status' => 'accepted',
    ]);

    $this->assertDatabaseHas('ai_usage_logs', [
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'feature' => 'improve_note',
        'status' => 'success',
    ]);
});

it('field-level improve writes suggestion to the selected field state only', function () {
    $practice = Practice::factory()->create();
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $encounter = createEncounterForPractice($practice, [
        'subjective' => 'neck tight better after tx',
        'objective' => 'ROM mildly limited',
        'assessment' => 'responding',
        'plan' => 'return next week',
    ]);

    app()->instance(AIService::class, new class extends AIService {
        public function improveField(string $text, string $fieldName, array $context = []): string
        {
            expect($fieldName)->toBe('Subjective');

            return 'Patient reports neck tightness improved after treatment.';
        }
    });

    $this->actingAs($user);

    Livewire::test(EditEncounter::class, ['record' => $encounter->id])
        ->set('data.subjective', 'neck tight better after tx')
        ->set('data.objective', 'ROM mildly limited')
        ->set('data.assessment', 'responding')
        ->set('data.plan', 'return next week')
        ->call('improveSubjectiveField')
        ->assertSet('data.active_ai_field', 'subjective')
        ->assertSet('data.active_ai_field_label', 'Subjective')
        ->assertSet('data.active_ai_suggestion', 'Patient reports neck tightness improved after treatment.')
        ->assertSee('AI Review')
        ->assertSet('data.subjective', 'neck tight better after tx')
        ->assertSet('data.objective', 'ROM mildly limited')
        ->assertSet('data.assessment', 'responding')
        ->assertSet('data.plan', 'return next week');

    $encounter->refresh();

    expect($encounter->subjective)->toBe('neck tight better after tx');
    expect($encounter->objective)->toBe('ROM mildly limited');

    $suggestion = AISuggestion::where('practice_id', $practice->id)
        ->where('feature', 'improve_field')
        ->firstOrFail();

    expect($suggestion->context_json)->toMatchArray([
        'field' => 'subjective',
        'field_label' => 'Subjective',
    ]);

    $this->assertDatabaseHas('ai_suggestions', [
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'encounter_id' => $encounter->id,
        'feature' => 'improve_field',
        'original_text' => 'neck tight better after tx',
        'suggested_text' => 'Patient reports neck tightness improved after treatment.',
        'status' => 'pending',
    ]);

    $this->assertDatabaseHas('ai_usage_logs', [
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'feature' => 'improve_field',
        'status' => 'success',
    ]);
});

it('accepting field-level suggestion updates only that field', function () {
    $practice = Practice::factory()->create();
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $encounter = createEncounterForPractice($practice, [
        'subjective' => 'neck tight better after tx',
        'objective' => 'ROM mildly limited',
        'assessment' => 'responding',
        'plan' => 'return next week',
    ]);

    $suggestion = AISuggestion::create([
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'patient_id' => $encounter->patient_id,
        'appointment_id' => $encounter->appointment_id,
        'encounter_id' => $encounter->id,
        'feature' => 'improve_field',
        'context_json' => ['field' => 'subjective', 'field_label' => 'Subjective'],
        'original_text' => 'neck tight better after tx',
        'suggested_text' => 'Improved subjective text.',
        'status' => 'pending',
    ]);

    $this->actingAs($user);

    Livewire::test(EditEncounter::class, ['record' => $encounter->id])
        ->set('data.active_ai_field', 'subjective')
        ->set('data.active_ai_field_label', 'Subjective')
        ->set('data.active_ai_suggestion', 'Improved subjective text.')
        ->set('data.active_ai_suggestion_id', $suggestion->id)
        ->call('acceptActiveFieldSuggestion')
        ->assertSet('data.subjective', 'Improved subjective text.')
        ->assertSet('data.objective', 'ROM mildly limited')
        ->assertSet('data.assessment', 'responding')
        ->assertSet('data.plan', 'return next week')
        ->assertSet('data.active_ai_field', null)
        ->assertSet('data.active_ai_suggestion', null)
        ->assertSet('data.active_ai_suggestion_id', null)
        ->assertSet('data.ai_assisted_fields.subjective', true);

    $encounter->refresh();
    $suggestion->refresh();

    expect($encounter->subjective)->toBe('Improved subjective text.');
    expect($encounter->objective)->toBe('ROM mildly limited');
    expect($encounter->assessment)->toBe('responding');
    expect($encounter->plan)->toBe('return next week');
    expect($suggestion->status)->toBe('accepted');
    expect($suggestion->accepted_text)->toBe('Improved subjective text.');
});

it('dismissing field-level suggestion clears the panel without changing the field', function () {
    $practice = Practice::factory()->create();
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $encounter = createEncounterForPractice($practice, [
        'objective' => 'ROM mildly limited',
    ]);

    $suggestion = AISuggestion::create([
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'patient_id' => $encounter->patient_id,
        'appointment_id' => $encounter->appointment_id,
        'encounter_id' => $encounter->id,
        'feature' => 'improve_field',
        'context_json' => ['field' => 'objective', 'field_label' => 'Objective'],
        'original_text' => 'ROM mildly limited',
        'suggested_text' => 'Objective range of motion is mildly limited.',
        'status' => 'pending',
    ]);

    $this->actingAs($user);

    Livewire::test(EditEncounter::class, ['record' => $encounter->id])
        ->set('data.active_ai_field', 'objective')
        ->set('data.active_ai_field_label', 'Objective')
        ->set('data.active_ai_suggestion', 'Objective range of motion is mildly limited.')
        ->set('data.active_ai_suggestion_id', $suggestion->id)
        ->call('dismissActiveFieldSuggestion')
        ->assertSet('data.objective', 'ROM mildly limited')
        ->assertSet('data.active_ai_field', null)
        ->assertSet('data.active_ai_suggestion', null)
        ->assertSet('data.active_ai_suggestion_id', null);

    $encounter->refresh();
    $suggestion->refresh();

    expect($encounter->objective)->toBe('ROM mildly limited');
    expect($suggestion->status)->toBe('dismissed');
});

it('field-level improve uses selected practice context for a super admin', function () {
    $practice = Practice::factory()->create();
    $otherPractice = Practice::factory()->create();
    $superAdmin = User::factory()->create(['practice_id' => null]);
    $encounter = createEncounterForPractice($practice, [
        'plan' => 'return one week',
    ]);

    app()->instance(AIService::class, new class extends AIService {
        public function improveField(string $text, string $fieldName, array $context = []): string
        {
            return 'Return in one week for follow-up.';
        }
    });

    $this->actingAs($superAdmin);
    PracticeContext::setCurrentPracticeId($practice->id);

    Livewire::test(EditEncounter::class, ['record' => $encounter->id])
        ->set('data.plan', 'return one week')
        ->call('improvePlanField')
        ->assertSet('data.active_ai_field', 'plan')
        ->assertSet('data.active_ai_suggestion', 'Return in one week for follow-up.');

    $this->assertDatabaseHas('ai_suggestions', [
        'practice_id' => $practice->id,
        'user_id' => $superAdmin->id,
        'encounter_id' => $encounter->id,
        'feature' => 'improve_field',
        'suggested_text' => 'Return in one week for follow-up.',
    ]);

    $this->assertDatabaseMissing('ai_suggestions', [
        'practice_id' => $otherPractice->id,
        'user_id' => $superAdmin->id,
        'feature' => 'improve_field',
    ]);
});

it('logs failed field-level AI calls cleanly', function () {
    $practice = Practice::factory()->create();
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $encounter = createEncounterForPractice($practice, [
        'objective' => 'ROM limited',
    ]);

    app()->instance(AIService::class, new class extends AIService {
        public function improveField(string $text, string $fieldName, array $context = []): string
        {
            throw new AIUnavailableException('Field AI offline');
        }
    });

    $this->actingAs($user);

    Livewire::test(EditEncounter::class, ['record' => $encounter->id])
        ->set('data.objective', 'ROM limited')
        ->call('improveObjectiveField');

    $encounter->refresh();
    expect($encounter->objective)->toBe('ROM limited');

    $this->assertDatabaseHas('ai_suggestions', [
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'encounter_id' => $encounter->id,
        'feature' => 'improve_field',
        'original_text' => 'ROM limited',
        'suggested_text' => null,
        'status' => 'failed',
    ]);

    $this->assertDatabaseHas('ai_usage_logs', [
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'feature' => 'improve_field',
        'status' => 'failed',
        'error_message' => 'Field AI offline',
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
