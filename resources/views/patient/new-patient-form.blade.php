<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $formTemplate->name }} | {{ $practice->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-stone-50 text-stone-900">
    <main class="mx-auto flex min-h-screen w-full max-w-3xl items-center px-4 py-10">
        <section class="w-full rounded-lg border border-stone-200 bg-white p-6 shadow-sm sm:p-8">
            <p class="text-sm font-medium text-teal-700">{{ $practice->name }}</p>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight">{{ $formTemplate->name }}</h1>
            <p class="mt-3 text-sm leading-6 text-stone-600">
                Please complete the form below so the clinic can review your request. This does not create a patient record or portal account.
            </p>

            <form method="POST" action="{{ route('patient.new-patient-form.store', ['token' => $token]) }}" class="mt-8 space-y-6">
                @csrf

                @foreach ($fields as $field)
                    @php
                        $name = $field['name'] ?? '';
                        $label = $field['label'] ?? $name;
                        $type = $field['type'] ?? 'text';
                        $required = (bool) ($field['required'] ?? false);
                        $inputName = "fields[{$name}]";
                    @endphp

                    <div>
                        @if ($type === 'checkbox')
                            <label class="flex items-start gap-3 text-sm leading-6 text-stone-700">
                                <input type="checkbox" name="{{ $inputName }}" value="1" @checked(old('fields.'.$name)) class="mt-1 rounded border-stone-300 text-teal-700 focus:ring-teal-700">
                                <span>{{ $label }} @if ($required)<span class="text-rose-700">*</span>@endif</span>
                            </label>
                        @else
                            <label for="field-{{ $name }}" class="block text-sm font-medium text-stone-800">
                                {{ $label }} @if ($required)<span class="text-rose-700">*</span>@endif
                            </label>

                            @if ($type === 'textarea')
                                <textarea id="field-{{ $name }}" name="{{ $inputName }}" rows="4" class="mt-2 block w-full rounded-md border-stone-300 shadow-sm focus:border-teal-700 focus:ring-teal-700">{{ old('fields.'.$name) }}</textarea>
                            @else
                                <input id="field-{{ $name }}" name="{{ $inputName }}" type="{{ $type }}" value="{{ old('fields.'.$name) }}" class="mt-2 block w-full rounded-md border-stone-300 shadow-sm focus:border-teal-700 focus:ring-teal-700">
                            @endif
                        @endif

                        @error('fields.'.$name)
                            <p class="mt-2 text-sm text-rose-700">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="inline-flex w-full justify-center rounded-md bg-teal-700 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-teal-800 sm:w-auto">
                        Submit forms
                    </button>
                    <span class="text-sm text-stone-500">The clinic will review your answers before contacting you.</span>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
