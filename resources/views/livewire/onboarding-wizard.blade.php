<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
    <div class="w-full max-w-2xl">
        <!-- Progress bar -->
        <div class="mb-8">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium text-gray-600">Step {{ $currentStep }} of 6</span>
                <span class="text-sm font-medium text-gray-600">{{ round(($currentStep / 6) * 100) }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-indigo-600 h-2 rounded-full transition-all duration-300" style="width: {{ ($currentStep / 6) * 100 }}%"></div>
            </div>
        </div>

        <!-- Card -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            <!-- Step 1: Welcome -->
            @if ($currentStep === 1)
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-4">Welcome to Practiq</h1>
                    <p class="text-gray-600 text-lg mb-6">Let's get your practice set up in just a few minutes.</p>
                    <div class="space-y-4 text-left max-w-md mx-auto">
                        <div class="flex items-start gap-3">
                            <span class="text-2xl text-indigo-600">✓</span>
                            <div>
                                <h3 class="font-semibold text-gray-900">Manage Your Practice</h3>
                                <p class="text-gray-600 text-sm">Schedule appointments and manage patients</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-2xl text-indigo-600">✓</span>
                            <div>
                                <h3 class="font-semibold text-gray-900">Intake & Consent</h3>
                                <p class="text-gray-600 text-sm">Collect patient information securely</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="text-2xl text-indigo-600">✓</span>
                            <div>
                                <h3 class="font-semibold text-gray-900">Clinical Notes</h3>
                                <p class="text-gray-600 text-sm">Document encounters in your discipline</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Step 2: Practice Details -->
            @if ($currentStep === 2)
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Your Practice Details</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Practice Name</label>
                        <input wire:model="practiceName" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                        @error('practiceName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Address (optional)</label>
                        <input wire:model="practiceAddress" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="123 Main St">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input wire:model="practicePhone" type="tel" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="(555) 123-4567" required>
                        @error('practicePhone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>
            @endif

            <!-- Step 3: Your Profile -->
            @if ($currentStep === 3)
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Your Profile</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Your Name</label>
                        <input wire:model="practitionerName" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                        @error('practitionerName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">License Number</label>
                        <input wire:model="licenseNumber" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="e.g., L.Ac. CA-12345" required>
                        @error('licenseNumber') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Primary Discipline</label>
                        <select wire:model="discipline" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" required>
                            <option value="">Select a discipline</option>
                            <option value="acupuncture">Acupuncture</option>
                            <option value="massage">Massage Therapy</option>
                            <option value="chiropractic">Chiropractic</option>
                            <option value="physiotherapy">Physiotherapy</option>
                        </select>
                        @error('discipline') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                </div>
            @endif

            <!-- Step 4: Disciplines -->
            @if ($currentStep === 4)
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Patient Intake Template</h2>
                <p class="text-gray-600 mb-6">Which disciplines do you treat? (Select all that apply)</p>
                <div class="space-y-3">
                    <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                        <input type="checkbox" wire:model="disciplines" value="acupuncture" class="w-5 h-5 text-indigo-600 rounded">
                        <span class="ml-3 font-medium text-gray-900">Acupuncture</span>
                    </label>
                    <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                        <input type="checkbox" wire:model="disciplines" value="massage" class="w-5 h-5 text-indigo-600 rounded">
                        <span class="ml-3 font-medium text-gray-900">Massage Therapy</span>
                    </label>
                    <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                        <input type="checkbox" wire:model="disciplines" value="chiropractic" class="w-5 h-5 text-indigo-600 rounded">
                        <span class="ml-3 font-medium text-gray-900">Chiropractic</span>
                    </label>
                    <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50">
                        <input type="checkbox" wire:model="disciplines" value="physiotherapy" class="w-5 h-5 text-indigo-600 rounded">
                        <span class="ml-3 font-medium text-gray-900">Physiotherapy</span>
                    </label>
                </div>
                @error('disciplines') <span class="text-red-500 text-sm block mt-2">{{ $message }}</span> @enderror
            @endif

            <!-- Step 5: Legal Forms -->
            @if ($currentStep === 5)
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Legal Forms Setup</h2>
                <p class="text-gray-600 mb-6">Set up legal forms now, or configure them later?</p>
                <div class="space-y-4">
                    <button wire:click="skipLegalSetup" class="w-full p-6 text-left border-2 border-gray-200 rounded-lg hover:border-gray-300 transition">
                        <h3 class="font-semibold text-gray-900 mb-2">I'll set this up later</h3>
                        <p class="text-gray-600 text-sm">Skip for now and configure in Settings later</p>
                    </button>
                    <button wire:click="proceedWithLegalSetup" class="w-full p-6 text-left border-2 border-indigo-300 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                        <h3 class="font-semibold text-indigo-900 mb-2">Set up forms now</h3>
                        <p class="text-indigo-700 text-sm">Create default legal forms before seeing patients</p>
                    </button>
                </div>
            @endif

            <!-- Step 6: Congratulations -->
            @if ($currentStep === 6)
                <div class="text-center">
                    <div class="mb-6"><span class="text-6xl">🎉</span></div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">You're All Set!</h2>
                    <p class="text-gray-600 text-lg mb-8">Your practice is ready. Start scheduling appointments and managing patients.</p>
                </div>
            @endif

            <!-- Navigation -->
            <div class="flex gap-4 mt-8">
                @if ($currentStep > 1)
                    <button wire:click="previousStep" class="flex-1 px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition">Back</button>
                @endif

                @if ($currentStep < 5)
                    <button wire:click="nextStep" class="flex-1 px-6 py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition">Continue</button>
                @elseif ($currentStep === 6)
                    <button wire:click="completeSetup" class="flex-1 px-6 py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition">Go to Dashboard</button>
                @endif
            </div>
        </div>
    </div>
</div>
