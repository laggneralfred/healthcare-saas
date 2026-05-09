<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.google-tag')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practiq - Documentation-first practice software for small clinics</title>
    <meta name="description" content="Practiq helps independent practitioners manage notes, forms, appointment requests, follow-up, setup readiness, legal acknowledgements, and financial exports from one care-first workflow.">
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
                        Practice management for independent clinicians and small teams
                    </p>
                    <h1 class="text-4xl font-extrabold leading-[1.05] tracking-tight text-slate-950 sm:text-6xl">
                        Documentation-first software for practices that put care before billing.
                    </h1>
                    <p class="mt-6 max-w-3xl text-lg leading-8 text-slate-600 sm:text-xl">
                        Practiq brings visit notes, patient requests, forms, follow-up, setup guidance, legal and AI readiness, billing readiness, and financial exports into a workflow built for the day-to-day reality of small health practices.
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
                            <h2 class="mt-3 text-2xl font-bold">Clear work, not scattered work.</h2>
                            <p class="mt-4 leading-7 text-teal-50/90">A front-desk and practitioner view of the day: visits, pending requests, unfinished notes, forms, checkout, and follow-up.</p>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach([
                                ['Visit notes', 'Simple notes or SOAP mode'],
                                ['Appointment requests', 'Patient preferences, staff control'],
                                ['Online forms', 'Sent, submitted, reviewed'],
                                ['Follow-up', 'Invite-back drafts and history'],
                                ['Financial exports', 'Collected revenue summaries and CSVs'],
                                ['Setup readiness', 'Checklist, links, and acknowledgements'],
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
                    <p class="text-sm font-bold uppercase tracking-wide text-teal-800">Product Overview</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">See Practiq in two minutes</h2>
                    <p class="mt-5 text-lg leading-8 text-slate-600">Watch a quick overview of how Practiq supports setup, appointment requests, documentation, follow-up, and financial exports.</p>
                </div>
                <div class="mx-auto mt-10 max-w-5xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl shadow-teal-950/10">
                    <video
                        class="block w-full"
                        controls
                        preload="metadata"
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
                <p class="text-sm font-bold uppercase tracking-wide text-teal-800">How Practiq Helps</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">The real work of a small practice should not live in five different places.</h2>
            </div>
            <div class="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach([
                    ['Clinical notes are too slow', 'Start with natural visit notes when that fits the visit, or use structured SOAP / Insurance Mode when documentation requirements call for it.'],
                    ['Workflows are scattered', 'Scheduling context, forms, requests, notes, checkout, follow-up, and setup readiness stay connected around the patient and practice.'],
                    ['Follow-up is inconsistent', 'The Follow-Up Center surfaces patients who may need attention, with staff-reviewed invite-back drafts and communication history.'],
                    ['Intake and requests get messy', 'Public website links, online forms, existing-patient access, and appointment requests give patients clearer entry points without exposing private records.'],
                    ['Setup can stall a trial', 'A practice setup checklist shows what is ready and what still needs attention before using public links or patient workflows, including practitioners, treatment types, working hours, website links, and acknowledgements.'],
                    ['Reporting often needs another tool', 'Practiq includes collected revenue summaries, payment method totals, practitioner breakdowns, line-item exports, and CSVs that help with bookkeeping without pretending to be full accounting software.'],
                    ['Many systems start with billing', 'Practiq starts with care and documentation, while still supporting Stripe subscription billing readiness, checkout, exports, and plan management when the practice is ready.'],
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
                    <p class="text-sm font-bold uppercase tracking-wide text-teal-800">Care-First Workflow</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Designed around the way practitioners actually move through the day.</h2>
                </div>
                <div class="mt-10 grid gap-6 lg:grid-cols-2">
                    @foreach([
                        ['Documentation-first visits', 'Practiq supports Simple Visit Note Mode for natural clinical writing and SOAP / Insurance Mode for structured documentation. AI assistance stays optional, reviewed, and practitioner-controlled for drafts, summaries, translations, and documentation checks.'],
                        ['Scheduling and appointment requests', 'Patients can submit appointment requests with treatment and practitioner preferences. Staff sees practitioner schedule context, working hours, time blocks, and deterministic suggested openings while still choosing and creating the final appointment. This is request-based scheduling with staff confirmation, not direct self-booking.'],
                        ['Forms, intake, and patient access', 'Practices can place public website links for new patient requests, existing patient access, and appointment requests. Online forms are sent, submitted, reviewed, and converted by staff instead of automatically changing patient records.'],
                        ['Follow-up and communication', 'The Follow-Up Center helps staff identify patients who may need attention. Invite Back workflows support drafts, translations, explicit sending, opt-out checks, and communication history.'],
                        ['Practice statistics and financial exports', 'Collected revenue summaries use payment dates, with payment method totals, practitioner breakdowns, line-type summaries, and dedicated CSV exports for bookkeeping including financial summaries, checkout payments, and line items.'],
                        ['Setup clarity for small practices', 'Trial practices see a checklist for practice profile, practitioners, treatment types, compatibility, working hours, public links, legal acknowledgements, HIPAA/BAA acknowledgement, and AI disclaimer acknowledgement.'],
                        ['Legal, AI, and billing readiness without taking over', 'Terms and Privacy acceptance tracking, HIPAA/BAA acknowledgement, AI disclaimer acknowledgement, Stripe subscription billing readiness tools, and plan configuration are available without implying legal advice or replacing practitioner review.'],
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
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Independent practitioners and small practices doing hands-on care.</h2>
                    <p class="mt-5 text-lg leading-8 text-slate-600">Practiq is built for clinics that need practical clinical workflow, not a giant hospital system or a sales-first CRM.</p>
                </div>
                <div class="flex flex-wrap gap-2 self-start">
                    @foreach(['Acupuncture', 'Massage therapy', 'Chiropractic', 'Physiotherapy', 'Wellness clinics', 'Solo practices', 'Small multi-practitioner teams', 'Cash-pay and mixed billing clinics'] as $item)
                        <span class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm">{{ $item }}</span>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="border-y border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
                <div class="grid gap-6 lg:grid-cols-3">
                    @foreach([
                        ['Practice statistics and financial exports', 'Collected revenue summaries and CSV exports for bookkeeping help practices review payment totals, practitioner activity, and line items without claiming full accounting.'],
                        ['Setup checklist so your clinic knows what to configure first', 'Practitioners, treatment types, working hours, public website links, and readiness acknowledgements are easier to review before a clinic starts advertising workflows publicly.'],
                        ['Legal and AI acknowledgements are tracked for practice readiness', 'Terms and Privacy acceptance, HIPAA/BAA acknowledgement, and AI disclaimer acknowledgement are recorded to support operational readiness. They do not replace legal advice or practitioner judgment.'],
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
                    <p class="text-sm font-bold uppercase tracking-wide text-teal-800">Feedback Loop</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Practitioner feedback stays close to the product.</h2>
                    <p class="mt-5 text-lg leading-8 text-slate-600">Practiq includes a Founding Practitioner Review Program and in-app questionnaire so trial practices can share what felt clear, what slowed setup down, and what would make the first week easier. The goal is simple: shape the product around small-practice reality.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                    <p class="text-sm font-bold uppercase tracking-wide text-teal-800">What feedback covers</p>
                    <div class="mt-5 flex flex-wrap gap-2">
                        @foreach(['Setup clarity', 'Website links', 'Appointment requests', 'Online forms', 'Visit documentation', 'Follow-up workflow', 'Pricing concerns'] as $item)
                            <span class="rounded-full border border-slate-200 bg-[#fbfaf6] px-4 py-2 text-sm font-semibold text-slate-700">{{ $item }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section id="pricing" class="border-y border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
                <div class="max-w-3xl">
                    <p class="text-sm font-bold uppercase tracking-wide text-teal-800">Pricing</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Clear monthly pricing for small practices.</h2>
                    <p class="mt-5 text-lg leading-8 text-slate-600">Start with the plan that matches your practice today. No credit card is required to start a trial. Stripe supports Practiq subscription billing readiness and plan management.</p>
                </div>
                <div class="mt-10 grid gap-6 lg:grid-cols-3">
                    @foreach([
                        ['Solo', '$49', 'For one practitioner.'],
                        ['Clinic', '$99', 'For small clinics with up to 5 practitioners.'],
                        ['Enterprise', '$199', 'For larger or expanding practices.'],
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
                    ['Does Practiq use AI?', 'AI features are optional support tools for drafts, summaries, translations, and documentation checks. Practitioners remain responsible for reviewing all output, and AI acknowledgement is required before first use.'],
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
                    <h2 class="text-3xl font-bold tracking-tight sm:text-5xl">Ready to see Practiq in a real workflow?</h2>
                    <p class="mt-5 text-lg leading-8 text-teal-50/90">Start a free trial or watch the overview again. Practiq is built to make the day clearer before it asks you to do more.</p>
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
