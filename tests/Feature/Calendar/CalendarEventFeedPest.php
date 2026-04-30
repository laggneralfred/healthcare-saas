<?php

use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\User;
use App\Services\PracticeContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

function makeCalendarContextForPractice(Practice $practice): array
{
    $user = User::factory()->create([
        'practice_id' => $practice->id,
    ]);

    $patient = Patient::factory()->create([
        'practice_id' => $practice->id,
        'first_name' => 'Jane',
        'last_name' => 'Carter',
        'name' => 'Jane Carter',
    ]);

    $practitioner = Practitioner::factory()->create([
        'practice_id' => $practice->id,
    ]);

    $appointmentType = AppointmentType::factory()->create([
        'practice_id' => $practice->id,
        'name' => 'Follow-up Acupuncture',
        'duration_minutes' => 45,
    ]);

    return compact('user', 'patient', 'practitioner', 'appointmentType');
}

it('returns calendar events for the current practice', function () {
    $practice = Practice::factory()->create();
    ['user' => $user, 'patient' => $patient, 'practitioner' => $practitioner, 'appointmentType' => $appointmentType] =
        makeCalendarContextForPractice($practice);

    $appointment = Appointment::factory()->create([
        'practice_id' => $practice->id,
        'patient_id' => $patient->id,
        'practitioner_id' => $practitioner->id,
        'appointment_type_id' => $appointmentType->id,
        'start_datetime' => '2026-04-22 09:00:00',
        'end_datetime' => '2026-04-22 09:45:00',
    ]);

    $this->actingAs($user);

    $response = $this->getJson(route('admin.calendar.events'));

    $response->assertOk();

    $response->assertJsonFragment([
        'id' => $appointment->id,
    ]);
});

it('uses patient full name in the calendar title', function () {
    $practice = Practice::factory()->create();
    ['user' => $user, 'patient' => $patient, 'practitioner' => $practitioner, 'appointmentType' => $appointmentType] =
        makeCalendarContextForPractice($practice);

    Appointment::factory()->create([
        'practice_id' => $practice->id,
        'patient_id' => $patient->id,
        'practitioner_id' => $practitioner->id,
        'appointment_type_id' => $appointmentType->id,
        'start_datetime' => '2026-04-22 10:00:00',
        'end_datetime' => '2026-04-22 10:45:00',
    ]);

    $this->actingAs($user);

    $response = $this->getJson(route('admin.calendar.events'));

    $response->assertOk();

    $titles = collect($response->json())->pluck('title')->implode(' | ');

    expect($titles)->toContain('Jane Carter');
});

it('includes patient care status and preferred language metadata', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-30 10:00:00'));

    $practice = Practice::factory()->create();
    ['user' => $user, 'patient' => $patient, 'practitioner' => $practitioner, 'appointmentType' => $appointmentType] =
        makeCalendarContextForPractice($practice);

    $patient->update([
        'preferred_language' => 'es',
    ]);

    Encounter::factory()->create([
        'practice_id' => $practice->id,
        'patient_id' => $patient->id,
        'appointment_id' => null,
        'practitioner_id' => $practitioner->id,
        'status' => 'complete',
        'visit_date' => now()->subDays(35)->toDateString(),
        'completed_on' => now()->subDays(35),
    ]);

    $appointment = Appointment::factory()->create([
        'practice_id' => $practice->id,
        'patient_id' => $patient->id,
        'practitioner_id' => $practitioner->id,
        'appointment_type_id' => $appointmentType->id,
        'start_datetime' => '2026-04-22 10:00:00',
        'end_datetime' => '2026-04-22 10:45:00',
    ]);

    $this->actingAs($user);

    $response = $this->getJson(route('admin.calendar.events', [
        'start' => '2026-04-01T00:00:00Z',
        'end' => '2026-04-30T23:59:59Z',
    ]));

    $response->assertOk();

    $event = collect($response->json())->firstWhere('id', $appointment->id);

    expect($event['extendedProps'])->toMatchArray([
        'care_status_key' => 'needs_follow_up',
        'care_status_label' => 'Needs Follow-Up',
        'care_status_color' => 'warning',
        'preferred_language' => 'es',
        'preferred_language_label' => 'Spanish',
    ]);

    expect($event['extendedProps']['care_status_helper'])->toBeString()->not->toBe('');

});

it('renders mobile schedule cards with care context', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-30 08:00:00'));

    $practice = Practice::factory()->create([
        'timezone' => 'America/Los_Angeles',
    ]);
    ['user' => $user, 'patient' => $patient, 'practitioner' => $practitioner, 'appointmentType' => $appointmentType] =
        makeCalendarContextForPractice($practice);

    $patient->update([
        'preferred_language' => 'es',
    ]);

    Appointment::factory()->create([
        'practice_id' => $practice->id,
        'patient_id' => $patient->id,
        'practitioner_id' => $practitioner->id,
        'appointment_type_id' => $appointmentType->id,
        'start_datetime' => '2026-04-30 17:00:00',
        'end_datetime' => '2026-04-30 17:45:00',
    ]);

    $this->actingAs($user);

    $this->get('/admin/schedule')
        ->assertSuccessful()
        ->assertSee('Today’s Appointments')
        ->assertSee('Jane Carter')
        ->assertSee('Care Status: Active')
        ->assertSee('Spanish')
        ->assertSee('Start Visit');

});

it('does not include cancelled appointments in the calendar feed', function () {
    $practice = Practice::factory()->create();
    ['user' => $user, 'patient' => $patient, 'practitioner' => $practitioner, 'appointmentType' => $appointmentType] =
        makeCalendarContextForPractice($practice);

    Appointment::factory()->create([
        'practice_id' => $practice->id,
        'patient_id' => $patient->id,
        'practitioner_id' => $practitioner->id,
        'appointment_type_id' => $appointmentType->id,
        'start_datetime' => '2026-04-22 11:00:00',
        'end_datetime' => '2026-04-22 11:45:00',
        'status' => 'cancelled',
    ]);

    $this->actingAs($user);

    $response = $this->getJson(route('admin.calendar.events'));

    $response->assertOk();

    expect($response->json())->toBeArray()->toHaveCount(0);
});

it('uses the session-selected practice for super-admin calendar feeds', function () {
    $practiceSelected = Practice::factory()->create();
    $practiceOther = Practice::factory()->create();

    $superAdmin = User::factory()->create([
        'practice_id' => null,
    ]);

    $selectedPatient = Patient::factory()->create([
        'practice_id' => $practiceSelected->id,
        'first_name' => 'Selected',
        'last_name' => 'Practice',
        'name' => 'Selected Practice',
    ]);

    $otherPatient = Patient::factory()->create([
        'practice_id' => $practiceOther->id,
        'first_name' => 'Other',
        'last_name' => 'Practice',
        'name' => 'Other Practice',
    ]);

    $selectedPractitioner = Practitioner::factory()->create([
        'practice_id' => $practiceSelected->id,
    ]);

    $otherPractitioner = Practitioner::factory()->create([
        'practice_id' => $practiceOther->id,
    ]);

    $selectedType = AppointmentType::factory()->create([
        'practice_id' => $practiceSelected->id,
        'name' => 'Acupuncture Follow-Up',
        'duration_minutes' => 45,
    ]);

    $otherType = AppointmentType::factory()->create([
        'practice_id' => $practiceOther->id,
        'name' => 'Massage Session',
        'duration_minutes' => 60,
    ]);

    $selectedAppointmentA = Appointment::factory()->create([
        'practice_id' => $practiceSelected->id,
        'patient_id' => $selectedPatient->id,
        'practitioner_id' => $selectedPractitioner->id,
        'appointment_type_id' => $selectedType->id,
        'start_datetime' => '2026-04-18 09:00:00',
        'end_datetime' => '2026-04-18 09:45:00',
    ]);

    $selectedAppointmentB = Appointment::factory()->create([
        'practice_id' => $practiceSelected->id,
        'patient_id' => $selectedPatient->id,
        'practitioner_id' => $selectedPractitioner->id,
        'appointment_type_id' => $selectedType->id,
        'start_datetime' => '2026-04-18 11:00:00',
        'end_datetime' => '2026-04-18 11:45:00',
    ]);

    Appointment::factory()->create([
        'practice_id' => $practiceOther->id,
        'patient_id' => $otherPatient->id,
        'practitioner_id' => $otherPractitioner->id,
        'appointment_type_id' => $otherType->id,
        'start_datetime' => '2026-04-18 10:00:00',
        'end_datetime' => '2026-04-18 11:00:00',
    ]);

    $this->actingAs($superAdmin);
    PracticeContext::setCurrentPracticeId($practiceSelected->id);

    $response = $this->getJson(route('admin.calendar.events', [
        'start' => '2026-04-01T00:00:00Z',
        'end' => '2026-04-30T23:59:59Z',
    ]));

    $response->assertOk();

    $json = collect($response->json());

    expect($json)->toHaveCount(2);

    $titles = $json->pluck('title')->implode(' | ');

    expect($titles)
        ->toContain('Selected Practice')
        ->not->toContain('Other Practice');

    expect($json->pluck('id')->all())
        ->toContain($selectedAppointmentA->id)
        ->toContain($selectedAppointmentB->id);
});
