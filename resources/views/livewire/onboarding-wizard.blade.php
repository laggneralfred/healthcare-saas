<div style="overflow-y:auto;min-height:100vh;background:linear-gradient(135deg,#eff6ff 0%,#e0e7ff 100%);display:flex;align-items:flex-start;justify-content:center;padding:1rem;">
    <div style="width:100%;max-width:640px;padding-top:2rem;padding-bottom:2rem;">

        {{-- Progress bar --}}
        <div style="margin-bottom:2rem;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;">
                <span style="font-size:0.875rem;font-weight:500;color:#4b5563;">Step {{ $currentStep }} of 6</span>
                <span style="font-size:0.875rem;font-weight:500;color:#4b5563;">{{ round(($currentStep / 6) * 100) }}%</span>
            </div>
            <div style="width:100%;background:#e5e7eb;border-radius:9999px;height:8px;">
                <div style="background:#4f46e5;height:8px;border-radius:9999px;transition:width 0.3s ease;width:{{ ($currentStep / 6) * 100 }}%;"></div>
            </div>
        </div>

        {{-- Card --}}
        <div style="background:#ffffff;border-radius:0.75rem;box-shadow:0 4px 24px rgba(0,0,0,0.10);padding:2rem;">

            {{-- Step 1: Welcome --}}
            @if ($currentStep === 1)
                <div style="text-align:center;">
                    <h1 style="font-size:1.875rem;font-weight:700;color:#111827;margin:0 0 1rem;">Welcome to Practiq</h1>
                    <p style="color:#6b7280;font-size:1.125rem;margin:0 0 1.5rem;">Let's get your practice set up in just a few minutes.</p>
                    <div style="display:flex;flex-direction:column;gap:1rem;text-align:left;max-width:420px;margin:0 auto 2rem;">
                        <div style="display:flex;align-items:flex-start;gap:0.75rem;">
                            <span style="font-size:1.375rem;color:#4f46e5;flex-shrink:0;">✓</span>
                            <div>
                                <p style="margin:0;font-weight:600;color:#111827;">Manage Your Practice</p>
                                <p style="margin:0.125rem 0 0;font-size:0.875rem;color:#6b7280;">Schedule appointments and manage patients</p>
                            </div>
                        </div>
                        <div style="display:flex;align-items:flex-start;gap:0.75rem;">
                            <span style="font-size:1.375rem;color:#4f46e5;flex-shrink:0;">✓</span>
                            <div>
                                <p style="margin:0;font-weight:600;color:#111827;">Intake &amp; Consent</p>
                                <p style="margin:0.125rem 0 0;font-size:0.875rem;color:#6b7280;">Collect patient information securely</p>
                            </div>
                        </div>
                        <div style="display:flex;align-items:flex-start;gap:0.75rem;">
                            <span style="font-size:1.375rem;color:#4f46e5;flex-shrink:0;">✓</span>
                            <div>
                                <p style="margin:0;font-weight:600;color:#111827;">Clinical Notes</p>
                                <p style="margin:0.125rem 0 0;font-size:0.875rem;color:#6b7280;">Document encounters in your discipline</p>
                            </div>
                        </div>
                    </div>
                    <button wire:click="nextStep"
                        style="display:block;width:100%;padding:0.75rem 1.5rem;background:#1a1a1a;color:#ffffff;border:none;border-radius:0.5rem;font-size:0.9375rem;font-weight:600;cursor:pointer;">
                        Get Started
                    </button>
                </div>
            @endif

            {{-- Step 2: Practice Details --}}
            @if ($currentStep === 2)
                <h2 style="font-size:1.5rem;font-weight:700;color:#111827;margin:0 0 1.5rem;">Your Practice Details</h2>
                <div style="display:flex;flex-direction:column;gap:1rem;">
                    <div>
                        <label style="display:block;font-size:0.875rem;font-weight:500;color:#374151;margin-bottom:0.25rem;">Practice Name</label>
                        <input wire:model="practiceName" type="text"
                            style="width:100%;padding:0.5rem 1rem;border:1px solid #d1d5db;border-radius:0.5rem;font-size:0.9375rem;box-sizing:border-box;" required>
                        @error('practiceName') <span style="color:#ef4444;font-size:0.875rem;">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label style="display:block;font-size:0.875rem;font-weight:500;color:#374151;margin-bottom:0.25rem;">Address <span style="color:#9ca3af;">(optional)</span></label>
                        <input wire:model="practiceAddress" type="text" placeholder="123 Main St"
                            style="width:100%;padding:0.5rem 1rem;border:1px solid #d1d5db;border-radius:0.5rem;font-size:0.9375rem;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block;font-size:0.875rem;font-weight:500;color:#374151;margin-bottom:0.25rem;">Phone</label>
                        <input wire:model="practicePhone" type="tel" placeholder="(555) 123-4567"
                            style="width:100%;padding:0.5rem 1rem;border:1px solid #d1d5db;border-radius:0.5rem;font-size:0.9375rem;box-sizing:border-box;" required>
                        @error('practicePhone') <span style="color:#ef4444;font-size:0.875rem;">{{ $message }}</span> @enderror
                    </div>
                </div>
            @endif

            {{-- Step 3: Default Settings --}}
            @if ($currentStep === 3)
                <h2 style="font-size:1.5rem;font-weight:700;color:#111827;margin:0 0 0.5rem;">Default Settings</h2>
                <p style="color:#6b7280;font-size:0.9375rem;margin:0 0 1.5rem;">These can be changed any time in Settings.</p>
                <div style="display:flex;flex-direction:column;gap:1.25rem;">
                    <div>
                        <label style="display:block;font-size:0.875rem;font-weight:500;color:#374151;margin-bottom:0.25rem;">Default Appointment Duration</label>
                        <select wire:model="defaultAppointmentDuration"
                            style="width:100%;padding:0.5rem 1rem;border:1px solid #d1d5db;border-radius:0.5rem;font-size:0.9375rem;background:#fff;box-sizing:border-box;">
                            <option value="15">15 minutes</option>
                            <option value="30">30 minutes</option>
                            <option value="45">45 minutes</option>
                            <option value="60">60 minutes</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:0.875rem;font-weight:500;color:#374151;margin-bottom:0.25rem;">Default Appointment Reminder</label>
                        <select wire:model="defaultReminderHours"
                            style="width:100%;padding:0.5rem 1rem;border:1px solid #d1d5db;border-radius:0.5rem;font-size:0.9375rem;background:#fff;box-sizing:border-box;">
                            <option value="24">24 hours before</option>
                            <option value="48">48 hours before</option>
                            <option value="0">No reminder</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:0.875rem;font-weight:500;color:#374151;margin-bottom:0.25rem;">Timezone</label>
                        <select wire:model="timezone"
                            style="width:100%;padding:0.5rem 1rem;border:1px solid #d1d5db;border-radius:0.5rem;font-size:0.9375rem;background:#fff;box-sizing:border-box;">
                            <option value="America/Los_Angeles">Pacific Time (PT)</option>
                            <option value="America/Denver">Mountain Time (MT)</option>
                            <option value="America/Chicago">Central Time (CT)</option>
                            <option value="America/New_York">Eastern Time (ET)</option>
                            <option value="America/Anchorage">Alaska Time (AKT)</option>
                            <option value="Pacific/Honolulu">Hawaii Time (HT)</option>
                            <option value="America/Toronto">Toronto (ET)</option>
                            <option value="America/Vancouver">Vancouver (PT)</option>
                            <option value="Europe/London">London (GMT/BST)</option>
                            <option value="UTC">UTC</option>
                        </select>
                    </div>
                </div>
            @endif

            {{-- Step 4: Your Profile --}}
            @if ($currentStep === 4)
                <h2 style="font-size:1.5rem;font-weight:700;color:#111827;margin:0 0 1.5rem;">Your Profile</h2>
                <div style="display:flex;flex-direction:column;gap:1rem;">
                    <div>
                        <label style="display:block;font-size:0.875rem;font-weight:500;color:#374151;margin-bottom:0.25rem;">Your Name</label>
                        <input wire:model="practitionerName" type="text"
                            style="width:100%;padding:0.5rem 1rem;border:1px solid #d1d5db;border-radius:0.5rem;font-size:0.9375rem;box-sizing:border-box;" required>
                        @error('practitionerName') <span style="color:#ef4444;font-size:0.875rem;">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label style="display:block;font-size:0.875rem;font-weight:500;color:#374151;margin-bottom:0.25rem;">License Number</label>
                        <input wire:model="licenseNumber" type="text" placeholder="e.g., L.Ac. CA-12345"
                            style="width:100%;padding:0.5rem 1rem;border:1px solid #d1d5db;border-radius:0.5rem;font-size:0.9375rem;box-sizing:border-box;" required>
                        @error('licenseNumber') <span style="color:#ef4444;font-size:0.875rem;">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label style="display:block;font-size:0.875rem;font-weight:500;color:#374151;margin-bottom:0.25rem;">Primary Discipline</label>
                        <select wire:model="discipline"
                            style="width:100%;padding:0.5rem 1rem;border:1px solid #d1d5db;border-radius:0.5rem;font-size:0.9375rem;background:#fff;box-sizing:border-box;" required>
                            <option value="">Select a discipline</option>
                            <option value="acupuncture">Acupuncture</option>
                            <option value="massage">Massage Therapy</option>
                            <option value="chiropractic">Chiropractic</option>
                            <option value="physiotherapy">Physiotherapy</option>
                        </select>
                        @error('discipline') <span style="color:#ef4444;font-size:0.875rem;">{{ $message }}</span> @enderror
                    </div>
                </div>
            @endif

            {{-- Step 5: Legal Forms --}}
            @if ($currentStep === 5)
                <h2 style="font-size:1.5rem;font-weight:700;color:#111827;margin:0 0 0.5rem;">Legal Forms Setup</h2>
                <p style="color:#6b7280;font-size:0.9375rem;margin:0 0 1.5rem;">Set up legal forms now, or configure them later?</p>
                <div style="display:flex;flex-direction:column;gap:1rem;">
                    <button wire:click="skipLegalSetup"
                        style="width:100%;padding:1.25rem 1.5rem;text-align:left;border:2px solid #e5e7eb;border-radius:0.5rem;background:#fff;cursor:pointer;">
                        <p style="margin:0;font-weight:600;color:#111827;">I'll set this up later</p>
                        <p style="margin:0.25rem 0 0;font-size:0.875rem;color:#6b7280;">Skip for now and configure in Settings later</p>
                    </button>
                    <button wire:click="proceedWithLegalSetup"
                        style="width:100%;padding:1.25rem 1.5rem;text-align:left;border:2px solid #a5b4fc;border-radius:0.5rem;background:#eef2ff;cursor:pointer;">
                        <p style="margin:0;font-weight:600;color:#3730a3;">Set up forms now</p>
                        <p style="margin:0.25rem 0 0;font-size:0.875rem;color:#4f46e5;">Create default legal forms before seeing patients</p>
                    </button>
                </div>
            @endif

            {{-- Step 6: Congratulations --}}
            @if ($currentStep === 6)
                <div style="text-align:center;">
                    <div style="font-size:4rem;margin-bottom:1.5rem;">🎉</div>
                    <h2 style="font-size:1.875rem;font-weight:700;color:#111827;margin:0 0 1rem;">You're All Set!</h2>
                    <p style="color:#6b7280;font-size:1.125rem;margin:0 0 2rem;">Your practice is ready. Start scheduling appointments and managing patients.</p>
                </div>
            @endif

            {{-- Navigation --}}
            @if ($currentStep >= 2 && $currentStep <= 4)
                <div style="display:flex;gap:0.75rem;margin-top:2rem;">
                    <button wire:click="previousStep"
                        style="flex:1;padding:0.75rem 1.5rem;background:#ffffff;color:#374151;border:1px solid #d1d5db;border-radius:0.5rem;font-size:0.9375rem;font-weight:600;cursor:pointer;">
                        Back
                    </button>
                    <button wire:click="nextStep"
                        style="flex:1;padding:0.75rem 1.5rem;background:#1a1a1a;color:#ffffff;border:none;border-radius:0.5rem;font-size:0.9375rem;font-weight:600;cursor:pointer;">
                        Continue
                    </button>
                </div>
            @elseif ($currentStep === 5)
                <div style="display:flex;gap:0.75rem;margin-top:2rem;">
                    <button wire:click="previousStep"
                        style="flex:1;padding:0.75rem 1.5rem;background:#ffffff;color:#374151;border:1px solid #d1d5db;border-radius:0.5rem;font-size:0.9375rem;font-weight:600;cursor:pointer;">
                        Back
                    </button>
                </div>
            @elseif ($currentStep === 6)
                <div style="display:flex;gap:0.75rem;margin-top:2rem;">
                    <button wire:click="previousStep"
                        style="flex:1;padding:0.75rem 1.5rem;background:#ffffff;color:#374151;border:1px solid #d1d5db;border-radius:0.5rem;font-size:0.9375rem;font-weight:600;cursor:pointer;">
                        Back
                    </button>
                    <button wire:click="completeSetup"
                        style="flex:1;padding:0.75rem 1.5rem;background:#1a1a1a;color:#ffffff;border:none;border-radius:0.5rem;font-size:0.9375rem;font-weight:600;cursor:pointer;">
                        Go to Dashboard
                    </button>
                </div>
            @endif

        </div>{{-- end card --}}

        {{-- Skip link --}}
        <div style="text-align:center;margin-top:1rem;">
            <button wire:click="skipOnboarding"
                style="background:none;border:none;font-size:0.875rem;color:#9ca3af;cursor:pointer;">
                Skip for now →
            </button>
        </div>

    </div>
</div>
