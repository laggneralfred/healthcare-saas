<?php

use App\Filament\Resources\Encounters\Pages\CreateEncounter;
use App\Filament\Resources\Encounters\Pages\EditEncounter;
use App\Filament\Resources\Encounters\Pages\ViewEncounter;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\User;
use App\Services\EncounterNoteDocument;
use App\Services\PracticeContext;
use Livewire\Livewire;

function encounterCreationFixtures(Practice $practice): array
{
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $patient = Patient::factory()->create(['practice_id' => $practice->id]);
    $practitioner = Practitioner::factory()->create([
        'practice_id' => $practice->id,
        'specialty' => 'Acupuncture',
    ]);
    $appointmentType = AppointmentType::factory()->create(['practice_id' => $practice->id]);
    $appointment = Appointment::factory()->create([
        'practice_id' => $practice->id,
        'patient_id' => $patient->id,
        'practitioner_id' => $practitioner->id,
        'appointment_type_id' => $appointmentType->id,
    ]);

    return compact('user', 'patient', 'practitioner', 'appointmentType', 'appointment');
}

it('creates an encounter from patient context without an appointment', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => false]);
    ['user' => $user, 'patient' => $patient, 'practitioner' => $practitioner] = encounterCreationFixtures($practice);

    $this->actingAs($user);

    Livewire::withQueryParams(['patient_id' => $patient->id])
        ->test(CreateEncounter::class)
        ->set('data.patient_id', $patient->id)
        ->set('data.practitioner_id', $practitioner->id)
        ->set('data.discipline', 'acupuncture')
        ->set('data.visit_date', '2026-04-25')
        ->set('data.status', 'draft')
        ->set('data.visit_note_document', "Chief Complaint:\nWalk-in neck tension\n\nTreatment Notes:\nDirect chart note from handwritten source.\n\nPlan / Follow-up:\nFollow up as needed.")
        ->call('create');

    $encounter = Encounter::withoutPracticeScope()
        ->where('practice_id', $practice->id)
        ->where('patient_id', $patient->id)
        ->firstOrFail();

    expect($encounter->appointment_id)->toBeNull();
    expect($encounter->status)->toBe('draft');
    expect($encounter->completed_on)->toBeNull();
    expect($encounter->practitioner_id)->toBe($practitioner->id);
    expect($encounter->chief_complaint)->toBe('Walk-in neck tension');
    expect($encounter->visit_notes)->toBe('Direct chart note from handwritten source.');
    expect($encounter->plan)->toBe('Follow up as needed.');
});

it('always creates encounters as drafts', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => false]);
    ['user' => $user, 'patient' => $patient, 'practitioner' => $practitioner] = encounterCreationFixtures($practice);

    $this->actingAs($user);

    Livewire::withQueryParams(['patient_id' => $patient->id])
        ->test(CreateEncounter::class)
        ->set('data.patient_id', $patient->id)
        ->set('data.practitioner_id', $practitioner->id)
        ->set('data.discipline', 'acupuncture')
        ->set('data.visit_date', '2026-04-25')
        ->set('data.status', 'complete')
        ->set('data.visit_note_document', "Chief Complaint:\nNew note\n\nTreatment Notes:\nCreated as draft.\n\nPlan / Follow-up:\nFollow up.")
        ->call('create');

    $encounter = Encounter::withoutPracticeScope()
        ->where('practice_id', $practice->id)
        ->where('patient_id', $patient->id)
        ->firstOrFail();

    expect($encounter->status)->toBe('draft');
    expect($encounter->completed_on)->toBeNull();
});

it('creates an appointment-linked encounter and keeps appointment id', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => false]);
    [
        'user' => $user,
        'patient' => $patient,
        'practitioner' => $practitioner,
        'appointment' => $appointment,
    ] = encounterCreationFixtures($practice);

    $this->actingAs($user);

    Livewire::withQueryParams([
        'patient_id' => $patient->id,
        'appointment_id' => $appointment->id,
    ])
        ->test(CreateEncounter::class)
        ->assertSet('data.appointment_id', $appointment->id)
        ->assertSet('data.patient_id', $patient->id)
        ->assertSet('data.practitioner_id', $practitioner->id)
        ->set('data.discipline', 'acupuncture')
        ->set('data.visit_date', '2026-04-25')
        ->set('data.status', 'draft')
        ->set('data.visit_note_document', "Chief Complaint:\nScheduled follow-up\n\nTreatment Notes:\nAppointment-linked note.\n\nPlan / Follow-up:\nReturn next week.")
        ->call('create');

    $encounter = Encounter::withoutPracticeScope()
        ->where('practice_id', $practice->id)
        ->where('appointment_id', $appointment->id)
        ->firstOrFail();

    expect($encounter->patient_id)->toBe($patient->id);
    expect($encounter->practitioner_id)->toBe($practitioner->id);
    expect($encounter->chief_complaint)->toBe('Scheduled follow-up');
    expect($encounter->visit_notes)->toBe('Appointment-linked note.');
    expect($encounter->plan)->toBe('Return next week.');
});

it('uses selected practice context when a super admin creates an encounter', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => false]);
    $otherPractice = Practice::factory()->create();
    $superAdmin = User::factory()->create(['practice_id' => null]);
    ['patient' => $patient, 'practitioner' => $practitioner] = encounterCreationFixtures($practice);

    $this->actingAs($superAdmin);
    PracticeContext::setCurrentPracticeId($practice->id);

    Livewire::withQueryParams(['patient_id' => $patient->id])
        ->test(CreateEncounter::class)
        ->set('data.patient_id', $patient->id)
        ->set('data.practitioner_id', $practitioner->id)
        ->set('data.discipline', 'acupuncture')
        ->set('data.visit_date', '2026-04-25')
        ->set('data.status', 'draft')
        ->set('data.visit_note_document', "Chief Complaint:\nRetroactive chart entry\n\nTreatment Notes:\nSelected-practice note.\n\nPlan / Follow-up:\nContinue care.")
        ->call('create');

    $this->assertDatabaseHas('encounters', [
        'practice_id' => $practice->id,
        'patient_id' => $patient->id,
        'practitioner_id' => $practitioner->id,
        'appointment_id' => null,
    ]);

    $this->assertDatabaseMissing('encounters', [
        'practice_id' => $otherPractice->id,
        'patient_id' => $patient->id,
    ]);
});

it('blocks cross-practice patient and practitioner ids during encounter creation', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => false]);
    $otherPractice = Practice::factory()->create(['insurance_billing_enabled' => false]);
    ['user' => $user, 'practitioner' => $practitioner] = encounterCreationFixtures($practice);
    $otherPatient = Patient::factory()->create(['practice_id' => $otherPractice->id]);

    $this->actingAs($user);

    Livewire::test(CreateEncounter::class)
        ->set('data.patient_id', $otherPatient->id)
        ->set('data.practitioner_id', $practitioner->id)
        ->set('data.discipline', 'acupuncture')
        ->set('data.visit_date', '2026-04-25')
        ->set('data.status', 'draft')
        ->set('data.visit_note_document', "Chief Complaint:\nBlocked cross-practice note\n\nTreatment Notes:\nShould not save.\n\nPlan / Follow-up:\nShould not save.")
        ->call('create')
        ->assertHasErrors();

    expect(Encounter::withoutPracticeScope()->where('practice_id', $practice->id)->count())->toBe(0);
});

it('blocks cross-practice appointments during encounter creation', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => false]);
    $otherPractice = Practice::factory()->create(['insurance_billing_enabled' => false]);
    ['user' => $user, 'patient' => $patient, 'practitioner' => $practitioner] = encounterCreationFixtures($practice);
    ['appointment' => $otherAppointment] = encounterCreationFixtures($otherPractice);

    $this->actingAs($user);

    Livewire::test(CreateEncounter::class)
        ->set('data.appointment_id', $otherAppointment->id)
        ->set('data.patient_id', $patient->id)
        ->set('data.practitioner_id', $practitioner->id)
        ->set('data.discipline', 'acupuncture')
        ->set('data.visit_date', '2026-04-25')
        ->set('data.status', 'draft')
        ->set('data.visit_note_document', "Chief Complaint:\nBlocked appointment note\n\nTreatment Notes:\nShould not save.\n\nPlan / Follow-up:\nShould not save.")
        ->call('create')
        ->assertHasErrors();

    expect(Encounter::withoutPracticeScope()->where('practice_id', $practice->id)->count())->toBe(0);
});

it('falls back to visit notes when simple note document headings cannot be parsed', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => false]);
    ['user' => $user, 'patient' => $patient, 'practitioner' => $practitioner] = encounterCreationFixtures($practice);

    $this->actingAs($user);

    Livewire::withQueryParams(['patient_id' => $patient->id])
        ->test(CreateEncounter::class)
        ->set('data.patient_id', $patient->id)
        ->set('data.practitioner_id', $practitioner->id)
        ->set('data.discipline', 'acupuncture')
        ->set('data.visit_date', '2026-04-25')
        ->set('data.status', 'draft')
        ->set('data.visit_note_document', 'Free-form note without recognized headings.')
        ->call('create');

    $encounter = Encounter::withoutPracticeScope()
        ->where('practice_id', $practice->id)
        ->where('patient_id', $patient->id)
        ->firstOrFail();

    expect($encounter->chief_complaint)->toBeNull();
    expect($encounter->visit_notes)->toBe('Free-form note without recognized headings.');
    expect($encounter->plan)->toBeNull();
});

it('uses the acupuncture simple note template', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => false]);
    ['user' => $user] = encounterCreationFixtures($practice);

    $this->actingAs($user);

    Livewire::test(CreateEncounter::class)
        ->set('data.discipline', 'acupuncture')
        ->assertSet('data.visit_note_document', EncounterNoteDocument::template('acupuncture'));
});

it('uses the chiropractic simple note template', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => false]);
    ['user' => $user] = encounterCreationFixtures($practice);

    $this->actingAs($user);

    Livewire::test(CreateEncounter::class)
        ->set('data.discipline', 'chiropractic')
        ->assertSet('data.visit_note_document', EncounterNoteDocument::template('chiropractic'));
});

it('uses the massage therapy simple note template', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => false]);
    ['user' => $user] = encounterCreationFixtures($practice);

    $this->actingAs($user);

    Livewire::test(CreateEncounter::class)
        ->set('data.discipline', 'massage')
        ->assertSet('data.visit_note_document', EncounterNoteDocument::template('massage'));
});

it('uses the physical therapy simple note template', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => false]);
    ['user' => $user] = encounterCreationFixtures($practice);

    $this->actingAs($user);

    Livewire::test(CreateEncounter::class)
        ->set('data.discipline', 'physiotherapy')
        ->assertSet('data.visit_note_document', EncounterNoteDocument::template('physiotherapy'));
});

it('does not overwrite typed simple note text when discipline changes', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => false]);
    ['user' => $user] = encounterCreationFixtures($practice);

    $this->actingAs($user);

    Livewire::test(CreateEncounter::class)
        ->set('data.discipline', 'acupuncture')
        ->set('data.visit_note_document', 'Clinician already typed this note.')
        ->set('data.discipline', 'chiropractic')
        ->assertSet('data.visit_note_document', 'Clinician already typed this note.');
});

it('saves discipline-specific middle sections safely into visit notes', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => false]);
    ['user' => $user, 'patient' => $patient, 'practitioner' => $practitioner] = encounterCreationFixtures($practice);

    $this->actingAs($user);

    Livewire::withQueryParams(['patient_id' => $patient->id])
        ->test(CreateEncounter::class)
        ->set('data.patient_id', $patient->id)
        ->set('data.practitioner_id', $practitioner->id)
        ->set('data.discipline', 'chiropractic')
        ->set('data.visit_date', '2026-04-25')
        ->set('data.status', 'draft')
        ->set('data.visit_note_document', "Chief Complaint:\nLow back pain\n\nSpinal / Musculoskeletal Findings:\nLumbar restriction noted.\n\nAdjustment / Treatment:\nDiversified adjustment performed.\n\nResponse:\nImproved range of motion.\n\nPlan / Follow-up:\nReturn next week.")
        ->call('create');

    $encounter = Encounter::withoutPracticeScope()
        ->where('practice_id', $practice->id)
        ->where('patient_id', $patient->id)
        ->firstOrFail();

    expect($encounter->chief_complaint)->toBe('Low back pain');
    expect($encounter->visit_notes)->toBe("Spinal / Musculoskeletal Findings:\nLumbar restriction noted.\n\nAdjustment / Treatment:\nDiversified adjustment performed.\n\nResponse:\nImproved range of motion.");
    expect($encounter->plan)->toBe('Return next week.');
});

it('keeps SOAP mode structured instead of showing the simple discipline template', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => true]);
    ['user' => $user] = encounterCreationFixtures($practice);

    $this->actingAs($user);

    Livewire::test(CreateEncounter::class)
        ->set('data.discipline', 'chiropractic')
        ->assertSee('Insurance SOAP Note')
        ->assertDontSee('Adjustment / Treatment');
});

it('reconstructs and saves the simple note document on edit', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => false]);
    ['user' => $user, 'patient' => $patient, 'practitioner' => $practitioner] = encounterCreationFixtures($practice);
    $encounter = Encounter::factory()->create([
        'practice_id' => $practice->id,
        'patient_id' => $patient->id,
        'appointment_id' => null,
        'practitioner_id' => $practitioner->id,
        'discipline' => 'acupuncture',
        'visit_date' => '2026-04-25',
        'chief_complaint' => 'Original complaint',
        'visit_notes' => 'Original treatment notes.',
        'plan' => 'Original plan.',
    ]);

    $this->actingAs($user);

    Livewire::test(EditEncounter::class, ['record' => $encounter->id])
        ->assertSet('data.visit_note_document', EncounterNoteDocument::fromFields('Original complaint', 'Original treatment notes.', 'Original plan.', 'acupuncture'))
        ->set('data.visit_note_document', "Chief Complaint:\nUpdated complaint\n\nTreatment Notes:\nUpdated treatment notes.\n\nPlan / Follow-up:\nUpdated plan.")
        ->call('saveDraft');

    $encounter->refresh();

    expect($encounter->chief_complaint)->toBe('Updated complaint');
    expect($encounter->visit_notes)->toBe('Updated treatment notes.');
    expect($encounter->plan)->toBe('Updated plan.');
});

it('saves a simple note in place without resetting status or losing edit actions', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => false]);
    ['user' => $user, 'patient' => $patient, 'practitioner' => $practitioner] = encounterCreationFixtures($practice);
    $encounter = Encounter::factory()->create([
        'practice_id' => $practice->id,
        'patient_id' => $patient->id,
        'appointment_id' => null,
        'practitioner_id' => $practitioner->id,
        'discipline' => 'acupuncture',
        'visit_date' => '2026-04-25',
        'status' => 'complete',
        'chief_complaint' => 'Saved complaint',
        'visit_notes' => 'Saved treatment notes.',
        'plan' => 'Saved plan.',
    ]);

    $this->actingAs($user);

    Livewire::test(EditEncounter::class, ['record' => $encounter->id])
        ->assertSee('Save Note')
        ->assertSee('Reopen Note')
        ->assertDontSee('Complete Note')
        ->assertSet('data.visit_note_document', EncounterNoteDocument::fromFields('Saved complaint', 'Saved treatment notes.', 'Saved plan.', 'acupuncture'))
        ->set('data.visit_note_document', "Chief Complaint:\nFirst save complaint\n\nTreatment Notes:\nFirst save treatment notes.\n\nPlan / Follow-up:\nFirst save plan.")
        ->call('saveDraft')
        ->assertNoRedirect()
        ->assertSee('Save Note')
        ->assertSee('Reopen Note')
        ->set('data.visit_note_document', "Chief Complaint:\nSecond save complaint\n\nTreatment Notes:\nSecond save treatment notes.\n\nPlan / Follow-up:\nSecond save plan.")
        ->call('saveDraft')
        ->assertNoRedirect()
        ->assertSee('Save Note');

    $encounter->refresh();

    expect($encounter->status)->toBe('complete');
    expect($encounter->chief_complaint)->toBe('Second save complaint');
    expect($encounter->visit_notes)->toBe('Second save treatment notes.');
    expect($encounter->plan)->toBe('Second save plan.');
});

it('completes and reopens notes only through explicit actions', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => false]);
    ['user' => $user, 'patient' => $patient, 'practitioner' => $practitioner] = encounterCreationFixtures($practice);
    $encounter = Encounter::factory()->create([
        'practice_id' => $practice->id,
        'patient_id' => $patient->id,
        'appointment_id' => null,
        'practitioner_id' => $practitioner->id,
        'discipline' => 'acupuncture',
        'visit_date' => '2026-04-25',
        'status' => 'draft',
        'completed_on' => null,
        'chief_complaint' => 'Draft complaint',
        'visit_notes' => 'Draft notes.',
        'plan' => 'Draft plan.',
    ]);

    $this->actingAs($user);

    $component = Livewire::test(EditEncounter::class, ['record' => $encounter->id])
        ->assertSee('Complete Note')
        ->assertDontSee('Reopen Note')
        ->set('data.visit_note_document', "Chief Complaint:\nCompleted complaint\n\nTreatment Notes:\nCompleted notes.\n\nPlan / Follow-up:\nCompleted plan.")
        ->call('completeEncounter')
        ->assertNoRedirect()
        ->assertSee('Reopen Note')
        ->assertDontSee('Complete Note');

    $encounter->refresh();

    expect($encounter->status)->toBe('complete');
    expect($encounter->completed_on)->not->toBeNull();
    expect($encounter->chief_complaint)->toBe('Completed complaint');
    expect($encounter->visit_notes)->toBe('Completed notes.');
    expect($encounter->plan)->toBe('Completed plan.');

    $component
        ->call('reopenEncounter')
        ->assertNoRedirect()
        ->assertSee('Complete Note');

    $encounter->refresh();

    expect($encounter->status)->toBe('draft');
    expect($encounter->completed_on)->toBeNull();
    expect($encounter->chief_complaint)->toBe('Completed complaint');
    expect($encounter->visit_notes)->toBe('Completed notes.');
    expect($encounter->plan)->toBe('Completed plan.');
});

it('shows an edit note action when reopening a saved encounter view', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => false]);
    ['user' => $user, 'patient' => $patient, 'practitioner' => $practitioner] = encounterCreationFixtures($practice);
    $encounter = Encounter::factory()->create([
        'practice_id' => $practice->id,
        'patient_id' => $patient->id,
        'appointment_id' => null,
        'practitioner_id' => $practitioner->id,
        'discipline' => 'acupuncture',
        'visit_date' => '2026-04-25',
        'chief_complaint' => 'Saved complaint',
        'visit_notes' => 'Saved treatment notes.',
        'plan' => 'Saved plan.',
    ]);

    $this->actingAs($user);

    Livewire::test(ViewEncounter::class, ['record' => $encounter->id])
        ->assertSee('Edit Note');

    Livewire::test(EditEncounter::class, ['record' => $encounter->id])
        ->assertSet('data.visit_note_document', EncounterNoteDocument::fromFields('Saved complaint', 'Saved treatment notes.', 'Saved plan.', 'acupuncture'));
});
