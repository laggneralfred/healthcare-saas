<x-filament-panels::page>
    @if(! ($practice ?? null))
        <div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;padding:32px;text-align:center;color:#6b7280;">
            No practice selected. Use the practice switcher in the top bar.
        </div>
    @else
        <div style="margin-bottom:16px;">
            <p style="margin:0;font-size:13px;color:#6b7280;">Here is what needs your attention today.</p>
        </div>

        @if($practice->is_demo)
            <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:12px 14px;margin-bottom:16px;color:#92400e;font-size:13px;line-height:1.5;">
                <strong>Demo Mode</strong> — this practice uses seeded test data. Some payment, reminder, and reset behavior may differ from a live practice.
            </div>
        @endif

        <div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;padding:16px;margin-bottom:16px;">
            <h2 style="margin:0 0 12px;font-size:16px;font-weight:700;color:#111827;">Alerts</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;">
                @foreach($alerts as $alert)
                    <div style="border:1px solid #e5e7eb;border-radius:8px;padding:14px;">
                        <div style="font-size:12px;font-weight:600;color:#6b7280;text-transform:uppercase;">{{ $alert['label'] }}</div>
                        <div style="font-size:28px;font-weight:700;color:#111827;margin-top:4px;">{{ $alert['count'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div style="display:grid;grid-template-columns:minmax(0,2fr) minmax(320px,1fr);gap:16px;align-items:start;">
            <div style="display:grid;gap:16px;">
                <section style="background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 18px;border-bottom:1px solid #e5e7eb;">
                        <h2 style="margin:0;font-size:16px;font-weight:700;color:#111827;">Today’s Schedule</h2>
                        <a href="{{ $appointmentsUrl }}" style="font-size:13px;font-weight:600;color:#2563eb;text-decoration:none;">All appointments</a>
                    </div>
                    <table style="width:100%;border-collapse:collapse;font-size:13px;">
                        <thead>
                            <tr style="background:#f9fafb;color:#6b7280;">
                                <th style="text-align:left;padding:10px 14px;font-weight:600;">Time</th>
                                <th style="text-align:left;padding:10px 14px;font-weight:600;">Patient</th>
                                <th style="text-align:left;padding:10px 14px;font-weight:600;">Practitioner</th>
                                <th style="text-align:left;padding:10px 14px;font-weight:600;">Status</th>
                                <th style="text-align:right;padding:10px 14px;font-weight:600;">Open</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($todayAppointments as $appointment)
                                @php
                                    $careStatus = $this->careStatusForAppointment($appointment);
                                    $language = $appointment->patient?->preferred_language ?? 'en';
                                @endphp
                                <tr style="border-top:1px solid #f3f4f6;">
                                    <td style="padding:11px 14px;color:#111827;">{{ $appointment->start_datetime?->format('g:i A') ?? '—' }}</td>
                                    <td style="padding:11px 14px;color:#111827;">
                                        <a href="{{ $this->patientUrl($appointment->patient) }}" style="color:#111827;font-weight:600;text-decoration:none;">
                                            {{ $appointment->patient?->name ?? 'Patient' }}
                                        </a>
                                        <div style="display:flex;flex-wrap:wrap;gap:5px;margin-top:5px;">
                                            @if($careStatus)
                                                <span style="display:inline-flex;align-items:center;border-radius:9999px;padding:2px 7px;font-size:11px;font-weight:600;{{ $this->careStatusBadgeStyle($careStatus) }}">Care Status: {{ $careStatus['label'] }}</span>
                                            @endif
                                            @if($language !== 'en')
                                                <span style="display:inline-flex;align-items:center;border-radius:9999px;padding:2px 7px;font-size:11px;font-weight:600;background:#f3f4f6;color:#374151;">{{ $appointment->patient?->preferred_language_label }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td style="padding:11px 14px;color:#374151;">{{ $appointment->practitioner?->user?->name ?? 'Unassigned' }}</td>
                                    <td style="padding:11px 14px;color:#374151;">{{ str($appointment->status)->replace('_', ' ')->title() }}</td>
                                    <td style="padding:11px 14px;text-align:right;">
                                        @if($this->canCheckIn($appointment))
                                            <button wire:click="checkInAppointment({{ $appointment->id }})" type="button" style="margin-right:8px;background:#047857;color:#ffffff;border:0;border-radius:6px;padding:5px 8px;font-size:12px;font-weight:600;cursor:pointer;">
                                                Start Visit
                                            </button>
                                        @endif
                                        @if($this->canOpenCheckout($appointment))
                                            <button wire:click="openCheckout({{ $appointment->id }})" type="button" style="margin-right:8px;background:#2563eb;color:#ffffff;border:0;border-radius:6px;padding:5px 8px;font-size:12px;font-weight:600;cursor:pointer;">
                                                Open Checkout
                                            </button>
                                        @endif
                                        <a href="{{ $this->appointmentUrl($appointment) }}" style="color:#2563eb;font-weight:600;text-decoration:none;">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="padding:24px 14px;text-align:center;color:#9ca3af;">No appointments scheduled today.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </section>

                <section style="background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
                    <div style="padding:16px 18px;border-bottom:1px solid #e5e7eb;">
                        <h2 style="margin:0;font-size:16px;font-weight:700;color:#111827;">Intake & Forms</h2>
                    </div>
                    <table style="width:100%;border-collapse:collapse;font-size:13px;">
                        <tbody>
                            @forelse($intakeItems as $appointment)
                                @php
                                    $missing = collect([
                                        $appointment->medicalHistory?->isComplete() ? null : 'Intake',
                                        $appointment->consentRecord?->isComplete() ? null : 'Consent',
                                    ])->filter()->implode(', ');
                                @endphp
                                <tr style="border-top:1px solid #f3f4f6;">
                                    <td style="padding:11px 14px;color:#111827;font-weight:600;">{{ $appointment->patient?->name ?? 'Patient' }}</td>
                                    <td style="padding:11px 14px;color:#6b7280;">{{ $appointment->start_datetime?->format('g:i A') ?? '—' }}</td>
                                    <td style="padding:11px 14px;color:#991b1b;">Missing {{ $missing }}</td>
                                    <td style="padding:11px 14px;text-align:right;">
                                        @if($this->canMarkInProgress($appointment))
                                            <button wire:click="markInProgress({{ $appointment->id }})" type="button" style="margin-right:8px;background:#d97706;color:#ffffff;border:0;border-radius:6px;padding:5px 8px;font-size:12px;font-weight:600;cursor:pointer;">
                                                Start Visit
                                            </button>
                                        @endif
                                        @if($this->canResendIntakeLink($appointment))
                                            <button wire:click="resendIntakeLink({{ $appointment->id }})" type="button" style="margin-right:8px;background:#0f766e;color:#ffffff;border:0;border-radius:6px;padding:5px 8px;font-size:12px;font-weight:600;cursor:pointer;">
                                                Resend Intake Link
                                            </button>
                                        @endif
                                        @if($this->medicalHistoryUrl($appointment))
                                            <a href="{{ $this->medicalHistoryUrl($appointment) }}" style="color:#2563eb;font-weight:600;text-decoration:none;">Intake</a>
                                        @else
                                            <a href="{{ $this->appointmentUrl($appointment) }}" style="color:#2563eb;font-weight:600;text-decoration:none;">Appointment</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td style="padding:24px 14px;text-align:center;color:#047857;">No missing intake or consent forms for today’s appointments.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </section>
            </div>

            <div style="display:grid;gap:16px;">
                <section style="background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
                    <div style="padding:16px 18px;border-bottom:1px solid #e5e7eb;">
                        <h2 style="margin:0;font-size:16px;font-weight:700;color:#111827;">Appointment Requests</h2>
                        <p style="margin:4px 0 0;font-size:13px;color:#6b7280;">These patients requested a follow-up. Review their preferences and schedule manually.</p>
                    </div>
                    <div style="padding:12px 18px;">
                        @forelse($appointmentRequestItems as $request)
                            <div style="display:flex;justify-content:space-between;gap:12px;padding:10px 0;border-bottom:1px solid #f3f4f6;flex-wrap:wrap;">
                                <div style="min-width:0;flex:1 1 220px;">
                                    <a href="{{ $this->patientUrl($request->patient) }}" style="font-size:13px;font-weight:700;color:#111827;text-decoration:none;">
                                        {{ $request->patient?->name ?? 'Patient' }}
                                    </a>
                                    <div style="font-size:12px;color:#6b7280;margin-top:2px;">Submitted {{ $request->submitted_at?->format('M j, g:i A') ?? 'recently' }}</div>
                                    <div style="font-size:13px;color:#374151;margin-top:6px;white-space:pre-line;">{{ $request->preferred_times }}</div>
                                    @if($request->note)
                                        <div style="font-size:12px;color:#6b7280;margin-top:6px;white-space:pre-line;">{{ $request->note }}</div>
                                    @endif
                                </div>
                                <div style="display:flex;gap:8px;align-items:flex-start;flex-wrap:wrap;justify-content:flex-end;">
                                    <a href="{{ $this->patientUrl($request->patient) }}" style="font-size:12px;font-weight:600;color:#2563eb;text-decoration:none;white-space:nowrap;">View Request</a>
                                    <a href="{{ $this->createAppointmentUrl($request) }}" style="font-size:12px;font-weight:600;color:#047857;text-decoration:none;white-space:nowrap;">Create Appointment</a>
                                    <button wire:click="markAppointmentRequestContacted({{ $request->id }})" type="button" style="background:#f3f4f6;color:#374151;border:0;border-radius:6px;padding:5px 8px;font-size:12px;font-weight:600;cursor:pointer;">
                                        Mark Contacted
                                    </button>
                                    <button wire:click="markAppointmentRequestScheduled({{ $request->id }})" type="button" style="background:#2563eb;color:#ffffff;border:0;border-radius:6px;padding:5px 8px;font-size:12px;font-weight:600;cursor:pointer;">
                                        Mark Scheduled
                                    </button>
                                    <button wire:click="dismissAppointmentRequest({{ $request->id }})" type="button" style="background:#ffffff;color:#6b7280;border:1px solid #d1d5db;border-radius:6px;padding:5px 8px;font-size:12px;font-weight:600;cursor:pointer;">
                                        Dismiss
                                    </button>
                                </div>
                            </div>
                        @empty
                            <p style="margin:0;padding:12px 0;color:#9ca3af;font-size:13px;">No appointment requests waiting.</p>
                        @endforelse
                    </div>
                </section>

                <section style="background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
                    <div style="padding:16px 18px;border-bottom:1px solid #e5e7eb;">
                        <h2 style="margin:0;font-size:16px;font-weight:700;color:#111827;">Arrivals / Waiting</h2>
                    </div>
                    <div style="padding:12px 18px;">
                        @forelse($arrivalItems as $appointment)
                            <div style="display:flex;justify-content:space-between;gap:12px;padding:8px 0;border-bottom:1px solid #f3f4f6;">
                                <div>
                                    <div style="font-size:13px;font-weight:600;color:#111827;">{{ $appointment->patient?->name ?? 'Patient' }}</div>
                                    <div style="font-size:12px;color:#6b7280;">{{ $appointment->start_datetime?->format('g:i A') ?? '—' }} · {{ $appointment->practitioner?->user?->name ?? 'Unassigned' }}</div>
                                </div>
                                <div style="display:flex;gap:8px;align-items:center;">
                                    @if($this->canOpenCheckout($appointment))
                                        <button wire:click="openCheckout({{ $appointment->id }})" type="button" style="background:#2563eb;color:#ffffff;border:0;border-radius:6px;padding:5px 8px;font-size:12px;font-weight:600;cursor:pointer;">
                                            Open Checkout
                                        </button>
                                    @endif
                                    <a href="{{ $this->appointmentUrl($appointment) }}" style="font-size:13px;font-weight:600;color:#2563eb;text-decoration:none;">View</a>
                                </div>
                            </div>
                        @empty
                            <p style="margin:0;padding:12px 0;color:#9ca3af;font-size:13px;">No patients currently waiting.</p>
                        @endforelse
                    </div>
                </section>

                <section style="background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
                    <div style="padding:16px 18px;border-bottom:1px solid #e5e7eb;">
                        <h2 style="margin:0;font-size:16px;font-weight:700;color:#111827;">Ready for Checkout</h2>
                    </div>
                    <div style="padding:12px 18px;">
                        @forelse($checkoutItems as $checkout)
                            <div style="display:flex;justify-content:space-between;gap:12px;padding:8px 0;border-bottom:1px solid #f3f4f6;">
                                <div>
                                    <div style="font-size:13px;font-weight:600;color:#111827;">{{ $checkout->patient?->name ?? 'Patient' }}</div>
                                    <div style="font-size:12px;color:#6b7280;">
                                        {{ $checkout->charge_label }}
                                        @if($checkout->appointment?->start_datetime)
                                            · {{ $checkout->appointment->start_datetime->format('g:i A') }}
                                        @elseif($checkout->encounter?->visit_date)
                                            · {{ $checkout->encounter->visit_date->format('M j') }}
                                        @endif
                                        · {{ $checkout->practitioner?->user?->name ?? 'Unassigned' }}
                                    </div>
                                </div>
                                <a href="{{ $this->checkoutUrl($checkout) }}" style="display:inline-flex;align-items:center;background:#2563eb;color:#ffffff;border:0;border-radius:6px;padding:5px 8px;font-size:12px;font-weight:600;text-decoration:none;white-space:nowrap;">
                                    Collect Payment
                                </a>
                            </div>
                        @empty
                            <p style="margin:0;padding:12px 0;color:#9ca3af;font-size:13px;">No patients ready for checkout right now.</p>
                        @endforelse
                    </div>
                </section>

                <section style="background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 18px;border-bottom:1px solid #e5e7eb;">
                        <h2 style="margin:0;font-size:16px;font-weight:700;color:#111827;">Patient Search / Quick Access</h2>
                        <a href="{{ $createPatientUrl }}" style="font-size:13px;font-weight:600;color:#2563eb;text-decoration:none;">New patient</a>
                    </div>
                    <div style="padding:14px 18px;">
                        <input wire:model.live.debounce.300ms="patientSearch" type="search" placeholder="Search patients" style="width:100%;padding:9px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;">
                        <div style="margin-top:10px;">
                            @forelse($patientResults as $patient)
                                <a href="{{ $this->patientUrl($patient) }}" style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f3f4f6;color:#111827;text-decoration:none;">
                                    <span style="font-size:13px;font-weight:600;">{{ $patient->name }}</span>
                                    <span style="font-size:12px;color:#6b7280;">{{ $patient->phone ?? $patient->email ?? 'Open' }}</span>
                                </a>
                            @empty
                                <p style="margin:10px 0 0;color:#9ca3af;font-size:13px;">No matching patients.</p>
                            @endforelse
                        </div>
                        <a href="{{ $patientsUrl }}" style="display:inline-block;margin-top:12px;font-size:13px;font-weight:600;color:#2563eb;text-decoration:none;">Open patient list</a>
                    </div>
                </section>
            </div>
        </div>
    @endif
</x-filament-panels::page>
