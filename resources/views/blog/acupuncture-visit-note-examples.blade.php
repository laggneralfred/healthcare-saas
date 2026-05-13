{{-- Redesigned: acupuncture-visit-note-examples — improved typography, accordion examples, CTA block --}}
@extends('layouts.blog-article', [
    'title'       => 'Acupuncture Visit Note Examples for Small Practices | Practiq',
    'description' => 'Four ready-to-use acupuncture visit note examples using a consistent six-part structure. Built for solo providers and small clinics that need practical documentation without the admin overhead.',
])

@push('head')
<link rel="canonical" href="https://practiqapp.com/blog/acupuncture-visit-note-examples">
<style>
    .art-serif { font-family: 'Lora', Georgia, serif; }
    .art-sans  { font-family: 'DM Sans', sans-serif; }
</style>
@endpush

@section('content')
<article class="art-sans mx-auto max-w-[720px]">

    {{-- Category label --}}
    <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-teal-700">Documentation Guide · Acupuncture</p>

    {{-- H1 --}}
    <h1 class="art-serif mt-3 text-[30px] font-medium leading-[1.3] text-slate-950 sm:text-[32px]">
        Acupuncture Visit Note Examples for Small Practices
    </h1>

    {{-- Deck --}}
    <p class="mt-4 text-base leading-[1.75] text-slate-500">
        A practical reference for consistent, efficient clinical documentation — without adding admin overhead to your day.
    </p>

    {{-- Intro --}}
    <div class="mt-6 space-y-3 text-[15px] leading-[1.75] text-slate-700">
        <p>Visit notes don't need to be a wall of text. A small acupuncture clinic can stay consistent across every visit type — initial, pain-focused, follow-up, or maintenance — using one simple six-part structure that's quick to write and easy to review later.</p>
        <p class="text-[13px] italic text-slate-400">These examples are about documentation structure only, not diagnosis or treatment advice.</p>
    </div>

    <hr class="art-hr">

    {{-- Six-part structure --}}
    <section class="mt-10">
        <h2 class="art-serif text-[19px] font-medium text-slate-950">A six-part structure for every visit</h2>

        @php
        $steps = [
            'Reason for visit',
            'Patient-reported symptoms',
            'Relevant observations',
            'Treatment provided',
            'Patient response',
            'Plan and follow-up',
        ];
        @endphp

        <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
            @foreach($steps as $i => $step)
            <div class="flex items-start gap-3 rounded-lg border border-slate-200 bg-white px-4 py-3">
                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-teal-700 text-[10px] font-bold leading-none text-white">{{ $i + 1 }}</span>
                <span class="text-[13px] font-medium leading-snug text-slate-700">{{ $step }}</span>
            </div>
            @endforeach
        </div>

        <p class="mt-4 text-[13px] leading-relaxed text-slate-400">One template across all visit types. No need to switch formats for an initial visit, a flare-up, or a maintenance session.</p>
    </section>

    <hr class="art-hr">

    {{-- Four accordion examples --}}
    <section class="mt-10">
        <h2 class="art-serif text-[19px] font-medium text-slate-950">Four examples</h2>
        <p class="mt-1.5 text-[13px] text-slate-400">Select any card to expand the visit note.</p>

        @php
        $examples = [
            [
                'num'   => '01',
                'title' => 'General acupuncture visit',
                'badge' => 'Stress & sleep',
                'cls'   => 'badge-teal',
                'rows'  => [
                    ['Reason for visit',          'Follow-up for ongoing stress and sleep disruption.'],
                    ['Patient-reported symptoms', 'Light sleep, frequent waking, afternoon fatigue.'],
                    ['Relevant observations',     'Tension in neck and upper back; patient reports elevated work stress.'],
                    ['Treatment provided',        'Acupuncture session completed as planned.'],
                    ['Patient response',          'Reports feeling calmer post-session.'],
                    ['Plan / follow-up',          'Return in one week; track sleep pattern changes.'],
                ],
            ],
            [
                'num'   => '02',
                'title' => 'Pain-focused visit',
                'badge' => 'Acute pain',
                'cls'   => 'badge-coral',
                'rows'  => [
                    ['Reason for visit',          'Low back pain flare following a lifting incident two days ago.'],
                    ['Patient-reported symptoms', 'Pain increased gradually; worse with bending and transitions.'],
                    ['Relevant observations',     'Guarded movement noted throughout session.'],
                    ['Treatment provided',        "Acupuncture session focused on today's pain presentation."],
                    ['Patient response',          'Reports reduced intensity following treatment.'],
                    ['Plan / follow-up',          'Recheck in three to five days; continue self-care guidance.'],
                ],
            ],
            [
                'num'   => '03',
                'title' => 'Follow-up visit',
                'badge' => 'Progress check',
                'cls'   => 'badge-blue',
                'rows'  => [
                    ['Reason for visit',          'Progress check after prior treatment course.'],
                    ['Patient-reported symptoms', 'Fewer headache episodes this week compared to last.'],
                    ['Relevant observations',     'Improved neck range of motion; lower symptom frequency by report.'],
                    ['Treatment provided',        'Follow-up acupuncture session completed.'],
                    ['Patient response',          'Tolerated well; left comfortable.'],
                    ['Plan / follow-up',          'Continue weekly for two more visits, then reassess frequency.'],
                ],
            ],
            [
                'num'   => '04',
                'title' => 'Wellness or maintenance visit',
                'badge' => 'Maintenance',
                'cls'   => 'badge-green',
                'rows'  => [
                    ['Reason for visit',          'Ongoing maintenance care for stress regulation and recovery.'],
                    ['Patient-reported symptoms', 'No acute complaint; patient wants to sustain current progress.'],
                    ['Relevant observations',     'Stable presentation today.'],
                    ['Treatment provided',        'Maintenance acupuncture session completed.'],
                    ['Patient response',          'Reports relaxation and improved mental clarity.'],
                    ['Plan / follow-up',          'Continue maintenance visits at agreed interval.'],
                ],
            ],
        ];
        @endphp

        <div class="mt-5 space-y-2.5">
            @foreach($examples as $ex)
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <button class="acc-trigger flex w-full items-center gap-4 px-5 py-4 text-left transition-colors hover:bg-slate-50" aria-expanded="false">
                    <span class="w-6 shrink-0 text-[11px] font-bold uppercase tracking-wider text-slate-300">{{ $ex['num'] }}</span>
                    <span class="flex-1 text-[14px] font-medium text-slate-900">{{ $ex['title'] }}</span>
                    <span class="{{ $ex['cls'] }} shrink-0 rounded-full px-3 py-0.5 text-[11px] font-semibold">{{ $ex['badge'] }}</span>
                    <svg class="acc-chevron h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div class="acc-body">
                    <div class="border-t border-slate-100 px-5 py-1">
                        @foreach($ex['rows'] as $row)
                        <div class="note-row flex gap-4 py-3">
                            <span class="w-[130px] shrink-0 text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ $row[0] }}</span>
                            <span class="text-[14px] leading-relaxed text-slate-700">{{ $row[1] }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </section>

    <hr class="art-hr">

    {{-- Closing guidance --}}
    <section class="mt-10">
        <h2 class="art-serif text-[19px] font-medium text-slate-950">What makes a note worth keeping</h2>
        <div class="mt-4 space-y-4 text-[15px] leading-[1.75] text-slate-700">
            <p>A good visit note is one you can return to six months later and understand immediately what was happening with that patient. The six-part structure keeps things scannable without forcing you to write more than you need to.</p>
            <p>The goal is consistent documentation that supports continuity of care — not documentation that adds admin weight to an already full day.</p>
        </div>
    </section>

    <hr class="art-hr">

    {{-- CTA block --}}
    <section class="mt-10">
        <div class="flex flex-col gap-5 rounded-xl border border-slate-200 bg-slate-50 px-6 py-7 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="art-serif text-[18px] font-medium text-slate-950">Practiq is built around this workflow</h2>
                <p class="mt-1.5 text-[13px] leading-relaxed text-slate-600">Notes, intake forms, appointment requests, follow-up tracking, and checkout — in one place, without the overhead.</p>
            </div>
            <a href="https://practiqapp.com/register"
               class="shrink-0 rounded-lg bg-slate-900 px-5 py-3 text-[13px] font-semibold text-white transition hover:bg-teal-800">
                Start free trial
            </a>
        </div>
    </section>

    <hr class="art-hr">

    {{-- Related links --}}
    <section class="mt-10">
        <h2 class="art-serif text-[17px] font-medium text-slate-950">Related pages</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <a href="/" class="rounded-xl border border-slate-200 bg-white px-5 py-4 text-[13px] text-slate-700 transition hover:border-teal-700/30 hover:text-teal-800">Return to Practiq homepage</a>
            <a href="/practice-software-for-acupuncturists" class="rounded-xl border border-slate-200 bg-white px-5 py-4 text-[13px] text-slate-700 transition hover:border-teal-700/30 hover:text-teal-800">Practice software for acupuncturists</a>
            <a href="/blog/small-clinic-visit-notes" class="rounded-xl border border-slate-200 bg-white px-5 py-4 text-[13px] text-slate-700 transition hover:border-teal-700/30 hover:text-teal-800">How small clinics can keep up with visit notes</a>
            <a href="/register" class="rounded-xl border border-slate-200 bg-white px-5 py-4 text-[13px] text-slate-700 transition hover:border-teal-700/30 hover:text-teal-800">Start a free trial</a>
        </div>
    </section>

    {{-- Disclaimer --}}
    <p class="mt-10 pb-4 text-[12px] leading-relaxed text-slate-400">
        These examples are for documentation reference only and do not constitute clinical, diagnostic, or treatment guidance. Practitioners remain responsible for the accuracy and completeness of all patient records.
    </p>

</article>

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
@endsection
