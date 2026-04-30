<?php

use App\Filament\Resources\Encounters\Pages\Concerns\HandlesEncounterAIActions;
use App\Filament\Resources\Encounters\Pages\CreateEncounter;
use App\Filament\Resources\Encounters\Pages\EditEncounter;
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
use App\Services\EncounterNoteDocument;
use App\Services\PracticeContext;
use App\Support\PracticeType;
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

function encounterAIActionHarness(Encounter $encounter, array $data): object
{
    return new class($encounter, $data)
    {
        use HandlesEncounterAIActions;

        public array $data;

        public object $form;

        public function __construct(public Encounter $record, array $data)
        {
            $this->data = $data;
            $this->form = new class
            {
                public function fillPartially(
                    array $state,
                    array $paths,
                    bool $shouldCallHydrationHooks = false,
                    bool $shouldFillStateWithNull = false,
                ): void {
                    //
                }
            };
        }
    };
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

it('AIService includes TCM acupuncture practice type context in improve note prompts', function () {
    config([
        'services.ai.provider' => 'openai',
        'services.ai.openai.api_key' => 'test-key',
        'services.ai.openai.model' => 'gpt-test',
    ]);

    Http::fake([
        'api.openai.com/v1/responses' => Http::response([
            'output_text' => 'Improved TCM-compatible note.',
        ]),
    ]);

    app(AIService::class)->improveNote('neck tight, LV qi constraint noted', [
        'practice_type' => PracticeType::TCM_ACUPUNCTURE,
    ]);

    Http::assertSent(fn ($request) => str_contains($request['instructions'], 'For TCM Acupuncture')
        && str_contains($request['instructions'], 'pattern impression')
        && str_contains($request['instructions'], 'channel logic')
        && str_contains($request['instructions'], 'Do not invent clinical findings')
        && str_contains($request['instructions'], 'Do not invent tongue, pulse, pattern diagnosis')
        && str_contains($request['input'], 'Practice Type: TCM Acupuncture'));
});

it('AIService includes Worsley Five Element practice type context for improve note and AI Draft prompts', function () {
    config([
        'services.ai.provider' => 'openai',
        'services.ai.openai.api_key' => 'test-key',
        'services.ai.openai.model' => 'gpt-test',
    ]);

    Http::fake([
        'api.openai.com/v1/responses' => Http::response([
            'output_text' => 'Improved Five Element-compatible note.',
        ]),
    ]);

    app(AIService::class)->improveNote('patient presentation included CSOE observations', [
        'practice_type' => PracticeType::FIVE_ELEMENT_ACUPUNCTURE,
    ]);

    Http::assertSent(fn ($request) => str_contains($request['instructions'], 'For Five Element Acupuncture')
        && str_contains($request['instructions'], 'The Worsley Five Element system has its own nomenclature')
        && str_contains($request['instructions'], 'Do not rewrite Five Element notes into generic TCM language')
        && str_contains($request['instructions'], 'Roman I = Heart')
        && str_contains($request['instructions'], 'Roman VII = Gallbladder')
        && str_contains($request['instructions'], 'Aggressive Energy treatment')
        && str_contains($request['instructions'], 'Husband-Wife treatment')
        && str_contains($request['instructions'], 'Entry-Exit blocks')
        && str_contains($request['instructions'], 'Causative Factor / CF')
        && str_contains($request['instructions'], 'Do not invent diagnosis, CF, points, pulses, blocks, or treatment details not present')
        && str_contains($request['instructions'], 'For practitioner-facing clinical notes, preserve Five Element terminology')
        && str_contains($request['instructions'], 'Do not force TCM pattern diagnosis')
        && str_contains($request['input'], 'Practice Type: Five Element Acupuncture'));
});

it('AIService includes Worsley Five Element practice type context in field improve prompts', function () {
    config([
        'services.ai.provider' => 'openai',
        'services.ai.openai.api_key' => 'test-key',
        'services.ai.openai.model' => 'gpt-test',
    ]);

    Http::fake([
        'api.openai.com/v1/responses' => Http::response([
            'output_text' => 'Improved Five Element-compatible field.',
        ]),
    ]);

    app(AIService::class)->improveField('AE drain documented with moxa on command points', 'Treatment Notes', [
        'practice_type' => PracticeType::FIVE_ELEMENT_ACUPUNCTURE,
    ]);

    Http::assertSent(fn ($request) => str_contains($request['instructions'], 'provided Treatment Notes text')
        && str_contains($request['instructions'], 'The Worsley Five Element system has its own nomenclature')
        && str_contains($request['instructions'], 'Roman I = Heart')
        && str_contains($request['instructions'], 'Roman VII = Gallbladder')
        && str_contains($request['instructions'], 'Aggressive Energy treatment')
        && str_contains($request['instructions'], 'Husband-Wife treatment')
        && str_contains($request['instructions'], 'moxa as part of treatment documentation')
        && str_contains($request['input'], 'Practice Type: Five Element Acupuncture'));
});

it('AIService does not include acupuncture-specific instructions for non-acupuncture practice types', function (string $practiceType, string $expectedInstruction) {
    config([
        'services.ai.provider' => 'openai',
        'services.ai.openai.api_key' => 'test-key',
        'services.ai.openai.model' => 'gpt-test',
    ]);

    Http::fake([
        'api.openai.com/v1/responses' => Http::response([
            'output_text' => 'Improved practice-compatible note.',
        ]),
    ]);

    app(AIService::class)->improveNote('rough visit note text', [
        'practice_type' => $practiceType,
    ]);

    Http::assertSent(fn ($request) => str_contains($request['instructions'], $expectedInstruction)
        && ! str_contains($request['instructions'], 'For TCM Acupuncture')
        && ! str_contains($request['instructions'], 'For Five Element Acupuncture')
        && ! str_contains($request['instructions'], 'Worsley Five Element')
        && ! str_contains($request['instructions'], 'Roman I = Heart')
        && ! str_contains($request['instructions'], 'Aggressive Energy treatment')
        && ! str_contains($request['instructions'], 'pattern diagnosis'));
})->with([
    'general wellness' => [PracticeType::GENERAL_WELLNESS, 'For General Wellness'],
    'chiropractic' => [PracticeType::CHIROPRACTIC, 'For Chiropractic'],
    'massage therapy' => [PracticeType::MASSAGE_THERAPY, 'For Massage Therapy'],
    'physiotherapy' => [PracticeType::PHYSIOTHERAPY, 'For Physiotherapy'],
]);

it('AIService keeps Worsley guidance out of TCM acupuncture improve prompts', function () {
    config([
        'services.ai.provider' => 'openai',
        'services.ai.openai.api_key' => 'test-key',
        'services.ai.openai.model' => 'gpt-test',
    ]);

    Http::fake([
        'api.openai.com/v1/responses' => Http::response([
            'output_text' => 'Improved TCM-compatible note.',
        ]),
    ]);

    app(AIService::class)->improveNote('neck tight, LV qi constraint noted', [
        'practice_type' => PracticeType::TCM_ACUPUNCTURE,
    ]);

    Http::assertSent(fn ($request) => str_contains($request['instructions'], 'For TCM Acupuncture')
        && ! str_contains($request['instructions'], 'Worsley Five Element')
        && ! str_contains($request['instructions'], 'Roman I = Heart')
        && ! str_contains($request['instructions'], 'Roman VII = Gallbladder')
        && ! str_contains($request['instructions'], 'Aggressive Energy treatment')
        && ! str_contains($request['instructions'], 'Husband-Wife treatment'));
});

it('resets an existing simple visit note from the encounter practice when ambient practice context is stale', function () {
    $visitPractice = Practice::factory()->create([
        'insurance_billing_enabled' => false,
        'practice_type' => PracticeType::MASSAGE_THERAPY,
    ]);
    $ambientPractice = Practice::factory()->create([
        'insurance_billing_enabled' => false,
        'practice_type' => PracticeType::TCM_ACUPUNCTURE,
    ]);
    $superAdmin = User::factory()->create(['practice_id' => null]);
    $encounter = createEncounterForPractice($visitPractice);

    $this->actingAs($superAdmin);
    PracticeContext::setCurrentPracticeId($ambientPractice->id);

    $component = encounterAIActionHarness($encounter, [
        'visit_note_document' => 'Unsaved custom note.',
    ]);

    $component->resetVisitNoteTemplate();

    expect($component->data['visit_note_document'])
        ->toBe(EncounterNoteDocument::template(PracticeType::MASSAGE_THERAPY));
});

it('uses the encounter practice type for simple visit note AI when ambient practice context is stale', function () {
    $visitPractice = Practice::factory()->create([
        'insurance_billing_enabled' => false,
        'practice_type' => PracticeType::TCM_ACUPUNCTURE,
    ]);
    $ambientPractice = Practice::factory()->create([
        'insurance_billing_enabled' => false,
        'practice_type' => PracticeType::MASSAGE_THERAPY,
    ]);
    $superAdmin = User::factory()->create(['practice_id' => null]);
    $encounter = createEncounterForPractice($visitPractice);

    app()->instance(AIService::class, new class extends AIService
    {
        public function improveNote(string $note, array $context = []): string
        {
            expect($context['practice_type'])->toBe(PracticeType::TCM_ACUPUNCTURE);

            return 'Improved record-practice note.';
        }
    });

    $this->actingAs($superAdmin);
    PracticeContext::setCurrentPracticeId($ambientPractice->id);

    $component = encounterAIActionHarness($encounter, [
        'chief_complaint' => 'neck tight',
        'visit_note_document' => EncounterNoteDocument::template(PracticeType::TCM_ACUPUNCTURE),
    ]);

    $component->improveNote(app(AIService::class));

    expect($component->data['ai_suggestion'])->toBe('Improved record-practice note.');

    $this->assertDatabaseHas('ai_suggestions', [
        'practice_id' => $visitPractice->id,
        'user_id' => $superAdmin->id,
        'encounter_id' => $encounter->id,
        'feature' => 'improve_note',
        'suggested_text' => 'Improved record-practice note.',
    ]);

    $this->assertDatabaseMissing('ai_suggestions', [
        'practice_id' => $ambientPractice->id,
        'user_id' => $superAdmin->id,
        'encounter_id' => $encounter->id,
        'feature' => 'improve_note',
    ]);
});

it('uses practitioner Clinical Style override for simple visit note AI', function () {
    $visitPractice = Practice::factory()->create([
        'insurance_billing_enabled' => false,
        'practice_type' => PracticeType::TCM_ACUPUNCTURE,
        'discipline' => 'acupuncture',
    ]);
    $user = User::factory()->create(['practice_id' => $visitPractice->id]);
    $encounter = createEncounterForPractice($visitPractice);
    $encounter->practitioner->update([
        'clinical_style' => PracticeType::FIVE_ELEMENT_ACUPUNCTURE,
    ]);

    app()->instance(AIService::class, new class extends AIService
    {
        public function improveNote(string $note, array $context = []): string
        {
            expect($context['practice_type'])->toBe(PracticeType::FIVE_ELEMENT_ACUPUNCTURE);

            return 'Improved practitioner-style note.';
        }
    });

    $this->actingAs($user);

    $component = encounterAIActionHarness($encounter, [
        'chief_complaint' => 'neck tight',
        'visit_note_document' => EncounterNoteDocument::template(PracticeType::FIVE_ELEMENT_ACUPUNCTURE),
    ]);

    $component->improveNote(app(AIService::class));

    expect($component->data['ai_suggestion'])->toBe('Improved practitioner-style note.');
});

it('AIService is unavailable when no API key is configured', function () {
    config(['services.ai.openai.api_key' => null]);

    expect(fn () => app(AIService::class)->improveNote('rough note'))
        ->toThrow(AIUnavailableException::class);
});

it('creates a practice-scoped AI suggestion and preserves the original note until accepted', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => true]);
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $encounter = createEncounterForPractice($practice);

    app()->instance(AIService::class, new class extends AIService
    {
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
    $practice = Practice::factory()->create(['insurance_billing_enabled' => true]);
    $user = User::factory()->create(['practice_id' => $practice->id]);

    app()->instance(AIService::class, new class extends AIService
    {
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
    $practice = Practice::factory()->create(['insurance_billing_enabled' => true]);
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $encounter = createEncounterForPractice($practice, [
        'subjective' => 'neck tight better after tx',
        'objective' => 'ROM mildly limited',
        'assessment' => 'responding',
        'plan' => 'return next week',
    ]);

    app()->instance(AIService::class, new class extends AIService
    {
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
        ->assertSee('AI suggestion')
        ->assertSee('Patient reports neck tightness improved after treatment.')
        ->assertSee('Accept')
        ->assertSee('Dismiss')
        ->assertDontSee('AI Review')
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

it('does not accept a field-level suggestion before one exists', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => true]);
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $encounter = createEncounterForPractice($practice, [
        'subjective' => 'original subjective text',
    ]);

    $this->actingAs($user);

    Livewire::test(EditEncounter::class, ['record' => $encounter->id])
        ->set('data.subjective', 'original subjective text')
        ->call('acceptSubjectiveFieldSuggestion')
        ->assertSet('data.subjective', 'original subjective text')
        ->assertSet('data.active_ai_field', null)
        ->assertSet('data.active_ai_suggestion', null);

    $encounter->refresh();

    expect($encounter->subjective)->toBe('original subjective text');
});

it('accepting field-level suggestion updates only that field', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => true]);
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
        ->assertSet('data.ai_assisted_fields.subjective', true)
        ->assertSee('AI-assisted');

    $encounter->refresh();
    $suggestion->refresh();

    expect($encounter->subjective)->toBe('Improved subjective text.');
    expect($encounter->objective)->toBe('ROM mildly limited');
    expect($encounter->assessment)->toBe('responding');
    expect($encounter->plan)->toBe('return next week');
    expect($suggestion->status)->toBe('accepted');
    expect($suggestion->accepted_text)->toBe('Improved subjective text.');
});

it('only keeps one active field-level suggestion at a time', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => true]);
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $encounter = createEncounterForPractice($practice, [
        'subjective' => 'neck tight',
        'plan' => 'return next week',
    ]);

    app()->instance(AIService::class, new class extends AIService
    {
        public function improveField(string $text, string $fieldName, array $context = []): string
        {
            return $fieldName === 'Plan'
                ? 'Return next week for follow-up.'
                : 'Patient reports neck tightness.';
        }
    });

    $this->actingAs($user);

    Livewire::test(EditEncounter::class, ['record' => $encounter->id])
        ->set('data.subjective', 'neck tight')
        ->set('data.plan', 'return next week')
        ->call('improveSubjectiveField')
        ->assertSet('data.active_ai_field', 'subjective')
        ->assertSet('data.active_ai_suggestion', 'Patient reports neck tightness.')
        ->call('improvePlanField')
        ->assertSet('data.active_ai_field', 'plan')
        ->assertSet('data.active_ai_suggestion', 'Return next week for follow-up.')
        ->assertSet('data.subjective', 'neck tight')
        ->assertSet('data.plan', 'return next week');
});

it('dismissing field-level suggestion clears the panel without changing the field', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => true]);
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

    app()->instance(AIService::class, new class extends AIService
    {
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
    $practice = Practice::factory()->create(['insurance_billing_enabled' => true]);
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $encounter = createEncounterForPractice($practice, [
        'objective' => 'ROM limited',
    ]);

    app()->instance(AIService::class, new class extends AIService
    {
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

    app()->instance(AIService::class, new class extends AIService
    {
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
    $practice = Practice::factory()->create(['insurance_billing_enabled' => true]);
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

    app()->instance(AIService::class, new class extends AIService
    {
        public function improveNote(string $note, array $context = []): string
        {
            throw new AIUnavailableException('AI offline');
        }
    });

    $this->actingAs($user);

    Livewire::test(EditEncounter::class, ['record' => $encounter->id])
        ->set('data.visit_note_document', "Chief Complaint:\n\nTreatment Notes:\nrough failed note\n\nPlan / Follow-up:\n")
        ->call('improveNote');

    $this->assertDatabaseHas('ai_suggestions', [
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'encounter_id' => $encounter->id,
        'original_text' => "Chief Complaint:\n\nTreatment Notes:\nrough failed note\n\nPlan / Follow-up:",
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

it('creates an AI Draft for the unified simple visit note without changing the note', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => false]);
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $encounter = createEncounterForPractice($practice, [
        'chief_complaint' => 'neck tight',
        'visit_notes' => 'tx helped',
        'plan' => 'return prn',
    ]);

    app()->instance(AIService::class, new class extends AIService
    {
        public function improveNote(string $note, array $context = []): string
        {
            expect($note)->toContain('Reason for Visit:');
            expect($note)->toContain('Visit Note:');
            expect($note)->toContain('Care Provided:');
            expect($note)->toContain('Response:');
            expect($note)->toContain('Plan / Follow-up:');

            return "Chief Complaint:\nNeck tightness.\n\nTreatment Notes:\nTreatment was tolerated well.\n\nPlan / Follow-up:\nReturn as needed.";
        }
    });

    $this->actingAs($user);

    Livewire::test(EditEncounter::class, ['record' => $encounter->id])
        ->assertSee('AI Assist / Improve with AI')
        ->assertDontSee('AI Draft')
        ->assertDontSee('Note Provenance')
        ->assertDontSee('Original Source')
        ->assertDontSee('Final Practitioner Note')
        ->call('improveNote')
        ->assertSet('data.ai_suggestion', "Chief Complaint:\nNeck tightness.\n\nTreatment Notes:\nTreatment was tolerated well.\n\nPlan / Follow-up:\nReturn as needed.")
        ->assertSet('data.visit_note_document', EncounterNoteDocument::fromFields('neck tight', 'tx helped', 'return prn', PracticeType::GENERAL_WELLNESS))
        ->assertSee('AI Draft')
        ->assertSee('Replace Note')
        ->assertSee('Insert Below')
        ->assertSee('Dismiss');

    $encounter->refresh();

    expect($encounter->chief_complaint)->toBe('neck tight');
    expect($encounter->visit_notes)->toBe('tx helped');
    expect($encounter->plan)->toBe('return prn');

    $this->assertDatabaseHas('ai_suggestions', [
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'encounter_id' => $encounter->id,
        'feature' => 'improve_note',
        'original_text' => EncounterNoteDocument::fromFields('neck tight', 'tx helped', 'return prn', PracticeType::GENERAL_WELLNESS),
        'suggested_text' => "Chief Complaint:\nNeck tightness.\n\nTreatment Notes:\nTreatment was tolerated well.\n\nPlan / Follow-up:\nReturn as needed.",
        'accepted_text' => null,
        'status' => 'pending',
    ]);
});

it('replaces the simple visit note with an AI Draft only after explicit replace and save', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => false]);
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $encounter = createEncounterForPractice($practice, [
        'chief_complaint' => 'neck tight',
        'visit_notes' => 'tx helped',
        'plan' => 'return prn',
    ]);

    $draft = "Chief Complaint:\nNeck tightness.\n\nTreatment Notes:\nTreatment was tolerated well.\n\nPlan / Follow-up:\nReturn as needed.";
    $suggestion = AISuggestion::create([
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'patient_id' => $encounter->patient_id,
        'appointment_id' => $encounter->appointment_id,
        'encounter_id' => $encounter->id,
        'feature' => 'improve_note',
        'original_text' => EncounterNoteDocument::fromFields('neck tight', 'tx helped', 'return prn', PracticeType::GENERAL_WELLNESS),
        'suggested_text' => $draft,
        'status' => 'pending',
    ]);

    $this->actingAs($user);

    Livewire::test(EditEncounter::class, ['record' => $encounter->id])
        ->set('data.ai_suggestion', $draft)
        ->set('data.ai_suggestion_id', $suggestion->id)
        ->call('replaceNoteWithAIDraft')
        ->assertSet('data.visit_note_document', $draft)
        ->assertSet('data.ai_suggestion', null);

    $encounter->refresh();
    expect($encounter->visit_notes)->toBe('tx helped');

    Livewire::test(EditEncounter::class, ['record' => $encounter->id])
        ->set('data.visit_note_document', $draft)
        ->call('saveDraft');

    $encounter->refresh();
    $suggestion->refresh();

    expect($encounter->chief_complaint)->toBe('Neck tightness.');
    expect($encounter->visit_notes)->toBe('Treatment was tolerated well.');
    expect($encounter->plan)->toBe('Return as needed.');
    expect($suggestion->status)->toBe('accepted');
    expect($suggestion->accepted_text)->toBe($draft);
});

it('inserts the AI Draft below the simple visit note without saving immediately', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => false]);
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $encounter = createEncounterForPractice($practice, [
        'chief_complaint' => 'neck tight',
        'visit_notes' => 'tx helped',
        'plan' => 'return prn',
    ]);

    $currentNote = EncounterNoteDocument::fromFields('neck tight', 'tx helped', 'return prn', PracticeType::GENERAL_WELLNESS);
    $draft = "Chief Complaint:\nNeck tightness.\n\nTreatment Notes:\nTreatment was tolerated well.\n\nPlan / Follow-up:\nReturn as needed.";
    $expected = $currentNote."\n\n---\n\nAI Draft\n".$draft;

    $this->actingAs($user);

    Livewire::test(EditEncounter::class, ['record' => $encounter->id])
        ->set('data.visit_note_document', $currentNote)
        ->set('data.ai_suggestion', $draft)
        ->call('insertAIDraftBelowNote')
        ->assertSet('data.visit_note_document', $expected)
        ->assertSet('data.ai_suggestion', null);

    $encounter->refresh();
    expect($encounter->visit_notes)->toBe('tx helped');
});

it('dismisses the AI Draft without changing the simple visit note', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => false]);
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $encounter = createEncounterForPractice($practice, [
        'chief_complaint' => 'neck tight',
        'visit_notes' => 'tx helped',
        'plan' => 'return prn',
    ]);

    $currentNote = EncounterNoteDocument::fromFields('neck tight', 'tx helped', 'return prn', PracticeType::GENERAL_WELLNESS);
    $suggestion = AISuggestion::create([
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'patient_id' => $encounter->patient_id,
        'appointment_id' => $encounter->appointment_id,
        'encounter_id' => $encounter->id,
        'feature' => 'improve_note',
        'original_text' => $currentNote,
        'suggested_text' => 'Improved draft.',
        'status' => 'pending',
    ]);

    $this->actingAs($user);

    Livewire::test(EditEncounter::class, ['record' => $encounter->id])
        ->set('data.visit_note_document', $currentNote)
        ->set('data.ai_suggestion', 'Improved draft.')
        ->set('data.ai_suggestion_id', $suggestion->id)
        ->call('dismissAIDraft')
        ->assertSet('data.visit_note_document', $currentNote)
        ->assertSet('data.ai_suggestion', null)
        ->assertSet('data.ai_suggestion_id', null);

    $encounter->refresh();
    $suggestion->refresh();

    expect($encounter->visit_notes)->toBe('tx helped');
    expect($suggestion->status)->toBe('dismissed');
});
