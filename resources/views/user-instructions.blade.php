<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.google-tag')
    @include('partials.robots-noindex')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Getting Started with Practiq — User Guide</title>
    <meta name="description" content="Step-by-step guide for getting started with Practiq. Take it one step at a time.">
    <script src="https://cdn.tailwindcss.com"></script>
    @include('partials.public-fonts')
</head>
<body class="bg-[#fbfaf6] text-slate-900 antialiased">
@php
    $trialUrl = '/register';
    $demoUrl  = 'https://demo.practiqapp.com/demo-login';
    $steps = [
        ['Log in and start with Today',           'Today is your calm starting place. Look for appointments, unfinished work, appointment requests, and what needs attention next.'],
        ['Check your practice settings',          'Confirm your practice name, timezone, appointment defaults, billing mode, payment methods, and communication settings.'],
        ['Add or import patients',                'Create a patient manually, or use patient import when you have a spreadsheet from another system.'],
        ['Schedule your first appointment',       'Create an appointment from the calendar, patient record, or appointment area. Choose the patient, practitioner, type, time, and status.'],
        ['Start a visit',                         'From Today or the appointment record, start the visit when the patient arrives or when you are ready to document care.'],
        ['Write or dictate a visit note',         'Use Simple Visit Note Mode for natural writing, or SOAP / Insurance Mode when structured documentation is needed. On your phone, tap the microphone on your keyboard to dictate.'],
        ['Save the note',                         'Save Note keeps your writing without forcing unexpected status changes. After saving, Practiq shows the next useful step when one is available.'],
        ['Use AI drafts carefully',               'AI suggestions are drafts only. Review, edit, and accept only what belongs in the note or message.'],
        ['Proceed to checkout',                   'When a visit is ready, send it to checkout and collect payment through the existing checkout flow.'],
        ['Review Follow-Up',                      'Open Follow-Up to see patients who may need attention: Needs Follow-Up, Cooling, At Risk, or Inactive.'],
        ['Invite a patient back',                 'Open Invite Back, review the message, translate only when helpful, and decide whether to save or send.'],
        ['Send or save a draft',                  'Saving a draft does not contact the patient. Sending email requires explicit confirmation and respects opt-out safeguards.'],
        ['Handle appointment requests on Today',  'When a patient submits preferred times, review the request on Today, create an appointment with the patient preselected, and mark the request Contacted, Scheduled, or Dismissed.'],
    ];
    $reminders = [
        'Saving a draft does not contact the patient.',
        'Sending email requires your explicit confirmation.',
        'AI suggestions are drafts — review everything.',
        'You stay in control at every step.',
        'Start with one patient, one appointment, one note.',
    ];
@endphp

{{-- NAV --}}
<header class="border-b border-teal-900/10 bg-[#fbfaf6]/95">
    <nav class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8" aria-label="Primary navigation">
        <a href="/" class="flex items-center gap-3" aria-label="Practiq home">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-700 text-lg font-bold text-white shadow-sm shadow-teal-900/10">P</span>
            <span class="text-xl font-bold tracking-tight text-slate-950" style="font-family:'DM Sans',sans-serif">Practiq</span>
        </a>
        <div class="flex items-center gap-3 text-sm font-medium">
            <a href="/" class="hidden text-slate-600 transition hover:text-teal-800 sm:inline">Home</a>
            <a href="/admin/login" class="hidden text-slate-600 transition hover:text-teal-800 sm:inline">Login</a>
            <a href="{{ $trialUrl }}" class="rounded-lg bg-slate-900 px-5 py-2.5 font-semibold text-white shadow-sm transition hover:bg-teal-800">Start free trial</a>
        </div>
    </nav>
</header>

<main>

    {{-- PAGE HEADER --}}
    <section class="mx-auto max-w-5xl px-4 py-16 sm:px-6 lg:px-8">
        <p class="inline-flex rounded-full border border-teal-800/15 bg-white px-4 py-2 text-[12px] font-medium text-teal-800 shadow-sm" style="font-family:'DM Sans',sans-serif">User guide</p>
        <h1 class="mt-4 max-w-3xl text-[30px] font-medium leading-[1.3] text-slate-950 sm:text-[36px]">Getting Started with Practiq</h1>
        <p class="mt-5 max-w-2xl text-[15px] leading-[1.75] text-slate-600">Take it one step at a time. You do not need to learn everything at once. Start with the first visit and build from there.</p>
    </section>

    {{-- STEPS --}}
    <section class="mx-auto max-w-5xl px-4 pb-10 sm:px-6 lg:px-8">
        <div class="space-y-3">
            @foreach($steps as $index => [$title, $body])
            <article class="rounded-xl border border-slate-200 bg-white p-6">
                <div class="flex gap-4">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-teal-700 text-[13px] font-semibold text-white" style="font-family:'DM Sans',sans-serif">{{ $index + 1 }}</div>
                    <div>
                        <h2 class="text-[16px] font-medium text-slate-950">{{ $title }}</h2>
                        <p class="mt-1.5 text-[14px] leading-[1.7] text-slate-600">{{ $body }}</p>
                    </div>
                </div>
            </article>
            @endforeach
        </div>
    </section>

    {{-- REMINDERS --}}
    <section class="border-y border-slate-200 bg-white">
        <div class="mx-auto max-w-5xl px-4 py-16 sm:px-6 lg:px-8">
            <h2 class="text-[22px] font-medium text-slate-950">A few things to keep in mind</h2>
            <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($reminders as $reminder)
                <div class="rounded-xl border border-slate-200 bg-[#fbfaf6] px-5 py-4 text-[14px] font-medium leading-relaxed text-slate-700">{{ $reminder }}</div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="mx-auto max-w-5xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-6 rounded-xl border border-slate-200 bg-slate-50 px-8 py-10 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-[22px] font-medium text-slate-950">Ready to try Practiq?</h2>
                <p class="mt-2 text-[14px] leading-relaxed text-slate-600">Start gently. Explore the demo, or begin a free trial when you are ready.</p>
            </div>
            <div class="flex shrink-0 flex-col gap-2 sm:flex-row">
                <a href="{{ $trialUrl }}" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-6 py-3.5 text-[14px] font-semibold text-white transition hover:bg-teal-800">Start free trial</a>
                <a href="{{ $demoUrl }}" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-6 py-3.5 text-[14px] font-medium text-slate-600 transition hover:border-teal-700/30 hover:text-teal-800">View demo</a>
            </div>
        </div>
    </section>

</main>

<footer class="border-t border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-400">
    <p>&copy; 2026 Practiq. Built for small clinics.</p>
</footer>
</body>
</html>
