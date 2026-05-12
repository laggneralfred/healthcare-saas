<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.google-tag')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practiq | Small Practice Healthcare Software for Notes, Forms, Follow-Up, and Checkout Tracking</title>
    <meta name="description" content="Practiq helps small healthcare practices manage visit notes, intake forms, appointment requests, follow-up, checkout tracking, and simple reports without a complicated EHR.">
    @php
        $structuredData = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => 'https://practiqapp.com/#organization',
                    'name' => 'Practiq',
                    'url' => 'https://practiqapp.com/',
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => 'https://practiqapp.com/#website',
                    'name' => 'Practiq',
                    'url' => 'https://practiqapp.com/',
                    'publisher' => ['@id' => 'https://practiqapp.com/#organization'],
                ],
                [
                    '@type' => 'SoftwareApplication',
                    '@id' => 'https://practiqapp.com/#softwareapplication',
                    'name' => 'Practiq',
                    'url' => 'https://practiqapp.com/',
                    'description' => 'Small practice healthcare software for visit notes, intake forms, appointment requests, follow-up, checkout tracking, and simple reports.',
                    'applicationCategory' => 'BusinessApplication',
                    'operatingSystem' => 'Web',
                    'audience' => [
                        '@type' => 'Audience',
                        'audienceType' => 'small healthcare practices, solo providers, small clinics',
                    ],
                    'publisher' => ['@id' => 'https://practiqapp.com/#organization'],
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet" />
    <style>
        body { font-family: 'Instrument Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#fbfaf6] text-slate-900 antialiased">
    @php
        $trialUrl = '/register';
        $demoUrl = 'https://demo.practiqapp.com/demo-login';
        $overviewVideoUrl = '/videos/practiq-product-demo.mp4';
        $navLinks = [
            ['How Practiq Helps', '#problems'],
            ['Workflow', '#workflow'],
            ['Pricing', '#pricing'],
            ['FAQ', '#faq'],
            ['User Instructions', '/user-instructions'],
        ];
    @endphp

    <header class="sticky top-0 z-30 border-b border-teal-900/10 bg-[#fbfaf6]/95 backdrop-blur">
        <nav class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8" aria-label="Primary navigation">
            <a href="/" class="flex items-center gap-3" aria-label="Practiq home">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-700 text-lg font-bold text-white shadow-sm shadow-teal-900/10">P</span>
                <span class="text-xl font-bold tracking-tight text-slate-950">Practiq</span>
            </a>
            <div class="hidden items-center gap-5 text-sm font-semibold text-slate-600 lg:flex">
                @foreach($navLinks as [$label, $href])
                    <a href="{{ $href }}" class="transition hover:text-teal-800">{{ $label }}</a>
                @endforeach
                <a href="/admin/login" class="transition hover:text-teal-800">Login</a>
            </div>
            <div class="flex items-center gap-2">
                <a href="#overview-video" class="hidden rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-teal-700/30 hover:text-teal-800 sm:inline-flex">Watch Overview</a>
                <a href="{{ $trialUrl }}" class="rounded-full bg-teal-700 px-5 py-2.5 text-sm font-bold text-white shadow-sm shadow-teal-900/10 transition hover:bg-teal-800">Start Free Trial</a>
            </div>
        </nav>
    </header>

    <main>
        <section class="border-b border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 pb-16 pt-16 sm:px-6 lg:px-8 lg:pb-24 lg:pt-24">
                <div class="max-w-4xl">
                    <p class="mb-5 inline-flex w-fit rounded-full border border-teal-800/15 bg-teal-50 px-4 py-2 text-sm font-semibold text-teal-900">
                        Small practice healthcare software for independent providers and small clinics
                    </p>
                    <h1 class="text-4xl font-extrabold leading-[1.05] tracking-tight text-slate-950 sm:text-6xl">
                        Simple practice software for busy healthcare providers.
                    </h1>
                    <p class="mt-6 max-w-3xl text-lg leading-8 text-slate-600 sm:text-xl">
                        Practiq helps small practices manage visit notes, intake forms, appointment requests, follow-up, checkout tracking, and simple reports — without adding more admin work to your day.
                    </p>
                    <p class="mt-4 max-w-3xl text-base leading-7 text-slate-500">
                        Built for solo providers and small clinics that need practical tools, not another oversized system.
                    </p>
                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ $trialUrl }}" class="inline-flex items-center justify-center rounded-xl bg-teal-700 px-7 py-4 text-base font-bold text-white shadow-lg shadow-teal-900/10 transition hover:bg-teal-800">
                            Start Free Trial
                        </a>
                        <a href="#overview-video" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-7 py-4 text-base font-bold text-slate-800 shadow-sm transition hover:border-teal-700/40 hover:text-teal-800">
                            Watch Overview
                        </a>
                    </div>
                </div>

                <div class="mt-14 rounded-2xl border border-slate-200 bg-[#f8faf7] p-5 shadow-xl shadow-teal-950/10">
                    <div class="grid gap-4 lg:grid-cols-[0.9fr_1.1fr]">
                        <div class="rounded-xl bg-teal-950 p-6 text-white">
                            <p class="text-xs font-bold uppercase tracking-wide text-teal-200">Today</p>
                            <h2 class="mt-3 text-2xl font-bold">Built for busy providers, not software administrators.</h2>
                            <p class="mt-4 leading-7 text-teal-50/90">A practical daily view for the work that keeps a clinic moving: visits, requests, notes, forms, follow-up, and checkout tracking.</p>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach([
                                ['Visit note software', 'Simple notes or SOAP mode'],
                                ['Appointment request software', 'Patient preferences with staff confirmation'],
                                ['Intake forms for small clinics', 'Sent, submitted, reviewed'],
                                ['Patient follow-up tools', 'Invite-back drafts and history'],
                                ['Checkout and payment tracking', 'Record charges, payments, and balance status'],
                                ['Practice statistics and exports', 'Collected totals and bookkeeping CSVs'],
                            ] as [$title, $body])
                                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <p class="font-bold text-slate-950">{{ $title }}</p>
                                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ $body }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="overview-video" class="border-b border-slate-200 bg-[#fbfaf6]">
            <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-4xl text-center">
                    <p class="text-sm font-bold uppercase tracking-wide text-teal-800">Daily Workflow Overview</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">See the daily workflow in two minutes</h2>
                    <p class="mt-5 text-lg leading-8 text-slate-600">Watch a quick overview of how Practiq supports setup, appointment requests, documentation, follow-up, and financial exports for small practices.</p>
                </div>
                <div class="mx-auto mt-10 max-w-5xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl shadow-teal-950/10">
                    <video
                        class="block w-full"
                        controls
                        preload="metadata"
                        aria-label="Practiq product overview video showing daily clinic workflow"
                    >
                        <source src="{{ $overviewVideoUrl }}" type="video/mp4">
                        Your browser does not support the overview video.
                    </video>
                </div>
                <div class="mt-5 text-center text-sm text-slate-500">
                    Prefer to click through the product yourself? The live demo is still available at
                    <a href="{{ $demoUrl }}" class="font-semibold text-teal-800 transition hover:text-teal-900"> demo.practiqapp.com</a>.
                </div>
            </div>
        </section>

        <section id="problems" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="text-sm font-bold uppercase tracking-wide text-teal-800">Built for the reality of a busy small practice</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">The real work of a small practice should not live in five different places.</h2>
            </div>
            <div class="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach([
                    ['Notes can pile up after a full day', 'Write clear visit notes without fighting a heavy screen. Use simple notes or SOAP mode based on the visit and your workflow.'],
                    ['Intake and consent forms get scattered', 'Keep intake and consent forms organized in one clinic workflow, from sending to review.'],
                    ['Follow-up slips through the cracks', 'See who may need a reminder or invitation back, with communication history in one place.'],
                    ['Appointment flow needs control', 'Patients can request appointments while staff stays in control of final scheduling.'],
                    ['Front-desk time is limited', 'Keep requests, forms, notes, and checkout tracking connected so less gets lost between steps.'],
                    ['Small clinics need basic business visibility', 'Track what was seen, what was paid, and export practical CSVs for bookkeeping.'],
                ] as [$title, $body])
                    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-slate-950">{{ $title }}</h3>
                        <p class="mt-3 leading-7 text-slate-600">{{ $body }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section id="workflow" class="border-y border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
                <div class="max-w-3xl">
                    <p class="text-sm font-bold uppercase tracking-wide text-teal-800">What Practiq Helps You Keep Under Control</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Keep notes, forms, follow-up, and checkout in one practical workflow.</h2>
                </div>
                <div class="mt-10 grid gap-6 lg:grid-cols-2">
                    @foreach([
                        ['Visit notes', 'Use visit note software that supports simple note mode and structured SOAP mode. Optional AI support is practitioner-controlled and review-first.'],
                        ['Intake and consent forms', 'Collect what you need before the visit and review submissions in staff workflow before making changes.'],
                        ['Appointment requests', 'Use appointment request software that keeps staff in control. Patients request care, and staff confirms appointment time.'],
                        ['Follow-up and communication', 'Use patient follow-up tools to spot patients who may need reminders or an invitation back.'],
                        ['Checkout and payment tracking', 'Record service charges and payments in a simple way so the day closes out cleanly.'],
                        ['Practice statistics and financial exports', 'Review collected totals and export financial summary, checkout payments, and line-item CSVs for bookkeeping.'],
                    ] as [$title, $body])
                        <article class="rounded-2xl border border-slate-200 bg-[#fbfaf6] p-7">
                            <h3 class="text-xl font-bold text-slate-950">{{ $title }}</h3>
                            <p class="mt-4 leading-7 text-slate-600">{{ $body }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="grid gap-8 lg:grid-cols-[0.9fr_1.1fr]">
                <div>
                    <p class="text-sm font-bold uppercase tracking-wide text-teal-800">Who Practiq Is For</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Practice software for healthcare providers in small clinics.</h2>
                    <p class="mt-5 text-lg leading-8 text-slate-600">Practiq is for acupuncture, massage therapy, chiropractic, physiotherapy, and wellness practices that want a calmer daily workflow with fewer moving parts.</p>
                </div>
                <div class="flex flex-wrap gap-2 self-start">
                    @foreach(['Acupuncture practice software', 'Massage therapy practice software', 'Chiropractic practice software', 'Physiotherapy practice software', 'Wellness practice software', 'Solo practices', 'Small multi-practitioner teams', 'Limited-staff clinics'] as $item)
                        <span class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm">{{ $item }}</span>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="border-y border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
                <div class="max-w-3xl">
                    <p class="text-sm font-bold uppercase tracking-wide text-teal-800">Built for small healthcare practices</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Practiq is designed for busy providers who need to keep notes, forms, follow-up, checkout tracking, and patient flow organized without adding more admin work.</h2>
                </div>
                <div class="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    @foreach([
                        ['/practice-software-for-acupuncturists', 'Acupuncture practices', 'Treatment notes, intake forms, appointment requests, follow-up, and checkout tracking for small acupuncture clinics.'],
                        ['/massage-therapy-practice-software', 'Massage therapy practices', 'Client notes, intake and consent forms, appointment requests, follow-up, and simple practice reports.'],
                        ['/chiropractic-practice-software', 'Chiropractic practices', 'Visit notes, intake forms, patient follow-up, checkout tracking, and basic practice visibility.'],
                        ['/physiotherapy-practice-software', 'Physiotherapy practices', 'Progress notes, intake forms, appointment requests, follow-up, checkout tracking, and simple reports.'],
                        ['/wellness-practice-software', 'Wellness practices', 'Client notes, forms, appointment requests, follow-up, checkout tracking, and simple admin support.'],
                    ] as [$href, $title, $body])
                        <a href="{{ $href }}" class="group rounded-2xl border border-slate-200 bg-[#fbfaf6] p-6 shadow-sm transition hover:border-teal-700/25 hover:shadow-md">
                            <h3 class="text-lg font-bold text-slate-950 transition group-hover:text-teal-800">{{ $title }}</h3>
                            <p class="mt-3 leading-7 text-slate-600">{{ $body }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="border-y border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
                <div class="grid gap-6 lg:grid-cols-3">
                    @foreach([
                        ['A calmer first day', 'When you start a trial, Practiq creates editable starter defaults so you are not staring at an empty system. You can change everything any time.'],
                        ['Starter defaults for guided trial setup', 'Starter setup includes an initial practitioner, weekday working hours, Initial Visit and Follow-up Visit appointment types, and starter fees.'],
                        ['Designed to stay practical', 'No forced online booking, no full accounting claims, and no replacement for clinical judgment. Practiq stays focused on daily clinic workflow.'],
                    ] as [$title, $body])
                        <article class="rounded-2xl border border-slate-200 bg-[#fbfaf6] p-7">
                            <h2 class="text-xl font-bold text-slate-950">{{ $title }}</h2>
                            <p class="mt-4 leading-7 text-slate-600">{{ $body }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="grid gap-8 lg:grid-cols-[1fr_0.9fr]">
                <div>
                    <p class="text-sm font-bold uppercase tracking-wide text-teal-800">Setup and Readiness</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Start with editable defaults, not an empty system.</h2>
                    <p class="mt-5 text-lg leading-8 text-slate-600">Practiq includes a setup checklist so you can quickly see what is configured: practice profile, practitioners, treatment types, compatibility, working hours, website links, and required acknowledgements.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                    <p class="text-sm font-bold uppercase tracking-wide text-teal-800">Checklist focus</p>
                    <div class="mt-5 flex flex-wrap gap-2">
                        @foreach(['Practice profile and slug', 'Practitioner setup', 'Appointment types', 'Working hours', 'Public website links', 'HIPAA/BAA acknowledgement', 'AI disclaimer acknowledgement'] as $item)
                            <span class="rounded-full border border-slate-200 bg-[#fbfaf6] px-4 py-2 text-sm font-semibold text-slate-700">{{ $item }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section id="pricing" class="border-y border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
                <div class="max-w-3xl">
                    <p class="text-sm font-bold uppercase tracking-wide text-teal-800">Simple enough for a small clinic budget</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Clear monthly pricing for small practices.</h2>
                    <p class="mt-5 text-lg leading-8 text-slate-600">Start with the plan that matches your practice today. No credit card is required to start a trial. Stripe is used for Practiq subscription billing.</p>
                </div>
                <div class="mt-10 grid gap-6 lg:grid-cols-3">
                    @foreach([
                        ['Solo', '$49', 'For one practitioner.'],
                        ['Clinic', '$99', 'For small clinics with up to 5 practitioners.'],
                        ['Growing Practice', '$199', 'For growing or multi-practitioner practices.'],
                    ] as [$plan, $price, $description])
                        <article class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                            <h3 class="text-2xl font-bold text-slate-950">{{ $plan }}</h3>
                            <p class="mt-5 text-4xl font-extrabold tracking-tight text-slate-950">{{ $price }}<span class="text-base font-semibold text-slate-500">/month</span></p>
                            <p class="mt-4 text-slate-600">{{ $description }}</p>
                            <a href="{{ $trialUrl }}" class="mt-7 inline-flex w-full justify-center rounded-xl bg-teal-700 px-5 py-3 font-bold text-white transition hover:bg-teal-800">Start Free Trial</a>
                        </article>
                    @endforeach
                </div>
                <div class="mt-6 rounded-2xl border border-slate-200 bg-[#fbfaf6] p-6 text-slate-700">
                    <p class="font-bold text-slate-950">Herb & Product Inventory add-on - $19/month.</p>
                    <p class="mt-2">Add inventory only when products are part of your practice workflow.</p>
                </div>
            </div>
        </section>

        <section class="border-y border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
                <div class="max-w-3xl">
                    <p class="text-sm font-bold uppercase tracking-wide text-teal-800">What Practiq Is Not</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Focused on daily workflow, not everything in healthcare.</h2>
                    <p class="mt-5 text-lg leading-8 text-slate-600">Practiq is intentionally focused on the daily workflow of a small practice.</p>
                </div>
                <div class="mt-8 grid gap-4 md:grid-cols-2">
                    @foreach([
                        'Not a full hospital EHR',
                        'Not an insurance billing clearinghouse',
                        'Not an automatic booking engine',
                        'Not a replacement for professional judgment',
                        'Not a full accounting system',
                        'Not a legal or compliance guarantee',
                    ] as $item)
                        <div class="rounded-xl border border-slate-200 bg-[#fbfaf6] px-5 py-4 text-slate-700">{{ $item }}</div>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="faq" class="mx-auto max-w-5xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="text-sm font-bold uppercase tracking-wide text-teal-800">FAQ</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Straight answers for practitioners.</h2>
            </div>
            <div class="mt-10 divide-y divide-slate-200 rounded-2xl border border-slate-200 bg-white shadow-sm">
                @foreach([
                    ['Does Practiq support simple notes and SOAP notes?', 'Yes. Simple Visit Note Mode supports natural clinical writing. SOAP / Insurance Mode supports structured documentation when your practice needs it.'],
                    ['Can patients book themselves automatically?', 'No. Patients can request appointments. Staff reviews context and explicitly creates the appointment.'],
                    ['Does Practiq include online forms?', 'Yes. Staff can send forms, patients can submit them securely, and staff reviews submissions before records are changed.'],
                    ['Does Practiq use AI?', 'AI features are optional support tools for drafts, summaries, translations, and documentation checks. Practitioners remain responsible for reviewing all output, and AI acknowledgement is required before first use. AI is not diagnosis and not autonomous clinical decision-making.'],
                    ['Can I put Practiq links on my website?', 'Yes. Practices can use stable public links for new patient requests, existing patient access, and appointment requests.'],
                    ['Is setup guided?', 'Yes. The setup checklist shows what is ready and what still needs attention before patient-facing workflows are advertised.'],
                    ['Does Practiq include financial reporting?', 'Practiq includes basic collected revenue summaries and CSV exports for bookkeeping. It does not claim to be full accounting software.'],
                ] as [$question, $answer])
                    <details class="group p-6">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-bold text-slate-950">
                            {{ $question }}
                            <span class="text-teal-700 transition group-open:rotate-45" aria-hidden="true">+</span>
                        </summary>
                        <p class="mt-4 leading-7 text-slate-600">{{ $answer }}</p>
                    </details>
                @endforeach
            </div>
        </section>

        <section class="px-4 pb-20 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-6xl rounded-2xl bg-teal-800 px-6 py-16 text-white shadow-2xl shadow-teal-950/15 sm:px-12">
                <div class="mx-auto max-w-3xl text-center">
                    <h2 class="text-3xl font-bold tracking-tight sm:text-5xl">Try Practiq with starter settings already in place.</h2>
                    <p class="mt-5 text-lg leading-8 text-teal-50/90">Start a free trial and see whether it fits your practice workflow.</p>
                </div>
                <div class="mt-9 flex flex-col justify-center gap-3 sm:flex-row">
                    <a href="{{ $trialUrl }}" class="inline-flex items-center justify-center rounded-xl bg-white px-7 py-4 font-bold text-teal-900 transition hover:bg-teal-50">Start Free Trial</a>
                    <a href="#overview-video" class="inline-flex items-center justify-center rounded-xl border border-white/40 px-7 py-4 font-bold text-white transition hover:bg-white/10">Watch Overview</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-500">
        <p>&copy; 2026 Practiq. Built for independent practitioners and small clinics.</p>
    </footer>
</body>
</html>
