<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.google-tag')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $page['title'] }}</title>
    <meta name="description" content="{{ $page['description'] }}">
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
        $overviewUrl = '/#overview-video';
    @endphp

    <header class="sticky top-0 z-30 border-b border-teal-900/10 bg-[#fbfaf6]/95 backdrop-blur">
        <nav class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8" aria-label="Primary navigation">
            <a href="/" class="flex items-center gap-3" aria-label="Practiq home">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-700 text-lg font-bold text-white shadow-sm shadow-teal-900/10">P</span>
                <span class="text-xl font-bold tracking-tight text-slate-950">Practiq</span>
            </a>
            <div class="hidden items-center gap-5 text-sm font-semibold text-slate-600 lg:flex">
                <a href="/" class="transition hover:text-teal-800">Home</a>
                <a href="/#overview-video" class="transition hover:text-teal-800">Overview</a>
                <a href="/#pricing" class="transition hover:text-teal-800">Pricing</a>
                <a href="/admin/login" class="transition hover:text-teal-800">Login</a>
            </div>
            <a href="{{ $trialUrl }}" class="rounded-full bg-teal-700 px-5 py-2.5 text-sm font-bold text-white shadow-sm shadow-teal-900/10 transition hover:bg-teal-800">Start Free Trial</a>
        </nav>
    </header>

    <main>
        <section class="border-b border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
                <div class="max-w-4xl">
                    <p class="mb-5 inline-flex w-fit rounded-full border border-teal-800/15 bg-teal-50 px-4 py-2 text-sm font-semibold text-teal-900">
                        {{ $page['eyebrow'] }}
                    </p>
                    <h1 class="text-4xl font-extrabold leading-[1.05] tracking-tight text-slate-950 sm:text-6xl">
                        {{ $page['h1'] }}
                    </h1>
                    <p class="mt-6 max-w-3xl text-lg leading-8 text-slate-600 sm:text-xl">
                        {{ $page['subheadline'] }}
                    </p>
                    <p class="mt-4 max-w-3xl text-base leading-7 text-slate-500">
                        Practiq is small practice healthcare software built for busy providers who need visit note software, intake forms for small clinics, appointment request software, patient follow-up tools, checkout tracking, and useful exports in one practical workflow.
                    </p>
                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ $trialUrl }}" class="inline-flex items-center justify-center rounded-xl bg-teal-700 px-7 py-4 text-base font-bold text-white shadow-lg shadow-teal-900/10 transition hover:bg-teal-800">
                            Start a free trial
                        </a>
                        <a href="{{ $overviewUrl }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-7 py-4 text-base font-bold text-slate-800 shadow-sm transition hover:border-teal-700/40 hover:text-teal-800">
                            Watch the overview
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="grid gap-10 lg:grid-cols-[0.9fr_1.1fr]">
                <div>
                    <p class="text-sm font-bold uppercase tracking-wide text-teal-800">Daily Reality</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">{{ $page['dailyHeading'] }}</h2>
                </div>
                <div class="space-y-5 text-lg leading-8 text-slate-600">
                    @foreach($page['dailyCopy'] as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="border-y border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
                <div class="max-w-3xl">
                    <p class="text-sm font-bold uppercase tracking-wide text-teal-800">What Practiq Helps With</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Keep the core work of a small practice under control.</h2>
                </div>
                <div class="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($page['helps'] as [$title, $body])
                        <article class="rounded-2xl border border-slate-200 bg-[#fbfaf6] p-6">
                            <h3 class="text-lg font-bold text-slate-950">{{ $title }}</h3>
                            <p class="mt-3 leading-7 text-slate-600">{{ $body }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-3">
                <article class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm lg:col-span-2">
                    <p class="text-sm font-bold uppercase tracking-wide text-teal-800">Guided Starter Setup</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950">{{ $page['starterHeading'] }}</h2>
                    <div class="mt-5 space-y-4 leading-7 text-slate-600">
                        @foreach($page['starterCopy'] as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach
                    </div>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-[#f8faf7] p-7">
                    <p class="text-sm font-bold uppercase tracking-wide text-teal-800">Good Fit For</p>
                    <ul class="mt-5 space-y-3 text-slate-700">
                        @foreach($page['fit'] as $item)
                            <li class="rounded-lg border border-slate-200 bg-white px-4 py-3">{{ $item }}</li>
                        @endforeach
                    </ul>
                </article>
            </div>
        </section>

        <section class="border-y border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
                <div class="max-w-3xl">
                    <p class="text-sm font-bold uppercase tracking-wide text-teal-800">What Practiq Is Not</p>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Focused on daily workflow, not everything in healthcare.</h2>
                    <p class="mt-5 text-lg leading-8 text-slate-600">{{ $page['not'] }}</p>
                    <p class="mt-4 text-lg leading-8 text-slate-600">It is intentionally focused on the daily workflow of a small practice.</p>
                </div>
            </div>
        </section>

        <section class="px-4 py-20 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-6xl rounded-2xl bg-teal-800 px-6 py-16 text-white shadow-2xl shadow-teal-950/15 sm:px-12">
                <div class="mx-auto max-w-3xl text-center">
                    <h2 class="text-3xl font-bold tracking-tight sm:text-5xl">Try Practiq with starter settings already in place.</h2>
                    <p class="mt-5 text-lg leading-8 text-teal-50/90">Start a free trial and see whether it fits your practice workflow.</p>
                </div>
                <div class="mt-9 flex flex-col justify-center gap-3 sm:flex-row">
                    <a href="{{ $trialUrl }}" class="inline-flex items-center justify-center rounded-xl bg-white px-7 py-4 font-bold text-teal-900 transition hover:bg-teal-50">Start a free trial</a>
                    <a href="{{ $overviewUrl }}" class="inline-flex items-center justify-center rounded-xl border border-white/40 px-7 py-4 font-bold text-white transition hover:bg-white/10">Watch the overview</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-500">
        <p>&copy; 2026 Practiq. Built for independent practitioners and small clinics.</p>
    </footer>
</body>
</html>
