<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practiq — Calm practice software for small clinics</title>
    <meta name="description" content="Practiq helps small clinics stay organized, write notes naturally, and keep patients from slipping through the cracks.">
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
            ['Features', '#features'],
            ['Follow-Up', '#follow-up'],
            ['Pricing', '#pricing'],
            ['FAQ', '#faq'],
            ['User Instructions', '/user-instructions'],
        ];
    @endphp

    <header class="sticky top-0 z-30 border-b border-teal-900/10 bg-[#fbfaf6]/95 backdrop-blur">
        <nav class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8" aria-label="Primary navigation">
            <a href="/" class="flex items-center gap-3" aria-label="Practiq home">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-700 text-lg font-bold text-white shadow-sm shadow-teal-900/10">P</span>
                <span class="text-xl font-bold tracking-tight text-slate-950">Practiq</span>
            </a>
            <div class="hidden items-center gap-5 text-sm font-semibold text-slate-600 lg:flex">
                @foreach($navLinks as [$label, $href])
                    <a href="{{ $href }}" class="transition hover:text-teal-800">{{ $label }}</a>
                @endforeach
                <a href="/admin/login" class="transition hover:text-teal-800">Login</a>
            </div>
            <div class="flex items-center gap-2">
                <a href="/user-instructions" class="hidden rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-teal-700/30 hover:text-teal-800 sm:inline-flex lg:hidden">User Instructions</a>
                <a href="{{ $trialUrl }}" class="rounded-full bg-teal-700 px-5 py-2.5 text-sm font-bold text-white shadow-sm shadow-teal-900/10 transition hover:bg-teal-800">Start Free Trial</a>
            </div>
        </nav>
    </header>

    <main>
        <section class="mx-auto grid max-w-7xl gap-12 px-4 pb-20 pt-16 sm:px-6 lg:grid-cols-[1fr_0.92fr] lg:px-8 lg:pb-28 lg:pt-24">
            <div class="flex flex-col justify-center">
                <p class="mb-5 inline-flex w-fit rounded-full border border-teal-800/15 bg-white px-4 py-2 text-sm font-semibold text-teal-800 shadow-sm">
                    Calm software for small clinics
                </p>
                <h1 class="max-w-4xl text-4xl font-extrabold leading-[1.05] tracking-tight text-slate-950 sm:text-6xl">
                    Keep your practice organized — and your patients from slipping through the cracks.
                </h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-600 sm:text-xl">
                    Practiq is a calm practice companion for small wellness clinics: simple scheduling, natural visit notes, gentle follow-up, language-aware patient communication, and a clear view of what needs attention today.
                </p>
                <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ $trialUrl }}" class="inline-flex items-center justify-center rounded-xl bg-teal-700 px-7 py-4 text-base font-bold text-white shadow-lg shadow-teal-900/10 transition hover:bg-teal-800">
                        Start Free Trial
                    </a>
                    <a href="{{ $demoUrl }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-7 py-4 text-base font-bold text-slate-800 shadow-sm transition hover:border-teal-700/40 hover:text-teal-800">
                        Watch Demo
                    </a>
                </div>
                <p class="mt-4 max-w-xl text-sm leading-6 text-slate-500">Start with one patient, one appointment, one note. Practiq helps guide the next step without taking over.</p>
            </div>

            <div class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-2xl shadow-teal-950/10">
                <div class="rounded-[1.5rem] border border-slate-200 bg-[#f8faf7] p-5">
                    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Today</p>
                            <p class="text-lg font-bold text-slate-950">What needs attention</p>
                        </div>
                        <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-800">Clear next steps</span>
                    </div>
                    <div class="space-y-3">
                        @foreach([
                            ['Today’s Schedule', '5 appointments, 1 ready for checkout'],
                            ['Visit Notes', '2 drafts waiting to be saved'],
                            ['Follow-Up', '4 patients may need a gentle check-in'],
                            ['Appointment Requests', '2 patients asked for preferred times'],
                        ] as [$title, $body])
                            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                <p class="font-bold text-slate-950">{{ $title }}</p>
                                <p class="mt-1 text-sm leading-6 text-slate-600">{{ $body }}</p>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4 rounded-2xl bg-teal-700 p-4 text-sm font-semibold leading-6 text-white">
                        Care continues after the visit. Practiq keeps the small things visible so they do not become missed opportunities for care.
                    </div>
                </div>
            </div>
        </section>

        <section class="border-y border-slate-200 bg-white">
            <div class="mx-auto max-w-5xl px-4 py-20 sm:px-6 lg:px-8">
                <h2 class="max-w-3xl text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Most practice software stores records. Practiq helps you remember who needs care.</h2>
                <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach([
                        'Unfinished notes at the end of the day',
                        'Patients who do not rebook',
                        'Missed follow-ups',
                        'Front desk overwhelm',
                        'Rigid documentation screens',
                        'Software that feels bigger than the practice',
                    ] as $item)
                        <div class="rounded-2xl border border-slate-200 bg-[#fbfaf6] p-5 font-semibold leading-7 text-slate-800">{{ $item }}</div>
                    @endforeach
                </div>
                <p class="mt-8 max-w-3xl text-lg leading-8 text-slate-600">Practiq is built for the daily rhythm of care: schedule, greet, treat, write, check out, follow up, and keep moving with kindness.</p>
            </div>
        </section>

        <section id="features" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="text-sm font-bold uppercase tracking-wide text-teal-800">Philosophy</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Built around the rhythm of a small clinic</h2>
            </div>
            <div class="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach([
                    ['See today clearly', 'Appointments, unfinished work, checkout, and patient requests stay visible without feeling noisy.'],
                    ['Write naturally', 'Notes can begin as plain language, not as a wall of required fields.'],
                    ['Follow up gently', 'Practiq shows who may need care without turning follow-up into sales pressure.'],
                    ['Invite patients back', 'Preview warm messages, save drafts, and send only when you choose.'],
                    ['Respect preferred language', 'Language context helps communication feel more welcoming and human.'],
                    ['Stay in control', 'AI can assist with drafts and translations, but staff reviews and approves the final message.'],
                ] as [$title, $body])
                    <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-bold text-slate-950">{{ $title }}</h3>
                        <p class="mt-3 leading-7 text-slate-600">{{ $body }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="border-y border-slate-200 bg-white">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 py-20 sm:px-6 lg:grid-cols-2 lg:px-8">
                <article class="rounded-3xl border border-slate-200 bg-[#fbfaf6] p-8">
                    <p class="text-sm font-bold uppercase tracking-wide text-teal-800">Today</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950">A calm overview of the day</h2>
                    <p class="mt-5 text-lg leading-8 text-slate-600">Today gives front desk and practitioners a clear view of appointments, unfinished work, pending appointment requests, and what needs attention next.</p>
                </article>
                <article class="rounded-3xl border border-slate-200 bg-[#fbfaf6] p-8">
                    <p class="text-sm font-bold uppercase tracking-wide text-teal-800">Visit Notes</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950">Write naturally first. Structure later if needed.</h2>
                    <p class="mt-5 text-lg leading-8 text-slate-600">Practiq supports Simple Visit Note Mode and SOAP / Insurance Mode. Note editing is mobile-friendly, with a gentle reminder that you can use your phone keyboard’s microphone to dictate.</p>
                </article>
            </div>
        </section>

        <section id="follow-up" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="grid gap-10 lg:grid-cols-[0.8fr_1.2fr]">
                <div>
                    <p class="text-sm font-bold uppercase tracking-wide text-teal-800">Follow-Up Center</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Care continues after the visit.</h2>
                    <p class="mt-5 text-lg leading-8 text-slate-600">Practiq shows patients who may need attention so small clinics can follow up with care, not pressure.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach([
                        ['Needs Follow-Up', 'A recent patient may benefit from a gentle check-in.'],
                        ['Cooling', 'It has been a little while since the last visit.'],
                        ['At Risk', 'A recent missed or canceled visit may need a caring follow-up.'],
                        ['Inactive', 'The patient has not been seen in a while.'],
                    ] as [$title, $body])
                        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                            <h3 class="font-bold text-slate-950">{{ $title }}</h3>
                            <p class="mt-2 leading-7 text-slate-600">{{ $body }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
            <p class="mt-8 rounded-2xl bg-teal-50 p-5 text-lg font-semibold leading-8 text-teal-950">Not sales pressure. Just a gentle reminder that someone may need care.</p>
        </section>

        <section class="border-y border-slate-200 bg-white">
            <div class="mx-auto grid max-w-7xl gap-6 px-4 py-20 sm:px-6 lg:grid-cols-3 lg:px-8">
                <article class="rounded-3xl border border-slate-200 bg-[#fbfaf6] p-8">
                    <h2 class="text-2xl font-bold text-slate-950">Invite Back</h2>
                    <p class="mt-4 leading-7 text-slate-600">Staff can preview a warm message, save a draft, translate when needed, and explicitly send an email. Opt-out safeguards and communication history keep the workflow safe.</p>
                    <p class="mt-4 text-sm font-semibold text-slate-500">AI suggestions are reviewed before use.</p>
                </article>
                <article class="rounded-3xl border border-slate-200 bg-[#fbfaf6] p-8">
                    <h2 class="text-2xl font-bold text-slate-950">Appointment Requests</h2>
                    <p class="mt-4 leading-7 text-slate-600">Invite Back emails can include a simple “Request an appointment” link. Patients do not need a portal login. They submit preferred times; staff reviews and schedules manually.</p>
                    <p class="mt-4 text-sm font-semibold text-slate-500">No automatic booking. No public availability exposure. No full patient portal required.</p>
                </article>
                <article class="rounded-3xl border border-slate-200 bg-[#fbfaf6] p-8">
                    <h2 class="text-2xl font-bold text-slate-950">Language-aware care</h2>
                    <p class="mt-4 leading-7 text-slate-600">Preferred language helps reminders and follow-ups feel more welcoming. Practiq includes deterministic Spanish Invite Back templates and can help prepare a translated draft for review.</p>
                    <p class="mt-4 text-sm font-semibold text-slate-500">Always review translations before sending.</p>
                </article>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="grid gap-8 lg:grid-cols-2">
                <article class="rounded-3xl bg-teal-950 p-8 text-white">
                    <p class="text-sm font-bold uppercase tracking-wide text-teal-200">Scheduling context</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight">Understand the relationship at a glance</h2>
                    <p class="mt-5 text-lg leading-8 text-teal-50/90">Calendar cards can show care status and language context so staff can understand the patient relationship while scheduling and preparing for the day.</p>
                </article>
                <article class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
                    <p class="text-sm font-bold uppercase tracking-wide text-teal-800">Who Practiq is for</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950">Small teams doing real care</h2>
                    <div class="mt-5 flex flex-wrap gap-2">
                        @foreach(['Acupuncture', 'Chiropractic', 'Massage therapy', 'Physiotherapy', 'Wellness practitioners', 'Cash-pay or mixed clinics', 'Solo and small teams'] as $item)
                            <span class="rounded-full border border-slate-200 bg-[#fbfaf6] px-4 py-2 text-sm font-semibold text-slate-700">{{ $item }}</span>
                        @endforeach
                    </div>
                </article>
            </div>
        </section>

        <section class="border-y border-slate-200 bg-white">
            <div class="mx-auto max-w-5xl px-4 py-20 text-center sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Practiq is not trying to be a giant hospital system.</h2>
                <p class="mx-auto mt-6 max-w-3xl text-lg leading-8 text-slate-600">Practiq starts with the daily rhythm of a small practice: who is coming in, what needs to be written, who needs a follow-up, and what the next clear step should be.</p>
            </div>
        </section>

        <section id="pricing" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="text-sm font-bold uppercase tracking-wide text-teal-800">Pricing</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Simple pricing for small practices.</h2>
                <p class="mt-5 text-lg leading-8 text-slate-600">Start with the plan that matches your clinic today. Add inventory only if products are part of your workflow.</p>
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
                        <a href="{{ $trialUrl }}" class="mt-7 inline-flex w-full justify-center rounded-xl bg-teal-700 px-5 py-3 font-bold text-white transition hover:bg-teal-800">Start Free Trial</a>
                    </article>
                @endforeach
            </div>
            <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 text-slate-700 shadow-sm">
                <p class="font-bold text-slate-950">Herb & Product Inventory add-on — $19/month.</p>
                <p class="mt-2">Add inventory when products are part of your practice workflow.</p>
            </div>
        </section>

        <section id="faq" class="mx-auto max-w-5xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="text-sm font-bold uppercase tracking-wide text-teal-800">FAQ</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Questions practitioners ask.</h2>
            </div>
            <div class="mt-10 divide-y divide-slate-200 rounded-3xl border border-slate-200 bg-white shadow-sm">
                @foreach([
                    ['Does Practiq include SOAP notes?', 'Yes. SOAP / Insurance Mode provides structured Subjective, Objective, Assessment, and Plan fields when your practice needs them.'],
                    ['Can I use simple natural notes instead?', 'Yes. Simple Visit Note Mode is built for natural writing, quick notes, and phone dictation.'],
                    ['Does Practiq have a full patient portal?', 'No. Practiq currently uses focused public links for specific tasks, such as appointment requests.'],
                    ['Can patients request appointments?', 'Yes. Invite Back emails can include a request link where patients share preferred times. Staff still schedules manually.'],
                    ['Does Practiq send follow-up emails?', 'Yes, when staff explicitly clicks Send Email and the patient can receive email. Drafts do not contact patients.'],
                    ['Does AI send messages automatically?', 'No. AI can help prepare drafts or translations for review, but staff stays in control.'],
                    ['Can Practiq help with different languages?', 'Yes. Preferred language is visible, Spanish Invite Back templates are deterministic, and AI translation preview can prepare drafts for review.'],
                    ['Is this good for acupuncture/wellness clinics?', 'Yes. Practiq is designed for small wellness clinics, including acupuncture, chiropractic, massage therapy, physiotherapy, and related practices.'],
                    ['Can I use it on my phone?', 'Yes. Key workflows are mobile-friendly, including Today, schedule views, and visit note editing.'],
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
                    <h2 class="text-3xl font-bold tracking-tight sm:text-5xl">Ready for calmer practice software?</h2>
                    <p class="mt-5 text-lg leading-8 text-teal-50/90">Try Practiq with your clinic, or watch the demo first. Either way, the goal is the same: clearer days, warmer follow-up, and less admin weight.</p>
                </div>
                <div class="mt-9 flex flex-col justify-center gap-3 sm:flex-row">
                    <a href="{{ $trialUrl }}" class="inline-flex items-center justify-center rounded-xl bg-white px-7 py-4 font-bold text-teal-900 transition hover:bg-teal-50">Start Free Trial</a>
                    <a href="{{ $demoUrl }}" class="inline-flex items-center justify-center rounded-xl border border-white/40 px-7 py-4 font-bold text-white transition hover:bg-white/10">Watch Demo</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-500">
        <p>&copy; 2026 Practiq. Built for small clinics.</p>
    </footer>
</body>
</html>
