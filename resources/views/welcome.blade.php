<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.google-tag')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practiq — Practice Management Software for Small Clinics</title>
    <meta name="description" content="Practiq keeps visit notes, intake forms, appointment requests, follow-up, and checkout organized for small acupuncture, massage, chiropractic, physiotherapy, and wellness practices. 30-day free trial.">
    @php
        $structuredData = [
            '@context' => 'https://schema.org',
            '@graph' => [
                ['@type' => 'Organization', '@id' => 'https://practiqapp.com/#organization', 'name' => 'Practiq', 'url' => 'https://practiqapp.com/'],
                ['@type' => 'WebSite', '@id' => 'https://practiqapp.com/#website', 'name' => 'Practiq', 'url' => 'https://practiqapp.com/', 'publisher' => ['@id' => 'https://practiqapp.com/#organization']],
                ['@type' => 'SoftwareApplication', '@id' => 'https://practiqapp.com/#softwareapplication', 'name' => 'Practiq', 'url' => 'https://practiqapp.com/', 'description' => 'Practice management software for small clinics: visit notes, intake forms, appointment requests, follow-up, checkout tracking, and simple reports.', 'applicationCategory' => 'BusinessApplication', 'operatingSystem' => 'Web', 'audience' => ['@type' => 'Audience', 'audienceType' => 'solo practitioners, small clinics'], 'publisher' => ['@id' => 'https://practiqapp.com/#organization']],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    <script src="https://cdn.tailwindcss.com"></script>
    @include('partials.public-fonts')
</head>
<body class="bg-[#fbfaf6] text-slate-900 antialiased">
@php
    $trialUrl        = '/register';
    $demoUrl         = 'https://demo.practiqapp.com/demo-login';
@endphp

{{-- NAV --}}
<header class="sticky top-0 z-30 border-b border-teal-900/10 bg-[#fbfaf6]/95 backdrop-blur">
    <nav class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8" aria-label="Primary navigation">
        <a href="/" class="flex items-center gap-3" aria-label="Practiq home">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-700 text-lg font-bold text-white shadow-sm shadow-teal-900/10">P</span>
            <span class="text-xl font-bold tracking-tight text-slate-950" style="font-family:'DM Sans',sans-serif">Practiq</span>
        </a>
        <div class="hidden items-center gap-5 text-sm font-medium text-slate-600 lg:flex">
            <a href="#workflow" class="transition hover:text-teal-800">How it helps</a>
            <a href="#pricing" class="transition hover:text-teal-800">Pricing</a>
            <a href="/blog" class="transition hover:text-teal-800">Blog</a>
            <a href="#faq" class="transition hover:text-teal-800">FAQ</a>
            <a href="/user-instructions" class="transition hover:text-teal-800">User guide</a>
            <a href="/admin/login" class="transition hover:text-teal-800">Login</a>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ $demoUrl }}" class="hidden rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition hover:border-teal-700/30 hover:text-teal-800 sm:inline-flex">Watch Demo</a>
            <a href="{{ $trialUrl }}" class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800">Start free trial</a>
        </div>
    </nav>
</header>

<main>

{{-- HERO --}}
<section class="border-b border-slate-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 pb-16 pt-16 sm:px-6 lg:px-8 lg:pb-24 lg:pt-24">
        <div class="max-w-3xl">
            <p class="mb-5 inline-flex w-fit rounded-full border border-teal-800/15 bg-teal-50 px-4 py-2 text-sm font-medium text-teal-800" style="font-family:'DM Sans',sans-serif">
                For independent practitioners and small clinics
            </p>
            <h1 class="text-[36px] font-medium leading-[1.2] text-slate-950 sm:text-[48px]">
                Your day is for patients.<br>Not paperwork.
            </h1>
            <p class="mt-6 max-w-2xl text-[17px] leading-[1.75] text-slate-500">
                Practiq keeps the everyday work of a small clinic organized — visit notes, intake forms, appointment requests, follow-up, and checkout — so you can finish the day without staying late.
            </p>
            <p class="mt-3 text-[14px] leading-relaxed text-slate-400">
                Built for solo providers and small clinics. Not an oversized EHR.
            </p>
            <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                <a href="{{ $trialUrl }}" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-7 py-3.5 text-[15px] font-semibold text-white shadow-sm transition hover:bg-teal-800">
                    Start free trial
                </a>
                <a href="{{ $demoUrl }}" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-7 py-3.5 text-[15px] font-medium text-slate-600 shadow-sm transition hover:border-teal-700/40 hover:text-teal-800">
                    Watch Demo
                </a>
            </div>
            <p class="mt-4 text-[12px] text-slate-400">30-day free trial. No credit card required.</p>
        </div>

    </div>
</section>

{{-- CORE WORKFLOW --}}
<section id="workflow" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
    <div class="max-w-2xl">
        <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-teal-700" style="font-family:'DM Sans',sans-serif">How Practiq helps</p>
        <h2 class="mt-3 text-[26px] font-medium leading-snug text-slate-950 sm:text-[30px]">One practical workflow for the whole day</h2>
        <p class="mt-4 text-[15px] leading-[1.75] text-slate-600">Keep the core pieces of a small practice connected, so less gets lost between visits.</p>
    </div>
    <div class="mt-10 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @foreach([
            ['Visit notes', 'Write rough fragments when time is tight. Practiq can help shape practitioner-written notes into clearer draft text. The practitioner supplies the facts, reviews the draft, and decides what belongs in the record.'],
            ['Follow-up', 'See who may need a check-in or an invite back, then review the message before it sends.'],
            ['Forms and appointment requests', 'Send forms before visits and let patients request times. Your clinic still confirms the schedule.'],
            ['Checkout and simple reports', 'Track charges, payments, and simple totals without turning your clinic into accounting software.'],
        ] as [$title, $body])
        <article class="rounded-xl border border-slate-200 bg-white p-6 @if($loop->index === 3) md:col-span-2 lg:col-span-1 @endif">
            <h3 class="text-[15px] font-medium text-slate-950">{{ $title }}</h3>
            <p class="mt-2.5 text-[14px] leading-[1.7] text-slate-600">{{ $body }}</p>
        </article>
        @endforeach
    </div>
</section>

{{-- DISCIPLINES --}}
<section id="practice-types" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
    <div class="max-w-2xl">
        <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-teal-700" style="font-family:'DM Sans',sans-serif">Built for small healthcare practices</p>
        <h2 class="mt-3 text-[26px] font-medium leading-snug text-slate-950 sm:text-[30px]">For your kind of practice</h2>
        <p class="mt-4 text-[15px] leading-[1.75] text-slate-600">Practiq is built for small clinics, but the day looks a little different in every discipline. Choose the page closest to your work.</p>
    </div>
    <div class="mt-10 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        @foreach([
            ['/practice-software-for-acupuncturists', 'Acupuncture', 'Preserve the thread of care between visits with notes, intake, and follow-up.', '/images/practitioner-pages/acupuncture.png', 'Acupuncture practitioner working in a calm small clinic setting'],
            ['/massage-therapy-practice-software', 'Massage therapy', 'Keep session notes, client response, follow-up, and checkout from getting scattered.', '/images/practitioner-pages/massage-therapy.png', 'Massage therapist working with a client in a professional small clinic setting'],
            ['/chiropractic-practice-software', 'Chiropractic', 'Keep short visits, progress notes, SOAP-style structure when needed, and follow-up organized.', '/images/practitioner-pages/chiropractic.png', 'Chiropractor working with a patient in a small clinic setting'],
            ['/physiotherapy-practice-software', 'Physiotherapy', 'Track progress over time, home exercises, reassessment notes, and follow-up plans.', '/images/practitioner-pages/physiotherapy.png', 'Physiotherapist guiding a patient through a supported exercise in a small clinic setting'],
            ['/wellness-practice-software', 'Wellness', 'Use flexible notes, forms, and follow-up without making the clinic feel like a hospital.', '/images/practitioner-pages/wellness.png', 'Wellness practitioner meeting with a client in a calm consultation setting'],
        ] as [$href, $title, $body, $img, $alt])
        <a href="{{ $href }}" class="group overflow-hidden rounded-xl border border-slate-200 bg-white transition hover:border-teal-700/30 hover:shadow-sm">
            <div class="aspect-[16/10] overflow-hidden bg-[#fbfaf6]">
                <img
                    src="{{ $img }}"
                    alt="{{ $alt }}"
                    class="h-full w-full object-cover"
                    width="1200"
                    height="750"
                    loading="lazy"
                    decoding="async"
                >
            </div>
            <div class="px-5 py-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-[15px] font-medium text-slate-950 group-hover:text-teal-800">{{ $title }}</h3>
                    <svg class="h-4 w-4 text-slate-300 transition group-hover:text-teal-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
                <p class="mt-2 text-[13px] leading-relaxed text-slate-500">{{ $body }}</p>
            </div>
        </a>
        @endforeach
    </div>
</section>

{{-- WHAT PRACTIQ IS NOT --}}
<section class="border-y border-slate-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
        <div class="max-w-3xl">
            <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-teal-700" style="font-family:'DM Sans',sans-serif">Scope</p>
            <h2 class="mt-3 text-[26px] font-medium leading-snug text-slate-950 sm:text-[30px]">What Practiq is — and what it is not</h2>
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

{{-- PRICING --}}
<section id="pricing" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
    <div class="max-w-2xl">
        <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-teal-700" style="font-family:'DM Sans',sans-serif">Pricing</p>
        <h2 class="mt-3 text-[26px] font-medium leading-snug text-slate-950 sm:text-[30px]">Clear pricing. No surprises.</h2>
        <p class="mt-4 text-[15px] leading-[1.75] text-slate-600">Start with the plan that fits your practice now. 30-day free trial. No credit card required.</p>
    </div>
    <div class="mt-10 grid gap-5 lg:grid-cols-3">
        @foreach([
            ['Solo',             '$49',  'month', 'For one practitioner. All core features included.'],
            ['Clinic',           '$99',  'month', 'For small clinics with up to 5 practitioners.'],
            ['Growing Practice', '$199', 'month', 'For growing or multi-practitioner practices.'],
        ] as [$plan, $price, $period, $description])
        <article class="rounded-xl border border-slate-200 bg-white p-8">
            <h3 class="text-[15px] font-medium text-slate-700" style="font-family:'DM Sans',sans-serif">{{ $plan }}</h3>
            <p class="mt-4 price-num text-[42px] font-medium leading-none text-slate-950">{{ $price }}<span class="text-[16px] font-normal text-slate-400" style="font-family:'DM Sans',sans-serif">/{{ $period }}</span></p>
            <p class="mt-4 text-[13px] leading-relaxed text-slate-600">{{ $description }}</p>
            <a href="{{ $trialUrl }}" class="mt-7 inline-flex w-full items-center justify-center rounded-lg bg-slate-900 px-5 py-3 text-[14px] font-semibold text-white transition hover:bg-teal-800">Start free trial</a>
        </article>
        @endforeach
    </div>
    <div class="mt-5 rounded-xl border border-slate-200 bg-[#fbfaf6] px-6 py-5">
        <p class="text-[13px] font-semibold text-slate-900" style="font-family:'DM Sans',sans-serif">Herb &amp; Product Inventory — $19/month add-on</p>
        <p class="mt-1 text-[13px] text-slate-500">Add inventory tracking only if products are part of your practice workflow.</p>
    </div>
    <p class="mt-4 text-[12px] text-slate-500">Stripe handles Practiq subscription billing.</p>
</section>

{{-- FAQ --}}
<section id="faq" class="mx-auto max-w-4xl px-4 py-20 sm:px-6 lg:px-8">
    <div class="max-w-2xl">
        <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-teal-700" style="font-family:'DM Sans',sans-serif">FAQ</p>
        <h2 class="mt-3 text-[26px] font-medium leading-snug text-slate-950 sm:text-[30px]">Straight answers.</h2>
    </div>
    <div class="mt-8 space-y-2">
        @foreach([
            ['Does Practiq support simple notes and SOAP notes?',
             'Yes. Write in natural language for routine visits, or switch to SOAP mode when the record needs more structure. You choose per visit.'],
            ['Can I use simple natural notes?',
             'Yes. Many teams write brief, natural notes and add structure only when needed.'],
            ['Can Practiq help with rough notes?',
             'Yes. If you write the facts in rough form, Practiq can help turn that into clearer draft text. You review and edit before saving.'],
            ['Can patients request appointments?',
             'Yes. Patients request a time and your clinic confirms it.'],
            ['Does AI send anything automatically?',
             'No. AI suggestions are drafts only. Nothing is sent or saved automatically.'],
            ['Is Practiq a billing service or clearinghouse?',
             'No. Practiq includes checkout tracking and simple reports, but it is not a billing clearinghouse or full accounting system.'],
        ] as [$question, $answer])
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <button class="acc-trigger flex w-full items-center gap-4 px-6 py-5 text-left transition-colors hover:bg-slate-50" aria-expanded="false">
                <span class="flex-1 text-[14px] font-medium text-slate-900" style="font-family:'DM Sans',sans-serif">{{ $question }}</span>
                <svg class="acc-chevron h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div class="acc-body">
                <div class="border-t border-slate-100 px-6 py-4">
                    <p class="text-[14px] leading-relaxed text-slate-600">{{ $answer }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</section>

{{-- FINAL CTA --}}
<section class="mx-auto max-w-4xl px-4 pb-20 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-6 rounded-xl border border-slate-200 bg-slate-50 px-8 py-10 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-[22px] font-medium text-slate-950">Ready to see if it fits your day?</h2>
            <p class="mt-2 text-[14px] leading-relaxed text-slate-600">Start a free trial and test Practiq with your real workflow.</p>
        </div>
        <div class="flex shrink-0 flex-col gap-2 sm:flex-row">
            <a href="{{ $trialUrl }}" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-6 py-3.5 text-[14px] font-semibold text-white transition hover:bg-teal-800">Start Free Trial</a>
            <a href="#practice-types" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-6 py-3.5 text-[14px] font-medium text-slate-600 transition hover:border-teal-700/30 hover:text-teal-800">Choose your practice type</a>
        </div>
    </div>
</section>

</main>

<footer class="border-t border-slate-200 bg-white px-4 py-10">
    <div class="mx-auto max-w-7xl text-center text-sm text-slate-400">
        <p>&copy; 2026 Practiq. Built for independent practitioners and small clinics.</p>
        <div class="mt-3 flex flex-wrap justify-center gap-x-5 gap-y-1 text-[12px]">
            <a href="/blog" class="text-teal-700 transition hover:text-teal-900">Blog</a>
            <a href="/blog/small-clinic-visit-notes" class="text-teal-700 transition hover:text-teal-900">Keeping up with visit notes</a>
            <a href="/blog/acupuncture-visit-note-examples" class="text-teal-700 transition hover:text-teal-900">Acupuncture note examples</a>
            <a href="/legal/privacy" class="hover:text-slate-600 transition">Privacy</a>
            <a href="/legal/terms" class="hover:text-slate-600 transition">Terms</a>
        </div>
    </div>
</footer>

<script>
document.querySelectorAll('.acc-trigger').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var body = this.parentElement.querySelector('.acc-body');
        var chevron = this.querySelector('.acc-chevron');
        var isOpen = body.classList.contains('open');
        body.classList.toggle('open', !isOpen);
        chevron.classList.toggle('open', !isOpen);
        this.setAttribute('aria-expanded', String(!isOpen));
    });
});
</script>
</body>
</html>
