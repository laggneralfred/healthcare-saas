<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.google-tag')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practiq Blog — Practical Ideas for Small Clinic Workflows</title>
    <meta name="description" content="Short, practical articles on visit notes, follow-up, and keeping the admin side of a small clinic manageable. Written for solo providers and small clinics.">
    <script src="https://cdn.tailwindcss.com"></script>
    @include('partials.public-fonts')
</head>
<body class="bg-[#fbfaf6] text-slate-900 antialiased">

    <header class="sticky top-0 z-30 border-b border-teal-900/10 bg-[#fbfaf6]/95 backdrop-blur">
        <nav class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8" aria-label="Blog navigation">
            <a href="/" class="flex items-center gap-3" aria-label="Practiq home">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-700 text-lg font-bold text-white shadow-sm shadow-teal-900/10">P</span>
                <span class="text-xl font-bold tracking-tight text-slate-950" style="font-family:'DM Sans',sans-serif">Practiq</span>
            </a>
            <a href="/" class="text-sm font-medium text-slate-600 transition hover:text-teal-800">Back to Home</a>
        </nav>
    </header>

    <main class="mx-auto max-w-[720px] px-4 py-14 sm:px-6">

        {{-- Page header --}}
        <div class="mb-10">
            <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-teal-700">Practiq Blog</p>
            <h1 class="mt-3 text-[30px] font-medium leading-[1.3] text-slate-950 sm:text-[32px]">Practical ideas for small clinic workflows</h1>
            <p class="mt-4 text-[15px] leading-[1.75] text-slate-500">
                Short, practical articles on visit notes, follow-up, and keeping the admin side of a small practice manageable. Written for solo providers and small clinics.
            </p>
        </div>

        <hr class="art-hr mb-10">

        {{-- Article list --}}
        <div class="space-y-4">

            <a href="/blog/acupuncture-visit-note-examples" class="group flex flex-col rounded-xl border border-slate-200 bg-white px-6 py-5 transition hover:border-teal-700/30 hover:shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-teal-700">Documentation Guide · Acupuncture</p>
                <h2 class="mt-2 text-[18px] font-medium leading-snug text-slate-950 group-hover:text-teal-800">Acupuncture Visit Note Examples for Small Practices</h2>
                <p class="mt-2 text-[13px] leading-relaxed text-slate-500">Four ready-to-use examples using a consistent six-part structure — for initial visits, pain flare-ups, follow-ups, and maintenance care.</p>
                <span class="mt-3 text-[12px] font-medium text-teal-700 group-hover:text-teal-800">Read article →</span>
            </a>

            <a href="/blog/small-clinic-visit-notes" class="group flex flex-col rounded-xl border border-slate-200 bg-white px-6 py-5 transition hover:border-teal-700/30 hover:shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-teal-700">Visit Notes · Daily Documentation</p>
                <h2 class="mt-2 text-[18px] font-medium leading-snug text-slate-950 group-hover:text-teal-800">How Small Clinics Can Keep Up With Visit Notes Without Staying Late</h2>
                <p class="mt-2 text-[13px] leading-relaxed text-slate-500">A realistic five-step note rhythm for practices where one person is doing most of the work.</p>
                <span class="mt-3 text-[12px] font-medium text-teal-700 group-hover:text-teal-800">Read article →</span>
            </a>

            <a href="/blog/soap-notes-vs-simple-visit-notes" class="group flex flex-col rounded-xl border border-slate-200 bg-white px-6 py-5 transition hover:border-teal-700/30 hover:shadow-sm">
                <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-teal-700">Documentation Workflow</p>
                <h2 class="mt-2 text-[18px] font-medium leading-snug text-slate-950 group-hover:text-teal-800">SOAP Notes or Simple Visit Notes?</h2>
                <p class="mt-2 text-[13px] leading-relaxed text-slate-500">How small clinics can choose the right note style for each visit — without making documentation harder than it needs to be.</p>
                <span class="mt-3 text-[12px] font-medium text-teal-700 group-hover:text-teal-800">Read article →</span>
            </a>

        </div>

        <hr class="art-hr mt-10">

        {{-- CTA block --}}
        <div class="mt-10 flex flex-col gap-5 rounded-xl border border-slate-200 bg-slate-50 px-6 py-7 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-[18px] font-medium text-slate-950" style="font-family:'Lora',serif">Practiq keeps the day-to-day organized</h2>
                <p class="mt-1.5 text-[13px] leading-relaxed text-slate-600">Notes, forms, follow-up, and checkout for small clinics. 30-day free trial.</p>
            </div>
            <a href="/register" class="shrink-0 rounded-lg bg-slate-900 px-5 py-3 text-[13px] font-semibold text-white transition hover:bg-teal-800">
                Start free trial
            </a>
        </div>

    </main>

    <footer class="border-t border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-400">
        <p>&copy; 2026 Practiq. Built for independent practitioners and small clinics.</p>
    </footer>

</body>
</html>
