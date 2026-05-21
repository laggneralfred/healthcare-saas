<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.google-tag')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $page['title'] }}</title>
    <meta name="description" content="{{ $page['description'] }}">
    <script src="https://cdn.tailwindcss.com"></script>
    @include('partials.public-fonts')
</head>
<body class="bg-[#fbfaf6] text-slate-900 antialiased">
@php
    $trialUrl = '/register';
    $workflowUrl = '#workflow';
    $practiceTypesUrl = '/#practice-types';
@endphp

{{-- NAV --}}
<header class="sticky top-0 z-30 border-b border-teal-900/10 bg-[#fbfaf6]/95 backdrop-blur">
    <nav class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8" aria-label="Primary navigation">
        <a href="/" class="flex items-center gap-3" aria-label="Practiq home">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-700 text-lg font-bold text-white shadow-sm shadow-teal-900/10">P</span>
            <span class="text-xl font-bold tracking-tight text-slate-950" style="font-family:'DM Sans',sans-serif">Practiq</span>
        </a>
        <div class="hidden items-center gap-5 text-sm font-medium text-slate-600 lg:flex">
            <a href="/" class="transition hover:text-teal-800">Home</a>
            <a href="{{ $workflowUrl }}" class="transition hover:text-teal-800">How it helps</a>
            <a href="/#pricing" class="transition hover:text-teal-800">Pricing</a>
            <a href="/blog" class="transition hover:text-teal-800">Blog</a>
            <a href="/admin/login" class="transition hover:text-teal-800">Login</a>
        </div>
        <a href="{{ $trialUrl }}" class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800">Start free with Starter</a>
    </nav>
</header>

<main>

{{-- HERO --}}
<section class="border-b border-slate-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
        <div class="@if(!empty($page['image']['src'])) grid gap-10 lg:grid-cols-[minmax(0,1fr)_420px] lg:items-center @endif">
            <div class="max-w-3xl">
                <p class="mb-5 inline-flex w-fit rounded-full border border-teal-800/15 bg-teal-50 px-4 py-2 text-[12px] font-medium text-teal-800" style="font-family:'DM Sans',sans-serif">
                    {{ $page['eyebrow'] }}
                </p>
                <h1 class="text-[32px] font-medium leading-[1.2] text-slate-950 sm:text-[42px]">
                    {{ $page['h1'] }}
                </h1>
                <p class="mt-6 max-w-2xl text-[17px] leading-[1.75] text-slate-500">
                    {{ $page['subheadline'] }}
                </p>
                <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ $trialUrl }}" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-7 py-3.5 text-[15px] font-semibold text-white shadow-sm transition hover:bg-teal-800">
                        Start free with Starter
                    </a>
                    <a href="{{ $workflowUrl }}" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-7 py-3.5 text-[15px] font-medium text-slate-600 shadow-sm transition hover:border-teal-700/40 hover:text-teal-800">
                        See how it helps
                    </a>
                </div>
                <p class="mt-4 text-[12px] text-slate-400">Starter is free. Upgrade to Plus or Clinic when you need more.</p>
            </div>

            @if(!empty($page['image']['src']))
            <div class="mx-auto w-full max-w-[560px] lg:max-w-none">
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-[#fbfaf6] shadow-lg shadow-slate-900/5">
                    <img
                        src="{{ $page['image']['src'] }}"
                        alt="{{ $page['image']['alt'] ?? 'Practitioner image' }}"
                        class="h-auto w-full object-cover"
                        width="1200"
                        height="800"
                        loading="eager"
                        fetchpriority="high"
                        decoding="async"
                    >
                </div>
            </div>
            @endif
        </div>
    </div>
</section>

{{-- DAILY REALITY --}}
<section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
    <div class="grid gap-8 lg:grid-cols-2 lg:items-start">
        <div class="max-w-xl">
            <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-teal-700" style="font-family:'DM Sans',sans-serif">Daily reality</p>
            <h2 class="mt-3 text-[24px] font-medium leading-snug text-slate-950 sm:text-[28px]">{{ $page['dailyHeading'] }}</h2>
        </div>
        <div class="space-y-4 rounded-xl border border-slate-200 bg-white p-6 text-[15px] leading-[1.75] text-slate-600 sm:p-7">
            @foreach($page['dailyCopy'] as $paragraph)
            <p>{{ $paragraph }}</p>
            @endforeach
        </div>
    </div>
</section>

{{-- OPTIONAL NOTE WORKFLOW --}}
@if(!empty($page['noteWorkflow']) && !empty($page['noteWorkflow']['steps']))
<section class="border-y border-slate-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="max-w-3xl">
            <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-teal-700" style="font-family:'DM Sans',sans-serif">
                {{ $page['noteWorkflow']['eyebrow'] ?? 'Workflow' }}
            </p>
            <h2 class="mt-3 text-[24px] font-medium leading-snug text-slate-950 sm:text-[28px]">
                {{ $page['noteWorkflow']['heading'] ?? 'From rough notes to a clearer draft' }}
            </h2>
            @if(!empty($page['noteWorkflow']['intro']))
            <p class="mt-4 text-[15px] leading-[1.75] text-slate-600">{{ $page['noteWorkflow']['intro'] }}</p>
            @endif
        </div>

        <div class="mt-8 grid gap-4 lg:grid-cols-[1fr_auto_1fr_auto_1fr] lg:items-stretch">
            @foreach($page['noteWorkflow']['steps'] as $index => $step)
            <article class="rounded-xl border border-slate-200 bg-[#fbfaf6] px-5 py-5">
                <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-slate-400" style="font-family:'DM Sans',sans-serif">Step {{ $index + 1 }}</p>
                <h3 class="mt-2 text-[16px] font-semibold leading-snug text-slate-900" style="font-family:'DM Sans',sans-serif">{{ $step['title'] }}</h3>
                <p class="mt-3 text-[14px] leading-relaxed text-slate-600">{{ $step['body'] }}</p>
            </article>

            @if($index < count($page['noteWorkflow']['steps']) - 1)
            <div class="hidden items-center justify-center lg:flex" aria-hidden="true">
                <svg class="h-5 w-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 12h16m-5-5 5 5-5 5" />
                </svg>
            </div>
            @endif
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- HOW IT HELPS --}}
<section id="workflow" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
    <div class="max-w-2xl">
        <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-teal-700" style="font-family:'DM Sans',sans-serif">How Practiq helps</p>
        <h2 class="mt-3 text-[24px] font-medium leading-snug text-slate-950 sm:text-[28px]">Keep the core work of your practice in one place.</h2>
    </div>
    <div class="mt-10 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @foreach($page['helps'] as [$title, $body])
        <article class="rounded-xl border border-slate-200 bg-white p-6">
            <h3 class="text-[14px] font-semibold text-slate-900" style="font-family:'DM Sans',sans-serif">{{ $title }}</h3>
            <p class="mt-2 text-[13px] leading-relaxed text-slate-600">{{ $body }}</p>
        </article>
        @endforeach
    </div>
</section>

{{-- WHAT PRACTIQ IS NOT --}}
<section class="border-y border-slate-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
        <div class="max-w-3xl">
            <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-teal-700" style="font-family:'DM Sans',sans-serif">Scope</p>
            <h2 class="mt-3 text-[24px] font-medium leading-snug text-slate-950 sm:text-[28px]">What Practiq is — and what it is not</h2>
            <p class="mt-4 text-[15px] leading-[1.75] text-slate-600">Practiq is simple practice software for small clinics. It helps with notes, forms, follow-up, appointment requests, checkout tracking, and simple reports.</p>
        </div>
        <div class="mt-8 grid gap-2 md:grid-cols-2 lg:max-w-3xl">
            @foreach([
                'Not hospital software',
                'Not an AI clinician',
                'Not automatic booking chaos',
                'Not a billing clearinghouse',
            ] as $item)
            <div class="flex items-center gap-3 rounded-lg border border-slate-200 bg-[#fbfaf6] px-4 py-3">
                <span class="text-slate-300">—</span>
                <span class="text-[13px] text-slate-700">{{ $item }}</span>
            </div>
                @endforeach
        </div>
    </div>
</section>

{{-- STARTER + GOOD FIT FOR --}}
<section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
    <div class="grid gap-6 lg:grid-cols-[1fr_auto]">

        <article class="rounded-xl border border-slate-200 bg-white p-8">
            <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-teal-700" style="font-family:'DM Sans',sans-serif">Getting started</p>
            <h2 class="mt-3 text-[22px] font-medium text-slate-950">{{ $page['starterHeading'] }}</h2>
            <div class="mt-4 space-y-3 text-[14px] leading-[1.75] text-slate-600">
                @foreach($page['starterCopy'] as $paragraph)
                <p>{{ $paragraph }}</p>
                @endforeach
            </div>
        </article>

        <div class="rounded-xl border border-slate-200 bg-[#fbfaf6] p-8 lg:w-72">
            <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-teal-700" style="font-family:'DM Sans',sans-serif">Good fit for</p>
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach($page['fit'] as $item)
                <span class="rounded-full border border-teal-200 bg-teal-50 px-3 py-1.5 text-[12px] font-medium text-teal-800">{{ $item }}</span>
                @endforeach
            </div>
        </div>

    </div>
</section>

{{-- CTA BLOCK --}}
<section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-6 rounded-xl border border-slate-200 bg-slate-50 px-8 py-10 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-[22px] font-medium text-slate-950">Try it with your real workflow.</h2>
            <p class="mt-2 text-[14px] leading-relaxed text-slate-600">Starter is free. Upgrade when your practice is ready.</p>
        </div>
        <div class="flex shrink-0 flex-col gap-2 sm:flex-row">
            <a href="{{ $trialUrl }}" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-6 py-3.5 text-[14px] font-semibold text-white transition hover:bg-teal-800">Start free with Starter</a>
            <a href="{{ $practiceTypesUrl }}" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-6 py-3.5 text-[14px] font-medium text-slate-600 transition hover:border-teal-700/30 hover:text-teal-800">Back to practice types</a>
        </div>
    </div>
</section>

</main>

<footer class="border-t border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-400">
    <p>&copy; 2026 Practiq. Built for independent practitioners and small clinics.</p>
</footer>
</body>
</html>
