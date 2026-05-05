@extends('patient.layout')

@section('title', 'Patient Dashboard')

@section('content')
    <section class="rounded-lg border border-slate-200 bg-white p-8 shadow-sm">
        <p class="text-sm font-semibold text-teal-700">{{ $practice->name }}</p>
        <h1 class="mt-3 text-3xl font-bold tracking-tight">Welcome, {{ $patient->preferred_name ?: $patient->first_name ?: 'there' }}</h1>
        @if (session('appointment_request_status'))
            <div class="mt-5 rounded-md border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900">
                {{ session('appointment_request_status') }}
            </div>
        @endif
        <p class="mt-4 leading-7 text-slate-600">
            This is your secure Practiq patient dashboard for {{ $practice->name }}.
        </p>
        <p class="mt-3 text-sm leading-6 text-slate-500">
            Clinical notes are not shown here.
        </p>
        <div class="mt-8 flex flex-wrap items-center gap-3">
            <a href="{{ route('patient.appointment-request.create') }}" class="rounded-md bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800">
                Request appointment
            </a>
            <a href="{{ route('patient.forms.index') }}" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                View Forms
            </a>
            <form method="POST" action="{{ route('patient.logout') }}">
                @csrf
                <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                    Log out
                </button>
            </form>
        </div>
    </section>

    <section class="mt-6 rounded-lg border border-slate-200 bg-white p-8 shadow-sm">
        <h2 class="text-lg font-semibold tracking-tight">Forms</h2>
        <p class="mt-2 text-sm leading-6 text-slate-500">
            Forms assigned by your clinic appear here. Submitted forms wait for staff review and do not change your record automatically.
        </p>
        <div class="mt-4 divide-y divide-slate-100">
            @forelse ($formSubmissions as $submission)
                <div class="py-4 first:pt-0 last:pb-0">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="font-medium text-slate-900">{{ $submission->formTemplate?->name ?? 'Form' }}</p>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                            {{ \App\Models\FormSubmission::STATUS_OPTIONS[$submission->status] ?? str($submission->status)->replace('_', ' ')->title() }}
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">{{ $submission->updated_at?->format('M j, Y') }}</p>
                    @if ($submission->status === \App\Models\FormSubmission::STATUS_PENDING)
                        <a href="{{ route('patient.forms.show', ['formSubmission' => $submission->id]) }}" class="mt-3 inline-flex rounded-md bg-teal-700 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-800">
                            Complete form
                        </a>
                    @endif
                </div>
            @empty
                <p class="text-sm text-slate-500">No forms are assigned right now.</p>
            @endforelse
        </div>
    </section>

    <section class="mt-6 rounded-lg border border-slate-200 bg-white p-8 shadow-sm">
        <h2 class="text-lg font-semibold tracking-tight">Appointment requests</h2>
        <p class="mt-2 text-sm leading-6 text-slate-500">
            These are requests only. Clinic staff will contact you to confirm an appointment time.
        </p>
        <div class="mt-4 divide-y divide-slate-100">
            @forelse ($appointmentRequests as $request)
                <div class="py-4 first:pt-0 last:pb-0">
                    @php
                        $requestLabel = $request->appointmentType?->name ?? ($request->requested_service ?: 'Appointment request');
                    @endphp
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="font-medium text-slate-900">{{ $requestLabel }}</p>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                            {{ str($request->status)->replace('_', ' ')->title() }}
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">
                        Practitioner preference:
                        {{ $request->practitioner?->user?->name ?? 'No preference' }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500">Submitted {{ $request->submitted_at?->format('M j, Y') ?? $request->created_at?->format('M j, Y') }}</p>
                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $request->preferred_times }}</p>
                </div>
            @empty
                <p class="text-sm text-slate-500">No appointment requests yet.</p>
            @endforelse
        </div>
    </section>
@endsection
