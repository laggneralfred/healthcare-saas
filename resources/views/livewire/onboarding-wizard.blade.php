@php
    $summary = $this->starterSummary;
    $urls = $this->actionUrls;
    $practice = $summary['practice'] ?? $practice;
    $practitioner = $summary['practitioner'] ?? null;
    $workingHours = $summary['working_hours'] ?? null;
    $appointmentTypes = $summary['appointment_types'] ?? collect();
@endphp

<div style="min-height:100vh;background:#f8fafc;padding:2rem 1rem;">
    <div style="width:100%;max-width:960px;margin:0 auto;">
        <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;padding:2rem;margin-bottom:1rem;">
            <p style="margin:0 0 0.5rem;font-size:0.8125rem;font-weight:700;color:#0f766e;text-transform:uppercase;letter-spacing:0.08em;">Welcome to Practiq</p>
            <h1 style="margin:0;font-size:2rem;line-height:1.2;font-weight:700;color:#0f172a;">Finish setting up {{ $practice?->name ?? 'your practice' }}</h1>
            <p style="margin:0.75rem 0 0;font-size:1rem;line-height:1.6;color:#475569;max-width:720px;">
                We created a few starter settings so you can try Practiq right away. You can change all of this later.
            </p>
            <p style="margin:0.5rem 0 0;font-size:0.8125rem;line-height:1.5;color:#64748b;">
                Practice Type options include General Wellness, TCM Acupuncture, Five Element Acupuncture, Chiropractic, Massage Therapy, and Physiotherapy.
            </p>
        </div>

        <div style="display:grid;grid-template-columns:minmax(0,2fr) minmax(280px,1fr);gap:1rem;align-items:start;">
            <div style="display:flex;flex-direction:column;gap:1rem;">
                <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;padding:1.5rem;">
                    <h2 style="margin:0 0 1rem;font-size:1.125rem;font-weight:700;color:#0f172a;">Starter Settings</h2>

                    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0.75rem;">
                        <div style="border:1px solid #e2e8f0;border-radius:8px;padding:1rem;">
                            <p style="margin:0;font-size:0.75rem;font-weight:600;color:#64748b;">Practice / Practice Type</p>
                            <p style="margin:0.375rem 0 0;font-size:0.9375rem;font-weight:600;color:#0f172a;">{{ $practice?->name ?? 'Not set' }}</p>
                            <p style="margin:0.25rem 0 0;font-size:0.8125rem;color:#64748b;">{{ $summary['practice_type_label'] ?? 'General Wellness' }}</p>
                        </div>

                        <div style="border:1px solid #e2e8f0;border-radius:8px;padding:1rem;">
                            <p style="margin:0;font-size:0.75rem;font-weight:600;color:#64748b;">Initial Practitioner</p>
                            <p style="margin:0.375rem 0 0;font-size:0.9375rem;font-weight:600;color:#0f172a;">{{ $practitioner?->user?->name ?? auth()->user()->name }}</p>
                            <p style="margin:0.25rem 0 0;font-size:0.8125rem;color:#64748b;">{{ $practitioner?->specialty ?? 'Wellness' }}</p>
                        </div>

                        <div style="border:1px solid #e2e8f0;border-radius:8px;padding:1rem;">
                            <p style="margin:0;font-size:0.75rem;font-weight:600;color:#64748b;">Working Hours</p>
                            <p style="margin:0.375rem 0 0;font-size:0.9375rem;font-weight:600;color:#0f172a;">{{ $workingHours['label'] ?? 'Not set' }}</p>
                            <p style="margin:0.25rem 0 0;font-size:0.8125rem;color:#64748b;">{{ $workingHours['time'] ?? 'Add hours before scheduling' }}</p>
                        </div>

                        <div style="border:1px solid #e2e8f0;border-radius:8px;padding:1rem;">
                            <p style="margin:0;font-size:0.75rem;font-weight:600;color:#64748b;">Treatment Rooms</p>
                            <p style="margin:0.375rem 0 0;font-size:0.9375rem;font-weight:600;color:#0f172a;">Not configured yet</p>
                            <p style="margin:0.25rem 0 0;font-size:0.8125rem;color:#64748b;">Practiq does not use treatment rooms in this setup slice.</p>
                        </div>
                    </div>
                </div>

                <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;padding:1.5rem;">
                    <h2 style="margin:0 0 1rem;font-size:1.125rem;font-weight:700;color:#0f172a;">Starter Treatment Types</h2>

                    <div style="display:flex;flex-direction:column;gap:0.75rem;">
                        @forelse ($appointmentTypes as $appointmentType)
                            <div style="display:flex;justify-content:space-between;gap:1rem;border:1px solid #e2e8f0;border-radius:8px;padding:1rem;">
                                <div>
                                    <p style="margin:0;font-size:0.9375rem;font-weight:600;color:#0f172a;">{{ $appointmentType->name }}</p>
                                    <p style="margin:0.25rem 0 0;font-size:0.8125rem;color:#64748b;">{{ $appointmentType->duration_minutes }} minutes</p>
                                </div>
                                <div style="text-align:right;">
                                    <p style="margin:0;font-size:0.9375rem;font-weight:700;color:#0f172a;">
                                        ${{ number_format((float) ($appointmentType->defaultServiceFee?->default_price ?? 0), 2) }}
                                    </p>
                                    <p style="margin:0.25rem 0 0;font-size:0.75rem;color:#64748b;">editable starter fee</p>
                                </div>
                            </div>
                        @empty
                            <p style="margin:0;color:#64748b;font-size:0.9375rem;">No treatment types have been created yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;padding:1.5rem;">
                <h2 style="margin:0 0 1rem;font-size:1.125rem;font-weight:700;color:#0f172a;">Next Steps</h2>

                <div style="display:flex;flex-direction:column;gap:0.625rem;">
                    <a href="{{ $urls['practitioner'] }}" style="display:block;padding:0.75rem 0.875rem;border:1px solid #cbd5e1;border-radius:8px;color:#0f172a;text-decoration:none;font-size:0.875rem;font-weight:600;">Edit practitioner</a>
                    <a href="{{ $urls['working_hours'] }}" style="display:block;padding:0.75rem 0.875rem;border:1px solid #cbd5e1;border-radius:8px;color:#0f172a;text-decoration:none;font-size:0.875rem;font-weight:600;">Edit working hours</a>
                    <a href="{{ $urls['appointment_types'] }}" style="display:block;padding:0.75rem 0.875rem;border:1px solid #cbd5e1;border-radius:8px;color:#0f172a;text-decoration:none;font-size:0.875rem;font-weight:600;">Edit appointment types</a>
                    <a href="{{ $urls['practice'] }}" style="display:block;padding:0.75rem 0.875rem;border:1px solid #cbd5e1;border-radius:8px;color:#0f172a;text-decoration:none;font-size:0.875rem;font-weight:600;">Copy website links</a>
                    <a href="{{ $urls['hipaa'] }}" style="display:block;padding:0.75rem 0.875rem;border:1px solid #cbd5e1;border-radius:8px;color:#0f172a;text-decoration:none;font-size:0.875rem;font-weight:600;">Acknowledge HIPAA/BAA</a>
                    <a href="{{ $urls['ai_disclaimer'] }}" style="display:block;padding:0.75rem 0.875rem;border:1px solid #cbd5e1;border-radius:8px;color:#0f172a;text-decoration:none;font-size:0.875rem;font-weight:600;">Acknowledge AI disclaimer</a>
                </div>

                <button wire:click="finishSetup" style="width:100%;margin-top:1rem;padding:0.875rem 1rem;background:#0f172a;color:#ffffff;border:0;border-radius:8px;font-size:0.9375rem;font-weight:700;cursor:pointer;">
                    Go to Today
                </button>

                <p style="margin:1rem 0 0;font-size:0.8125rem;line-height:1.5;color:#64748b;">
                    Legal and AI acknowledgements still require your explicit review. Starter settings only make the trial workspace easier to explore.
                </p>
            </div>
        </div>
    </div>
</div>
