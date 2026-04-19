<x-filament-widgets::widget>
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:0.75rem;padding:1.5rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
            <div>
                <p style="margin:0;font-size:1rem;font-weight:600;color:#0f172a;">Today's Schedule</p>
                <p style="margin:0.125rem 0 0;font-size:0.8125rem;color:#64748b;">{{ now()->format('l, F j') }}</p>
            </div>
            <span style="font-size:0.8125rem;color:#64748b;">{{ $appointments->count() }} {{ \Illuminate\Support\Str::plural('appointment', $appointments->count()) }}</span>
        </div>

        @if($appointments->isEmpty())
            <div style="background:#f8fafc;border:1px dashed #cbd5e1;border-radius:0.5rem;padding:2rem;text-align:center;color:#94a3b8;font-size:0.875rem;">
                No appointments today.
            </div>
        @else
            <div style="display:flex;flex-direction:column;gap:0.5rem;">
                @foreach($appointments as $appointment)
                    @php
                        $status     = $appointment->status;
                        $statusName = is_object($status) ? class_basename($status) : (string) $status;
                        $statusKey  = strtolower($statusName);
                        $statusStyle = match (true) {
                            str_contains($statusKey, 'complete')      => 'background:#dcfce7;color:#166534;',
                            str_contains($statusKey, 'cancel')        => 'background:#f1f5f9;color:#64748b;',
                            str_contains($statusKey, 'progress')      => 'background:#fef3c7;color:#92400e;',
                            str_contains($statusKey, 'checkout')      => 'background:#e0e7ff;color:#3730a3;',
                            str_contains($statusKey, 'closed')        => 'background:#f1f5f9;color:#64748b;',
                            default                                    => 'background:#dbeafe;color:#1e40af;',
                        };
                        $statusLabel = ucfirst(str_replace('_', ' ', \Illuminate\Support\Str::snake($statusName)));
                        $hasEncounter = $appointment->encounter !== null;
                    @endphp

                    <div style="display:flex;align-items:center;gap:1rem;padding:0.75rem 1rem;border:1px solid #e2e8f0;border-radius:0.5rem;background:#ffffff;">
                        <div style="min-width:5.5rem;">
                            <p style="margin:0;font-size:0.9375rem;font-weight:600;color:#0f172a;">{{ $appointment->start_datetime->format('g:i A') }}</p>
                            @if($appointment->appointmentType?->duration_minutes)
                                <p style="margin:0.125rem 0 0;font-size:0.75rem;color:#94a3b8;">{{ $appointment->appointmentType->duration_minutes }} min</p>
                            @endif
                        </div>

                        <div style="flex:1;min-width:0;">
                            <p style="margin:0;font-size:0.9375rem;font-weight:600;color:#0f172a;">{{ $appointment->patient?->name ?? 'Unknown patient' }}</p>
                            <p style="margin:0.125rem 0 0;font-size:0.8125rem;color:#64748b;">
                                {{ $appointment->practitioner?->user?->name ?? '—' }}
                                @if($appointment->appointmentType?->name)
                                    <span style="color:#cbd5e1;">·</span> {{ $appointment->appointmentType->name }}
                                @endif
                            </p>
                        </div>

                        <span style="display:inline-block;padding:0.25rem 0.625rem;border-radius:9999px;font-size:0.75rem;font-weight:600;{{ $statusStyle }}">
                            {{ $statusLabel }}
                        </span>

                        <div style="display:flex;gap:0.5rem;">
                            @if(! $hasEncounter)
                                <a href="{{ $newVisitUrlFn($appointment) }}" style="display:inline-block;padding:0.375rem 0.75rem;background:#14b8a6;color:#ffffff;font-size:0.75rem;font-weight:600;border-radius:0.375rem;text-decoration:none;">
                                    Start Visit
                                </a>
                            @endif
                            <a href="{{ $appointmentUrlFn($appointment->id) }}" style="display:inline-block;padding:0.375rem 0.75rem;background:#ffffff;color:#374151;border:1px solid #d1d5db;font-size:0.75rem;font-weight:600;border-radius:0.375rem;text-decoration:none;">
                                View
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-widgets::widget>
