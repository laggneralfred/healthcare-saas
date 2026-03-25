<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased">

    <header class="bg-white border-b border-gray-200">
        <div class="max-w-2xl mx-auto px-4 py-4">
            <p class="text-lg font-semibold text-teal-700">{{ config('app.name') }}</p>
        </div>
    </header>

    <main class="max-w-2xl mx-auto px-4 py-8">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
