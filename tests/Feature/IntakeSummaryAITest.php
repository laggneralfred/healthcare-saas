<?php

use App\Filament\Resources\MedicalHistories\Pages\ViewMedicalHistory;
use App\Models\Encounter;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\User;
use App\Services\AI\AIService;
use App\Services\PracticeContext;
use App\Support\PracticeType;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function createSubmittedIntakeForPractice(Practice $practice, array $attributes = []): MedicalHistory
{
    $patient = Patient::factory()->create(['practice_id' => $practice->id]);

    return MedicalHistory::factory()->create([
        'practice_id' => $practice->id,
        'patient_id' => $patient->id,
        'status' => 'complete',
        'chief_complaint' => 'Patient reports neck and shoulder tension after desk work.',
        'onset_duration' => '3 weeks',
        'aggravating_factors' => 'Worse after long work days.',
        'relieving_factors' => 'Heat helps temporarily.',
        'current_medications' => [['name' => 'Ibuprofen', 'dose' => '200mg', 'frequency' => 'as needed']],
        'allergies' => [['name' => 'Penicillin', 'reaction' => 'rash']],
        'treatment_goals' => 'Reduce tension and sleep better.',
        ...$attributes,
    ]);
}

it('AIService creates conservative intake summary prompts with patient-reported framing and Practice Type context', function () {
    config([
        'services.ai.provider' => 'openai',
        'services.ai.openai.api_key' => 'test-key',
        'services.ai.openai.model' => 'gpt-test',
    ]);

    Http::fake([
        'api.openai.com/v1/responses' => Http::response([
            'output_text' => "Patient-reported concerns:\nPatient reports neck tension.",
        ]),
    ]);

    $result = app(AIService::class)->summarizeIntake([
        'practice_type' => PracticeType::TCM_ACUPUNCTURE,
        'patient_reported' => [
            'chief_complaint' => 'Neck tension after desk work.',
        ],
    ]);

    expect($result)->toContain('Patient-reported concerns');

    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer test-key')
        && $request['model'] === 'gpt-test'
        && str_contains($request['instructions'], 'conservative intake summarization assistant')
        && str_contains($request['instructions'], 'Treat intake content as patient-reported information')
        && str_contains($request['instructions'], 'Do not diagnose')
        && str_contains($request['instructions'], 'Do not create a treatment plan')
        && str_contains($request['instructions'], 'Patient reports')
        && str_contains($request['input'], 'Practice Type')
        && str_contains($request['input'], 'TCM Acupuncture')
        && str_contains($request['input'], 'Patient-reported concerns')
        && str_contains($request['input'], 'Questions to clarify'));
});

it('TCM intake summary instructions do not allow diagnosis points or herbs', function () {
    config([
        'services.ai.provider' => 'openai',
        'services.ai.openai.api_key' => 'test-key',
        'services.ai.openai.model' => 'gpt-test',
    ]);

    Http::fake([
        'api.openai.com/v1/responses' => Http::response([
            'output_text' => 'Patient reports neck tension. Consider clarifying sleep and digestion.',
        ]),
    ]);

    app(AIService::class)->summarizeIntake([
        'practice_type' => PracticeType::TCM_ACUPUNCTURE,
        'patient_reported' => ['chief_complaint' => 'Neck tension.'],
    ]);

    Http::assertSent(fn ($request) => str_contains($request['instructions'], 'For TCM Acupuncture intake')
        && str_contains($request['instructions'], 'Do not assign TCM pattern diagnosis')
        && str_contains($request['instructions'], 'Do not suggest points or herbs')
        && str_contains($request['instructions'], 'Do not invent tongue or pulse'));
});

it('Five Element intake summary instructions do not assign CF or invent observations', function () {
    config([
        'services.ai.provider' => 'openai',
        'services.ai.openai.api_key' => 'test-key',
        'services.ai.openai.model' => 'gpt-test',
    ]);

    Http::fake([
        'api.openai.com/v1/responses' => Http::response([
            'output_text' => 'Patient mentions feeling flat after work stress.',
        ]),
    ]);

    app(AIService::class)->summarizeIntake([
        'practice_type' => PracticeType::FIVE_ELEMENT_ACUPUNCTURE,
        'patient_reported' => ['chief_complaint' => 'Feeling flat after work stress.'],
    ]);

    Http::assertSent(fn ($request) => str_contains($request['instructions'], 'For Five Element Acupuncture intake')
        && str_contains($request['instructions'], 'Do not assign CF or element')
        && str_contains($request['instructions'], 'Do not invent color, sound, odor, emotion, Officials, or treatment intention')
        && str_contains($request['instructions'], 'Do not translate patient story into TCM diagnosis'));
});

it('non-acupuncture intake summary instructions avoid invented discipline-specific findings', function (string $practiceType, string $expectedInstruction, string $guardrail) {
    config([
        'services.ai.provider' => 'openai',
        'services.ai.openai.api_key' => 'test-key',
        'services.ai.openai.model' => 'gpt-test',
    ]);

    Http::fake([
        'api.openai.com/v1/responses' => Http::response([
            'output_text' => 'Patient reports a concern. Consider clarifying details.',
        ]),
    ]);

    app(AIService::class)->summarizeIntake([
        'practice_type' => $practiceType,
        'patient_reported' => ['chief_complaint' => 'Patient concern.'],
    ]);

    Http::assertSent(fn ($request) => str_contains($request['instructions'], $expectedInstruction)
        && str_contains($request['instructions'], $guardrail)
        && ! str_contains($request['instructions'], 'Do not assign TCM pattern diagnosis')
        && ! str_contains($request['instructions'], 'Do not assign CF or element'));
})->with([
    'chiropractic' => [PracticeType::CHIROPRACTIC, 'For Chiropractic intake', 'Do not invent exam findings, orthopedic tests, neurological findings, adjustment plan, or diagnosis'],
    'massage therapy' => [PracticeType::MASSAGE_THERAPY, 'For Massage Therapy intake', 'Do not invent tissue findings, pressure level, techniques, contraindications, or response'],
    'physiotherapy' => [PracticeType::PHYSIOTHERAPY, 'For Physiotherapy intake', 'Do not invent ROM, strength grades, special tests, functional measures, exercise prescription, or diagnosis'],
    'general wellness' => [PracticeType::GENERAL_WELLNESS, 'For General Wellness intake', 'Avoid specialty framing unless already present'],
]);

it('generates a UI-only AI Intake Summary without changing the intake or Visit Note', function () {
    $practice = Practice::factory()->create([
        'practice_type' => PracticeType::MASSAGE_THERAPY,
        'discipline' => 'massage',
    ]);
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $intake = createSubmittedIntakeForPractice($practice, [
        'discipline' => 'massage',
        'discipline_responses' => [
            'massage' => [
                'focus_areas' => ['neck', 'shoulders'],
                'session_goals' => ['relaxation'],
            ],
        ],
    ]);
    $encounter = Encounter::factory()->create([
        'practice_id' => $practice->id,
        'patient_id' => $intake->patient_id,
        'appointment_id' => null,
        'chief_complaint' => 'Saved visit complaint',
        'visit_notes' => 'Saved visit note.',
        'plan' => 'Saved plan.',
    ]);

    app()->instance(AIService::class, new class extends AIService
    {
        public function summarizeIntake(array $context): string
        {
            expect($context['practice_type'])->toBe(PracticeType::MASSAGE_THERAPY);
            expect($context['patient_reported']['chief_complaint'])->toContain('Patient reports');

            return "Patient-reported concerns:\nPatient reports neck and shoulder tension.\n\nQuestions to clarify:\nConsider clarifying pressure preference.";
        }
    });

    $this->actingAs($user);

    Livewire::test(ViewMedicalHistory::class, ['record' => $intake->id])
        ->assertSee('Generate Intake Summary')
        ->assertDontSee('AI Intake Summary')
        ->call('generateIntakeSummary')
        ->assertSet('aiIntakeSummary', "Patient-reported concerns:\nPatient reports neck and shoulder tension.\n\nQuestions to clarify:\nConsider clarifying pressure preference.")
        ->assertSee('AI Intake Summary')
        ->assertSee('Questions to clarify');

    $intake->refresh();
    $encounter->refresh();

    expect($intake->summary_text)->toBeNull();
    expect($encounter->chief_complaint)->toBe('Saved visit complaint');
    expect($encounter->visit_notes)->toBe('Saved visit note.');
    expect($encounter->plan)->toBe('Saved plan.');

    $this->assertDatabaseHas('ai_suggestions', [
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'patient_id' => $intake->patient_id,
        'feature' => 'intake_summary',
        'suggested_text' => "Patient-reported concerns:\nPatient reports neck and shoulder tension.\n\nQuestions to clarify:\nConsider clarifying pressure preference.",
        'status' => 'pending',
    ]);

    $this->assertDatabaseHas('ai_usage_logs', [
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'feature' => 'intake_summary',
        'status' => 'success',
    ]);
});

it('does not generate an Intake Summary for a metadata-only intake', function () {
    $practice = Practice::factory()->create([
        'practice_type' => PracticeType::TCM_ACUPUNCTURE,
        'discipline' => 'acupuncture',
    ]);
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $intake = MedicalHistory::factory()->create([
        'practice_id' => $practice->id,
        'patient_id' => Patient::factory()->create(['practice_id' => $practice->id])->id,
        'status' => 'complete',
        'discipline' => 'acupuncture',
    ]);

    app()->instance(AIService::class, new class extends AIService
    {
        public function summarizeIntake(array $context): string
        {
            throw new RuntimeException('AI should not be called for a metadata-only intake.');
        }
    });

    $this->actingAs($user);

    Livewire::test(ViewMedicalHistory::class, ['record' => $intake->id])
        ->call('generateIntakeSummary')
        ->assertSet('aiIntakeSummary', null);

    $this->assertDatabaseMissing('ai_suggestions', [
        'practice_id' => $practice->id,
        'patient_id' => $intake->patient_id,
        'feature' => 'intake_summary',
    ]);

    $this->assertDatabaseMissing('ai_usage_logs', [
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'feature' => 'intake_summary',
    ]);
});

it('generates an Intake Summary when at least one substantive intake field exists', function () {
    $practice = Practice::factory()->create([
        'practice_type' => PracticeType::GENERAL_WELLNESS,
        'discipline' => 'general',
    ]);
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $intake = MedicalHistory::factory()->create([
        'practice_id' => $practice->id,
        'patient_id' => Patient::factory()->create(['practice_id' => $practice->id])->id,
        'status' => 'complete',
        'discipline' => 'general',
        'reason_for_visit' => 'Patient reports wanting help with stress and sleep.',
    ]);

    app()->instance(AIService::class, new class extends AIService
    {
        public function summarizeIntake(array $context): string
        {
            expect($context['patient_reported']['reason_for_visit'])->toContain('stress and sleep');

            return "Patient-reported concerns:\nPatient reports stress and sleep concerns.";
        }
    });

    $this->actingAs($user);

    Livewire::test(ViewMedicalHistory::class, ['record' => $intake->id])
        ->call('generateIntakeSummary')
        ->assertSet('aiIntakeSummary', "Patient-reported concerns:\nPatient reports stress and sleep concerns.")
        ->assertSee('Patient reports stress and sleep concerns.');

    $this->assertDatabaseHas('ai_suggestions', [
        'practice_id' => $practice->id,
        'patient_id' => $intake->patient_id,
        'feature' => 'intake_summary',
        'suggested_text' => "Patient-reported concerns:\nPatient reports stress and sleep concerns.",
        'status' => 'pending',
    ]);
});

it('uses the intake record practice type for Intake Summary when ambient PracticeContext is stale', function () {
    $intakePractice = Practice::factory()->create([
        'practice_type' => PracticeType::FIVE_ELEMENT_ACUPUNCTURE,
        'discipline' => 'acupuncture',
    ]);
    $ambientPractice = Practice::factory()->create([
        'practice_type' => PracticeType::MASSAGE_THERAPY,
        'discipline' => 'massage',
    ]);
    $superAdmin = User::factory()->create(['practice_id' => null]);
    $intake = createSubmittedIntakeForPractice($intakePractice, [
        'discipline' => 'acupuncture',
        'reason_for_visit' => 'Patient reports feeling disconnected after work stress.',
    ]);

    app()->instance(AIService::class, new class extends AIService
    {
        public function summarizeIntake(array $context): string
        {
            expect($context['practice_type'])->toBe(PracticeType::FIVE_ELEMENT_ACUPUNCTURE);

            return "Patient-reported concerns:\nPatient reports work stress.";
        }
    });

    $this->actingAs($superAdmin);
    PracticeContext::setCurrentPracticeId($ambientPractice->id);

    $page = new ViewMedicalHistory;
    $page->record = $intake;

    $page->generateIntakeSummary(app(AIService::class));

    expect($page->aiIntakeSummary)->toBe("Patient-reported concerns:\nPatient reports work stress.");

    $this->assertDatabaseHas('ai_suggestions', [
        'practice_id' => $intakePractice->id,
        'user_id' => $superAdmin->id,
        'patient_id' => $intake->patient_id,
        'feature' => 'intake_summary',
        'suggested_text' => "Patient-reported concerns:\nPatient reports work stress.",
        'status' => 'pending',
    ]);

    $this->assertDatabaseMissing('ai_suggestions', [
        'practice_id' => $ambientPractice->id,
        'user_id' => $superAdmin->id,
        'patient_id' => $intake->patient_id,
        'feature' => 'intake_summary',
    ]);
});

it('uses a Five Element-compatible section label for legacy acupuncture intake responses', function () {
    $practice = Practice::factory()->create([
        'practice_type' => PracticeType::FIVE_ELEMENT_ACUPUNCTURE,
        'discipline' => 'acupuncture',
    ]);
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $intake = createSubmittedIntakeForPractice($practice, [
        'discipline' => 'acupuncture',
        'discipline_responses' => [
            'tcm' => [
                'energy_level' => 'low',
                'sleep_issues' => ['staying_asleep'],
            ],
        ],
    ]);

    app()->instance(AIService::class, new class extends AIService
    {
        public function summarizeIntake(array $context): string
        {
            expect($context['practice_type'])->toBe(PracticeType::FIVE_ELEMENT_ACUPUNCTURE);
            expect($context['practice_type_specific_patient_responses']['section'])
                ->toBe('Five Element Acupuncture Intake')
                ->not->toBe('TCM Assessment');

            return "Patient-reported concerns:\nPatient reports neck and shoulder tension.";
        }
    });

    $this->actingAs($user);

    Livewire::test(ViewMedicalHistory::class, ['record' => $intake->id])
        ->call('generateIntakeSummary')
        ->assertSet('aiIntakeSummary', "Patient-reported concerns:\nPatient reports neck and shoulder tension.");
});

it('uses assigned practitioner Clinical Style for Intake Summary', function () {
    $practice = Practice::factory()->create([
        'practice_type' => PracticeType::TCM_ACUPUNCTURE,
        'discipline' => 'acupuncture',
    ]);
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $patient = Patient::factory()->create(['practice_id' => $practice->id]);
    $practitioner = Practitioner::factory()->create([
        'practice_id' => $practice->id,
        'clinical_style' => PracticeType::FIVE_ELEMENT_ACUPUNCTURE,
    ]);
    $intake = MedicalHistory::factory()->create([
        'practice_id' => $practice->id,
        'patient_id' => $patient->id,
        'practitioner_id' => $practitioner->id,
        'status' => 'complete',
        'discipline' => 'acupuncture',
        'reason_for_visit' => 'Patient reports wanting help with stress.',
    ]);

    app()->instance(AIService::class, new class extends AIService
    {
        public function summarizeIntake(array $context): string
        {
            expect($context['practice_type'])->toBe(PracticeType::FIVE_ELEMENT_ACUPUNCTURE);

            return "Patient-reported concerns:\nPatient reports stress.";
        }
    });

    $this->actingAs($user);

    Livewire::test(ViewMedicalHistory::class, ['record' => $intake->id])
        ->call('generateIntakeSummary')
        ->assertSet('aiIntakeSummary', "Patient-reported concerns:\nPatient reports stress.");
});

it('uses assigned Five Element practitioner section label and does not send TCM Assessment to Intake Summary AI', function () {
    $practice = Practice::factory()->create([
        'practice_type' => PracticeType::TCM_ACUPUNCTURE,
        'discipline' => 'acupuncture',
    ]);
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $patient = Patient::factory()->create(['practice_id' => $practice->id]);
    $practitioner = Practitioner::factory()->create([
        'practice_id' => $practice->id,
        'clinical_style' => PracticeType::FIVE_ELEMENT_ACUPUNCTURE,
    ]);
    $intake = MedicalHistory::factory()->create([
        'practice_id' => $practice->id,
        'patient_id' => $patient->id,
        'practitioner_id' => $practitioner->id,
        'status' => 'complete',
        'discipline' => 'acupuncture',
        'reason_for_visit' => 'Patient reports wanting help with stress.',
        'discipline_responses' => [
            'tcm' => [
                'energy_level' => 'low',
                'sleep_issues' => ['staying_asleep'],
            ],
        ],
    ]);

    app()->instance(AIService::class, new class extends AIService
    {
        public function summarizeIntake(array $context): string
        {
            expect($context['practice_type'])->toBe(PracticeType::FIVE_ELEMENT_ACUPUNCTURE);
            expect($context['practice_type_specific_patient_responses']['section'])
                ->toBe('Five Element Acupuncture Intake')
                ->not->toBe('TCM Assessment');

            return "Patient-reported concerns:\nPatient reports stress.";
        }
    });

    $this->actingAs($user);

    Livewire::test(ViewMedicalHistory::class, ['record' => $intake->id])
        ->call('generateIntakeSummary')
        ->assertSet('aiIntakeSummary', "Patient-reported concerns:\nPatient reports stress.");
});

it('falls back to practice type for Intake Summary when assigned practitioner has no Clinical Style', function () {
    $practice = Practice::factory()->create([
        'practice_type' => PracticeType::TCM_ACUPUNCTURE,
        'discipline' => 'acupuncture',
    ]);
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $patient = Patient::factory()->create(['practice_id' => $practice->id]);
    $practitioner = Practitioner::factory()->create([
        'practice_id' => $practice->id,
        'clinical_style' => null,
    ]);
    $intake = MedicalHistory::factory()->create([
        'practice_id' => $practice->id,
        'patient_id' => $patient->id,
        'practitioner_id' => $practitioner->id,
        'status' => 'complete',
        'discipline' => 'acupuncture',
        'reason_for_visit' => 'Patient reports wanting help with stress.',
    ]);

    app()->instance(AIService::class, new class extends AIService
    {
        public function summarizeIntake(array $context): string
        {
            expect($context['practice_type'])->toBe(PracticeType::TCM_ACUPUNCTURE);

            return "Patient-reported concerns:\nPatient reports stress.";
        }
    });

    $this->actingAs($user);

    Livewire::test(ViewMedicalHistory::class, ['record' => $intake->id])
        ->call('generateIntakeSummary')
        ->assertSet('aiIntakeSummary', "Patient-reported concerns:\nPatient reports stress.");
});
