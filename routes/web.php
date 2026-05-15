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
        'title' => 'Acupuncture Practice Software for Small Clinics — Notes, Intake, Follow-Up | Practiq',
        'description' => 'Acupuncture practice software for small clinics: keep visit notes, intake forms, follow-up, and checkout organized without losing continuity. 30-day free trial.',
        'eyebrow' => 'Acupuncture practice software',
        'h1' => 'Practice software for busy acupuncturists.',
        'image' => [
            'src' => '/images/practitioner-pages/acupuncture.png',
            'alt' => 'Acupuncture practitioner working in a calm small clinic setting',
        ],
        'subheadline' => 'Practiq is built for the way small acupuncture clinics actually run: notes written in between patients, forms and follow-up piling up, and too little time to clean everything up before the day ends.',
        'dailyHeading' => 'In acupuncture clinics, the important work often happens in the margins',
        'dailyCopy' => [
            'You finish a treatment, type a few rough lines while the next patient is already waiting, and tell yourself you will polish the note later. Then later turns into tomorrow. Meanwhile there are intake forms to review, follow-up to send, and checkout to close.',
            'That does not mean practitioners are careless. It means the day is full. Practiq helps keep those moving parts in one place so continuity does not depend on memory at 8:30 p.m.',
        ],
        'noteWorkflow' => [
            'eyebrow' => 'Documentation workflow',
            'heading' => 'From rough notes to a clearer draft',
            'intro' => 'A simple way to keep continuity when notes are first captured in shorthand between visits.',
            'steps' => [
                [
                    'title' => 'Rough note fragments',
                    'body' => '"neck better, sleep worse, right shoulder still catches, check driving next time"',
                ],
                [
                    'title' => 'Practiq helps organize the writing',
                    'body' => 'The practitioner supplies the facts. Practiq helps with the wording and structure.',
                ],
                [
                    'title' => 'Clearer draft note',
                    'body' => 'A readable draft the practitioner reviews, edits, and saves only when it is correct.',
                ],
            ],
        ],
        'helps' => [
            ['Visit notes that preserve the thread', 'Capture what changed, what stood out, what you did, and what to watch next. For rough first drafts typed between visits, Practiq can help turn practitioner-written fragments into clearer, more coherent, more standardized draft text.'],
            ['AI writing help with clear boundaries', 'The practitioner supplies the facts. Practiq helps with the writing. The practitioner reviews, edits, and decides what belongs in the chart. Practiq must not invent findings, diagnoses, treatments, or patient statements.'],
            ['Intake and consent forms', 'Send forms before the visit. Patients submit securely, and staff review before anything changes in the record.'],
            ['Appointment requests', 'Patients request a time. You confirm it. Your schedule stays yours.'],
            ['Follow-up that stays visible', 'See who may need a reminder or an invitation back, then review the message before it sends.'],
            ['Checkout tracking', 'Record what was charged, what was paid, and what is still open without adding a separate billing workflow.'],
            ['Reports and exports', 'Use straightforward totals and CSV exports for bookkeeping. Not accounting software, just practical reporting for a small clinic.'],
        ],
        'starterHeading' => 'Start with a usable setup, then adjust it to your style',
        'starterCopy' => [
            'Your trial starts with editable defaults: a practitioner, weekday hours, Initial Visit and Follow-up Visit types, and starter fees.',
            'Nothing is locked. The defaults are there so you can evaluate real workflow quickly instead of spending your first hour building scaffolding.',
        ],
        'fit' => ['Solo acupuncturists', 'Small acupuncture clinics', 'TCM practices', 'Five Element practices', 'Cash-based acupuncture practices', 'One-person practices wearing every hat'],
        'not' => 'Practiq is not a hospital EHR, insurance billing clearinghouse, automatic booking engine, full accounting system, or replacement for practitioner judgment.',
        'seoPhrase' => 'acupuncture practice software',
    ],
    'massage-therapy-practice-software' => [
        'title' => 'Massage Therapy Practice Software for Small Clinics — Notes, Intake, Follow-Up | Practiq',
        'description' => 'Massage therapy practice software for independent therapists and small clinics. Keep client notes, intake forms, follow-up, and checkout organized. 30-day free trial.',
        'eyebrow' => 'Massage therapy practice software',
        'h1' => 'Practice software for busy massage therapists.',
        'image' => [
            'src' => '/images/practitioner-pages/massage-therapy.png',
            'alt' => 'Massage therapist working with a client in a professional small clinic setting',
        ],
        'subheadline' => 'Practiq is built for the pace of small massage practices: back-to-back sessions, short gaps between clients, and admin work that can easily spill into your evening.',
        'dailyHeading' => 'When sessions are stacked, notes and follow-up get squeezed',
        'dailyCopy' => [
            'Many therapists only get a few minutes between clients. Notes start as quick fragments about areas worked, pressure tolerance, tissue response, home-care reminders, or what to revisit next time.',
            'Weeks later, that client returns and you need the thread fast. Practiq keeps notes, forms, requests, follow-up, and checkout in one place so you can step back into the session with context instead of guesswork.',
        ],
        'noteWorkflow' => [
            'eyebrow' => 'Documentation workflow',
            'heading' => 'From quick session notes to a clearer record',
            'intro' => 'A practical flow for busy days when the first note is only a rough sketch between clients.',
            'steps' => [
                [
                    'title' => 'Quick session fragments',
                    'body' => '"right shoulder tight, low back better, preferred lighter pressure, check desk setup next time"',
                ],
                [
                    'title' => 'Practiq helps organize the writing',
                    'body' => 'The therapist supplies the facts. Practiq helps shape the wording and structure.',
                ],
                [
                    'title' => 'Clearer draft note',
                    'body' => 'A readable draft the therapist reviews, edits, and saves only when it reflects the session.',
                ],
            ],
        ],
        'helps' => [
            ['Client notes that stay useful', 'Capture what mattered in plain language, even when you start with rough fragments between sessions. Practiq can help turn therapist-written fragments into clearer, more coherent, more standardized draft text.'],
            ['AI writing help with clear boundaries', 'The practitioner supplies the facts. Practiq helps with the writing. The practitioner reviews, edits, and decides what belongs in the chart. Practiq must not invent findings, diagnoses, treatments, or client statements. AI assists writing; it does not replace practitioner judgment.'],
            ['Intake and consent forms', 'Send forms before the appointment so clients can submit in advance and staff can review before the session starts.'],
            ['Appointment requests', 'Clients request a time. You confirm it. Your schedule stays yours.'],
            ['Follow-up', 'See who may need a reminder or an invitation back, then review the message before it sends.'],
            ['Checkout tracking', 'Record session charges, payments, and open balances without adding extra billing complexity.'],
            ['Simple reports and exports', 'Use straightforward totals and CSV exports for bookkeeping. Not accounting software, just practical reporting.'],
        ],
        'starterHeading' => 'Start quickly, then make it your own',
        'starterCopy' => [
            'Your trial begins with editable starter defaults: a practitioner, weekday hours, Initial Visit and Follow-up Visit types, and starter fees.',
            'Nothing is locked. The point is to test your real workflow without spending the first hour building setup from scratch.',
        ],
        'fit' => ['Solo massage therapists', 'Small massage studios', 'Therapeutic massage practices', 'Bodywork practitioners', 'Wellness clinics with massage therapy', 'Practitioners who want less paperwork after sessions'],
        'not' => 'Practiq is not a spa booking marketplace, full accounting system, automatic booking engine, or replacement for professional judgment.',
        'seoPhrase' => 'massage therapy practice software',
    ],
    'chiropractic-practice-software' => [
        'title' => 'Chiropractic Practice Software for Small Clinics — Notes, Intake, Follow-Up | Practiq',
        'description' => 'Chiropractic practice software for independent chiropractors and small clinics. Keep visit notes, intake forms, follow-up, and checkout organized. 30-day free trial.',
        'eyebrow' => 'Chiropractic practice software',
        'h1' => 'Practice software for busy chiropractors.',
        'image' => [
            'src' => '/images/practitioner-pages/chiropractic.png',
            'alt' => 'Chiropractor working with a patient in a small clinic setting',
        ],
        'subheadline' => 'Practiq is built for small chiropractic clinics where visits may be short, schedules are full, and documentation can easily spill past clinic hours.',
        'dailyHeading' => 'When visits are back-to-back, the chart can fall behind',
        'dailyCopy' => [
            'In a busy chiropractic day, charting often starts as shorthand between visits. You may only have time for quick fragments: why the person came in, what changed since last time, relevant observations, care provided, response, and what to recheck next.',
            'That becomes harder when the case extends over multiple visits, and it may become more burdensome when insurance or review expectations are involved. Requirements vary, and the record often needs to show progression clearly over time, not just what happened today.',
        ],
        'noteWorkflow' => [
            'eyebrow' => 'Documentation workflow',
            'heading' => 'From quick chart notes to a clearer chiropractic record',
            'intro' => 'A practical flow for days when the first chart note is typed quickly between appointments.',
            'steps' => [
                [
                    'title' => 'Quick chart fragments',
                    'body' => '"neck improved, low back stiff after travel, rotation still limited, tolerated care, recheck desk posture next visit"',
                ],
                [
                    'title' => 'Practiq helps organize the writing',
                    'body' => 'The chiropractor supplies the facts. Practiq helps shape the wording and structure.',
                ],
                [
                    'title' => 'Clearer draft note',
                    'body' => 'A readable draft the chiropractor reviews, edits, and saves only when it reflects the visit.',
                ],
            ],
        ],
        'helps' => [
            ['Visit notes that hold up over time', 'Keep the thread across visits, including what changed, what stood out, what care was provided, and what should be rechecked next. Practiq can help turn rough practitioner-written fragments into clearer, more coherent, more standardized draft text.'],
            ['Simple notes or structured SOAP when needed', 'Use concise notes for routine visits and switch to more structured SOAP or insurance-style documentation when the case or reviewer expectations call for it.'],
            ['AI writing help with clear boundaries', 'The practitioner supplies the facts. Practiq helps with the writing. The practitioner reviews, edits, and decides what belongs in the chart. Practiq must not invent findings, diagnoses, treatments, or patient statements. AI assists writing; it does not replace practitioner judgment.'],
            ['Intake and consent forms', 'Send forms before the first visit so information is available before the appointment starts.'],
            ['Appointment requests', 'Patients request a time. Your clinic confirms it. You stay in control of the schedule.'],
            ['Follow-up and checkout tracking', 'Keep reminders and invite-back workflow visible, and record charges and payments without adding unnecessary billing overhead.'],
            ['Simple reports and exports', 'Use straightforward totals and CSV exports for bookkeeping. This may help day-to-day operations, but it is not accounting software and does not guarantee billing or compliance outcomes.'],
        ],
        'starterHeading' => 'Start with a usable setup, then adjust it to your clinic',
        'starterCopy' => [
            'Your trial starts with editable defaults: a practitioner, weekday hours, Initial Visit and Follow-up Visit types, and starter fees.',
            'Everything is adjustable. The defaults are there so you can test your real workflow quickly instead of spending the first session configuring from scratch.',
        ],
        'fit' => ['Solo chiropractors', 'Small chiropractic offices', 'Cash-based or mixed small practices', 'Clinics that need clearer patient follow-up', 'Practices with limited admin staff'],
        'not' => 'Practiq is not a full insurance billing clearinghouse, hospital EHR, automatic booking engine, or full accounting system.',
        'seoPhrase' => 'chiropractic practice software',
    ],
    'physiotherapy-practice-software' => [
        'title' => 'Physiotherapy Practice Software for Small Clinics — Notes, Intake, Follow-Up | Practiq',
        'description' => 'Physiotherapy practice software for independent physiotherapists and small clinics. Keep progress notes, intake forms, follow-up, and checkout organized. 30-day free trial.',
        'eyebrow' => 'Physiotherapy practice software',
        'h1' => 'Practice software for busy physiotherapists.',
        'image' => [
            'src' => '/images/practitioner-pages/physiotherapy.png',
            'alt' => 'Physiotherapist guiding a patient through a supported exercise in a small clinic setting',
        ],
        'subheadline' => 'Practiq is built for small physiotherapy clinics where progress is tracked across visits, schedules stay full, and documentation can quickly pile up between appointments.',
        'dailyHeading' => 'Progress can get lost when notes are rushed',
        'dailyCopy' => [
            'Physiotherapy often depends on what changed over time, not only what happened today. If notes are unclear, important details may slip: exercise progress, functional changes, pain or activity tolerance, reassessment points, and follow-up plans.',
            'Small clinics often balance treatment, notes, intake, scheduling, follow-up, checkout, and reporting without a large admin team. Some insurance or referral-related documentation may need more structure, and requirements vary.',
        ],
        'noteWorkflow' => [
            'eyebrow' => 'Documentation workflow',
            'heading' => 'From quick progress notes to a clearer physiotherapy record',
            'intro' => 'A practical flow for days when progress notes start as rough fragments between visits.',
            'steps' => [
                [
                    'title' => 'Quick progress fragments',
                    'body' => '"stairs easier, knee sore after longer walk, added balance work, review home exercises next visit"',
                ],
                [
                    'title' => 'Practiq helps organize the writing',
                    'body' => 'The physiotherapist supplies the facts. Practiq helps shape the wording and structure.',
                ],
                [
                    'title' => 'Clearer draft note',
                    'body' => 'A readable draft the physiotherapist reviews, edits, and saves only when it reflects the visit.',
                ],
            ],
        ],
        'helps' => [
            ['Progress notes that stay useful', 'Keep the thread across visits, including functional changes, exercise updates, care provided, response, and what to recheck next. Practiq can help turn rough practitioner-written fragments into clearer, more coherent, more standardized draft text.'],
            ['Simple notes or structured SOAP when needed', 'Use concise notes for routine visits and switch to more structured SOAP or insurance-style documentation when needed by the case, referral context, or reviewer expectations.'],
            ['AI writing help with clear boundaries', 'The practitioner supplies the facts. Practiq helps with the writing. The practitioner reviews, edits, and decides what belongs in the chart. Practiq must not invent findings, diagnoses, treatments, exercises, outcomes, or patient statements. AI assists writing; it does not replace practitioner judgment.'],
            ['Intake and consent forms', 'Send forms before the first visit so key information is available before treatment begins.'],
            ['Appointment requests', 'Patients request a time. You or your staff confirm the schedule.'],
            ['Follow-up and checkout tracking', 'Keep reminders and return-planning visible, and record charges and payments without adding unnecessary billing overhead.'],
            ['Simple reports and exports', 'Use straightforward totals and CSV exports for bookkeeping. This may help day-to-day operations, but it is not accounting software and does not guarantee billing or compliance outcomes.'],
        ],
        'starterHeading' => 'Start with a usable setup, then adapt it to your clinic',
        'starterCopy' => [
            'Your trial starts with editable defaults: a practitioner, weekday hours, Initial Visit and Follow-up Visit types, and starter fees.',
            'Everything is adjustable. The defaults are there so you can test your real workflow quickly instead of spending the first session building setup from scratch.',
        ],
        'fit' => ['Solo physiotherapists', 'Small rehab clinics', 'Private physiotherapy practices', 'Practices with repeated patient visits', 'Clinics that need clearer progress tracking and follow-up'],
        'not' => 'Practiq is not a hospital EHR, insurance billing clearinghouse, automatic booking engine, or full accounting system.',
        'seoPhrase' => 'physiotherapy practice software',
    ],
    'wellness-practice-software' => [
        'title' => 'Wellness Practice Software for Small Clinics — Notes, Intake, Follow-Up | Practiq',
        'description' => 'Wellness practice software for independent practitioners and small clinics. Keep notes, intake forms, follow-up, and checkout organized without overcomplicating care. 30-day free trial.',
        'eyebrow' => 'Wellness practice software',
        'h1' => 'Practice software for busy wellness practitioners.',
        'image' => [
            'src' => '/images/practitioner-pages/wellness.png',
            'alt' => 'Wellness practitioner meeting with a client in a calm consultation setting',
        ],
        'subheadline' => 'Practiq is built for small wellness practices where styles vary, schedules stay full, and the admin side can pile up fast without much team support.',
        'dailyHeading' => 'Wellness work is flexible, but continuity still matters',
        'dailyCopy' => [
            'Wellness visits can look different from one practice to another. One practitioner tracks lifestyle context and next-step habits. Another focuses on session response, goals, and follow-up plans. The software needs to stay flexible enough for both.',
            'When clients return weeks later, the thread of care should still be easy to recover. Without clear notes, small details can get lost, and follow-up plans may fade. Practiq helps keep the workflow organized without making the practice feel like a hospital.',
        ],
        'noteWorkflow' => [
            'eyebrow' => 'Documentation workflow',
            'heading' => 'From quick wellness notes to a clearer record',
            'intro' => 'A practical flow for busy days when your first draft is a few quick lines between sessions.',
            'steps' => [
                [
                    'title' => 'Quick visit fragments',
                    'body' => '"stress higher this week, sleep uneven, liked breathing practice, follow up on evening routine next time"',
                ],
                [
                    'title' => 'Practiq helps organize the writing',
                    'body' => 'The practitioner supplies the facts. Practiq helps shape the wording and structure.',
                ],
                [
                    'title' => 'Clearer draft note',
                    'body' => 'A readable draft the practitioner reviews, edits, and saves only when it reflects the visit.',
                ],
            ],
        ],
        'helps' => [
            ['Wellness notes that stay useful', 'Capture goals, context, care provided, response, follow-up ideas, and next steps in plain language. Practiq can help turn rough practitioner-written fragments into clearer, more coherent, more standardized draft text.'],
            ['Flexible structure for different practice styles', 'Use simple narrative notes for straightforward visits, and use more structured documentation when needed. Requirements vary by setting and workflow.'],
            ['AI writing help with clear boundaries', 'The practitioner supplies the facts. Practiq helps with the writing. The practitioner reviews, edits, and decides what belongs in the record. Practiq must not invent findings, diagnoses, treatments, client statements, outcomes, or promises. AI assists writing; it does not replace practitioner judgment.'],
            ['Intake and consent forms', 'Send forms before the first visit so practitioners have context before the session starts.'],
            ['Appointment requests', 'Clients request a time. You confirm it. Your schedule stays yours.'],
            ['Follow-up and checkout tracking', 'Keep check-ins and return reminders visible, and record charges and payments without adding unnecessary billing overhead.'],
            ['Simple reports and exports', 'Use straightforward totals and CSV exports for bookkeeping. This may help day-to-day operations, but it is not accounting software and does not guarantee legal, billing, or compliance outcomes.'],
        ],
        'starterHeading' => 'Start with useful defaults, then adapt to your style',
        'starterCopy' => [
            'Your trial starts with editable defaults: a practitioner, weekday hours, Initial Visit and Follow-up Visit types, and starter fees.',
            'Everything is adjustable. The defaults are there so you can test your real workflow quickly instead of building setup from scratch first.',
        ],
        'fit' => ['Solo wellness practitioners', 'Small wellness clinics', 'Integrative health practices', 'Bodywork and holistic care providers', 'Practices built on long-term client relationships'],
        'not' => 'Practiq is not a hospital EHR, automatic booking marketplace, full accounting system, or replacement for professional judgment.',
        'seoPhrase' => 'wellness practice software',
    ],
];

foreach ($seoLandingPages as $slug => $page) {
    Route::get("/{$slug}", fn () => view('seo.practitioner-page', ['page' => $page]))->name("seo.{$slug}");
}

Route::view('/blog/small-clinic-visit-notes', 'blog.small-clinic-visit-notes')
    ->name('blog.small-clinic-visit-notes');
Route::view('/blog/acupuncture-visit-note-examples', 'blog.acupuncture-visit-note-examples')
    ->name('blog.acupuncture-visit-note-examples');
Route::view('/blog/soap-notes-vs-simple-visit-notes', 'blog.soap-notes-vs-simple-visit-notes')
    ->name('blog.soap-notes-vs-simple-visit-notes');
Route::view('/blog/what-to-include-in-a-visit-note', 'blog.what-to-include-in-a-visit-note')
    ->name('blog.what-to-include-in-a-visit-note');
Route::view('/blog', 'blog.index')->name('blog.index');

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
        '/blog' => ['changefreq' => 'weekly', 'priority' => '0.7'],
        '/blog/small-clinic-visit-notes' => ['changefreq' => 'monthly', 'priority' => '0.6'],
        '/blog/acupuncture-visit-note-examples' => ['changefreq' => 'monthly', 'priority' => '0.6'],
        '/blog/soap-notes-vs-simple-visit-notes' => ['changefreq' => 'monthly', 'priority' => '0.6'],
        '/blog/what-to-include-in-a-visit-note' => ['changefreq' => 'monthly', 'priority' => '0.6'],
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
Route::view('/legal/terms', 'legal.terms')->name('legal.terms');
Route::view('/legal/privacy', 'legal.privacy')->name('legal.privacy');
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
