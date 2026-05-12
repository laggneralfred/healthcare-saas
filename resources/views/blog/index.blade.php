<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.google-tag')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practiq Blog | Practical Notes, Workflow, and Follow-Up Tips for Small Clinics</title>
    <meta name="description" content="Practical blog articles for small clinics on visit notes, follow-up workflows, and day-to-day documentation structure for acupuncture, massage, chiropractic, physiotherapy, and wellness practices.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet" />
    <style>
        body { font-family: 'Instrument Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#fbfaf6] text-slate-900 antialiased">
    <header class="sticky top-0 z-30 border-b border-teal-900/10 bg-[#fbfaf6]/95 backdrop-blur">
        <nav class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8" aria-label="Blog navigation">
            <a href="/" class="flex items-center gap-3" aria-label="Practiq home">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-700 text-lg font-bold text-white shadow-sm shadow-teal-900/10">P</span>
                <span class="text-xl font-bold tracking-tight text-slate-950">Practiq</span>
            </a>
            <a href="/" class="text-sm font-semibold text-slate-700 transition hover:text-teal-800">Back to Home</a>
        </nav>
    </header>

    <main class="mx-auto max-w-5xl px-4 py-14 sm:px-6 lg:px-8">
        <section class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm sm:p-10">
            <p class="text-sm font-bold uppercase tracking-wide text-teal-800">Practiq Blog</p>
            <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-950 sm:text-5xl">Practical ideas for small clinic workflows</h1>
            <p class="mt-6 text-lg leading-8 text-slate-600">
                This is a working library of plain-language articles for solo providers and small clinics. The focus is practical: notes, follow-up, and keeping day-to-day workflows manageable.
            </p>
        </section>

        <section class="mt-10 grid gap-5">
            <a href="/blog/small-clinic-visit-notes" class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm transition hover:border-teal-700/30 hover:shadow-md">
                <h2 class="text-2xl font-bold text-slate-950">How Small Clinics Can Keep Up With Visit Notes Without Staying Late</h2>
                <p class="mt-3 leading-7 text-slate-600">A realistic five-step note workflow for clinics with limited staff and full schedules.</p>
            </a>
            <a href="/blog/acupuncture-visit-note-examples" class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm transition hover:border-teal-700/30 hover:shadow-md">
                <h2 class="text-2xl font-bold text-slate-950">Acupuncture Visit Note Examples for Small Practices</h2>
                <p class="mt-3 leading-7 text-slate-600">Simple documentation structures and example note formats for general, pain-focused, follow-up, and maintenance visits.</p>
            </a>
        </section>
    </main>

    <footer class="border-t border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-500">
        <p>&copy; 2026 Practiq. Built for independent practitioners and small clinics.</p>
    </footer>
</body>
</html>
