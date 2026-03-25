<div>
    @if ($submitted)
        {{-- Already submitted / just submitted --}}
        <div class="rounded-2xl bg-white border border-gray-200 shadow-sm p-8 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-teal-50">
                <svg class="h-7 w-7 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h1 class="text-xl font-semibold text-gray-900 mb-2">Intake form received</h1>
            <p class="text-gray-500 text-sm">
                Thank you, <strong>{{ $submission->patient->name }}</strong>.
                Your intake information has been sent to the clinic.
            </p>
            @if ($submission->submitted_on)
                <p class="mt-3 text-xs text-gray-400">Submitted {{ $submission->submitted_on->format('M j, Y \a\t g:ia') }}</p>
            @endif
        </div>
    @else
        <div class="rounded-2xl bg-white border border-gray-200 shadow-sm overflow-hidden">
            {{-- Header --}}
            <div class="bg-teal-600 px-6 py-5">
                <h1 class="text-xl font-semibold text-white">Patient Intake Form</h1>
                <p class="text-teal-100 text-sm mt-1">
                    {{ $submission->practice->name }} &mdash; {{ $submission->patient->name }}
                </p>
                @if ($submission->appointment)
                    <p class="text-teal-200 text-xs mt-1">
                        Appointment: {{ $submission->appointment->start_datetime->format('M j, Y \a\t g:ia') }}
                    </p>
                @endif
            </div>

            <form wire:submit="submit" class="px-6 py-6 space-y-5">
                <p class="text-sm text-gray-500">Please complete as many fields as you can. All fields are optional, but at least one is required.</p>

                @error('reason_for_visit')
                    <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                        {{ $message }}
                    </div>
                @enderror

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason for visit</label>
                    <textarea wire:model="reason_for_visit" rows="3"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 focus:outline-none"
                        placeholder="What brings you in today?"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current concerns or symptoms</label>
                    <textarea wire:model="current_concerns" rows="3"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 focus:outline-none"
                        placeholder="Describe your current symptoms or concerns"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Relevant medical history</label>
                    <textarea wire:model="relevant_history" rows="3"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 focus:outline-none"
                        placeholder="Past conditions, surgeries, injuries..."></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Current medications &amp; supplements</label>
                    <textarea wire:model="medications" rows="2"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 focus:outline-none"
                        placeholder="List any medications or supplements you currently take"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Additional notes</label>
                    <textarea wire:model="notes" rows="2"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 focus:outline-none"
                        placeholder="Anything else you'd like us to know"></textarea>
                </div>

                <div class="pt-2">
                    <button type="submit"
                        class="w-full rounded-lg bg-teal-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 transition-colors"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove>Submit intake form</span>
                        <span wire:loading>Submitting…</span>
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
