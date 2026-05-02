@extends('patient.layout')

@section('title', 'Patient Forms')

@section('content')
    <section class="rounded-lg border border-slate-200 bg-white p-8 shadow-sm">
        <p class="text-sm font-semibold text-teal-700">{{ $practice->name }}</p>
        <h1 class="mt-3 text-3xl font-bold tracking-tight">Forms</h1>
        <p class="mt-4 leading-7 text-slate-600">
            Complete forms your clinic has assigned. Submitted forms are reviewed by staff and do not automatically update your patient record.
        </p>

        @if (session('form_status'))
            <div class="mt-5 rounded-md border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900">
                {{ session('form_status') }}
            </div>
        @endif

        <div class="mt-6 divide-y divide-slate-100">
            @forelse ($formSubmissions as $submission)
                <div class="py-4 first:pt-0 last:pb-0">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="font-medium text-slate-900">{{ $submission->formTemplate?->name ?? 'Form' }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $submission->updated_at?->format('M j, Y') }}</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                            {{ \App\Models\FormSubmission::STATUS_OPTIONS[$submission->status] ?? str($submission->status)->replace('_', ' ')->title() }}
                        </span>
                    </div>
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

        <div class="mt-8 flex flex-wrap items-center gap-3">
            <a href="{{ route('patient.dashboard') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">Back to dashboard</a>
            <a href="{{ route('patient.appointment-request.create') }}" class="text-sm font-semibold text-teal-700 hover:text-teal-900">Request appointment</a>
        </div>
    </section>
@endsection
