<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    @livewireStyles
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f8fafc; color: #1e293b; min-height: 100vh; }
        input, textarea, select, button { font-family: inherit; }
    </style>
</head>
<body>

    <header style="background: #ffffff; border-bottom: 1px solid #e2e8f0; padding: 1rem 1.5rem;">
        <div style="max-width: 48rem; margin: 0 auto; display: flex; align-items: center; gap: 0.75rem;">
            <div style="width: 2rem; height: 2rem; background: #0d9488; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                <span style="color: white; font-size: 1rem;">+</span>
            </div>
            <span style="font-weight: 700; font-size: 1.125rem; color: #0f172a;">{{ $practiceNameHeader ?? config('app.name') }}</span>
        </div>
    </header>

    <main style="max-width: 48rem; margin: 0 auto; padding: 1.5rem 1rem 4rem;">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
