@extends('layouts.blog-article', [
    'title'       => 'What to Include in a Visit Note | Practiq',
    'description' => 'A practical guide for small clinics on what to include in a visit note so documentation stays useful, clear, and manageable.',
])

@push('head')
<link rel="canonical" href="https://practiqapp.com/blog/what-to-include-in-a-visit-note">
<style>
    .art-serif { font-family: 'Lora', Georgia, serif; }
    .art-sans  { font-family: 'DM Sans', sans-serif; }
</style>
@endpush

@section('content')
<article class="art-sans mx-auto max-w-[720px]">
    <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-teal-700">Visit Notes · Documentation Basics</p>

    <h1 class="art-serif mt-3 text-[30px] font-medium leading-[1.3] text-slate-950 sm:text-[32px]">
        What to Include in a Visit Note
    </h1>

    <p class="mt-4 text-base leading-[1.75] text-slate-500">
        A useful visit note does not need to be long. It needs to help you remember what happened, support continuity, and make the next visit easier.
    </p>

    <div class="mt-6 space-y-4 text-[15px] leading-[1.75] text-slate-700">
        <p>Most small clinics do not struggle with notes because they do not care. They struggle because the day is full. One visit runs long, a form is missing, someone needs to reschedule, and by late afternoon the charting you planned to finish between appointments is still waiting.</p>
        <p>When time is tight, the best note is usually the one you can finish while the visit is still fresh. You do not need a wall of text. You need the parts that make the record useful later.</p>
    </div>

    <hr class="art-hr">

    <section class="mt-10">
        <h2 class="art-serif text-[19px] font-medium text-slate-950">The six details that keep a note useful</h2>
        <p class="mt-4 text-[15px] leading-[1.75] text-slate-700">For many routine visits, this simple structure is enough:</p>

        <div class="mt-5 space-y-0 overflow-hidden rounded-xl border border-slate-200 bg-white">
            @foreach([
                ['Reason for visit', 'Why the patient or client came in today.'],
                ['What changed since last time', 'What improved, worsened, or stayed the same.'],
                ['Important observations', 'Only the observations that matter for continuity and next decisions.'],
                ['Care provided', 'What you did during the visit.'],
                ['Patient/client response', 'How they responded in the room or immediately after care.'],
                ['Plan or follow-up', 'What happens next and what to check at the next visit.'],
            ] as [$label, $text])
            <div class="note-row flex gap-4 px-5 py-3.5">
                <span class="w-[180px] shrink-0 text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ $label }}</span>
                <span class="text-[14px] leading-relaxed text-slate-700">{{ $text }}</span>
            </div>
            @endforeach
        </div>
    </section>

    <hr class="art-hr">

    <section class="mt-10">
        <h2 class="art-serif text-[19px] font-medium text-slate-950">When to use a more formal note</h2>
        <div class="mt-4 space-y-4 text-[15px] leading-[1.75] text-slate-700">
            <p>Some visits need more structure than a short narrative. More complex cases, changing presentations, insurance-related documentation, or records likely to be reviewed more closely may benefit from a formal SOAP-style format.</p>
            <p>That does not mean every visit needs to be written the same way. Many clinics do better with a simple default for routine care and a more structured format when the situation calls for it.</p>
            <p class="text-[13px] italic leading-relaxed text-slate-400">Requirements vary by profession, payer, and location. This article is general information, not clinical, legal, or billing advice.</p>
        </div>
    </section>

    <hr class="art-hr">

    <section class="mt-10">
        <h2 class="art-serif text-[19px] font-medium text-slate-950">Write for your next visit, not for perfection</h2>
        <div class="mt-4 space-y-4 text-[15px] leading-[1.75] text-slate-700">
            <p>A practical note helps your future self. If you can open the chart next week and quickly understand what happened, how the person responded, and what you planned next, the note has done its job.</p>
            <p>Practiq supports this approach with both simple visit note workflows and SOAP-style structure when needed, so small clinics can choose the level of detail that fits the visit without forcing one rigid format all day.</p>
        </div>
    </section>

    <hr class="art-hr">

    <section class="mt-10">
        <h2 class="art-serif text-[17px] font-medium text-slate-950">Related pages</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <a href="/blog" class="rounded-xl border border-slate-200 bg-white px-5 py-4 text-[13px] text-slate-700 transition hover:border-teal-700/30 hover:text-teal-800">Browse all blog articles</a>
            <a href="/blog/small-clinic-visit-notes" class="rounded-xl border border-slate-200 bg-white px-5 py-4 text-[13px] text-slate-700 transition hover:border-teal-700/30 hover:text-teal-800">Keeping up with visit notes</a>
            <a href="/blog/soap-notes-vs-simple-visit-notes" class="rounded-xl border border-slate-200 bg-white px-5 py-4 text-[13px] text-slate-700 transition hover:border-teal-700/30 hover:text-teal-800">SOAP notes or simple notes?</a>
            <a href="/register" class="rounded-xl border border-slate-200 bg-white px-5 py-4 text-[13px] text-slate-700 transition hover:border-teal-700/30 hover:text-teal-800">Start a free trial</a>
        </div>
    </section>

    <p class="mt-10 pb-4 text-[12px] leading-relaxed text-slate-400">
        This article is for general informational purposes only and does not replace clinical judgment or profession-specific documentation requirements.
    </p>
</article>
@endsection
