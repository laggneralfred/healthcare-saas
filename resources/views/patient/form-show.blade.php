@extends('patient.layout')

@section('title', $formTemplate->name.' | '.$practice->name)

@section('content')
    <section class="rounded-lg border border-slate-200 bg-white p-8 shadow-sm">
        <p class="text-sm font-semibold text-teal-700">{{ $practice->name }}</p>
        <h1 class="mt-3 text-3xl font-bold tracking-tight">{{ $formTemplate->name }}</h1>
        <p class="mt-4 leading-7 text-slate-600">
            Complete this form for your clinic. Your clinical notes are not shown here, and submitted answers wait for staff review.
        </p>

        <form method="POST" action="{{ route('patient.forms.store', ['formSubmission' => $submission->id]) }}" class="mt-8 space-y-6">
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
                        <label class="flex items-start gap-3 text-sm leading-6 text-slate-700">
                            <input type="checkbox" name="{{ $inputName }}" value="1" @checked(old('fields.'.$name)) class="mt-1 rounded border-slate-300 text-teal-700 focus:ring-teal-700">
                            <span>{{ $label }} @if ($required)<span class="text-rose-700">*</span>@endif</span>
                        </label>
                    @else
                        <label for="field-{{ $name }}" class="block text-sm font-medium text-slate-800">
                            {{ $label }} @if ($required)<span class="text-rose-700">*</span>@endif
                        </label>

                        @if ($type === 'textarea')
                            <textarea id="field-{{ $name }}" name="{{ $inputName }}" rows="4" class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-teal-700 focus:ring-teal-700">{{ old('fields.'.$name) }}</textarea>
                        @else
                            <input id="field-{{ $name }}" name="{{ $inputName }}" type="{{ $type }}" value="{{ old('fields.'.$name) }}" class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-teal-700 focus:ring-teal-700">
                        @endif
                    @endif

                    @error('fields.'.$name)
                        <p class="mt-2 text-sm text-rose-700">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" class="rounded-md bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800">
                    Submit form
                </button>
                <a href="{{ route('patient.forms.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">Back to forms</a>
            </div>
        </form>
    </section>
@endsection
