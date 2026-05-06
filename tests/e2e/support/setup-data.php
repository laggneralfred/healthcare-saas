<?php

use App\Models\Appointment;
use App\Models\AppointmentRequest;
use App\Models\AppointmentType;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\PractitionerTimeBlock;
use App\Models\PractitionerWorkingHour;
use App\Models\User;
use App\Services\PatientPortalTokenService;
use App\Support\PracticeAccessRoles;
use Carbon\Carbon;
use Filament\Auth\Pages\Login;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

require __DIR__.'/../../../vendor/autoload.php';

$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

PracticeAccessRoles::ensureRoles();

$scenario = $argv[1] ?? 'base';
$baseUrl = rtrim(getenv('PLAYWRIGHT_BASE_URL') ?: 'http://127.0.0.1:8002', '/');

function e2ePractice(): Practice
{
    $practice = Practice::query()->firstOrCreate(
        ['slug' => 'playwright-e2e-practice'],
        [
            'name' => 'Playwright E2E Practice',
            'timezone' => 'America/Los_Angeles',
            'is_active' => true,
            'is_demo' => false,
            'trial_ends_at' => now()->addDays(30),
            'default_appointment_duration' => 45,
        ],
    );

    $practice->forceFill([
        'is_active' => true,
        'is_demo' => false,
        'timezone' => 'America/Los_Angeles',
        'trial_ends_at' => $practice->trial_ends_at && $practice->trial_ends_at->isFuture()
            ? $practice->trial_ends_at
            : now()->addDays(30),
        'default_appointment_duration' => $practice->default_appointment_duration ?: 45,
    ])->save();

    return $practice->refresh();
}

function e2eAdmin(Practice $practice): User
{
    $email = getenv('E2E_ADMIN_EMAIL') ?: 'admin@healthcare.test';
    $password = getenv('E2E_ADMIN_PASSWORD') ?: 'password';

    $user = User::query()->firstOrNew(['email' => $email]);
    $user->forceFill([
        'name' => 'Playwright E2E Admin',
        'practice_id' => $practice->id,
        'email_verified_at' => $user->email_verified_at ?: now(),
    ]);

    if (! $user->exists || ! Hash::check($password, $user->password ?? '')) {
        $user->password = Hash::make($password);
    }

    $user->save();

    $user->assignRole(User::ROLE_ADMINISTRATOR);

    return $user;
}

function e2ePatient(Practice $practice, string $key = 'patient'): Patient
{
    $email = "playwright-{$key}@example.test";

    return Patient::withoutPracticeScope()->updateOrCreate(
        [
            'practice_id' => $practice->id,
            'email' => $email,
        ],
        [
            'first_name' => 'Playwright',
            'last_name' => Str::headline($key),
            'phone' => '555-555-0100',
            'is_patient' => true,
        ],
    );
}

function e2ePractitioner(Practice $practice, string $name = 'Dr. Playwright'): Practitioner
{
    $user = User::query()->updateOrCreate(
        ['email' => Str::slug($name).'-e2e@example.test'],
        [
            'name' => $name,
            'password' => Hash::make('password'),
            'practice_id' => $practice->id,
            'email_verified_at' => now(),
        ],
    );

    $practitioner = Practitioner::withoutPracticeScope()->updateOrCreate(
        [
            'practice_id' => $practice->id,
            'user_id' => $user->id,
        ],
        [
            'license_number' => 'E2E-'.$practice->id,
            'specialty' => 'Acupuncture',
            'is_active' => true,
        ],
    );

    return $practitioner->load('user');
}

function e2eAppointmentType(Practice $practice, string $name = 'E2E Acupuncture Follow-Up'): AppointmentType
{
    return AppointmentType::withoutPracticeScope()->updateOrCreate(
        [
            'practice_id' => $practice->id,
            'name' => $name,
        ],
        [
            'duration_minutes' => 45,
            'is_active' => true,
        ],
    );
}

function attachType(Practice $practice, Practitioner $practitioner, AppointmentType $type): void
{
    $practitioner->appointmentTypes()->syncWithoutDetaching([
        $type->id => [
            'practice_id' => $practice->id,
            'is_active' => true,
        ],
    ]);
}

function workingDate(Practice $practice, Practitioner $practitioner): Carbon
{
    $date = now($practice->timezone ?: 'America/Los_Angeles')->addDay()->setTime(9, 0);

    foreach (range(0, 6) as $dayOfWeek) {
        PractitionerWorkingHour::withoutPracticeScope()->updateOrCreate(
            [
                'practice_id' => $practice->id,
                'practitioner_id' => $practitioner->id,
                'day_of_week' => $dayOfWeek,
            ],
            [
                'start_time' => '09:00',
                'end_time' => '17:00',
                'is_active' => true,
            ],
        );
    }

    return $date;
}

function appointmentRequest(Practice $practice, Patient $patient, AppointmentType $type, ?Practitioner $practitioner, string $key): AppointmentRequest
{
    return AppointmentRequest::withoutPracticeScope()->updateOrCreate(
        [
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'token_hash' => hash('sha256', 'e2e-'.$key),
        ],
        [
            'status' => AppointmentRequest::STATUS_PENDING,
            'requested_service' => $type->name,
            'appointment_type_id' => $type->id,
            'practitioner_id' => $practitioner?->id,
            'preferred_times' => 'Monday morning',
            'note' => 'E2E appointment request',
            'submitted_at' => now(),
        ],
    );
}

function formSubmission(Practice $practice, Patient $patient): FormSubmission
{
    $template = FormTemplate::findOrCreateDefaultNewPatientIntake($practice->id);

    return FormSubmission::withoutPracticeScope()->updateOrCreate(
        [
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'form_template_id' => $template->id,
        ],
        [
            'submitted_data_json' => null,
            'status' => FormSubmission::STATUS_PENDING,
        ],
    );
}

function appointmentCreateUrl(string $baseUrl, AppointmentRequest $request, Patient $patient, AppointmentType $type, Practitioner $practitioner, Carbon $start): string
{
    return $baseUrl.'/admin/appointments/create?'.http_build_query([
        'appointment_request_id' => $request->id,
        'patient_id' => $patient->id,
        'appointment_type_id' => $type->id,
        'practitioner_id' => $practitioner->id,
        'start_datetime' => $start->format('Y-m-d H:i:s'),
        'return_url' => $baseUrl.'/admin/front-desk',
    ]);
}

$practice = e2ePractice();
$admin = e2eAdmin($practice);
RateLimiter::clear('livewire-rate-limiter:'.sha1(Login::class.'|authenticate|127.0.0.1'));
$patientKey = in_array($scenario, ['new-interest-count', 'appointment-count'], true)
    ? ($argv[2] ?? $scenario)
    : $scenario;
$patient = e2ePatient($practice, $patientKey);
$practitionerName = match ($patientKey) {
    'preferred-request' => 'Dr. Preferred Playwright',
    'validation' => 'Dr. Validation Playwright',
    default => 'Dr. Playwright',
};
$practitioner = e2ePractitioner($practice, $practitionerName);
$type = e2eAppointmentType($practice);
attachType($practice, $practitioner, $type);
$start = workingDate($practice, $practitioner);

if (! in_array($scenario, ['appointment-count'], true)) {
    Appointment::withoutPracticeScope()
        ->where('practice_id', $practice->id)
        ->where('patient_id', $patient->id)
        ->delete();
}

$data = [
    'practiceId' => $practice->id,
    'practiceSlug' => $practice->slug,
    'adminEmail' => $admin->email,
    'patientId' => $patient->id,
    'patientName' => $patient->name,
    'patientEmail' => $patient->email,
    'practitionerId' => $practitioner->id,
    'practitionerName' => $practitioner->user?->name,
    'appointmentTypeId' => $type->id,
    'appointmentTypeName' => $type->name,
    'validStart' => $start->format('Y-m-d H:i:s'),
    'validEnd' => $start->copy()->addMinutes(45)->format('Y-m-d H:i:s'),
    'calendarClickUrl' => $baseUrl.'/admin/schedule?'.http_build_query([
        'date' => $start->format('Y-m-d'),
        'appointment_request_id' => null,
        'patient_id' => $patient->id,
        'appointment_type_id' => $type->id,
        'practitioner_id' => $practitioner->id,
        'return_url' => $baseUrl.'/admin/front-desk',
    ]),
];

if ($scenario === 'portal') {
    [$token, $plainToken] = app(PatientPortalTokenService::class)->createForExistingPatient($patient, $admin);
    $data['portalUrl'] = $baseUrl.'/patient/magic-link/'.$plainToken;
}

if ($scenario === 'preferred-request') {
    $request = appointmentRequest($practice, $patient, $type, $practitioner, 'preferred-request');
    $data['appointmentRequestId'] = $request->id;
    $data['todayUrl'] = $baseUrl.'/admin/front-desk';
    $data['calendarUrl'] = $baseUrl.'/admin/schedule?'.http_build_query([
        'date' => $start->format('Y-m-d'),
        'appointment_request_id' => $request->id,
        'patient_id' => $patient->id,
        'appointment_type_id' => $type->id,
        'practitioner_id' => $practitioner->id,
        'return_url' => $baseUrl.'/admin/front-desk',
    ]);
    $data['createUrl'] = appointmentCreateUrl($baseUrl, $request, $patient, $type, $practitioner, $start);
}

if ($scenario === 'no-preference-request') {
    $request = appointmentRequest($practice, $patient, $type, null, 'no-preference-request');
    $data['appointmentRequestId'] = $request->id;
    $data['createUrl'] = $baseUrl.'/admin/appointments/create?'.http_build_query([
        'appointment_request_id' => $request->id,
        'patient_id' => $patient->id,
        'appointment_type_id' => $type->id,
        'return_url' => $baseUrl.'/admin/front-desk',
    ]);
}

if ($scenario === 'forms') {
    $submission = formSubmission($practice, $patient);
    [$token, $plainToken] = app(PatientPortalTokenService::class)->createForExistingPatientForm($patient, $admin);
    $data['formSubmissionId'] = $submission->id;
    $data['portalFormsUrl'] = $baseUrl.'/patient/magic-link/'.$plainToken;
}

if ($scenario === 'validation') {
    $request = appointmentRequest($practice, $patient, $type, $practitioner, 'validation');

    PractitionerTimeBlock::withoutPracticeScope()->updateOrCreate(
        [
            'practice_id' => $practice->id,
            'practitioner_id' => $practitioner->id,
            'starts_at' => $start->copy()->addHours(2)->format('Y-m-d H:i:s'),
        ],
        [
            'ends_at' => $start->copy()->addHours(3)->format('Y-m-d H:i:s'),
            'block_type' => 'admin',
            'reason' => 'E2E blocked time',
        ],
    );

    $data['outsideStart'] = $start->copy()->setTime(7, 0)->format('Y-m-d H:i:s');
    $data['outsideEnd'] = $start->copy()->setTime(7, 45)->format('Y-m-d H:i:s');
    $data['blockedStart'] = $start->copy()->addHours(2)->format('Y-m-d H:i:s');
    $data['blockedEnd'] = $start->copy()->addHours(2)->addMinutes(45)->format('Y-m-d H:i:s');
    $data['outsideCreateUrl'] = appointmentCreateUrl($baseUrl, $request, $patient, $type, $practitioner, $start->copy()->setTime(7, 0));
    $data['blockedCreateUrl'] = appointmentCreateUrl($baseUrl, $request, $patient, $type, $practitioner, $start->copy()->addHours(2));
    $data['validCreateUrl'] = appointmentCreateUrl($baseUrl, $request, $patient, $type, $practitioner, $start);
}

if ($scenario === 'new-interest-count') {
    $email = $argv[3] ?? '';
    $data['patientCount'] = Patient::withoutPracticeScope()
        ->where('practice_id', $practice->id)
        ->where('email', $email)
        ->count();
}

if ($scenario === 'appointment-count') {
    $data['appointmentCount'] = Appointment::withoutPracticeScope()
        ->where('practice_id', $practice->id)
        ->where('patient_id', $patient->id)
        ->count();
}

echo json_encode($data, JSON_THROW_ON_ERROR);
