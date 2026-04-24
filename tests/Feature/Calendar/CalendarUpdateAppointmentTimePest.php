<?php

use App\Filament\Widgets\AppointmentCalendarWidget;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('updates appointment start and end times from the calendar widget action', function () {
    $practice = Practice::factory()->create([
        'timezone' => 'America/Los_Angeles',
    ]);

    $user = User::factory()->create([
        'practice_id' => $practice->id,
    ]);

    $patient = Patient::factory()->create([
        'practice_id' => $practice->id,
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

    Livewire::test(AppointmentCalendarWidget::class)
        ->call(
            'updateAppointmentTime',
            $appointment->id,
            '2026-04-22 11:00:00',
            '2026-04-22 11:45:00'
        );

    $appointment->refresh();

    expect($appointment->start_datetime->format('Y-m-d H:i'))->toBe('2026-04-22 18:00');
    expect($appointment->end_datetime->format('Y-m-d H:i'))->toBe('2026-04-22 18:45');

    $response = $this->getJson(route('admin.calendar.events', [
        'start' => '2026-04-20T00:00:00Z',
        'end' => '2026-04-27T00:00:00Z',
    ]));

    $response->assertOk();

    $event = collect($response->json())->firstWhere('id', (string) $appointment->id);

    expect($event)->not->toBeNull();
    expect($event['start'])->toBe('2026-04-22T11:00:00-07:00');
    expect($event['end'])->toBe('2026-04-22T11:45:00-07:00');
});

it('does not allow updating an appointment from another practice', function () {
    $practiceA = Practice::factory()->create();
    $practiceB = Practice::factory()->create();

    $userB = User::factory()->create([
        'practice_id' => $practiceB->id,
    ]);

    $patientA = Patient::factory()->create(['practice_id' => $practiceA->id]);
    $practitionerA = Practitioner::factory()->create(['practice_id' => $practiceA->id]);
    $typeA = AppointmentType::factory()->create(['practice_id' => $practiceA->id]);

    $appointmentA = Appointment::factory()->create([
        'practice_id' => $practiceA->id,
        'patient_id' => $patientA->id,
        'practitioner_id' => $practitionerA->id,
        'appointment_type_id' => $typeA->id,
        'start_datetime' => '2026-04-22 09:00:00',
        'end_datetime' => '2026-04-22 09:45:00',
    ]);

    $this->actingAs($userB);

    expect(fn () => Livewire::test(AppointmentCalendarWidget::class)
        ->call(
            'updateAppointmentTime',
            $appointmentA->id,
            '2026-04-22 13:00:00',
            '2026-04-22 13:45:00'
        ))
        ->toThrow(ModelNotFoundException::class);

    $appointmentA->refresh();

    expect($appointmentA->start_datetime->format('Y-m-d H:i'))->toBe('2026-04-22 09:00');
});
