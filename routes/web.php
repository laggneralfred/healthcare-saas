<?php

use App\Http\Controllers\Admin\PracticeSwitchController;
use App\Http\Controllers\NewPatientFormController;
use App\Http\Controllers\NewPatientInterestController;
use App\Http\Controllers\PatientPortalAppointmentRequestController;
use App\Http\Controllers\PatientPortalFormController;
use App\Http\Controllers\PatientPortalMagicLinkController;
use App\Http\Controllers\PublicPracticeLinksController;
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

$seoLandingPages = [
    'practice-software-for-acupuncturists' => [
        'title' => 'Acupuncture Practice Software for Notes, Intake Forms, Follow-Up, and Checkout Tracking | Practiq',
        'description' => 'Practiq helps acupuncture practices manage visit notes, intake forms, appointment requests, patient follow-up, checkout tracking, and simple reports without a complicated system.',
        'eyebrow' => 'Acupuncture practice software',
        'h1' => 'Practice software for busy acupuncturists.',
        'subheadline' => 'Practiq helps acupuncture practices keep up with treatment notes, intake forms, appointment requests, follow-up, checkout tracking, and simple reports — without adding more admin work to your day.',
        'dailyHeading' => 'Built for the daily rhythm of an acupuncture practice',
        'dailyCopy' => [
            'Acupuncture care depends on attention, continuity, and time with the patient. But the work around the visit can pile up quickly: notes to finish, forms to review, patients to follow up with, and payments to track.',
            'Practiq is designed to help small acupuncture practices keep the everyday work organized in one practical workflow.',
            'It is not built for hospitals. It is not trying to be a giant insurance system. It is for small practices that need a clearer way to manage the day.',
        ],
        'helps' => [
            ['Visit notes', 'Write clear visit notes without fighting a complicated screen. Practiq supports natural note-taking, with optional tools to help organize or improve a draft while keeping you in control.'],
            ['Intake and consent forms', 'Collect intake and consent information before the visit, so patient information is easier to review when care begins.'],
            ['Appointment requests', 'Patients can request appointments, while you or your staff still confirm the actual time. Practiq does not force automatic booking.'],
            ['Follow-up', 'See which patients may need a reminder, invitation back, or continued care follow-up.'],
            ['Checkout tracking', 'Record charges and payments in a simple way, so you can keep track of what happened at the visit.'],
            ['Simple reports and exports', 'View useful practice totals and export records for bookkeeping. Practiq is not a full accounting system, but it helps you stay organized.'],
        ],
        'starterHeading' => 'A calmer first day',
        'starterCopy' => [
            'When you start a trial, Practiq creates editable starter settings for your practice, including a practitioner, weekday working hours, an Initial Visit, a Follow-up Visit, and starter fees.',
            'You can change all of this later. The point is simple: you should be able to try the software without staring at an empty system.',
        ],
        'fit' => ['Solo acupuncturists', 'Small acupuncture clinics', 'TCM practices', 'Five Element acupuncture practices', 'Wellness clinics that include acupuncture', 'Practitioners who want simpler notes and follow-up tracking'],
        'not' => 'Practiq is not a hospital EHR, insurance billing clearinghouse, automatic booking engine, full accounting system, or replacement for practitioner judgment.',
        'seoPhrase' => 'acupuncture practice software',
    ],
    'massage-therapy-practice-software' => [
        'title' => 'Massage Therapy Practice Software for Client Notes, Intake Forms, Follow-Up, and Checkout Tracking | Practiq',
        'description' => 'Practiq helps massage therapists manage client notes, intake and consent forms, appointment requests, follow-up, checkout tracking, and simple practice reports.',
        'eyebrow' => 'Massage therapy practice software',
        'h1' => 'Practice software for busy massage therapists.',
        'subheadline' => 'Practiq helps massage practices manage client notes, intake forms, appointment requests, follow-up, checkout tracking, and simple reports — without making your day more complicated.',
        'dailyHeading' => 'Keep the hands-on work at the center',
        'dailyCopy' => [
            'Massage therapy is personal, practical, and time-based. Between sessions, it can be hard to keep up with notes, intake forms, follow-up, scheduling requests, and payment records.',
            'Practiq helps massage therapists keep the daily business of care organized without turning the practice into a software project.',
        ],
        'helps' => [
            ['Client notes', 'Keep clear notes about the session, areas addressed, tissue response, client goals, and follow-up needs.'],
            ['Intake and consent forms', 'Collect client information and consent forms before the appointment, so you are not chasing paperwork at the last minute.'],
            ['Appointment requests', 'Let clients request appointments while you remain in control of the schedule. Practiq supports appointment requests, not forced automatic booking.'],
            ['Follow-up', 'See who may need a follow-up message, reminder, or invitation to return.'],
            ['Checkout tracking', 'Track visit charges and payments in a straightforward way.'],
            ['Simple reports and exports', 'See useful totals and export information for bookkeeping or practice review.'],
        ],
        'starterHeading' => 'Start with useful defaults',
        'starterCopy' => [
            'A new trial should not feel empty. Practiq sets up editable starter defaults such as a practitioner, weekday hours, an Initial Visit, a Follow-up Visit, and starter fees.',
            'You can change them any time.',
        ],
        'fit' => ['Solo massage therapists', 'Small massage studios', 'Therapeutic massage practices', 'Bodywork practitioners', 'Wellness clinics that include massage therapy', 'Practitioners who want less paperwork after sessions'],
        'not' => 'Practiq is not a spa booking marketplace, full accounting system, automatic booking engine, or replacement for professional judgment.',
        'seoPhrase' => 'massage therapy practice software',
    ],
    'chiropractic-practice-software' => [
        'title' => 'Chiropractic Practice Software for Visit Notes, Intake Forms, Follow-Up, and Checkout Tracking | Practiq',
        'description' => 'Practiq helps chiropractic practices manage visit notes, intake forms, appointment requests, patient follow-up, checkout tracking, and simple reports.',
        'eyebrow' => 'Chiropractic practice software',
        'h1' => 'Practice software for busy chiropractors.',
        'subheadline' => 'Practiq helps chiropractic practices manage visit notes, intake forms, appointment requests, follow-up, checkout tracking, and simple reports — without adding more admin work to your day.',
        'dailyHeading' => 'Support patient flow without burying the clinic in admin',
        'dailyCopy' => [
            'A chiropractic practice depends on steady patient flow, clear documentation, and consistent follow-up. But small clinics often have limited time and limited staff to keep everything moving.',
            'Practiq helps organize the daily work around care: notes, forms, appointment requests, follow-up, checkout tracking, and basic reports.',
            'It is built for small practices that want a practical workflow, not a giant system.',
        ],
        'helps' => [
            ['Visit notes', 'Keep clear visit notes for each patient encounter. Practiq supports straightforward documentation without forcing you through unnecessary complexity.'],
            ['Intake and consent forms', 'Collect patient information and consent forms before the first visit.'],
            ['Appointment requests', 'Patients can request appointments online, while your clinic confirms the actual time.'],
            ['Follow-up', 'See which patients may need a reminder, continued care follow-up, or invitation back.'],
            ['Checkout tracking', 'Record charges and payments in a simple, practical way.'],
            ['Practice statistics and exports', 'Review useful totals and export information for bookkeeping or practice review.'],
        ],
        'starterHeading' => 'A simpler start',
        'starterCopy' => [
            'Practiq creates editable starter settings for new trial practices, including a practitioner, weekday working hours, an Initial Visit, a Follow-up Visit, and starter fees.',
            'You can adjust everything later. The goal is to help you evaluate the software quickly.',
        ],
        'fit' => ['Solo chiropractors', 'Small chiropractic offices', 'Cash-based or mixed small practices', 'Clinics that want simpler patient follow-up', 'Practices that need basic checkout and reporting visibility'],
        'not' => 'Practiq is not a full insurance billing clearinghouse, hospital EHR, automatic booking engine, or full accounting system.',
        'seoPhrase' => 'chiropractic practice software',
    ],
    'physiotherapy-practice-software' => [
        'title' => 'Physiotherapy Practice Software for Progress Notes, Intake Forms, Follow-Up, and Checkout Tracking | Practiq',
        'description' => 'Practiq helps physiotherapy practices manage progress notes, intake forms, appointment requests, patient follow-up, checkout tracking, and simple reports.',
        'eyebrow' => 'Physiotherapy practice software',
        'h1' => 'Practice software for busy physiotherapists.',
        'subheadline' => 'Practiq helps physiotherapy practices manage progress notes, intake forms, appointment requests, follow-up, checkout tracking, and simple reports — without adding more admin work to your day.',
        'dailyHeading' => 'Keep patient progress and daily clinic work organized',
        'dailyCopy' => [
            'Physiotherapy care often involves repeated visits, progress tracking, follow-up, and clear communication. In a small practice, the administrative work can easily crowd the time meant for patient care.',
            'Practiq helps small physiotherapy practices keep the daily workflow organized: notes, forms, appointment requests, follow-up, checkout tracking, and simple reporting.',
        ],
        'helps' => [
            ['Progress and visit notes', 'Keep clear notes for each session, including patient concerns, care provided, response, and follow-up plans.'],
            ['Intake and consent forms', 'Collect patient information before the first visit so the appointment can start with better context.'],
            ['Appointment requests', 'Let patients request appointments while staff or the practitioner confirms the schedule.'],
            ['Follow-up', 'See which patients may need follow-up, reminders, or continued care outreach.'],
            ['Checkout tracking', 'Record charges and payments without turning checkout into a complicated process.'],
            ['Simple reports and exports', 'View useful practice totals and export records for bookkeeping or review.'],
        ],
        'starterHeading' => 'Start with editable defaults',
        'starterCopy' => [
            'When a new trial begins, Practiq creates starter settings so the practice is not empty: practitioner, weekday hours, Initial Visit, Follow-up Visit, and starter fees.',
            'Everything can be edited later.',
        ],
        'fit' => ['Solo physiotherapists', 'Small rehab clinics', 'Private physiotherapy practices', 'Practices with repeated patient visits', 'Clinics that need clearer follow-up and visit tracking'],
        'not' => 'Practiq is not a hospital EHR, insurance billing clearinghouse, automatic booking engine, or full accounting system.',
        'seoPhrase' => 'physiotherapy practice software',
    ],
    'wellness-practice-software' => [
        'title' => 'Wellness Practice Software for Client Notes, Forms, Follow-Up, and Checkout Tracking | Practiq',
        'description' => 'Practiq helps wellness practices manage client notes, intake forms, appointment requests, follow-up, checkout tracking, and simple reports.',
        'eyebrow' => 'Wellness practice software',
        'h1' => 'Practice software for busy wellness practitioners.',
        'subheadline' => 'Practiq helps wellness practices manage notes, intake forms, appointment requests, follow-up, checkout tracking, and simple reports — without making your day more complicated.',
        'dailyHeading' => 'Practical support for the work around client care',
        'dailyCopy' => [
            'Wellness practices often run on trust, consistency, and personal attention. But the daily admin can still pile up: notes, forms, appointment requests, follow-up messages, payment tracking, and basic reporting.',
            'Practiq helps wellness practitioners keep that work organized in a simple, practical workflow.',
        ],
        'helps' => [
            ['Client notes', 'Keep clear notes about sessions, client goals, care provided, response, and follow-up needs.'],
            ['Intake and consent forms', 'Collect important information before the first visit, so you are better prepared.'],
            ['Appointment requests', 'Let clients request appointments without giving up control of your schedule.'],
            ['Follow-up', 'See who may need a reminder, check-in, or invitation to return.'],
            ['Checkout tracking', 'Record charges and payments in a straightforward way.'],
            ['Simple reports and exports', 'See useful totals and export information for bookkeeping or practice review.'],
        ],
        'starterHeading' => 'Start without an empty system',
        'starterCopy' => [
            'Practiq creates starter settings for a new trial, including a practitioner, weekday working hours, Initial Visit and Follow-up Visit appointment types, and starter fees.',
            'You can change all of it later. It is just there to help you get started.',
        ],
        'fit' => ['Solo wellness practitioners', 'Small wellness clinics', 'Integrative health practices', 'Bodywork and holistic care providers', 'Practices that want a simpler daily workflow'],
        'not' => 'Practiq is not a hospital EHR, automatic booking marketplace, full accounting system, or replacement for professional judgment.',
        'seoPhrase' => 'wellness practice software',
    ],
];

foreach ($seoLandingPages as $slug => $page) {
    Route::get("/{$slug}", fn () => view('seo.practitioner-page', ['page' => $page]))->name("seo.{$slug}");
}

Route::view('/blog/small-clinic-visit-notes', 'blog.small-clinic-visit-notes')
    ->name('blog.small-clinic-visit-notes');

Route::get('/sitemap.xml', function () {
    $baseUrl = 'https://practiqapp.com';
    $lastmod = now()->toDateString();

    $urls = [
        '/' => ['changefreq' => 'weekly', 'priority' => '1.0'],
        '/register' => ['changefreq' => 'weekly', 'priority' => '0.8'],
        '/subscribe' => ['changefreq' => 'monthly', 'priority' => '0.7'],
        '/legal/terms' => ['changefreq' => 'monthly', 'priority' => '0.4'],
        '/legal/privacy' => ['changefreq' => 'monthly', 'priority' => '0.4'],
        '/legal/hipaa-baa' => ['changefreq' => 'monthly', 'priority' => '0.4'],
        '/legal/ai-disclaimer' => ['changefreq' => 'monthly', 'priority' => '0.4'],
        '/practice-software-for-acupuncturists' => ['changefreq' => 'monthly', 'priority' => '0.7'],
        '/massage-therapy-practice-software' => ['changefreq' => 'monthly', 'priority' => '0.7'],
        '/chiropractic-practice-software' => ['changefreq' => 'monthly', 'priority' => '0.7'],
        '/physiotherapy-practice-software' => ['changefreq' => 'monthly', 'priority' => '0.7'],
        '/wellness-practice-software' => ['changefreq' => 'monthly', 'priority' => '0.7'],
        '/blog/small-clinic-visit-notes' => ['changefreq' => 'monthly', 'priority' => '0.6'],
    ];

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "
";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "
";

    foreach ($urls as $path => $meta) {
        $loc = htmlspecialchars(rtrim($baseUrl, '/') . $path, ENT_XML1, 'UTF-8');

        $xml .= "    <url>
";
        $xml .= "        <loc>{$loc}</loc>
";
        $xml .= "        <lastmod>{$lastmod}</lastmod>
";
        $xml .= "        <changefreq>{$meta['changefreq']}</changefreq>
";
        $xml .= "        <priority>{$meta['priority']}</priority>
";
        $xml .= "    </url>
";
    }

    $xml .= '</urlset>' . "
";

    return response($xml, 200, [
        'Content-Type' => 'application/xml; charset=UTF-8',
    ]);
})->name('sitemap');

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
Route::get('/p/{practiceSlug}/new-patient', [PublicPracticeLinksController::class, 'newPatient'])->name('public.practice.new-patient');
Route::post('/p/{practiceSlug}/new-patient', [PublicPracticeLinksController::class, 'storeNewPatient'])->name('public.practice.new-patient.store');
Route::get('/p/{practiceSlug}/existing-patient', [PublicPracticeLinksController::class, 'existingPatient'])->name('public.practice.existing-patient');
Route::post('/p/{practiceSlug}/existing-patient', [PublicPracticeLinksController::class, 'sendExistingPatientLink'])
    ->middleware('throttle:6,1')
    ->name('public.practice.existing-patient.store');
Route::get('/p/{practiceSlug}/request-appointment', [PublicPracticeLinksController::class, 'requestAppointment'])->name('public.practice.request-appointment');

// Public trial registration — no authentication required
use App\Http\Controllers\RegistrationController;
Route::get('/register', [RegistrationController::class, 'show'])->name('register');
Route::post('/register', [RegistrationController::class, 'store'])->name('register.store');

// Trial expired / upgrade page — no authentication required (but logged-in users see their data)
Route::get('/subscribe', fn() => view('subscribe'))->name('subscribe');

// Legal documents — no authentication required
Route::view('/terms', 'legal.terms')->name('terms');
Route::view('/privacy', 'legal.privacy')->name('privacy');
Route::view('/legal/hipaa-baa', 'legal.hipaa-baa')->name('legal.hipaa-baa');
Route::view('/legal/ai-disclaimer', 'legal.ai-disclaimer')->name('legal.ai-disclaimer');

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
