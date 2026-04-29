<?php

use App\Filament\Resources\LegalForms\Pages\CreateLegalForm;
use App\Filament\Resources\Practices\Pages\CreatePractice;
use App\Filament\Resources\Practices\Pages\EditPractice;
use App\Filament\Resources\Practitioners\Pages\CreatePractitioner;
use App\Livewire\OnboardingWizard;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\User;
use App\Services\EncounterDisciplineTemplate;
use App\Services\EncounterNoteDocument;
use App\Support\PracticeType;
use Livewire\Livewire;

it('shows and stores Practice Type on practice forms', function () {
    $admin = User::factory()->create();
    $this->actingAs($admin);

    Livewire::test(CreatePractice::class)
        ->assertSee('Practice Type')
        ->assertSee('Five Element Acupuncture')
        ->assertSee('Used to customize visit note templates and AI suggestions.')
        ->set('data.name', 'Five Element Clinic')
        ->set('data.slug', 'five-element-clinic')
        ->set('data.timezone', 'America/Los_Angeles')
        ->set('data.practice_type', PracticeType::FIVE_ELEMENT_ACUPUNCTURE)
        ->call('create');

    $this->assertDatabaseHas('practices', [
        'name' => 'Five Element Clinic',
        'practice_type' => PracticeType::FIVE_ELEMENT_ACUPUNCTURE,
        'discipline' => 'acupuncture',
    ]);
});

it('defaults Practice Type safely to General Wellness', function () {
    $practice = Practice::factory()->create();

    expect($practice->practice_type)->toBe(PracticeType::GENERAL_WELLNESS)
        ->and(PracticeType::fromPractice($practice))->toBe(PracticeType::GENERAL_WELLNESS)
        ->and(EncounterNoteDocument::template(PracticeType::fromPractice($practice)))
        ->toContain('Reason for Visit:')
        ->toContain('Visit Note:');
});

it('returns practitioner-native headings for each Practice Type template', function () {
    expect(EncounterDisciplineTemplate::headings(PracticeType::GENERAL_WELLNESS))->toBe([
        'Reason for Visit',
        'Visit Note',
        'Care Provided',
        'Response',
        'Plan / Follow-up',
    ])
        ->and(EncounterDisciplineTemplate::headings(PracticeType::TCM_ACUPUNCTURE))->toBe([
            'Reason for Visit',
            'History / Presentation',
            'TCM Assessment',
            'Pattern Impression',
            'Tongue / Pulse',
            'Treatment Principle',
            'Points / Techniques',
            'Response',
            'Plan / Follow-up',
        ])
        ->and(EncounterDisciplineTemplate::headings(PracticeType::FIVE_ELEMENT_ACUPUNCTURE))->toBe([
            'Reason for Visit',
            'Patient Presentation',
            'Constitutional / Elemental Impression',
            'Color / Sound / Odor / Emotion Observations',
            'Officials / Element Considerations',
            'Treatment Intention',
            'Points Used',
            'Response',
            'Plan / Follow-up',
        ])
        ->and(EncounterDisciplineTemplate::headings(PracticeType::CHIROPRACTIC))->toBe([
            'Reason for Visit',
            'History / Presentation',
            'Exam / Findings',
            'Assessment',
            'Treatment Performed',
            'Response',
            'Plan / Follow-up',
        ])
        ->and(EncounterDisciplineTemplate::headings(PracticeType::MASSAGE_THERAPY))->toBe([
            'Reason for Visit',
            'Client Presentation',
            'Areas Treated',
            'Techniques Used',
            'Tissue Response',
            'Self-Care / Plan',
        ])
        ->and(EncounterDisciplineTemplate::headings(PracticeType::PHYSIOTHERAPY))->toBe([
            'Reason for Visit',
            'Subjective',
            'Objective / Findings',
            'Assessment',
            'Treatment / Exercises',
            'Response',
            'Plan / Follow-up',
        ]);
});

it('resolves legacy discipline values to compatible practice types', function () {
    expect(PracticeType::normalize(null, 'acupuncture'))->toBe(PracticeType::TCM_ACUPUNCTURE)
        ->and(PracticeType::normalize(null, 'chiropractic'))->toBe(PracticeType::CHIROPRACTIC)
        ->and(PracticeType::normalize(null, 'massage'))->toBe(PracticeType::MASSAGE_THERAPY)
        ->and(PracticeType::normalize(null, 'physiotherapy'))->toBe(PracticeType::PHYSIOTHERAPY)
        ->and(PracticeType::normalize(null, null))->toBe(PracticeType::GENERAL_WELLNESS);
});

it('can update Practice Type later from the practice edit form', function () {
    $practice = Practice::factory()->create([
        'practice_type' => PracticeType::TCM_ACUPUNCTURE,
        'discipline' => 'acupuncture',
    ]);
    $admin = User::factory()->create(['practice_id' => $practice->id]);
    $this->actingAs($admin);

    Livewire::test(EditPractice::class, ['record' => $practice->id])
        ->assertSee('Practice Type')
        ->assertSee('Five Element Acupuncture')
        ->set('data.practice_type', PracticeType::FIVE_ELEMENT_ACUPUNCTURE)
        ->call('save');

    expect($practice->refresh()->practice_type)->toBe(PracticeType::FIVE_ELEMENT_ACUPUNCTURE)
        ->and($practice->discipline)->toBe('acupuncture');
});

it('shows and stores Five Element Acupuncture in onboarding', function () {
    $user = User::factory()->create(['practice_id' => null]);
    $this->actingAs($user);

    Livewire::test(OnboardingWizard::class)
        ->set('currentStep', 4)
        ->assertSee('Practice Type')
        ->assertSee('Five Element Acupuncture')
        ->set('practiceName', 'Five Element Onboarding')
        ->set('practitionerName', 'Jane Practitioner')
        ->set('practiceType', PracticeType::FIVE_ELEMENT_ACUPUNCTURE)
        ->call('completeSetup');

    $practice = $user->refresh()->practice;

    expect($practice)->not->toBeNull()
        ->and($practice->practice_type)->toBe(PracticeType::FIVE_ELEMENT_ACUPUNCTURE)
        ->and($practice->discipline)->toBe('acupuncture')
        ->and($user->fresh()->hasRole(User::ROLE_OWNER))->toBeTrue()
        ->and($user->fresh()->roles)->not->toBeEmpty();
});

it('uses honest broad form categories for legal forms', function () {
    $practice = Practice::factory()->create([
        'practice_type' => PracticeType::FIVE_ELEMENT_ACUPUNCTURE,
        'discipline' => 'acupuncture',
    ]);
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $this->actingAs($user);

    Livewire::test(CreateLegalForm::class)
        ->assertSee('Form Category')
        ->assertSee('Acupuncture')
        ->assertSee('Used to choose which kind of form this is for. Specific Practice Type is set on the practice.')
        ->assertSee('Only one active form per form category is allowed')
        ->assertDontSee('TCM Acupuncture')
        ->assertDontSee('Five Element Acupuncture')
        ->assertDontSee('per discipline')
        ->set('data.discipline', 'acupuncture')
        ->set('data.title', 'Acupuncture consent')
        ->set('data.body', 'Consent body')
        ->call('create');

    $this->assertDatabaseHas('legal_forms', [
        'practice_id' => $practice->id,
        'discipline' => 'acupuncture',
        'title' => 'Acupuncture consent',
    ]);
});

it('stores nullable practitioner Clinical Style overrides', function () {
    $practice = Practice::factory()->create([
        'practice_type' => PracticeType::TCM_ACUPUNCTURE,
        'discipline' => 'acupuncture',
    ]);
    $admin = User::factory()->create(['practice_id' => $practice->id]);
    $practitionerUser = User::factory()->create(['practice_id' => $practice->id]);
    $this->actingAs($admin);

    Livewire::test(CreatePractitioner::class)
        ->assertSee('Clinical Style')
        ->assertSee('Use practice default')
        ->assertSee('Five Element Acupuncture')
        ->assertSee('Used to customize visit templates and AI suggestions for this practitioner. Leave blank to use the practice default.')
        ->set('data.user_id', $practitionerUser->id)
        ->set('data.license_number', 'FE-12345')
        ->set('data.specialty', 'Acupuncture')
        ->set('data.clinical_style', PracticeType::FIVE_ELEMENT_ACUPUNCTURE)
        ->set('data.is_active', true)
        ->call('create');

    $this->assertDatabaseHas('practitioners', [
        'practice_id' => $practice->id,
        'user_id' => $practitionerUser->id,
        'clinical_style' => PracticeType::FIVE_ELEMENT_ACUPUNCTURE,
    ]);

    $defaultPractitioner = Practitioner::factory()->create([
        'practice_id' => $practice->id,
        'clinical_style' => null,
    ]);

    expect($defaultPractitioner->clinical_style)->toBeNull();
});
