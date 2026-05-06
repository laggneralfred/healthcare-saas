<?php

use App\Filament\Pages\SchedulePage;
use App\Filament\Resources\Appointments\Pages\CreateAppointment;
use App\Filament\Resources\Appointments\Pages\EditAppointment;
use App\Filament\Widgets\AppointmentCalendarWidget;
use App\Models\Appointment;
use App\Models\AppointmentRequest;
use App\Models\AppointmentType;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\PractitionerWorkingHour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('preserves return_url from calendar to view page', function () {
    $practice = Practice::factory()->create();

    $user = User::factory()->create([
        'practice_id' => $practice->id,
    ]);

    $patient = Patient::factory()->create([
        'practice_id' => $practice->id,
        'name' => 'Calendar Patient',
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
    ]);

    $this->actingAs($user);

    $response = $this->getJson(route('admin.calendar.events', [
        'start' => $appointment->start_datetime->copy()->startOfDay()->toIso8601String(),
        'end' => $appointment->end_datetime->copy()->endOfDay()->toIso8601String(),
    ]));

    $response->assertOk();

    $event = collect($response->json())->firstWhere('id', (string) $appointment->id);

    expect($event)->not->toBeNull();
    expect($event['url'])->toContain('return_url=');
    expect(urldecode($event['url']))->toContain(SchedulePage::getUrl());
});

it('view page preserves return_url on back and edit actions', function () {
    $practice = Practice::factory()->create();

    $user = User::factory()->create(['practice_id' => $practice->id]);
    $patient = Patient::factory()->create([
        'practice_id' => $practice->id,
        'first_name' => 'Calendar',
        'last_name' => 'Patient',
    ]);
    $practitioner = Practitioner::factory()->create(['practice_id' => $practice->id]);
    $appointmentType = AppointmentType::factory()->create(['practice_id' => $practice->id]);
    $appointment = Appointment::factory()->create([
        'practice_id' => $practice->id,
        'patient_id' => $patient->id,
        'practitioner_id' => $practitioner->id,
        'appointment_type_id' => $appointmentType->id,
    ]);

    $this->actingAs($user);

    $response = $this->get(route('filament.admin.resources.appointments.view', [
        'record' => $appointment->id,
        'return_url' => SchedulePage::getUrl(),
    ]));

    $response->assertSuccessful();
    $response->assertSee(SchedulePage::getUrl(), false);
    $response->assertSee(route('filament.admin.resources.appointments.edit', [
        'record' => $appointment->id,
        'return_url' => SchedulePage::getUrl(),
    ]), false);
});

it('create page redirects to return_url when provided', function () {
    $practice = Practice::factory()->create();

    $user = User::factory()->create(['practice_id' => $practice->id]);
    $patient = Patient::factory()->create(['practice_id' => $practice->id]);
    $practitioner = Practitioner::factory()->create(['practice_id' => $practice->id]);
    PractitionerWorkingHour::withoutPracticeScope()->create([
        'practice_id' => $practice->id,
        'practitioner_id' => $practitioner->id,
        'day_of_week' => 4,
        'start_time' => '09:00',
        'end_time' => '17:00',
        'is_active' => true,
    ]);
    $appointmentType = AppointmentType::factory()->create([
        'practice_id' => $practice->id,
        'duration_minutes' => 45,
    ]);

    $this->actingAs($user);

    Livewire::withQueryParams(['return_url' => SchedulePage::getUrl()])
        ->test(CreateAppointment::class)
        ->fillForm([
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'appointment_type_id' => $appointmentType->id,
            'start_datetime' => '2026-04-23 09:00:00',
            'end_datetime' => '2026-04-23 09:45:00',
        ])
        ->call('create')
        ->assertRedirect(SchedulePage::getUrl());
});

it('edit page redirects to return_url when provided', function () {
    $practice = Practice::factory()->create();

    $user = User::factory()->create(['practice_id' => $practice->id]);
    $patient = Patient::factory()->create(['practice_id' => $practice->id]);
    $practitioner = Practitioner::factory()->create(['practice_id' => $practice->id]);
    PractitionerWorkingHour::withoutPracticeScope()->create([
        'practice_id' => $practice->id,
        'practitioner_id' => $practitioner->id,
        'day_of_week' => 4,
        'start_time' => '09:00',
        'end_time' => '17:00',
        'is_active' => true,
    ]);
    $appointmentType = AppointmentType::factory()->create([
        'practice_id' => $practice->id,
        'duration_minutes' => 45,
    ]);
    $appointment = Appointment::factory()->create([
        'practice_id' => $practice->id,
        'patient_id' => $patient->id,
        'practitioner_id' => $practitioner->id,
        'appointment_type_id' => $appointmentType->id,
        'start_datetime' => '2026-04-23 09:00:00',
        'end_datetime' => '2026-04-23 09:45:00',
    ]);

    $this->actingAs($user);

    Livewire::withQueryParams(['return_url' => SchedulePage::getUrl()])
        ->test(EditAppointment::class, ['record' => $appointment->id])
        ->call('save')
        ->assertRedirect(SchedulePage::getUrl());
});

it('calendar preserves appointment request context when choosing a time', function () {
    $practice = Practice::factory()->create(['timezone' => 'America/Los_Angeles']);

    $user = User::factory()->create(['practice_id' => $practice->id]);
    $patient = Patient::factory()->create(['practice_id' => $practice->id]);
    $practitioner = Practitioner::factory()->create(['practice_id' => $practice->id]);
    $appointmentType = AppointmentType::factory()->create(['practice_id' => $practice->id]);
    $request = AppointmentRequest::withoutPracticeScope()->create([
        'practice_id' => $practice->id,
        'patient_id' => $patient->id,
        'token_hash' => hash('sha256', 'calendar-request-context'),
        'status' => AppointmentRequest::STATUS_PENDING,
        'appointment_type_id' => $appointmentType->id,
        'practitioner_id' => $practitioner->id,
        'preferred_times' => 'Tuesday morning',
        'submitted_at' => now(),
    ]);

    $this->actingAs($user);

    Livewire::withQueryParams([
        'appointment_request_id' => $request->id,
        'patient_id' => $patient->id,
        'appointment_type_id' => $appointmentType->id,
        'practitioner_id' => $practitioner->id,
        'return_url' => SchedulePage::getUrl(),
    ])
        ->test(AppointmentCalendarWidget::class)
        ->assertSee('appointment_request_id=' . $request->id, false)
        ->assertSee('patient_id=' . $patient->id, false)
        ->assertSee('appointment_type_id=' . $appointmentType->id, false)
        ->assertSee('practitioner_id=' . $practitioner->id, false)
        ->assertSee('return_url=', false)
        ->assertSee('start_datetime=', false);
});
