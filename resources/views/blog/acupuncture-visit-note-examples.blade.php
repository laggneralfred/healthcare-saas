@extends('layouts.blog-article', [
    'title' => 'Acupuncture Visit Note Examples for Small Practices | Practiq',
    'description' => 'Practical acupuncture visit note examples for small practices: reason for visit, symptoms, observations, treatment provided, response, and follow-up plan.',
])

@section('content')
    <article class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm sm:p-10">
        <p class="text-sm font-bold uppercase tracking-wide text-teal-800">Documentation Structure</p>
        <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-950 sm:text-5xl">Acupuncture Visit Note Examples for Small Practices</h1>
        <p class="mt-6 text-lg leading-8 text-slate-600">
            Acupuncture notes do not need to become a wall of text. A small clinic can stay consistent with simple sections that are easy to write and easy to review later.
            These examples are about documentation structure, not diagnosis or treatment advice.
        </p>

        <section class="mt-10 rounded-xl border border-slate-200 bg-[#f8faf7] p-6">
            <h2 class="text-2xl font-bold text-slate-950">Use a six-part structure</h2>
            <ol class="mt-4 space-y-2 text-slate-700">
                <li>1. Reason for visit</li>
                <li>2. Patient-reported symptoms</li>
                <li>3. Relevant observations</li>
                <li>4. Treatment provided</li>
                <li>5. Patient response</li>
                <li>6. Plan and follow-up</li>
            </ol>
        </section>

        <div class="mt-10 space-y-9">
            <section>
                <h2 class="text-2xl font-bold text-slate-950">Example: General acupuncture visit</h2>
                <p class="mt-3 leading-7 text-slate-600">
                    Reason for visit: Follow-up for ongoing stress and sleep disruption. Patient-reported symptoms: Light sleep, frequent waking, afternoon fatigue. Relevant observations: Tension in neck and upper back, patient reports stress at work. Treatment provided: Acupuncture session completed as planned. Patient response: Reports feeling calmer post-session. Plan/follow-up: Return in one week and track sleep pattern changes.
                </p>
            </section>

            <section>
                <h2 class="text-2xl font-bold text-slate-950">Example: Pain-focused visit</h2>
                <p class="mt-3 leading-7 text-slate-600">
                    Reason for visit: Low back pain flare after lifting. Patient-reported symptoms: Pain increased over two days, worse with bending. Relevant observations: Guarded movement and reduced comfort during transitions. Treatment provided: Acupuncture session focused on today&apos;s pain presentation. Patient response: Reports reduced intensity after treatment. Plan/follow-up: Recheck in three to five days and continue self-care guidance.
                </p>
            </section>

            <section>
                <h2 class="text-2xl font-bold text-slate-950">Example: Follow-up visit</h2>
                <p class="mt-3 leading-7 text-slate-600">
                    Reason for visit: Progress check after prior treatment course. Patient-reported symptoms: Fewer headache episodes this week. Relevant observations: Improved neck range and lower symptom frequency by report. Treatment provided: Follow-up acupuncture session completed. Patient response: Tolerated well and left comfortable. Plan/follow-up: Continue weekly for two more visits, then reassess frequency.
                </p>
            </section>

            <section>
                <h2 class="text-2xl font-bold text-slate-950">Example: Wellness or maintenance visit</h2>
                <p class="mt-3 leading-7 text-slate-600">
                    Reason for visit: Maintenance care for stress regulation and recovery. Patient-reported symptoms: No acute complaint, wants to maintain current progress. Relevant observations: Stable presentation today. Treatment provided: Maintenance acupuncture session completed. Patient response: Reports relaxation and improved clarity after session. Plan/follow-up: Continue maintenance visits at agreed interval.
                </p>
            </section>
        </div>

        <section class="mt-12 rounded-xl border border-slate-200 bg-[#f8faf7] p-6">
            <h2 class="text-2xl font-bold text-slate-950">Keep it practical</h2>
            <p class="mt-3 leading-7 text-slate-600">
                The goal is consistent documentation that supports continuity of care without adding unnecessary admin load. Practiq is designed around this small-practice workflow: notes, forms, appointment requests with confirmation, follow-up tracking, and checkout visibility in one place.
            </p>
        </section>

        <section class="mt-12">
            <h2 class="text-2xl font-bold text-slate-950">Related pages</h2>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <a href="/" class="rounded-xl border border-slate-200 bg-white px-5 py-4 text-slate-700 transition hover:border-teal-700/30 hover:text-teal-800">Return to Practiq homepage</a>
                <a href="/practice-software-for-acupuncturists" class="rounded-xl border border-slate-200 bg-white px-5 py-4 text-slate-700 transition hover:border-teal-700/30 hover:text-teal-800">Practice software for acupuncturists</a>
                <a href="/blog/small-clinic-visit-notes" class="rounded-xl border border-slate-200 bg-white px-5 py-4 text-slate-700 transition hover:border-teal-700/30 hover:text-teal-800">How small clinics can keep up with visit notes</a>
                <a href="/register" class="rounded-xl border border-slate-200 bg-white px-5 py-4 text-slate-700 transition hover:border-teal-700/30 hover:text-teal-800">Start a free trial</a>
            </div>
        </section>
    </article>
@endsection
