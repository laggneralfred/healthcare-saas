@extends('patient.layout')

@section('title', 'Request Appointment')
@section('portalWidth', 'max-w-2xl')

@section('content')
    <section class="rounded-lg border border-slate-200 bg-white p-8 shadow-sm">
        <p class="text-sm font-semibold text-teal-700">{{ $practice->name }}</p>
        <h1 class="mt-3 text-3xl font-bold tracking-tight">Request an appointment</h1>
        <p class="mt-4 leading-7 text-slate-600">
            Tell the clinic what works for you. This does not book an appointment automatically; staff will contact you to confirm a time.
        </p>

        <form method="POST" action="{{ route('patient.appointment-request.store') }}" class="mt-8 space-y-6">
            @csrf

            <div>
                <label for="appointment_type_id" class="block text-sm font-medium text-slate-800">What kind of visit would you like? <span class="text-rose-700">*</span></label>
                <select id="appointment_type_id" name="appointment_type_id" required class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-teal-700 focus:ring-teal-700" onchange="if (this.value) window.location='{{ route('patient.appointment-request.create') }}?appointment_type_id=' + this.value">
                    <option value="">Choose a visit type</option>
                    @foreach ($appointmentTypes as $appointmentType)
                        <option value="{{ $appointmentType->id }}" @selected((int) old('appointment_type_id', $selectedAppointmentType?->id) === $appointmentType->id)>
                            {{ $appointmentType->name }}
                        </option>
                    @endforeach
                </select>
                @error('appointment_type_id')
                    <p class="mt-2 text-sm text-rose-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="practitioner_id" class="block text-sm font-medium text-slate-800">Do you prefer a practitioner?</label>
                <select id="practitioner_id" name="practitioner_id" class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-teal-700 focus:ring-teal-700" @disabled(! $selectedAppointmentType)>
                    <option value="">No preference</option>
                    @foreach ($practitioners as $practitioner)
                        <option value="{{ $practitioner->id }}" @selected((int) old('practitioner_id') === $practitioner->id)>
                            {{ $practitioner->user?->name ?? 'Practitioner #'.$practitioner->id }}
                        </option>
                    @endforeach
                </select>
                @if (! $selectedAppointmentType)
                    <p class="mt-2 text-sm text-slate-500">Choose a visit type first so we can show the right practitioners.</p>
                @elseif ($suggestedPractitioner)
                    <p class="mt-2 text-sm text-teal-800">
                        Suggested: {{ $suggestedPractitioner->user?->name ?? 'Practitioner #'.$suggestedPractitioner->id }} — you have seen them before.
                    </p>
                @endif
                @error('practitioner_id')
                    <p class="mt-2 text-sm text-rose-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="preferred_days_times" class="block text-sm font-medium text-slate-800">Preferred days and times <span class="text-rose-700">*</span></label>
                <textarea id="preferred_days_times" name="preferred_days_times" rows="5" required class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-teal-700 focus:ring-teal-700" placeholder="For example: Tuesday morning or Thursday after 2">{{ old('preferred_days_times') }}</textarea>
                @error('preferred_days_times')
                    <p class="mt-2 text-sm text-rose-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="message" class="block text-sm font-medium text-slate-800">Message</label>
                <textarea id="message" name="message" rows="4" class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-teal-700 focus:ring-teal-700" placeholder="Anything you want the clinic to know">{{ old('message') }}</textarea>
                @error('message')
                    <p class="mt-2 text-sm text-rose-700">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" class="rounded-md bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800">
                    Send request
                </button>
                <a href="{{ route('patient.dashboard') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">Back to dashboard</a>
            </div>
        </form>
    </section>
@endsection
