@extends('layouts.blog-article', [
    'title' => 'How Small Clinics Can Keep Up With Visit Notes Without Staying Late | Practiq',
    'description' => 'A practical, human workflow for small clinics to keep up with visit notes: reason for visit, natural notes, care provided, patient response, and follow-up plan.',
])

@section('content')
    <article class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm sm:p-10">
        <p class="text-sm font-bold uppercase tracking-wide text-teal-800">Daily Documentation</p>
        <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-950 sm:text-5xl">How Small Clinics Can Keep Up With Visit Notes Without Staying Late</h1>
        <p class="mt-6 text-lg leading-8 text-slate-600">
            Small clinics do not usually fall behind on notes because they are careless. They fall behind because the day is full. Appointments run back-to-back, staff is limited, and the same person is often clinician, front desk, and follow-up coordinator all at once.
        </p>
        <p class="mt-4 text-lg leading-8 text-slate-600">
            Another common problem is waiting until the end of the day to write a perfect note from memory. By then, details blur and the task feels heavy. Notes can also end up scattered across paper, text messages, and multiple systems.
        </p>
        <p class="mt-4 text-lg leading-8 text-slate-600">
            A better path is not longer notes. It is a consistent note structure you can finish while the visit is still fresh.
        </p>

        <section class="mt-10 rounded-xl border border-slate-200 bg-[#f8faf7] p-6">
            <h2 class="text-2xl font-bold text-slate-950">A realistic five-step workflow</h2>
            <ol class="mt-4 space-y-2 text-slate-700">
                <li>1. Capture the reason for visit.</li>
                <li>2. Write the visit in natural language first.</li>
                <li>3. Add what care was provided.</li>
                <li>4. Record how the patient or client responded.</li>
                <li>5. Write the plan or follow-up while it is still fresh.</li>
            </ol>
        </section>

        <div class="mt-10 space-y-9">
            <section>
                <h2 class="text-2xl font-bold text-slate-950">1. Capture the reason for visit</h2>
                <p class="mt-3 leading-7 text-slate-600">
                    Open with the patient goal in plain language. Why are they here today? This anchors the note and keeps everything else focused.
                </p>
            </section>

            <section>
                <h2 class="text-2xl font-bold text-slate-950">2. Write the visit in natural language first</h2>
                <p class="mt-3 leading-7 text-slate-600">
                    Before structured fields and checkboxes, write the clinical story in your own words. This is usually faster and clearer. Structure can be added after the core note exists.
                </p>
            </section>

            <section>
                <h2 class="text-2xl font-bold text-slate-950">3. Add what care was provided</h2>
                <p class="mt-3 leading-7 text-slate-600">
                    Record the care delivered during the session so the next visit has proper context and continuity.
                </p>
            </section>

            <section>
                <h2 class="text-2xl font-bold text-slate-950">4. Record response in the room</h2>
                <p class="mt-3 leading-7 text-slate-600">
                    Include immediate response and any meaningful change observed or reported. This helps guide follow-up and future sessions.
                </p>
            </section>

            <section>
                <h2 class="text-2xl font-bold text-slate-950">5. Write the follow-up plan before moving on</h2>
                <p class="mt-3 leading-7 text-slate-600">
                    Add return timing, reminders, or home guidance while it is still clear in your mind. This step prevents care plans from disappearing after checkout.
                </p>
            </section>
        </div>

        <section class="mt-12 rounded-xl border border-slate-200 bg-[#f8faf7] p-6">
            <h2 class="text-2xl font-bold text-slate-950">A small operational shift that helps</h2>
            <p class="mt-3 leading-7 text-slate-600">
                Many clinics see better consistency when they aim to complete most notes the same day, even if the first pass is brief. Later edits can improve detail, but the core documentation and follow-up plan are already captured.
            </p>
            <p class="mt-3 leading-7 text-slate-600">
                Practiq supports this style of workflow with notes, forms, appointment requests with confirmation, follow-up tracking, and checkout visibility in one system. It is one option for teams that want fewer disconnected tools and less end-of-day catch-up.
            </p>
        </section>

        <section class="mt-12">
            <h2 class="text-2xl font-bold text-slate-950">Related pages</h2>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <a href="/blog" class="rounded-xl border border-slate-200 bg-white px-5 py-4 text-slate-700 transition hover:border-teal-700/30 hover:text-teal-800">Browse blog articles</a>
                <a href="/" class="rounded-xl border border-slate-200 bg-white px-5 py-4 text-slate-700 transition hover:border-teal-700/30 hover:text-teal-800">Return to Practiq homepage</a>
                <a href="/practice-software-for-acupuncturists" class="rounded-xl border border-slate-200 bg-white px-5 py-4 text-slate-700 transition hover:border-teal-700/30 hover:text-teal-800">Practice software for acupuncturists</a>
                <a href="/massage-therapy-practice-software" class="rounded-xl border border-slate-200 bg-white px-5 py-4 text-slate-700 transition hover:border-teal-700/30 hover:text-teal-800">Practice software for massage therapists</a>
                <a href="/chiropractic-practice-software" class="rounded-xl border border-slate-200 bg-white px-5 py-4 text-slate-700 transition hover:border-teal-700/30 hover:text-teal-800">Practice software for chiropractors</a>
                <a href="/physiotherapy-practice-software" class="rounded-xl border border-slate-200 bg-white px-5 py-4 text-slate-700 transition hover:border-teal-700/30 hover:text-teal-800">Practice software for physiotherapists</a>
                <a href="/wellness-practice-software" class="rounded-xl border border-slate-200 bg-white px-5 py-4 text-slate-700 transition hover:border-teal-700/30 hover:text-teal-800">Practice software for wellness practitioners</a>
            </div>
        </section>
    </article>
@endsection
