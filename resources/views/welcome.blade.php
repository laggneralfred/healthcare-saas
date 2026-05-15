<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.google-tag')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practiq — Practice Management Software for Small Clinics</title>
    <meta name="description" content="Practiq keeps visit notes, intake forms, appointment requests, follow-up, and checkout organized for small acupuncture, massage, chiropractic, physiotherapy, and wellness practices. 30-day free trial.">
    @php
        $structuredData = [
            '@context' => 'https://schema.org',
            '@graph' => [
                ['@type' => 'Organization', '@id' => 'https://practiqapp.com/#organization', 'name' => 'Practiq', 'url' => 'https://practiqapp.com/'],
                ['@type' => 'WebSite', '@id' => 'https://practiqapp.com/#website', 'name' => 'Practiq', 'url' => 'https://practiqapp.com/', 'publisher' => ['@id' => 'https://practiqapp.com/#organization']],
                ['@type' => 'SoftwareApplication', '@id' => 'https://practiqapp.com/#softwareapplication', 'name' => 'Practiq', 'url' => 'https://practiqapp.com/', 'description' => 'Practice management software for small clinics: visit notes, intake forms, appointment requests, follow-up, checkout tracking, and simple reports.', 'applicationCategory' => 'BusinessApplication', 'operatingSystem' => 'Web', 'audience' => ['@type' => 'Audience', 'audienceType' => 'solo practitioners, small clinics'], 'publisher' => ['@id' => 'https://practiqapp.com/#organization']],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    <script src="https://cdn.tailwindcss.com"></script>
    @include('partials.public-fonts')
</head>
<body class="bg-[#fbfaf6] text-slate-900 antialiased">
@php
    $trialUrl        = '/register';
    $demoUrl         = 'https://demo.practiqapp.com/demo-login';
    $overviewVideoUrl = '/videos/practiq-product-demo.mp4';
@endphp

{{-- NAV --}}
<header class="sticky top-0 z-30 border-b border-teal-900/10 bg-[#fbfaf6]/95 backdrop-blur">
    <nav class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8" aria-label="Primary navigation">
        <a href="/" class="flex items-center gap-3" aria-label="Practiq home">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-700 text-lg font-bold text-white shadow-sm shadow-teal-900/10">P</span>
            <span class="text-xl font-bold tracking-tight text-slate-950" style="font-family:'DM Sans',sans-serif">Practiq</span>
        </a>
        <div class="hidden items-center gap-5 text-sm font-medium text-slate-600 lg:flex">
            <a href="#problems" class="transition hover:text-teal-800">How it helps</a>
            <a href="#pricing" class="transition hover:text-teal-800">Pricing</a>
            <a href="/blog" class="transition hover:text-teal-800">Blog</a>
            <a href="#faq" class="transition hover:text-teal-800">FAQ</a>
            <a href="/user-instructions" class="transition hover:text-teal-800">User guide</a>
            <a href="/admin/login" class="transition hover:text-teal-800">Login</a>
        </div>
        <div class="flex items-center gap-2">
            <a href="#overview-video" class="hidden rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:border-teal-700/30 hover:text-teal-800 sm:inline-flex">Watch overview</a>
            <a href="{{ $trialUrl }}" class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800">Start free trial</a>
        </div>
    </nav>
</header>

<main>

{{-- HERO --}}
<section class="border-b border-slate-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 pb-16 pt-16 sm:px-6 lg:px-8 lg:pb-24 lg:pt-24">
        <div class="max-w-3xl">
            <p class="mb-5 inline-flex w-fit rounded-full border border-teal-800/15 bg-teal-50 px-4 py-2 text-sm font-medium text-teal-800" style="font-family:'DM Sans',sans-serif">
                For independent practitioners and small clinics
            </p>
            <h1 class="text-[36px] font-medium leading-[1.2] text-slate-950 sm:text-[48px]">
                Your day is for patients.<br>Not paperwork.
            </h1>
            <p class="mt-6 max-w-2xl text-[17px] leading-[1.75] text-slate-500">
                Practiq keeps the everyday work of a small clinic organized — visit notes, intake forms, appointment requests, follow-up, and checkout — so you can finish the day without staying late.
            </p>
            <p class="mt-3 text-[14px] leading-relaxed text-slate-400">
                Built for solo providers and small clinics. Not an oversized EHR.
            </p>
            <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                <a href="{{ $trialUrl }}" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-7 py-3.5 text-[15px] font-semibold text-white shadow-sm transition hover:bg-teal-800">
                    Start free trial
                </a>
                <a href="#overview-video" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-7 py-3.5 text-[15px] font-medium text-slate-600 shadow-sm transition hover:border-teal-700/40 hover:text-teal-800">
                    Watch overview
                </a>
            </div>
            <p class="mt-4 text-[12px] text-slate-400">30-day free trial. No credit card required.</p>
        </div>

        <div class="mx-auto mt-10 w-full max-w-5xl overflow-hidden rounded-2xl border border-slate-200 bg-[#fbfaf6] shadow-lg shadow-slate-900/5">
            <img
                src="/images/practitioner-pages/Collage.png"
                alt="Practitioner collage showing acupuncture, massage therapy, chiropractic, physiotherapy, and wellness care"
                class="h-auto w-full object-cover"
                width="2400"
                height="1200"
                loading="eager"
                decoding="async"
                fetchpriority="high"
            >
        </div>

        {{-- Feature preview grid --}}
        <div class="mt-14 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:max-w-3xl">
            @foreach([
                ['Visit notes',          'Simple or SOAP mode per visit'],
                ['Intake & consent',     'Sent, submitted, reviewed'],
                ['Appointment requests', 'Patient requests, you confirm'],
                ['Follow-up',            'See who needs attention'],
                ['Checkout tracking',    'Charges, payments, balance'],
                ['Reports & exports',    'Revenue totals and CSVs'],
            ] as [$title, $desc])
            <div class="rounded-lg border border-slate-200 bg-[#fbfaf6] px-4 py-3">
                <p class="text-[13px] font-semibold text-slate-900" style="font-family:'DM Sans',sans-serif">{{ $title }}</p>
                <p class="mt-0.5 text-[12px] leading-snug text-slate-500">{{ $desc }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- VIDEO --}}
<section id="overview-video" class="border-b border-slate-200 bg-[#fbfaf6]">
    <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl">
            <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-teal-700" style="font-family:'DM Sans',sans-serif">Daily workflow overview</p>
            <h2 class="mt-3 text-[24px] font-medium leading-snug text-slate-950 sm:text-[28px]">See the daily workflow in two minutes</h2>
            <p class="mt-4 text-[15px] leading-[1.75] text-slate-600">A quick overview of how Practiq supports setup, appointment requests, documentation, follow-up, and financial exports for small practices.</p>
        </div>
        <div class="mx-auto mt-8 max-w-5xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-teal-950/8">
            <video class="block w-full" controls preload="metadata" aria-label="Practiq product overview video">
                <source src="{{ $overviewVideoUrl }}" type="video/mp4">
                Your browser does not support the overview video.
            </video>
        </div>
        <p class="mt-5 text-center text-[13px] text-slate-500">
            Prefer to click through yourself?
            <a href="{{ $demoUrl }}" class="font-semibold text-teal-800 transition hover:text-teal-900">demo.practiqapp.com</a>
        </p>
    </div>
</section>

{{-- PROBLEMS --}}
<section id="problems" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
    <div class="max-w-2xl">
        <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-teal-700" style="font-family:'DM Sans',sans-serif">The real work of a small practice</p>
        <h2 class="mt-3 text-[26px] font-medium leading-snug text-slate-950 sm:text-[30px]">Your practice shouldn't live in five different places.</h2>
    </div>
    <div class="mt-10 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @foreach([
            ['Notes pile up after a full schedule',      'A full day means notes get pushed to the end. By then, details blur. Practiq is built for notes written close to the visit — short, clear, and done.'],
            ['Intake forms end up scattered',             'Forms in emails, on paper, in attachments that never got filed. Practiq keeps intake and consent together — sent, submitted, and reviewed in one place.'],
            ['Follow-up slips through the cracks',        'The patient you meant to check on Thursday. The invitation back you drafted but never sent. Practiq shows you who needs attention.'],
            ['You need control of the schedule',          'Patients can request a time. You confirm it. Your schedule stays yours.'],
            ['Front-desk capacity is real',               'When one person is doing everything, less gets lost if everything is connected. Notes, forms, requests, and checkout in the same place.'],
            ['You need to see where your practice stands', 'Track what was seen, what was paid, and export what your bookkeeper needs. Not accounting software — just what a small practice actually uses.'],
        ] as [$title, $body])
        <article class="rounded-xl border border-slate-200 bg-white p-6">
            <h3 class="text-[15px] font-medium text-slate-950">{{ $title }}</h3>
            <p class="mt-2.5 text-[14px] leading-[1.7] text-slate-600">{{ $body }}</p>
        </article>
        @endforeach
    </div>
</section>

{{-- FEATURES CHIP GRID --}}
<section class="border-y border-slate-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
        <div class="max-w-2xl">
            <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-teal-700" style="font-family:'DM Sans',sans-serif">What Practiq covers</p>
            <h2 class="mt-3 text-[26px] font-medium leading-snug text-slate-950 sm:text-[30px]">Notes, forms, follow-up, and checkout. One place.</h2>
        </div>
        <div class="mt-10 grid gap-3 md:grid-cols-2 lg:grid-cols-3">
            @foreach([
                ['Visit notes',                         'Write what happened, in your words. SOAP mode for visits that need structure. AI drafting is available but never automatic — you review everything before it enters the chart.'],
                ['Intake and consent forms',             'Send before the visit. The patient submits it; you review it before anything changes. No chasing paper on the day.'],
                ['Appointment requests',                 'Patients request a time. You confirm it. You stay in control of the schedule.'],
                ['Follow-up and communication',          'See who may be slipping away. Invite back with a message you review before it sends.'],
                ['Checkout and payment tracking',        'Close out each visit cleanly. Record what was charged, what was paid, and what\'s outstanding.'],
                ['Practice statistics and exports',      'Revenue totals and bookkeeping CSV exports. Not accounting software — just the numbers a small practice actually uses.'],
            ] as [$title, $body])
            <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-[#fbfaf6] px-5 py-4">
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-teal-700" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <p class="text-[14px] font-semibold text-slate-900" style="font-family:'DM Sans',sans-serif">{{ $title }}</p>
                    <p class="mt-1 text-[13px] leading-relaxed text-slate-600">{{ $body }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- DISCIPLINES --}}
<section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
    <div class="max-w-2xl">
        <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-teal-700" style="font-family:'DM Sans',sans-serif">Built for small healthcare practices</p>
        <h2 class="mt-3 text-[26px] font-medium leading-snug text-slate-950 sm:text-[30px]">For your kind of practice</h2>
        <p class="mt-4 text-[15px] leading-[1.75] text-slate-600">Practiq is built for small clinics, but the day looks a little different in every discipline. Choose the page closest to your work.</p>
    </div>
    <div class="mt-10 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @foreach([
            ['/practice-software-for-acupuncturists', 'Acupuncture', 'Preserve the thread of care between visits with notes, intake, follow-up, and checkout in one place.'],
            ['/massage-therapy-practice-software', 'Massage therapy', 'Keep session notes, client response, follow-up, and checkout from getting scattered across the day.'],
            ['/chiropractic-practice-software', 'Chiropractic', 'Keep short visits, progress notes, SOAP-style documentation when needed, and follow-up organized.'],
            ['/physiotherapy-practice-software', 'Physiotherapy', 'Track progress over time, home exercises, reassessment notes, and follow-up plans clearly.'],
            ['/wellness-practice-software', 'Wellness', 'Use flexible notes, forms, and follow-up for varied wellness practices without making the clinic feel like a hospital.'],
        ] as [$href, $title, $body])
        <a href="{{ $href }}" class="group flex flex-col rounded-xl border border-slate-200 bg-white px-6 py-5 transition hover:border-teal-700/30 hover:shadow-sm">
            <div class="flex items-center justify-between">
                <h3 class="text-[15px] font-medium text-slate-950 group-hover:text-teal-800">{{ $title }}</h3>
                <svg class="h-4 w-4 text-slate-300 transition group-hover:text-teal-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
            <p class="mt-2 text-[13px] leading-relaxed text-slate-500">{{ $body }}</p>
        </a>
        @endforeach
    </div>
    <div class="mt-6 flex flex-wrap gap-2">
        @foreach(['Solo practitioners', 'Small multi-practitioner teams', 'Limited-staff clinics', 'Cash-based practices'] as $item)
        <span class="rounded-full border border-slate-200 bg-white px-4 py-1.5 text-[12px] font-medium text-slate-600">{{ $item }}</span>
        @endforeach
    </div>
</section>

{{-- STARTER SETUP --}}
<section class="border-y border-slate-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
        <div class="max-w-2xl">
            <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-teal-700" style="font-family:'DM Sans',sans-serif">Getting started</p>
            <h2 class="mt-3 text-[26px] font-medium leading-snug text-slate-950 sm:text-[30px]">Start with useful defaults, not an empty screen.</h2>
        </div>
        <div class="mt-10 grid gap-4 lg:grid-cols-3">
            @foreach([
                ['A calmer first day',          'When you start a trial, Practiq sets up editable defaults — a practitioner, weekday working hours, Initial Visit and Follow-up Visit types, and starter fees. You\'re not staring at a blank system.'],
                ['Change everything later',     'Every default is editable. The starter settings are just there so you can evaluate the software without first having to configure the software.'],
                ['Built to stay out of the way', 'No forced online booking. No full accounting claims. No replacement for clinical judgment. Practiq stays focused on the daily clinic workflow.'],
            ] as [$title, $body])
            <article class="rounded-xl border border-slate-200 bg-[#fbfaf6] p-6">
                <h3 class="text-[15px] font-medium text-slate-950">{{ $title }}</h3>
                <p class="mt-2.5 text-[14px] leading-[1.7] text-slate-600">{{ $body }}</p>
            </article>
            @endforeach
        </div>
        <div class="mt-6 rounded-xl border border-slate-200 bg-white p-6">
            <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-teal-700" style="font-family:'DM Sans',sans-serif">Setup checklist covers</p>
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach(['Practice profile', 'Practitioner setup', 'Appointment types', 'Working hours', 'Public website links', 'HIPAA / BAA acknowledgement', 'AI disclaimer acknowledgement'] as $item)
                <span class="rounded-full border border-slate-200 bg-[#fbfaf6] px-3 py-1.5 text-[12px] font-medium text-slate-600">{{ $item }}</span>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- PRICING --}}
<section id="pricing" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
    <div class="max-w-2xl">
        <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-teal-700" style="font-family:'DM Sans',sans-serif">Pricing</p>
        <h2 class="mt-3 text-[26px] font-medium leading-snug text-slate-950 sm:text-[30px]">Clear pricing. No surprises.</h2>
        <p class="mt-4 text-[15px] leading-[1.75] text-slate-600">Start with the plan that fits your practice today. 30-day free trial, no credit card required. Stripe handles subscription billing.</p>
    </div>
    <div class="mt-10 grid gap-5 lg:grid-cols-3">
        @foreach([
            ['Solo',             '$49',  'month', 'For one practitioner. All core features included.'],
            ['Clinic',           '$99',  'month', 'For small clinics with up to 5 practitioners.'],
            ['Growing Practice', '$199', 'month', 'For growing or multi-practitioner practices.'],
        ] as [$plan, $price, $period, $description])
        <article class="rounded-xl border border-slate-200 bg-white p-8">
            <h3 class="text-[15px] font-medium text-slate-700" style="font-family:'DM Sans',sans-serif">{{ $plan }}</h3>
            <p class="mt-4 price-num text-[42px] font-medium leading-none text-slate-950">{{ $price }}<span class="text-[16px] font-normal text-slate-400" style="font-family:'DM Sans',sans-serif">/{{ $period }}</span></p>
            <p class="mt-4 text-[13px] leading-relaxed text-slate-600">{{ $description }}</p>
            <a href="{{ $trialUrl }}" class="mt-7 inline-flex w-full items-center justify-center rounded-lg bg-slate-900 px-5 py-3 text-[14px] font-semibold text-white transition hover:bg-teal-800">Start free trial</a>
        </article>
        @endforeach
    </div>
    <div class="mt-5 rounded-xl border border-slate-200 bg-[#fbfaf6] px-6 py-5">
        <p class="text-[13px] font-semibold text-slate-900" style="font-family:'DM Sans',sans-serif">Herb &amp; Product Inventory — $19/month add-on</p>
        <p class="mt-1 text-[13px] text-slate-500">Add inventory tracking only if products are part of your practice workflow.</p>
    </div>
</section>

{{-- WHAT PRACTIQ IS NOT --}}
<section class="border-y border-slate-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
        <div class="max-w-2xl">
            <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-teal-700" style="font-family:'DM Sans',sans-serif">Scope</p>
            <h2 class="mt-3 text-[26px] font-medium leading-snug text-slate-950 sm:text-[30px]">Focused on your day. Not everything in healthcare.</h2>
            <p class="mt-4 text-[15px] leading-[1.75] text-slate-600">Practiq does one thing: keeps the day-to-day work of a small practice organized. It is not trying to do everything.</p>
        </div>
        <div class="mt-8 grid gap-2 md:grid-cols-2 lg:max-w-3xl">
            @foreach([
                'Not a full hospital EHR',
                'Not an insurance billing clearinghouse',
                'Not an automatic booking engine',
                'Not a full accounting system',
                'Not a replacement for professional judgment',
                'Not a legal or compliance guarantee',
            ] as $item)
            <div class="flex items-center gap-3 rounded-lg border border-slate-200 bg-[#fbfaf6] px-4 py-3">
                <span class="text-slate-300">—</span>
                <span class="text-[13px] text-slate-700">{{ $item }}</span>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- FAQ --}}
<section id="faq" class="mx-auto max-w-4xl px-4 py-20 sm:px-6 lg:px-8">
    <div class="max-w-2xl">
        <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-teal-700" style="font-family:'DM Sans',sans-serif">FAQ</p>
        <h2 class="mt-3 text-[26px] font-medium leading-snug text-slate-950 sm:text-[30px]">Straight answers.</h2>
    </div>
    <div class="mt-8 space-y-2">
        @foreach([
            ['Does Practiq support simple notes and SOAP notes?',
             'Yes. Write in natural language for routine visits, or switch to SOAP mode when the record needs more structure. You choose per visit.'],
            ['Can patients book themselves automatically?',
             'No. Patients request a time. You confirm it. You stay in control of the schedule.'],
            ['Does Practiq include online forms?',
             'Yes. You send the link, the patient submits it securely, you review it before anything changes in the chart.'],
            ['Does Practiq use AI?',
             'AI features are optional drafting tools — never automatic, never diagnostic. You review everything before it enters the record. AI acknowledgement is required before first use.'],
            ['Can I put Practiq links on my website?',
             'Yes. Stable public links for new patient requests, existing patient access, and appointment booking requests.'],
            ['Is setup guided?',
             'Yes. A setup checklist shows what\'s ready and what still needs attention. Editable starter defaults are already in place when you begin your trial.'],
            ['Does Practiq include financial reporting?',
             'Basic collected revenue summaries and CSV exports for bookkeeping. Not accounting software — just the numbers a small practice actually needs.'],
        ] as [$question, $answer])
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <button class="acc-trigger flex w-full items-center gap-4 px-6 py-5 text-left transition-colors hover:bg-slate-50" aria-expanded="false">
                <span class="flex-1 text-[14px] font-medium text-slate-900" style="font-family:'DM Sans',sans-serif">{{ $question }}</span>
                <svg class="acc-chevron h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div class="acc-body">
                <div class="border-t border-slate-100 px-6 py-4">
                    <p class="text-[14px] leading-relaxed text-slate-600">{{ $answer }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</section>

{{-- FINAL CTA --}}
<section class="mx-auto max-w-4xl px-4 pb-20 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-6 rounded-xl border border-slate-200 bg-slate-50 px-8 py-10 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-[22px] font-medium text-slate-950">Try it with your real workflow.</h2>
            <p class="mt-2 text-[14px] leading-relaxed text-slate-600">30-day free trial. Starter settings are already in place. No credit card required.</p>
        </div>
        <div class="flex shrink-0 flex-col gap-2 sm:flex-row">
            <a href="{{ $trialUrl }}" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-6 py-3.5 text-[14px] font-semibold text-white transition hover:bg-teal-800">Start free trial</a>
            <a href="{{ $demoUrl }}" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-6 py-3.5 text-[14px] font-medium text-slate-600 transition hover:border-teal-700/30 hover:text-teal-800">View demo</a>
        </div>
    </div>
</section>

</main>

<footer class="border-t border-slate-200 bg-white px-4 py-10">
    <div class="mx-auto max-w-7xl text-center text-sm text-slate-400">
        <p>&copy; 2026 Practiq. Built for independent practitioners and small clinics.</p>
        <div class="mt-3 flex flex-wrap justify-center gap-x-5 gap-y-1 text-[12px]">
            <a href="/blog" class="text-teal-700 transition hover:text-teal-900">Blog</a>
            <a href="/blog/small-clinic-visit-notes" class="text-teal-700 transition hover:text-teal-900">Keeping up with visit notes</a>
            <a href="/blog/acupuncture-visit-note-examples" class="text-teal-700 transition hover:text-teal-900">Acupuncture note examples</a>
            <a href="/legal/privacy" class="hover:text-slate-600 transition">Privacy</a>
            <a href="/legal/terms" class="hover:text-slate-600 transition">Terms</a>
        </div>
    </div>
</footer>

<script>
document.querySelectorAll('.acc-trigger').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var body = this.parentElement.querySelector('.acc-body');
        var chevron = this.querySelector('.acc-chevron');
        var isOpen = body.classList.contains('open');
        body.classList.toggle('open', !isOpen);
        chevron.classList.toggle('open', !isOpen);
        this.setAttribute('aria-expanded', String(!isOpen));
    });
});
</script>
</body>
</html>
