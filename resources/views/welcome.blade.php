<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practiq &mdash; A smarter practice system for independent healthcare</title>
    <meta name="description" content="Practiq helps independent healthcare practitioners manage visits, notes, reminders, billing, patient communication, and data import with calm workflows and practical AI support.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet" />
    <style>
        body { font-family: 'Instrument Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#fbfaf6] text-slate-900 antialiased">
    @php
        $earlyAccessUrl = '/register';
        $demoUrl = 'https://demo.practiqapp.com/demo-login';
    @endphp

    <header class="border-b border-teal-900/10 bg-[#fbfaf6]/90 backdrop-blur">
        <nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-5 sm:px-6 lg:px-8" aria-label="Primary navigation">
            <a href="/" class="flex items-center gap-3" aria-label="Practiq home">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-700 text-lg font-bold text-white shadow-sm shadow-teal-900/10">P</span>
                <span class="text-xl font-bold tracking-tight text-slate-900">Practiq</span>
            </a>
            <div class="flex items-center gap-3 text-sm font-semibold">
                <a href="/admin/login" class="hidden text-slate-600 transition hover:text-teal-800 sm:inline">Log in</a>
                <a href="{{ $demoUrl }}" class="rounded-full bg-teal-700 px-5 py-2.5 text-white shadow-sm shadow-teal-900/10 transition hover:bg-teal-800">Explore Demo</a>
            </div>
        </nav>
    </header>

    <main>
        <section class="mx-auto grid max-w-7xl gap-12 px-4 pb-20 pt-16 sm:px-6 lg:grid-cols-[1.02fr_0.98fr] lg:px-8 lg:pb-28 lg:pt-24">
            <div class="flex flex-col justify-center">
                <p class="mb-5 inline-flex w-fit rounded-full border border-teal-800/15 bg-white px-4 py-2 text-sm font-semibold text-teal-800 shadow-sm">
                    Less admin. Better notes. Smarter workflows.
                </p>
                <h1 class="max-w-4xl text-4xl font-extrabold leading-[1.05] tracking-tight text-slate-950 sm:text-6xl">
                    A smarter practice system for independent healthcare.
                </h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-600 sm:text-xl">
                    Practiq helps acupuncture, chiropractic, massage, physiotherapy, and wellness practices manage visits, notes, reminders, billing support, patient communication, and data import &mdash; with practical AI support where it actually helps.
                </p>
                <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ $demoUrl }}" class="inline-flex items-center justify-center rounded-xl bg-teal-700 px-7 py-4 text-base font-bold text-white shadow-lg shadow-teal-900/10 transition hover:bg-teal-800">
                        Explore Demo
                    </a>
                    <a href="{{ $earlyAccessUrl }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-7 py-4 text-base font-bold text-slate-800 shadow-sm transition hover:border-teal-700/40 hover:text-teal-800">
                        Join Early Access
                    </a>
                </div>
                <p class="mt-4 max-w-xl text-sm leading-6 text-slate-500">Explore Practiq with sample data &mdash; no credit card required. Join early access when you&rsquo;re ready to help shape the product.</p>
            </div>

            <div class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-2xl shadow-teal-950/10">
                <div class="rounded-[1.5rem] border border-slate-200 bg-[#f8faf7] p-5">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Visit Note</p>
                            <p class="text-lg font-bold text-slate-950">Emma Nakamura &middot; Apr 25, 2026</p>
                        </div>
                        <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-800">Simple Visit Note Mode</span>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="space-y-5 font-mono text-sm leading-7 text-slate-700">
                            <div>
                                <p class="font-semibold text-slate-950">Chief Complaint:</p>
                                <p>Neck and shoulder tension after long work days.</p>
                            </div>
                            <div>
                                <p class="font-semibold text-slate-950">Treatment Notes:</p>
                                <p>Patient tolerated treatment well. Breath settled during session. Soft tissue tone improved by end of visit.</p>
                            </div>
                            <div>
                                <p class="font-semibold text-slate-950">Points / Techniques:</p>
                                <p>GB20, LI4, local shoulder points. Gentle cupping to upper trapezius.</p>
                            </div>
                            <div>
                                <p class="font-semibold text-slate-950">Plan / Follow-up:</p>
                                <p>Return next week. Home stretching reviewed.</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold text-slate-600">
                        <span class="rounded-full border border-slate-200 bg-white px-3 py-1">Visit note</span>
                        <span class="rounded-full border border-slate-200 bg-white px-3 py-1">AI assist optional</span>
                        <span class="rounded-full border border-slate-200 bg-white px-3 py-1">Ready for checkout</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="border-y border-slate-200 bg-white">
            <div class="mx-auto max-w-5xl px-4 py-20 sm:px-6 lg:px-8">
                <div class="max-w-3xl">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Built for the way small practices actually work.</h2>
                    <div class="mt-6 space-y-4 text-lg leading-8 text-slate-600">
                        <p>Most practice software feels like it was built for billing departments, hospitals, or generic appointment booking.</p>
                        <p>Practiq is different.</p>
                        <p>It combines the essential tools of a practice management system with a calmer clinical workflow and practical AI support.</p>
                    </div>
                </div>
                <div class="mt-10 grid gap-3 sm:grid-cols-2">
                    @foreach([
                        'Natural or structured visit notes',
                        'AI-assisted documentation',
                        'Structured reminders',
                        'Generated patient emails',
                        'Quick AI-supported import',
                        'Intake and consent forms',
                        'Checkout and billing support',
                        'Flexible workflows with or without appointments',
                    ] as $item)
                        <div class="rounded-2xl border border-slate-200 bg-[#fbfaf6] px-5 py-4 font-semibold text-slate-800">
                            {{ $item }}
                        </div>
                    @endforeach
                </div>
                <p class="mt-8 max-w-3xl text-lg font-semibold leading-8 text-slate-900">
                    The result is a system that feels lighter, smarter, and more useful from the first day.
                </p>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="text-sm font-bold uppercase tracking-wide text-teal-800">Practice platform</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Everything a small practice needs, without the noise.</h2>
                <p class="mt-5 text-lg leading-8 text-slate-600">Practiq brings daily clinical, administrative, and communication work into one calmer system.</p>
            </div>
            <div class="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach([
                    ['Clinical Notes', 'Write natural visit notes or use structured SOAP documentation when needed. AI can help organize, improve, or draft from your existing note - without taking control away from you.'],
                    ['Smart Reminders', 'Create structured reminders for patients, follow-ups, appointments, and care plans. Keep communication consistent without manually writing the same messages again and again.'],
                    ['AI-Supported Emails', 'Generate clear, professional patient emails from your workflow. Use AI to help write reminders, follow-ups, instructions, or administrative messages, then review before sending.'],
                    ['Quick AI-Supported Import', 'Move from spreadsheets or older systems faster. Practiq helps map and clean imported patient data so setup does not become a painful manual project.'],
                    ['Intake & Consent', 'Send digital forms before the first visit and keep patient information connected to the clinical record.'],
                    ['Checkout & Billing Support', 'Manage checkout, payments, receipts, service fees, and billing-related workflows without turning Practiq into a bloated hospital EHR.'],
                    ['Patient Records', 'Keep demographics, visit history, forms, communication, and notes organized in one place.'],
                    ['Scheduling', 'Manage appointments and daily workflows with a clear practitioner-friendly calendar.'],
                    ['Data Ownership', 'Import your data. Export your data. Your practice records stay portable.'],
                ] as [$title, $body])
                    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-slate-950">{{ $title }}</h3>
                        <p class="mt-3 leading-7 text-slate-600">{{ $body }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="bg-teal-950 py-20 text-white">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl">
                    <p class="text-sm font-bold uppercase tracking-wide text-teal-200">Why Practiq feels different</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Designed around the practitioner&rsquo;s workflow.</h2>
                </div>
                <div class="mt-10 overflow-hidden rounded-3xl border border-white/10 bg-white/5">
                    <div class="grid gap-3 border-b border-white/10 bg-white/10 p-5 text-sm font-bold uppercase tracking-wide text-teal-100 md:grid-cols-2">
                        <p>Typical practice software</p>
                        <p>Practiq</p>
                    </div>
                    @foreach([
                        ['Built around rigid forms', 'Built around the practitioner&rsquo;s workflow'],
                        ['Notes feel like data entry', 'Notes can feel like writing'],
                        ['AI bolted on as a gimmick', 'AI supports real daily tasks'],
                        ['Import is manual and frustrating', 'AI-supported import helps you get started faster'],
                        ['Reminders are generic', 'Structured reminders fit clinical workflows'],
                        ['Too much EHR noise', 'Calm, focused interface'],
                        ['Expensive or overbuilt', 'Designed for solo and small practices'],
                    ] as [$typical, $practiq])
                        <div class="grid gap-3 border-b border-white/10 p-5 last:border-b-0 md:grid-cols-2">
                            <p class="text-slate-300">{{ $typical }}</p>
                            <p class="font-semibold text-white">{{ $practiq }}</p>
                        </div>
                    @endforeach
                </div>
                <p class="mt-8 max-w-3xl text-lg font-semibold leading-8 text-teal-50">
                    Practiq is not trying to be the biggest system. It is trying to be the one you actually enjoy using.
                </p>
            </div>
        </section>

        <section class="border-y border-slate-200 bg-white">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 py-20 sm:px-6 lg:grid-cols-[0.82fr_1.18fr] lg:px-8">
                <div>
                    <p class="text-sm font-bold uppercase tracking-wide text-teal-800">Founder perspective</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Built by someone who knows the treatment room.</h2>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-[#fbfaf6] p-8 shadow-sm">
                    <p class="text-xl font-bold leading-8 text-slate-950">Practiq is built by someone who understands both the treatment room and the codebase.</p>
                    <div class="mt-6 space-y-4 text-lg leading-8 text-slate-600">
                        <p>Practiq is built by a former practicing acupuncturist and longtime software developer.</p>
                        <p>After years of seeing how clinical software often gets in the way of the practitioner&rsquo;s thinking, I wanted to create something calmer: a system that supports notes, scheduling, reminders, communication, intake, billing support, and daily practice workflow without turning every visit into data entry.</p>
                        <p class="font-semibold text-slate-900">Practiq is in active development, and early users have a real voice in what gets built next.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="text-sm font-bold uppercase tracking-wide text-teal-800">Visit documentation</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Better notes, without forcing one workflow.</h2>
                <p class="mt-5 text-lg leading-8 text-slate-600">Choose a simple writing surface for everyday care, or structured SOAP documentation when the practice needs it.</p>
            </div>
            <div class="mt-10 grid gap-6 lg:grid-cols-3">
                <article class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
                    <h3 class="text-2xl font-bold text-slate-950">Simple Visit Note Mode</h3>
                    <p class="mt-4 text-base leading-7 text-slate-600">A clean document-style editor for natural writing, dictation, quick follow-ups, and handwritten note transcription.</p>
                </article>
                <article class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
                    <h3 class="text-2xl font-bold text-slate-950">SOAP / Insurance Mode</h3>
                    <p class="mt-4 text-base leading-7 text-slate-600">Structured Subjective, Objective, Assessment, and Plan fields when insurance or internal policy requires formal documentation.</p>
                </article>
                <article class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
                    <h3 class="text-2xl font-bold text-slate-950">Discipline-aware templates</h3>
                    <p class="mt-4 text-base leading-7 text-slate-600">Acupuncture, chiropractic, massage, physiotherapy, and wellness guidance that helps shape the note without trapping you in a form.</p>
                </article>
            </div>
        </section>

        <section class="border-y border-slate-200 bg-white">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 py-20 sm:px-6 lg:grid-cols-[0.82fr_1.18fr] lg:px-8">
                <div>
                    <p class="text-sm font-bold uppercase tracking-wide text-teal-800">Practical AI</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">AI assists. You decide.</h2>
                </div>
                <div class="space-y-4 text-lg leading-8 text-slate-600">
                    <p>Practiq uses AI for real daily work: drafting and improving visit notes, organizing documentation, generating patient emails, shaping reminders, and helping map imported patient data.</p>
                    <p>AI suggestions do not automatically become final clinical or patient-facing text. You review, edit, and approve what belongs in the record or message.</p>
                    <p class="font-semibold text-slate-900">AI helps draft, organize, import, and communicate. You stay in control.</p>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-5xl px-4 py-20 text-center sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Built for small practices, not hospital systems.</h2>
            <div class="mx-auto mt-6 max-w-3xl space-y-3 text-lg leading-8 text-slate-600">
                <p>Practiq is not a hospital EHR.</p>
                <p>It is not billing-first software.</p>
                <p>It is not designed to bury you in screens.</p>
                <p class="font-semibold text-slate-900">Practiq is a practitioner-first system for managing visits, documenting care, communicating with patients, and keeping your practice organized.</p>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8" id="pricing">
            <div class="max-w-3xl">
                <p class="text-sm font-bold uppercase tracking-wide text-teal-800">Pricing</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Simple early-access pricing.</h2>
                <p class="mt-5 text-lg leading-8 text-slate-600">Practiq is currently available in early access for practitioners who want to help shape the product.</p>
            </div>
            <div class="mt-10 grid gap-6 lg:grid-cols-3">
                @foreach([
                    ['Solo', '$49', 'For one practitioner.'],
                    ['Clinic', '$99', 'For small clinics with up to 5 practitioners.'],
                    ['Growing Practice', '$199', 'For larger or expanding practices.'],
                ] as [$plan, $price, $description])
                    <article class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
                        <h3 class="text-2xl font-bold text-slate-950">{{ $plan }}</h3>
                        <p class="mt-5 text-4xl font-extrabold tracking-tight text-slate-950">{{ $price }}<span class="text-base font-semibold text-slate-500">/month</span></p>
                        <p class="mt-4 text-slate-600">{{ $description }}</p>
                        <a href="{{ $earlyAccessUrl }}" class="mt-7 inline-flex w-full justify-center rounded-xl bg-teal-700 px-5 py-3 font-bold text-white transition hover:bg-teal-800">Join Early Access</a>
                    </article>
                @endforeach
            </div>
            <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 text-slate-700 shadow-sm">
                <p class="font-bold text-slate-950">Optional Herb & Product Inventory &mdash; $19/month.</p>
                <p class="mt-2">Add inventory when products are part of your practice workflow.</p>
                <p class="mt-2 text-sm text-slate-500">Explore the demo first. No credit card required.</p>
            </div>
        </section>

        <section class="border-y border-slate-200 bg-white">
            <div class="mx-auto max-w-4xl px-4 py-20 text-center sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Built with practitioner feedback.</h2>
                <p class="mx-auto mt-6 max-w-3xl text-lg leading-8 text-slate-600">
                    Practiq is in active development with input from practitioners who want calmer, simpler clinical software. Early users help shape the workflow, language, templates, AI tools, and daily practice features.
                </p>
            </div>
        </section>

        <section class="mx-auto max-w-5xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="text-sm font-bold uppercase tracking-wide text-teal-800">FAQ</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Questions practitioners ask.</h2>
            </div>
            <div class="mt-10 divide-y divide-slate-200 rounded-3xl border border-slate-200 bg-white shadow-sm">
                @foreach([
                    ['Is Practiq an EHR?', 'Practiq is clinical practice software for independent practitioners. It is not a hospital EHR and is intentionally lighter than enterprise systems.'],
                    ['What does Practiq help manage?', 'Practiq helps manage patient records, scheduling, visit notes, reminders, patient communication, intake forms, checkout, billing support, import, and export.'],
                    ['What is Simple Visit Note Mode?', 'It is one large document-style writing area for natural treatment notes, dictation, handwritten note transcription, and quick follow-up visits.'],
                    ['Can I use SOAP notes?', 'Yes. SOAP / Insurance Mode provides structured Subjective, Objective, Assessment, and Plan fields when your practice needs them.'],
                    ['Does Practiq use AI?', 'AI Assist is optional and user-controlled. It can help draft, organize, import, and communicate, but you review before anything becomes final.'],
                    ['Does it work without appointments?', 'Yes. You can create visit notes directly from a patient chart for walk-ins, retroactive notes, and handwritten form transcription.'],
                    ['Who is Practiq for?', 'Practiq is built for acupuncture, chiropractic, massage therapy, physiotherapy, wellness, and other small independent practices.'],
                    ['Can I import my existing patient records?', 'Yes. Practiq includes CSV import tools with preview, cleanup workflows, and AI-supported mapping.'],
                    ['What happens to my data if I cancel?', 'Your records belong to you. Practiq includes data export tools so you can retrieve practice data when needed.'],
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
            <div class="mx-auto max-w-6xl rounded-[2rem] bg-teal-800 px-6 py-16 text-white shadow-2xl shadow-teal-950/15 sm:px-12">
                <div class="mx-auto max-w-3xl text-center">
                    <h2 class="text-3xl font-bold tracking-tight sm:text-5xl">Explore the demo. Join early access when it feels right.</h2>
                    <p class="mt-5 text-lg leading-8 text-teal-50/90">
                        You can explore Practiq with sample data before creating an account. If the workflow feels useful, join early access and help shape a calmer practice platform for independent practitioners.
                    </p>
                </div>
                <div class="mx-auto mt-9 grid max-w-3xl gap-3 text-sm font-semibold text-teal-50 sm:grid-cols-2">
                    @foreach([
                        'Try the demo without entering patient data',
                        'See visit notes, scheduling, intake, reminders, and practice workflows',
                        'Join early access when you want to test it with your own practice',
                        'Share feedback that directly influences the roadmap',
                    ] as $item)
                        <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3">{{ $item }}</div>
                    @endforeach
                </div>
                <div class="mt-9 flex flex-col justify-center gap-3 sm:flex-row">
                    <a href="{{ $demoUrl }}" class="inline-flex items-center justify-center rounded-xl bg-white px-7 py-4 font-bold text-teal-900 transition hover:bg-teal-50">Explore Demo</a>
                    <a href="{{ $earlyAccessUrl }}" class="inline-flex items-center justify-center rounded-xl border border-white/40 px-7 py-4 font-bold text-white transition hover:bg-white/10">Join Early Access</a>
                </div>
            </div>
        </section>

    </main>

    <footer class="border-t border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-500">
        <p>&copy; 2026 Practiq. Built for independent practitioners.</p>
    </footer>
</body>
</html>
