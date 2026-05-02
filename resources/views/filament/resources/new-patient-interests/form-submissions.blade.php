<div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
    <div class="mb-3 flex items-center justify-between gap-3">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Form submissions</h3>
        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $formSubmissions->count() }} total</span>
    </div>

    @forelse ($formSubmissions as $submission)
        <div class="border-t border-gray-100 py-4 first:border-t-0 first:pt-0 last:pb-0 dark:border-gray-800">
            <div class="flex flex-wrap items-center gap-2">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ $submission->formTemplate?->name ?? 'Form' }}
                </p>
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                    {{ \App\Models\FormSubmission::STATUS_OPTIONS[$submission->status] ?? str($submission->status)->replace('_', ' ')->title() }}
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $submission->updated_at?->format('M j, Y g:i A') }}
                </span>
                @if ($submission->patient_id)
                    <span class="rounded-full bg-teal-50 px-2 py-0.5 text-xs font-medium text-teal-700 dark:bg-teal-950 dark:text-teal-200">
                        Linked to patient #{{ $submission->patient_id }}
                    </span>
                @endif
            </div>

            @if ($submission->submitted_data_json)
                <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                    @foreach ($submission->submitted_data_json as $key => $value)
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                {{ str($key)->replace('_', ' ')->title() }}
                            </dt>
                            <dd class="mt-1 whitespace-pre-line text-sm text-gray-800 dark:text-gray-200">
                                {{ is_bool($value) ? ($value ? 'Yes' : 'No') : ($value ?: '—') }}
                            </dd>
                        </div>
                    @endforeach
                </dl>
            @else
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Not submitted yet.</p>
            @endif
        </div>
    @empty
        <p class="text-sm text-gray-500 dark:text-gray-400">No forms have been sent yet.</p>
    @endforelse
</div>
