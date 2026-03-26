<div>
    @if ($submitted)
        <div class="rounded-2xl bg-white border border-gray-200 shadow-sm p-8 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-teal-50">
                <svg class="h-7 w-7 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h1 class="text-xl font-semibold text-gray-900 mb-2">Consent recorded</h1>
            <p class="text-gray-500 text-sm">
                Thank you, <strong>{{ $record->patient->name }}</strong>.
                Your consent has been recorded.
            </p>
            @if ($record->signed_on)
                <p class="mt-3 text-xs text-gray-400">Signed {{ $record->signed_on->format('M j, Y \a\t g:ia') }}</p>
            @endif
            @if ($intakeUrl)
                <div class="mt-6">
                    <a href="{{ $intakeUrl }}"
                       class="inline-block rounded-lg bg-teal-600 px-5 py-3 text-sm font-semibold text-white hover:bg-teal-700 transition-colors">
                        📋 Also complete your Intake Form
                    </a>
                </div>
            @endif
        </div>
    @else
        <div class="rounded-2xl bg-white border border-gray-200 shadow-sm overflow-hidden">
            {{-- Header --}}
            <div class="bg-teal-600 px-6 py-5">
                <h1 class="text-xl font-semibold text-white">Patient Consent Form</h1>
                <p class="text-teal-100 text-sm mt-1">
                    {{ $record->practice->name }} &mdash; {{ $record->patient->name }}
                </p>
                @if ($record->appointment)
                    <p class="text-teal-200 text-xs mt-1">
                        Appointment: {{ $record->appointment->start_datetime->format('M j, Y \a\t g:ia') }}
                    </p>
                @endif
            </div>

            <form wire:submit="submit" class="px-6 py-6 space-y-5">
                {{-- Consent text --}}
                <div class="rounded-lg bg-gray-50 border border-gray-200 px-4 py-4 text-sm text-gray-700 leading-relaxed">
                    <p class="font-medium text-gray-900 mb-2">Informed Consent for Treatment</p>
                    <p>I consent to the healthcare services provided by {{ $record->practice->name }}. I understand that
                    treatment involves professional judgment and that outcomes cannot be guaranteed. I have the right to ask
                    questions and to refuse or discontinue treatment at any time.</p>
                </div>

                @error('confirmed')
                    <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                        You must confirm consent before submitting.
                    </div>
                @enderror

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Full name of person giving consent <span class="text-red-500">*</span>
                    </label>
                    <input type="text" wire:model="consent_given_by"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 focus:outline-none @error('consent_given_by') border-red-400 @enderror"
                        placeholder="Enter your full name">
                    @error('consent_given_by')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Any questions or notes for the practitioner</label>
                    <textarea wire:model="consent_summary" rows="3"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 focus:outline-none"
                        placeholder="Questions, concerns, or things you want the practitioner to be aware of"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Additional notes</label>
                    <textarea wire:model="notes" rows="2"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 focus:outline-none"
                        placeholder="Anything else you'd like to add"></textarea>
                </div>

                <div class="flex items-start gap-3 rounded-lg bg-teal-50 border border-teal-200 px-4 py-3">
                    <input type="checkbox" wire:model="confirmed" id="confirmed"
                        class="mt-0.5 h-4 w-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                    <label for="confirmed" class="text-sm text-gray-700">
                        I have read and understood the consent statement above, and I agree to the terms of treatment.
                    </label>
                </div>

                <div class="pt-2">
                    <button type="submit"
                        class="w-full rounded-lg bg-teal-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 transition-colors"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove>Sign and submit consent</span>
                        <span wire:loading>Submitting…</span>
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
