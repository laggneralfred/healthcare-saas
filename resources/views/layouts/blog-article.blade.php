<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.google-tag')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Practiq Blog' }}</title>
    <meta name="description" content="{{ $description ?? 'Practical workflow articles for small clinics.' }}">
    <script src="https://cdn.tailwindcss.com"></script>
    @include('partials.public-fonts')
    <style>
        /* Accordion */
        .acc-body    { max-height: 0; overflow: hidden; transition: max-height 0.32s ease; }
        .acc-body.open { max-height: 920px; }
        .acc-chevron { transition: transform 0.32s ease; }
        .acc-chevron.open { transform: rotate(180deg); }
        /* Category badges */
        .badge-teal  { background: #E1F5EE; color: #0F6E56; }
        .badge-coral { background: #FAECE7; color: #993C1D; }
        .badge-blue  { background: #E6F1FB; color: #185FA5; }
        .badge-green { background: #EAF3DE; color: #3B6D11; }
        /* Note table row separators */
        .note-row + .note-row { border-top: 0.5px solid #e8e8e4; }
        /* Thin section separator */
        .art-hr { border: none; border-top: 1px solid #e8e8e4; margin-top: 2.5rem; }
        @media (prefers-color-scheme: dark) {
            .badge-teal  { background: #0F3B2E; color: #5ECBA7; }
            .badge-coral { background: #3D1207; color: #F4A68C; }
            .badge-blue  { background: #0D2A4A; color: #6AAEE8; }
            .badge-green { background: #1A2E06; color: #8BC34A; }
        }
    </style>
    @stack('head')
</head>
<body class="bg-[#fbfaf6] text-slate-900 antialiased">
    <header class="sticky top-0 z-30 border-b border-teal-900/10 bg-[#fbfaf6]/95 backdrop-blur">
        <nav class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8" aria-label="Blog navigation">
            <a href="/" class="flex items-center gap-3" aria-label="Practiq home">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-700 text-lg font-bold text-white shadow-sm shadow-teal-900/10">P</span>
                <span class="text-xl font-bold tracking-tight text-slate-950" style="font-family:'DM Sans',sans-serif">Practiq</span>
            </a>
            <a href="/blog" class="text-sm font-medium text-slate-600 transition hover:text-teal-800">Back to Blog</a>
        </nav>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
        @yield('content')
    </main>

    <footer class="border-t border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-400">
        <p>&copy; 2026 Practiq. Built for independent practitioners and small clinics.</p>
    </footer>
</body>
</html>
