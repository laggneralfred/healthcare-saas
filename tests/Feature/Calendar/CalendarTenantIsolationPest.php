<?php

use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('only returns appointments for the authenticated users practice', function () {
    $practiceA = Practice::factory()->create();
    $practiceB = Practice::factory()->create();

    $userB = User::factory()->create([
        'practice_id' => $practiceB->id,
    ]);

    $patientA = Patient::factory()->create([
        'practice_id' => $practiceA->id,
        'first_name' => 'Alpha',
        'last_name' => 'Patient',
    ]);
    $patientB = Patient::factory()->create([
        'practice_id' => $practiceB->id,
        'first_name' => 'Beta',
        'last_name' => 'Patient',
    ]);

    $practitionerA = Practitioner::factory()->create(['practice_id' => $practiceA->id]);
    $practitionerB = Practitioner::factory()->create(['practice_id' => $practiceB->id]);

    $typeA = AppointmentType::factory()->create(['practice_id' => $practiceA->id]);
    $typeB = AppointmentType::factory()->create(['practice_id' => $practiceB->id]);

    foreach ([9, 10, 11, 12, 13] as $hour) {
        Appointment::factory()->create([
            'practice_id' => $practiceA->id,
            'patient_id' => $patientA->id,
            'practitioner_id' => $practitionerA->id,
            'appointment_type_id' => $typeA->id,
            'start_datetime' => now()->setTime($hour, 0),
            'end_datetime' => now()->setTime($hour, 45),
        ]);
    }

    foreach ([9, 10, 11] as $hour) {
        Appointment::factory()->create([
            'practice_id' => $practiceB->id,
            'patient_id' => $patientB->id,
            'practitioner_id' => $practitionerB->id,
            'appointment_type_id' => $typeB->id,
            'start_datetime' => now()->setTime($hour, 0),
            'end_datetime' => now()->setTime($hour, 45),
        ]);
    }

    $this->actingAs($userB);

    $response = $this->getJson(route('admin.calendar.events'));

    $response->assertOk();

    $json = collect($response->json());

    expect($json)->toHaveCount(3);

    $titles = $json->pluck('title')->implode(' | ');

    expect($titles)->toContain('Beta Patient')
        ->not->toContain('Alpha Patient');
});
