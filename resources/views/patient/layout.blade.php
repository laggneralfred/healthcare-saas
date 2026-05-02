<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Patient Portal')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <main class="mx-auto flex min-h-screen @yield('portalWidth', 'max-w-3xl') flex-col justify-center px-6 py-12">
        @isset($practice)
            @include('patient.partials.nav', ['practice' => $practice])
        @endisset

        @yield('content')
    </main>
</body>
</html>
