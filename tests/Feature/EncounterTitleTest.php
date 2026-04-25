<?php

use App\Filament\Resources\Encounters\EncounterResource;
use App\Filament\Resources\Encounters\Pages\EditEncounter;
use App\Filament\Resources\Encounters\Pages\ViewEncounter;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\User;
use Livewire\Livewire;

function encounterTitleRecord(): array
{
    $practice = Practice::factory()->create(['insurance_billing_enabled' => false]);
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $patient = Patient::factory()->create([
        'practice_id' => $practice->id,
        'first_name' => 'Emma',
        'last_name' => 'Nakamura',
    ]);
    $practitioner = Practitioner::factory()->create(['practice_id' => $practice->id]);
    $encounter = Encounter::factory()->create([
        'practice_id' => $practice->id,
        'patient_id' => $patient->id,
        'appointment_id' => null,
        'practitioner_id' => $practitioner->id,
        'visit_date' => '2026-04-25',
        'chief_complaint' => 'Neck tension',
    ]);

    return compact('practice', 'user', 'patient', 'encounter');
}

it('uses a human-readable encounter title on the view page', function () {
    ['user' => $user, 'encounter' => $encounter] = encounterTitleRecord();

    $this->actingAs($user);

    $page = Livewire::test(ViewEncounter::class, ['record' => $encounter->id])
        ->instance();

    expect((string) $page->getTitle())->toBe('Visit — Emma Nakamura — Apr 25, 2026');
});

it('uses a human-readable encounter title on the edit page', function () {
    ['user' => $user, 'encounter' => $encounter] = encounterTitleRecord();

    $this->actingAs($user);

    $page = Livewire::test(EditEncounter::class, ['record' => $encounter->id])
        ->instance();

    expect((string) $page->getTitle())->toBe('Visit — Emma Nakamura — Apr 25, 2026');
});

it('falls back to encounter id when no patient is available for title formatting', function () {
    $encounter = new Encounter;
    $encounter->id = 987;

    expect(EncounterResource::getRecordTitle($encounter))->toBe('Visit #987');
});
