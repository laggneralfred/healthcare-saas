<div>
    @if($submitted)
        <div class="rounded-2xl bg-white border border-gray-200 shadow-sm p-8 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-teal-50">
                <svg class="h-7 w-7 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h1 class="text-xl font-semibold text-gray-900 mb-2">Appointment request received</h1>
            <p class="text-gray-500 text-sm">
                Thank you. The practice will review your request and contact you to schedule and confirm an appointment.
            </p>
        </div>
    @else
        <div class="rounded-2xl bg-white border border-gray-200 shadow-sm overflow-hidden">
            <div class="bg-teal-600 px-6 py-5">
                <h1 class="text-xl font-semibold text-white">We’re glad to hear from you.</h1>
                <p class="text-teal-100 text-sm mt-1">Let us know when you’d like to come in.</p>
            </div>

            <form wire:submit="submit" class="px-6 py-6 space-y-5">
                <p class="text-sm text-gray-500">
                    This sends a request to {{ $appointmentRequest->practice?->name ?? 'the practice' }} only. Staff will contact you to schedule and confirm an appointment.
                </p>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Preferred days or times</label>
                    <textarea wire:model="preferred_times" rows="4"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 focus:outline-none"
                        placeholder="For example: Tuesday mornings, Thursday after 2, or any afternoon next week"></textarea>
                    @error('preferred_times')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Optional note</label>
                    <textarea wire:model="note" rows="3"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-teal-500 focus:ring-1 focus:ring-teal-500 focus:outline-none"
                        placeholder="Anything you’d like the practice to know"></textarea>
                </div>

                <div class="pt-2">
                    <button type="submit"
                        class="w-full rounded-lg bg-teal-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 transition-colors"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove>Send request</span>
                        <span wire:loading>Sending…</span>
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
