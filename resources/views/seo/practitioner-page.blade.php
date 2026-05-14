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
@php $trialUrl = '/register'; $overviewUrl = '/#overview-video'; @endphp

{{-- NAV --}}
<header class="sticky top-0 z-30 border-b border-teal-900/10 bg-[#fbfaf6]/95 backdrop-blur">
    <nav class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8" aria-label="Primary navigation">
        <a href="/" class="flex items-center gap-3" aria-label="Practiq home">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-700 text-lg font-bold text-white shadow-sm shadow-teal-900/10">P</span>
            <span class="text-xl font-bold tracking-tight text-slate-950" style="font-family:'DM Sans',sans-serif">Practiq</span>
        </a>
        <div class="hidden items-center gap-5 text-sm font-medium text-slate-600 lg:flex">
            <a href="/" class="transition hover:text-teal-800">Home</a>
            <a href="/#pricing" class="transition hover:text-teal-800">Pricing</a>
            <a href="/blog" class="transition hover:text-teal-800">Blog</a>
            <a href="/admin/login" class="transition hover:text-teal-800">Login</a>
        </div>
        <a href="{{ $trialUrl }}" class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800">Start free trial</a>
    </nav>
</header>

<main>

{{-- HERO --}}
<section class="border-b border-slate-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
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
                    Start free trial
                </a>
                <a href="{{ $overviewUrl }}" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-7 py-3.5 text-[15px] font-medium text-slate-600 shadow-sm transition hover:border-teal-700/40 hover:text-teal-800">
                    Watch overview
                </a>
            </div>
            <p class="mt-4 text-[12px] text-slate-400">30-day free trial. No credit card required.</p>
        </div>
    </div>
</section>

{{-- DAILY REALITY --}}
<section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
    <div class="grid gap-10 lg:grid-cols-2">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-teal-700" style="font-family:'DM Sans',sans-serif">Daily reality</p>
            <h2 class="mt-3 text-[24px] font-medium leading-snug text-slate-950 sm:text-[28px]">{{ $page['dailyHeading'] }}</h2>
        </div>
        <div class="space-y-4 text-[15px] leading-[1.75] text-slate-600">
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

{{-- FEATURES CHIP GRID --}}
<section class="border-y border-slate-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
        <div class="max-w-2xl">
            <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-teal-700" style="font-family:'DM Sans',sans-serif">What Practiq covers</p>
            <h2 class="mt-3 text-[24px] font-medium leading-snug text-slate-950 sm:text-[28px]">Keep the core work of your practice in one place.</h2>
        </div>
        <div class="mt-10 grid gap-3 md:grid-cols-2 lg:grid-cols-3">
            @foreach($page['helps'] as [$title, $body])
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

{{-- WHAT PRACTIQ IS NOT --}}
<section class="border-y border-slate-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
        <div class="max-w-2xl">
            <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-teal-700" style="font-family:'DM Sans',sans-serif">Scope</p>
            <h2 class="mt-3 text-[24px] font-medium leading-snug text-slate-950 sm:text-[28px]">One thing, done well.</h2>
            <p class="mt-4 text-[15px] leading-[1.75] text-slate-600">Practiq does one thing: keeps the day-to-day work of a small practice organized. It is not trying to do everything.</p>
            <ul class="mt-6 space-y-2">
                @foreach(explode(', ', str_replace(['Practiq is not a ', 'Practiq is not an ', 'Not a ', 'Not an '], '', $page['not'])) as $item)
                @php $item = rtrim(trim($item), '.'); @endphp
                @if($item)
                <li class="flex items-start gap-3 text-[14px] text-slate-600">
                    <span class="text-slate-300 mt-0.5">—</span>
                    <span>Not {{ strtolower(trim($item)) }}</span>
                </li>
                @endif
                @endforeach
            </ul>
        </div>
    </div>
</section>

{{-- CTA BLOCK --}}
<section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-6 rounded-xl border border-slate-200 bg-slate-50 px-8 py-10 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-[22px] font-medium text-slate-950">Try it with your real workflow.</h2>
            <p class="mt-2 text-[14px] leading-relaxed text-slate-600">30-day free trial. Starter settings are already in place. No credit card required.</p>
        </div>
        <div class="flex shrink-0 flex-col gap-2 sm:flex-row">
            <a href="{{ $trialUrl }}" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-6 py-3.5 text-[14px] font-semibold text-white transition hover:bg-teal-800">Start free trial</a>
            <a href="{{ $overviewUrl }}" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-6 py-3.5 text-[14px] font-medium text-slate-600 transition hover:border-teal-700/30 hover:text-teal-800">Watch overview</a>
        </div>
    </div>
</section>

</main>

<footer class="border-t border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-400">
    <p>&copy; 2026 Practiq. Built for independent practitioners and small clinics.</p>
</footer>
</body>
</html>
