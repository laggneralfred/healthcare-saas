<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Existing Patient Access</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <main class="mx-auto max-w-xl px-6 py-12">
        <section class="rounded-lg border border-slate-200 bg-white p-8 shadow-sm">
            <p class="text-sm font-semibold text-teal-700">{{ $practice->name }}</p>
            <h1 class="mt-3 text-3xl font-bold tracking-tight">Existing patient access</h1>
            <p class="mt-4 leading-7 text-slate-600">
                Enter your email and we will send a secure access link if we find a matching patient record.
            </p>

            @if(session('status'))
                <div class="mt-6 rounded-md border border-teal-200 bg-teal-50 p-4 text-sm leading-6 text-teal-900">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ $action }}" class="mt-8 space-y-5">
                @csrf

                <label class="block">
                    <span class="text-sm font-semibold">Email</span>
                    <input type="email" name="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                    @error('email') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
                </label>

                <button type="submit" class="rounded-md bg-teal-700 px-5 py-3 text-sm font-bold text-white hover:bg-teal-800">
                    Send secure access link
                </button>
            </form>
        </section>
    </main>
</body>
</html>
