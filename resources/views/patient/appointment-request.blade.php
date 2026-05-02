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
                <label for="requested_service" class="block text-sm font-medium text-slate-800">Requested service</label>
                <input id="requested_service" name="requested_service" type="text" value="{{ old('requested_service') }}" class="mt-2 block w-full rounded-md border-slate-300 shadow-sm focus:border-teal-700 focus:ring-teal-700" placeholder="Follow-up visit, massage, acupuncture, etc.">
                @error('requested_service')
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
