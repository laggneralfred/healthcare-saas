<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practiq - Documentation-first practice software for small clinics</title>
    <meta name="description" content="Practiq helps independent practitioners manage notes, forms, appointment requests, follow-up, setup, and billing readiness from one care-first workflow.">
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
        $navLinks = [
            ['Problems', '#problems'],
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
                <a href="{{ $demoUrl }}" class="hidden rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-teal-700/30 hover:text-teal-800 sm:inline-flex">View Demo</a>
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
                        Practiq brings visit notes, patient requests, forms, follow-up, setup, billing readiness, and legal acknowledgements into a workflow built for the day-to-day reality of small health practices.
                    </p>
                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ $trialUrl }}" class="inline-flex items-center justify-center rounded-xl bg-teal-700 px-7 py-4 text-base font-bold text-white shadow-lg shadow-teal-900/10 transition hover:bg-teal-800">
                            Start Free Trial
                        </a>
                        <a href="{{ $demoUrl }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-7 py-4 text-base font-bold text-slate-800 shadow-sm transition hover:border-teal-700/40 hover:text-teal-800">
                            View Demo
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

        <section id="problems" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="text-sm font-bold uppercase tracking-wide text-teal-800">Problems Practiq Solves</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">The real work of a small practice should not live in five different places.</h2>
            </div>
            <div class="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach([
                    ['Clinical notes are too slow', 'Start with natural visit notes when that fits the visit, or use structured SOAP / Insurance Mode when documentation requirements call for it.'],
                    ['Workflows are scattered', 'Scheduling context, forms, requests, notes, checkout, follow-up, and setup readiness stay connected around the patient and practice.'],
                    ['Follow-up is inconsistent', 'The Follow-Up Center surfaces patients who may need attention, with staff-reviewed invite-back drafts and communication history.'],
                    ['Intake and requests get messy', 'Public website links, online forms, existing-patient access, and appointment requests give patients clearer entry points without exposing private records.'],
                    ['Setup can stall a trial', 'A practice setup checklist shows what is ready and what still needs attention before using public links or patient workflows.'],
                    ['Many systems start with billing', 'Practiq starts with care and documentation, while still supporting Stripe readiness, checkout, and plan management when the practice is ready.'],
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
                        ['Documentation-first visits', 'Practiq supports Simple Visit Note Mode for natural clinical writing and SOAP / Insurance Mode for structured documentation. Controlled AI assistance can help draft, organize, summarize, translate, or check documentation, but practitioners review the result before use.'],
                        ['Scheduling and appointment requests', 'Patients can submit appointment requests with treatment and practitioner preferences. Staff sees practitioner schedule context, working hours, time blocks, and deterministic suggested openings while still choosing and creating the final appointment.'],
                        ['Forms, intake, and patient access', 'Practices can place public website links for new patient requests, existing patient access, and appointment requests. Online forms are sent, submitted, reviewed, and converted by staff instead of automatically changing patient records.'],
                        ['Follow-up and communication', 'The Follow-Up Center helps staff identify patients who may need attention. Invite Back workflows support drafts, translations, explicit sending, opt-out checks, and communication history.'],
                        ['Setup clarity for small practices', 'Trial practices see a checklist for practice profile, practitioners, treatment types, compatibility, working hours, public links, HIPAA/BAA acknowledgement, and AI disclaimer acknowledgement.'],
                        ['Billing and readiness without taking over', 'Stripe billing readiness, subscription plan configuration, checkout, and legal acceptance tracking are present, but the product stays centered on clinical work and patient relationships.'],
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

        <section id="pricing" class="border-y border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
                <div class="max-w-3xl">
                    <p class="text-sm font-bold uppercase tracking-wide text-teal-800">Pricing</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Clear monthly pricing for small practices.</h2>
                    <p class="mt-5 text-lg leading-8 text-slate-600">Start with the plan that matches your practice today. No credit card is required to start a trial.</p>
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
                    ['Does Practiq use AI?', 'AI features are optional support tools for drafts, summaries, translations, and documentation checks. Practitioners remain responsible for reviewing all output.'],
                    ['Can I put Practiq links on my website?', 'Yes. Practices can use stable public links for new patient requests, existing patient access, and appointment requests.'],
                    ['Is setup guided?', 'Yes. The setup checklist shows what is ready and what still needs attention before patient-facing workflows are advertised.'],
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
                    <p class="mt-5 text-lg leading-8 text-teal-50/90">Start a free trial or open the demo. Practiq is built to make the day clearer before it asks you to do more.</p>
                </div>
                <div class="mt-9 flex flex-col justify-center gap-3 sm:flex-row">
                    <a href="{{ $trialUrl }}" class="inline-flex items-center justify-center rounded-xl bg-white px-7 py-4 font-bold text-teal-900 transition hover:bg-teal-50">Start Free Trial</a>
                    <a href="{{ $demoUrl }}" class="inline-flex items-center justify-center rounded-xl border border-white/40 px-7 py-4 font-bold text-white transition hover:bg-white/10">View Demo</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-500">
        <p>&copy; 2026 Practiq. Built for independent practitioners and small clinics.</p>
    </footer>
</body>
</html>
