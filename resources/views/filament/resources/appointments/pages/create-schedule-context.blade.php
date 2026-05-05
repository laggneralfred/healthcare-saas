@if($scheduleContext)
    @php
        $date = $scheduleContext['date'];
        $workingWindows = $scheduleContext['workingWindows'];
        $timeBlocks = $scheduleContext['timeBlocks'];
        $appointments = $scheduleContext['appointments'];
        $suggestedSlots = $scheduleContext['suggestedSlots'];
    @endphp

    <div style="background:#ffffff;border:1px solid #d1d5db;border-radius:8px;padding:14px 16px;margin:0 0 16px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <div>
                <h2 style="margin:0;font-size:15px;font-weight:700;color:#111827;">Schedule Context</h2>
                <p style="margin:3px 0 0;font-size:12px;color:#6b7280;">
                    {{ $scheduleContext['practitioner']?->user?->name ?? ($scheduleContext['appointmentType']?->name ? 'Any eligible practitioner' : 'Selected practitioner') }} · {{ $date->format('l, M j, Y') }}
                </p>
            </div>
            @if($scheduleContext['calendarUrl'])
                <a
                    href="{{ $scheduleContext['calendarUrl'] }}"
                    style="display:inline-flex;align-items:center;border-radius:6px;background:#2563eb;color:#ffffff;padding:7px 10px;font-size:12px;font-weight:700;text-decoration:none;"
                >
                    Open Calendar for this Practitioner
                </a>
            @endif
        </div>

        <section style="margin-top:14px;border-top:1px solid #e5e7eb;padding-top:14px;">
            <h3 style="margin:0 0 8px;font-size:12px;font-weight:700;text-transform:uppercase;color:#6b7280;">Suggested Openings</h3>
            @if($suggestedSlots->isNotEmpty())
                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                    @foreach($suggestedSlots as $slot)
                        <a
                            href="{{ $slot['use_url'] }}"
                            style="display:inline-flex;align-items:center;gap:6px;border:1px solid #bfdbfe;border-radius:6px;background:#eff6ff;color:#1d4ed8;padding:7px 9px;font-size:12px;font-weight:700;text-decoration:none;"
                        >
                            Use this time · {{ $slot['label'] }}
                        </a>
                    @endforeach
                </div>
            @else
                <div style="font-size:13px;color:#6b7280;">No matching openings found in the next 14 days. Try a different practitioner, service, or date range.</div>
            @endif
        </section>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin-top:14px;border-top:1px solid #e5e7eb;padding-top:14px;">
            <section>
                <h3 style="margin:0 0 7px;font-size:12px;font-weight:700;text-transform:uppercase;color:#6b7280;">Working Hours</h3>
                @forelse($workingWindows as $window)
                    <div style="font-size:13px;color:#111827;margin-bottom:5px;">
                        {{ $window['start']->format('g:i A') }} - {{ $window['end']->format('g:i A') }}
                    </div>
                @empty
                    <div style="font-size:13px;color:#9ca3af;">No working hours on this date.</div>
                @endforelse
            </section>

            <section>
                <h3 style="margin:0 0 7px;font-size:12px;font-weight:700;text-transform:uppercase;color:#6b7280;">Time Blocks</h3>
                @forelse($timeBlocks as $block)
                    <div style="font-size:13px;color:#111827;margin-bottom:7px;">
                        <div>{{ $block->starts_at?->format('g:i A') }} - {{ $block->ends_at?->format('g:i A') }}</div>
                        <div style="font-size:12px;color:#6b7280;">{{ $block->reason ?: str($block->block_type)->replace('_', ' ')->title() }}</div>
                    </div>
                @empty
                    <div style="font-size:13px;color:#9ca3af;">No blocks on this date.</div>
                @endforelse
            </section>

            <section>
                <h3 style="margin:0 0 7px;font-size:12px;font-weight:700;text-transform:uppercase;color:#6b7280;">Existing Appointments</h3>
                @forelse($appointments as $appointment)
                    <div style="font-size:13px;color:#111827;margin-bottom:7px;">
                        <div>{{ $appointment->start_datetime?->format('g:i A') }} - {{ $appointment->end_datetime?->format('g:i A') }}</div>
                        <div style="font-size:12px;color:#6b7280;">
                            {{ $appointment->patient?->name ?? 'Patient' }}
                            @if($appointment->appointmentType?->name)
                                · {{ $appointment->appointmentType->name }}
                            @endif
                        </div>
                    </div>
                @empty
                    <div style="font-size:13px;color:#9ca3af;">No appointments for this practitioner on this date.</div>
                @endforelse
            </section>
        </div>
    </div>
@endif
