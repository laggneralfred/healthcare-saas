<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Getting Started with Practiq</title>
    <meta name="description" content="User instructions for getting started with Practiq.">
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
        $steps = [
            ['Log in and start with Today', 'Today is your calm starting place. Look for appointments, unfinished work, appointment requests, and what needs attention next.'],
            ['Check your practice settings', 'Confirm your practice name, timezone, appointment defaults, billing mode, payment methods, and communication settings.'],
            ['Add or import patients', 'Create a patient manually or use patient import when you have a spreadsheet from another system.'],
            ['Schedule your first appointment', 'Create an appointment from the calendar, patient record, or appointment area. Choose the patient, practitioner, type, time, and status.'],
            ['Start a visit', 'From Today or the appointment record, start the visit when the patient arrives or when you are ready to document care.'],
            ['Write or dictate a visit note', 'Use Simple Visit Note Mode for natural writing, or SOAP / Insurance Mode when structured documentation is needed. On your phone, tap the microphone on your keyboard to dictate.'],
            ['Save the note', 'Save Note keeps your writing without forcing unexpected status changes. After saving, Practiq shows the next useful step when one is available.'],
            ['Use AI drafts carefully', 'AI suggestions are drafts only. Review, edit, and accept only what belongs in the note or message.'],
            ['Proceed to checkout', 'When a visit is ready, send it to checkout and collect payment through the existing checkout flow.'],
            ['Review Follow-Up', 'Open Follow-Up to see patients who may need attention: Needs Follow-Up, Cooling, At Risk, or Inactive.'],
            ['Invite a patient back', 'Open Invite Back, review the message, translate only when helpful, and decide whether to save or send.'],
            ['Send or save a draft', 'Saving a draft does not contact the patient. Sending email requires explicit confirmation and respects opt-out safeguards.'],
            ['Handle appointment requests on Today', 'When a patient submits preferred times, review the request on Today, create an appointment with the patient preselected, and mark the request Contacted, Scheduled, or Dismissed.'],
        ];
    @endphp

    <header class="border-b border-teal-900/10 bg-[#fbfaf6]/95">
        <nav class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8" aria-label="Primary navigation">
            <a href="/" class="flex items-center gap-3" aria-label="Practiq home">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-700 text-lg font-bold text-white shadow-sm shadow-teal-900/10">P</span>
                <span class="text-xl font-bold tracking-tight text-slate-950">Practiq</span>
            </a>
            <div class="flex items-center gap-3 text-sm font-semibold">
                <a href="/" class="hidden text-slate-600 transition hover:text-teal-800 sm:inline">Home</a>
                <a href="/admin/login" class="hidden text-slate-600 transition hover:text-teal-800 sm:inline">Login</a>
                <a href="{{ $trialUrl }}" class="rounded-full bg-teal-700 px-5 py-2.5 font-bold text-white shadow-sm transition hover:bg-teal-800">Start Free Trial</a>
            </div>
        </nav>
    </header>

    <main>
        <section class="mx-auto max-w-5xl px-4 py-16 sm:px-6 lg:px-8">
            <p class="mb-5 inline-flex rounded-full border border-teal-800/15 bg-white px-4 py-2 text-sm font-semibold text-teal-800 shadow-sm">User Instructions</p>
            <h1 class="max-w-4xl text-4xl font-extrabold tracking-tight text-slate-950 sm:text-5xl">Getting Started with Practiq</h1>
            <p class="mt-6 max-w-3xl text-lg leading-8 text-slate-600">Take it one step at a time. You do not need to learn everything at once.</p>
        </section>

        <section class="mx-auto max-w-5xl px-4 pb-20 sm:px-6 lg:px-8">
            <div class="grid gap-4">
                @foreach($steps as $index => [$title, $body])
                    <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex gap-4">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-teal-700 text-sm font-bold text-white">{{ $index + 1 }}</div>
                            <div>
                                <h2 class="text-xl font-bold text-slate-950">{{ $title }}</h2>
                                <p class="mt-2 leading-7 text-slate-600">{{ $body }}</p>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="border-y border-slate-200 bg-white">
            <div class="mx-auto max-w-5xl px-4 py-16 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold tracking-tight text-slate-950">Gentle reminders</h2>
                <div class="mt-8 grid gap-4 sm:grid-cols-2">
                    @foreach([
                        'Saving a draft does not contact the patient.',
                        'Sending email requires explicit confirmation.',
                        'AI suggestions are drafts only.',
                        'You stay in control.',
                        'Start with one patient, one appointment, one note.',
                    ] as $reminder)
                        <div class="rounded-2xl border border-slate-200 bg-[#fbfaf6] p-5 font-semibold leading-7 text-slate-800">{{ $reminder }}</div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="px-4 py-20 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-5xl rounded-[2rem] bg-teal-800 px-6 py-14 text-center text-white shadow-2xl shadow-teal-950/15 sm:px-12">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">Ready to try Practiq?</h2>
                <p class="mx-auto mt-5 max-w-2xl text-lg leading-8 text-teal-50/90">Start gently. Explore the demo or begin a free trial when you are ready.</p>
                <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
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
