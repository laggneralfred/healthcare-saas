<?php

use App\Http\Controllers\Admin\PracticeSwitchController;
use App\Http\Controllers\NewPatientFormController;
use App\Http\Controllers\NewPatientInterestController;
use App\Http\Controllers\PatientPortalAppointmentRequestController;
use App\Http\Controllers\PatientPortalFormController;
use App\Http\Controllers\PatientPortalMagicLinkController;
use App\Http\Controllers\StripeWebhookController;
use App\Livewire\Public\AppointmentRequestForm;
use App\Livewire\Public\BookingCalendar;
use App\Livewire\Public\ConsentForm;
use App\Livewire\Public\IntakeForm;
use App\Livewire\OnboardingWizard;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Services\PatientCareStatusService;
use App\Services\PracticeContext;

$isAppHost = fn (Request $request): bool => $request->getHost() === 'app.practiqapp.com';

Route::get('/', function (Request $request) use ($isAppHost) {
    if ($isAppHost($request)) {
        return auth()->check()
            ? redirect('/admin/dashboard')
            : redirect('/login');
    }

    return view('welcome');
});

Route::get('/login', function (Request $request) use ($isAppHost) {
    abort_unless($isAppHost($request), 404);

    return redirect('/admin/login');
})->name('login');

Route::view('/user-instructions', 'user-instructions')->name('user-instructions');

// Filament v5 homeUrl bug workaround: /admin always redirects to dashboard
Route::get('/admin', function () {
    return redirect('/admin/dashboard');
})->middleware(['web']);

// Stripe webhook — exempt from CSRF, no auth required
// Takes precedence over Cashier's own /stripe/webhook route
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class])
    ->name('cashier.webhook');

// Admin practice switcher — authenticated, no subscription check needed
Route::post('/admin/switch-practice', [PracticeSwitchController::class, 'switch'])
    ->middleware(['web', 'auth'])
    ->name('admin.switch-practice');

// Public booking page — no authentication required
Route::get('/book/{practice:slug}', BookingCalendar::class)->name('booking.show');

// Public token-based forms — no authentication required
Route::get('/intake/{token}', IntakeForm::class)->name('intake.show');
Route::get('/consent/{token}', ConsentForm::class)->name('consent.show');
Route::get('/appointment-request/{token}', AppointmentRequestForm::class)->name('appointment-request.show');
Route::get('/patient/magic-link/{token}', [PatientPortalMagicLinkController::class, 'show'])->name('patient.magic-link');
Route::get('/patient/link-unavailable', [PatientPortalMagicLinkController::class, 'invalid'])->name('patient.portal.invalid');
Route::get('/patient/logged-out', [PatientPortalMagicLinkController::class, 'loggedOut'])->name('patient.portal.logged-out');
Route::get('/patient/new-patient-form-submitted', [NewPatientFormController::class, 'thanks'])->name('patient.new-patient-form.thanks');
Route::get('/patient/new-patient-form/{token}', [NewPatientFormController::class, 'show'])->name('patient.new-patient-form.show');
Route::post('/patient/new-patient-form/{token}', [NewPatientFormController::class, 'store'])->name('patient.new-patient-form.store');
Route::get('/patient/dashboard', [PatientPortalMagicLinkController::class, 'dashboard'])->middleware('patient.portal')->name('patient.dashboard');
Route::get('/patient/appointments/request', [PatientPortalAppointmentRequestController::class, 'create'])->middleware('patient.portal')->name('patient.appointment-request.create');
Route::post('/patient/appointments/request', [PatientPortalAppointmentRequestController::class, 'store'])->middleware('patient.portal')->name('patient.appointment-request.store');
Route::get('/patient/forms', [PatientPortalFormController::class, 'index'])->middleware('patient.portal')->name('patient.forms.index');
Route::get('/patient/forms/{formSubmission}', [PatientPortalFormController::class, 'show'])->middleware('patient.portal')->name('patient.forms.show');
Route::post('/patient/forms/{formSubmission}', [PatientPortalFormController::class, 'store'])->middleware('patient.portal')->name('patient.forms.store');
Route::post('/patient/logout', [PatientPortalMagicLinkController::class, 'logout'])->middleware('patient.portal')->name('patient.logout');
Route::get('/new-patient', [NewPatientInterestController::class, 'create'])->name('new-patient.interest');
Route::post('/new-patient/interest', [NewPatientInterestController::class, 'store'])->name('new-patient.interest.store');
Route::get('/new-patient/thanks', [NewPatientInterestController::class, 'thanks'])->name('new-patient.thanks');
Route::get('/new-patient/unavailable', [NewPatientInterestController::class, 'unavailable'])->name('new-patient.unavailable');

// Public trial registration — no authentication required
use App\Http\Controllers\RegistrationController;
Route::get('/register', [RegistrationController::class, 'show'])->name('register');
Route::post('/register', [RegistrationController::class, 'store'])->name('register.store');

// Trial expired / upgrade page — no authentication required (but logged-in users see their data)
Route::get('/subscribe', fn() => view('subscribe'))->name('subscribe');

// Legal documents — no authentication required
Route::view('/terms', 'legal.terms')->name('terms');
Route::view('/privacy', 'legal.privacy')->name('privacy');

// Data export — authenticated, but accessible to expired trial users within grace period
use App\Http\Controllers\ExportController;
Route::post('/export', [ExportController::class, 'request'])->name('export.request')->middleware(['web', 'auth']);
Route::get('/export/download/{token}', [ExportController::class, 'download'])->name('export.download')->middleware(['web', 'auth']);

// CSV import template download
Route::get('/import/template', function () {
    $csv = \App\Services\CSVImportService::generateTemplate();
    return response($csv, 200, [
        'Content-Type'        => 'text/csv',
        'Content-Disposition' => 'attachment; filename="patient_import_template.csv"',
    ]);
})->name('import.template')->middleware(['web', 'auth']);

// Trial user onboarding wizard
Route::get('/onboarding', OnboardingWizard::class)->middleware(['web', 'auth'])->name('onboarding');

// Dismiss the "Complete Setup" banner on the dashboard
Route::post('/admin/dismiss-setup-banner', function () {
    auth()->user()->practice?->update(['dismissed_onboarding_banner' => true]);
    return back();
})->middleware(['web', 'auth'])->name('admin.dismiss-setup-banner');

// FullCalendar events feed — authenticated, scoped to logged-in user's practice
Route::get('/admin/calendar/events', function (Request $request) {
    $practiceId  = PracticeContext::currentPracticeId();
    $practice    = $practiceId ? \App\Models\Practice::find($practiceId) : null;
    $timezone    = $practice?->timezone ?? 'UTC';

    $start = $request->get('start') ? Carbon::parse($request->get('start')) : now()->startOfMonth();
    $end   = $request->get('end')   ? Carbon::parse($request->get('end'))   : now()->endOfMonth();

    $statusColors = [
        'scheduled'   => '#3b82f6',
        'in_progress' => '#d97706',
        'completed'   => '#16a34a',
        'closed'      => '#6b7280',
        'checkout'    => '#7c3aed',
        'no_show'     => '#9ca3af',
    ];

    $careStatusService = app(PatientCareStatusService::class);

    $practitionerId = $request->integer('practitioner_id');

    $events = Appointment::where('practice_id', $practiceId)
        ->when($practitionerId, fn ($query) => $query->where('practitioner_id', $practitionerId))
        ->whereNotIn('status', ['cancelled'])
        ->whereBetween('start_datetime', [$start, $end])
        ->with([
            'patient.appointments',
            'patient.encounters',
            'practitioner.user',
            'appointmentType',
        ])
        ->get()
        ->map(function ($appt) use ($careStatusService, $statusColors, $timezone) {
            $patientName      = $appt->patient?->full_name ?: $appt->patient?->name ?? 'Unknown';
            $practitionerName = $appt->practitioner?->user?->name ?? '';
            $statusKey        = $appt->getRawOriginal('status') ?? 'scheduled';
            $careStatus       = $appt->patient
                ? $careStatusService->forPatient($appt->patient)
                : null;

            return [
                'id'    => $appt->id,
                'title' => $patientName . ($practitionerName ? ' · ' . $practitionerName : ''),
                'start' => $appt->start_datetime->copy()->setTimezone($timezone)->toIso8601String(),
                'end'   => $appt->end_datetime->copy()->setTimezone($timezone)->toIso8601String(),
                'url'   => route('filament.admin.resources.appointments.view', [
                    'record' => $appt->id,
                    'return_url' => \App\Filament\Pages\SchedulePage::getUrl(),
                ]),
                'color' => $statusColors[$statusKey] ?? '#6b7280',
                'extendedProps' => [
                    'status'          => $statusKey,
                    'appointmentType' => $appt->appointmentType?->name ?? '',
                    'care_status_key' => $careStatus['key'] ?? null,
                    'care_status_label' => $careStatus['label'] ?? null,
                    'care_status_color' => $careStatus['color'] ?? null,
                    'care_status_helper' => $careStatus['helper'] ?? null,
                    'preferred_language' => $appt->patient?->preferred_language,
                    'preferred_language_label' => $appt->patient?->preferred_language_label,
                ],
            ];
        });

    return response()->json($events);
})->middleware(['web', 'auth'])->name('admin.calendar.events');

// Demo instant login — public, redirects to admin
Route::get('/demo-login', function () {
    $user = \App\Models\User::where('email', 'demo@practiqapp.com')->first();
    if ($user) {
        auth()->login($user);
        return redirect('/admin/dashboard');
    }
    return redirect('/admin/login')->with('error', 'Demo account not found. Please wait for the next reset.');
})->middleware(['web']);
