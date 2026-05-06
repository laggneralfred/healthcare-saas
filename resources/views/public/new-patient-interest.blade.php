<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Patient Request</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <main class="mx-auto max-w-2xl px-6 py-12">
        @if(! $practice)
            <section class="rounded-lg border border-slate-200 bg-white p-8 shadow-sm">
                <h1 class="text-2xl font-bold">New patient requests are not available right now.</h1>
                <p class="mt-4 leading-7 text-slate-600">Please contact the clinic directly.</p>
            </section>
        @else
            <section class="rounded-lg border border-slate-200 bg-white p-8 shadow-sm">
                <p class="text-sm font-semibold text-teal-700">{{ $practice->name }}</p>
                <h1 class="mt-3 text-3xl font-bold tracking-tight">Request to become a new patient</h1>
                <p class="mt-4 leading-7 text-slate-600">
                    Share a little about what you are looking for. Staff will review your request and contact you if they are able to accept new patients.
                </p>

                <form method="POST" action="{{ $storeRoute ?? route('new-patient.interest.store') }}" class="mt-8 space-y-5">
                    @csrf
                    <div class="grid gap-5 sm:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-semibold">First name</span>
                            <input name="first_name" value="{{ old('first_name') }}" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                            @error('first_name') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
                        </label>
                        <label class="block">
                            <span class="text-sm font-semibold">Last name</span>
                            <input name="last_name" value="{{ old('last_name') }}" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                            @error('last_name') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
                        </label>
                    </div>

                    <label class="block">
                        <span class="text-sm font-semibold">Email</span>
                        <input type="email" name="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                        @error('email') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-semibold">Phone</span>
                        <input name="phone" value="{{ old('phone') }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                        @error('phone') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-semibold">Preferred service</span>
                        <input name="preferred_service" value="{{ old('preferred_service') }}" placeholder="Acupuncture, massage, consultation..." class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">
                        @error('preferred_service') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-semibold">Preferred days/times</span>
                        <textarea name="preferred_days_times" rows="3" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">{{ old('preferred_days_times') }}</textarea>
                        @error('preferred_days_times') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-semibold">Message</span>
                        <textarea name="message" rows="4" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2">{{ old('message') }}</textarea>
                        @error('message') <span class="mt-1 block text-sm text-red-700">{{ $message }}</span> @enderror
                    </label>

                    <label class="flex gap-3 rounded-md bg-slate-50 p-3 text-sm leading-6 text-slate-700">
                        <input type="checkbox" name="contact_acknowledgement" value="1" required class="mt-1">
                        <span>I understand the clinic may contact me about this request.</span>
                    </label>
                    @error('contact_acknowledgement') <span class="block text-sm text-red-700">{{ $message }}</span> @enderror

                    <button type="submit" class="rounded-md bg-teal-700 px-5 py-3 text-sm font-bold text-white hover:bg-teal-800">
                        Send request
                    </button>
                </form>
            </section>
        @endif
    </main>
</body>
</html>
