<?php

use App\Filament\Resources\Encounters\Pages\CreateEncounter;
use App\Filament\Resources\Practices\Pages\EditPractice;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\User;
use Livewire\Livewire;

function practiceSettingsEncounterFixtures(Practice $practice): array
{
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $patient = Patient::factory()->create(['practice_id' => $practice->id]);
    $practitioner = Practitioner::factory()->create(['practice_id' => $practice->id]);
    $appointmentType = AppointmentType::factory()->create(['practice_id' => $practice->id]);
    $appointment = Appointment::factory()->create([
        'practice_id' => $practice->id,
        'patient_id' => $patient->id,
        'practitioner_id' => $practitioner->id,
        'appointment_type_id' => $appointmentType->id,
    ]);

    return compact('user', 'patient', 'practitioner', 'appointment');
}

it('shows and stores the practice documentation billing mode switch', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => false]);
    $user = User::factory()->create(['practice_id' => $practice->id]);

    $this->actingAs($user);

    Livewire::test(EditPractice::class, ['record' => $practice->id])
        ->assertSee('Documentation & Billing Mode')
        ->assertSee('Simple Visit Note Mode')
        ->assertSee('Best for cash-pay, wellness, and practices that do not need insurance-style SOAP documentation.')
        ->assertSee('SOAP / Insurance Documentation Mode')
        ->assertSee('Shows structured SOAP fields and insurance-oriented documentation tools.')
        ->assertSee('You can change this later. Existing saved notes are not automatically rewritten.')
        ->set('data.insurance_billing_enabled', 1)
        ->call('save');

    expect($practice->refresh()->insurance_billing_enabled)->toBeTrue();

    Livewire::test(EditPractice::class, ['record' => $practice->id])
        ->set('data.insurance_billing_enabled', 0)
        ->call('save');

    expect($practice->refresh()->insurance_billing_enabled)->toBeFalse();
});

it('uses simple visit note mode when practice billing documentation is off', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => false]);
    ['user' => $user] = practiceSettingsEncounterFixtures($practice);

    $this->actingAs($user);

    Livewire::test(CreateEncounter::class)
        ->assertSee('Simple Visit Note')
        ->assertSee('Save Note')
        ->assertDontSee('Insurance SOAP Note');
});

it('uses SOAP insurance documentation mode when practice billing documentation is on', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => true]);
    ['user' => $user] = practiceSettingsEncounterFixtures($practice);

    $this->actingAs($user);

    Livewire::test(CreateEncounter::class)
        ->assertSee('Insurance SOAP Note')
        ->assertSee('Subjective')
        ->assertDontSee('Simple Visit Note');
});
