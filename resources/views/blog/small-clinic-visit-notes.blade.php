@extends('layouts.blog-article', [
    'title'       => 'How Small Clinics Can Keep Up With Visit Notes | Practiq',
    'description' => 'A realistic five-step note rhythm for small clinics with full schedules. Practical documentation guidance for solo providers who need to finish their notes without staying late.',
])

@push('head')
<link rel="canonical" href="https://practiqapp.com/blog/small-clinic-visit-notes">
<style>
    .art-serif { font-family: 'Lora', Georgia, serif; }
    .art-sans  { font-family: 'DM Sans', sans-serif; }
</style>
@endpush

@section('content')
<article class="art-sans mx-auto max-w-[720px]">

    {{-- Category label --}}
    <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-teal-700">Visit Notes · Daily Documentation</p>

    {{-- H1 --}}
    <h1 class="art-serif mt-3 text-[30px] font-medium leading-[1.3] text-slate-950 sm:text-[32px]">
        How Small Clinics Can Keep Up With Visit Notes Without Staying Late
    </h1>

    {{-- Deck --}}
    <p class="mt-4 text-base leading-[1.75] text-slate-500">
        A realistic note-writing rhythm for practices where one person is doing most of the work.
    </p>

    {{-- Intro --}}
    <div class="mt-6 space-y-4 text-[15px] leading-[1.75] text-slate-700">
        <p>Small clinics don't fall behind on notes because they are careless. They fall behind because the day is full. Appointments run back-to-back. Staff is limited. And the same person is often clinician, front desk, and follow-up coordinator all at once.</p>
        <p>The usual failure mode: waiting until the end of the day to write a perfect note from memory. By then, the details have blurred and the task feels heavy. The answer isn't longer notes. It's a rhythm you can actually repeat.</p>
    </div>

    <hr class="art-hr">

    {{-- Five-step workflow --}}
    <section class="mt-10">
        <h2 class="art-serif text-[19px] font-medium text-slate-950">A five-step note rhythm you can sustain</h2>
        <p class="mt-2 text-[13px] text-slate-400">The same sequence works across visit types. Practice it until it's automatic.</p>

        @php
        $steps = [
            'Capture the reason for visit',
            'Describe what happened in your own words',
            'Record what care was provided',
            'Note the patient response',
            'Write the follow-up plan',
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

        <p class="mt-4 text-[13px] leading-relaxed text-slate-400">Write while the visit is still fresh. A clear note finished today beats a detailed note that never quite gets done.</p>
    </section>

    <hr class="art-hr">

    {{-- Section: Why rhythm beats format --}}
    <section class="mt-10">
        <h2 class="art-serif text-[19px] font-medium text-slate-950">Rhythm matters more than format</h2>
        <div class="mt-4 space-y-4 text-[15px] leading-[1.75] text-slate-700">
            <p>The best note is the one you actually write. A clear, concise note completed close to the visit is more useful than a perfectly structured one written from memory three days later.</p>
            <p>This is especially true when you're carrying clinical work and admin at the same time. A documentation system that looks ideal on paper but breaks under real schedule pressure isn't helping. Aim for a note that takes three to five minutes, captures what matters, and keeps the next appointment starting with good context.</p>
        </div>
    </section>

    <hr class="art-hr">

    {{-- Section: Match format to visit --}}
    <section class="mt-10">
        <h2 class="art-serif text-[19px] font-medium text-slate-950">Match the note style to the visit</h2>
        <div class="mt-4 space-y-4 text-[15px] leading-[1.75] text-slate-700">
            <p>Routine follow-ups often only need a concise narrative. More complex cases, insurance-related visits, or new patient encounters may warrant structured SOAP documentation. Both have a place in the same clinic, sometimes on the same day.</p>
            <p>A practical default for many small practices: simple notes for routine visits, SOAP mode when the record needs to be more explicit. Easy to remember. Easy to maintain.</p>
        </div>
    </section>

    <hr class="art-hr">

    {{-- CTA block --}}
    <section class="mt-10">
        <div class="flex flex-col gap-5 rounded-xl border border-slate-200 bg-slate-50 px-6 py-7 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="art-serif text-[18px] font-medium text-slate-950">Practiq keeps the note workflow simple</h2>
                <p class="mt-1.5 text-[13px] leading-relaxed text-slate-600">Simple notes and SOAP mode, intake forms, follow-up, and checkout — in one place.</p>
            </div>
            <a href="/register" class="shrink-0 rounded-lg bg-slate-900 px-5 py-3 text-[13px] font-semibold text-white transition hover:bg-teal-800">
                Start free trial
            </a>
        </div>
    </section>

    <hr class="art-hr">

    {{-- Related links --}}
    <section class="mt-10">
        <h2 class="art-serif text-[17px] font-medium text-slate-950">Related pages</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <a href="/blog" class="rounded-xl border border-slate-200 bg-white px-5 py-4 text-[13px] text-slate-700 transition hover:border-teal-700/30 hover:text-teal-800">Browse all blog articles</a>
            <a href="/" class="rounded-xl border border-slate-200 bg-white px-5 py-4 text-[13px] text-slate-700 transition hover:border-teal-700/30 hover:text-teal-800">Return to Practiq homepage</a>
            <a href="/blog/acupuncture-visit-note-examples" class="rounded-xl border border-slate-200 bg-white px-5 py-4 text-[13px] text-slate-700 transition hover:border-teal-700/30 hover:text-teal-800">Acupuncture visit note examples</a>
            <a href="/register" class="rounded-xl border border-slate-200 bg-white px-5 py-4 text-[13px] text-slate-700 transition hover:border-teal-700/30 hover:text-teal-800">Start a free trial</a>
        </div>
    </section>

    {{-- Disclaimer --}}
    <p class="mt-10 pb-4 text-[12px] leading-relaxed text-slate-400">
        These articles are for general informational purposes only and do not constitute clinical, billing, or legal guidance. Practitioners remain responsible for their own documentation standards.
    </p>

</article>
@endsection
