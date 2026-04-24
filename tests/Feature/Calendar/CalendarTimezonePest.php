<?php

use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('serializes appointment times in the practice timezone for the event feed', function () {
    $practice = Practice::factory()->create([
        'timezone' => 'America/Los_Angeles',
    ]);

    $user = User::factory()->create([
        'practice_id' => $practice->id,
    ]);

    $patient = Patient::factory()->create([
        'practice_id' => $practice->id,
        'name' => 'Timezone Patient',
    ]);

    $practitioner = Practitioner::factory()->create([
        'practice_id' => $practice->id,
    ]);

    $appointmentType = AppointmentType::factory()->create([
        'practice_id' => $practice->id,
    ]);

    $appointment = Appointment::factory()->create([
        'practice_id' => $practice->id,
        'patient_id' => $patient->id,
        'practitioner_id' => $practitioner->id,
        'appointment_type_id' => $appointmentType->id,
        'start_datetime' => '2026-04-22 16:00:00',
        'end_datetime' => '2026-04-22 16:45:00',
    ]);

    $this->actingAs($user);

    $response = $this->getJson(route('admin.calendar.events'));

    $response->assertOk();

    $event = collect($response->json())->firstWhere('id', (string) $appointment->id);

    expect($event)->not->toBeNull();
    expect($event['start'])->toBe('2026-04-22T09:00:00-07:00');
    expect($event['end'])->toBe('2026-04-22T09:45:00-07:00');
});
