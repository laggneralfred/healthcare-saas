<div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;padding:14px 16px;margin-bottom:16px;">
    <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
        <div>
            <h2 style="margin:0;font-size:15px;font-weight:700;color:#111827;">Appointment Request</h2>
            <p style="margin:3px 0 0;font-size:12px;color:#6b7280;">
                Submitted {{ $appointmentRequest->submitted_at?->format('M j, Y g:i A') ?? 'recently' }}
            </p>
        </div>
        <span style="display:inline-flex;align-items:center;border-radius:9999px;background:#f3f4f6;color:#374151;padding:3px 8px;font-size:12px;font-weight:600;">
            {{ str($appointmentRequest->status)->replace('_', ' ')->title() }}
        </span>
    </div>

    <dl style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px 16px;margin:14px 0 0;font-size:13px;">
        <div>
            <dt style="font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;">Requested Treatment</dt>
            <dd style="margin:3px 0 0;color:#111827;">{{ $appointmentRequest->appointmentType?->name ?? $appointmentRequest->requested_service ?? 'Not specified' }}</dd>
        </div>
        <div>
            <dt style="font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;">Practitioner Preference</dt>
            <dd style="margin:3px 0 0;color:#111827;">{{ $appointmentRequest->practitioner?->user?->name ?? 'No preference' }}</dd>
        </div>
        <div>
            <dt style="font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;">Preferred Days/Times</dt>
            <dd style="margin:3px 0 0;color:#111827;white-space:pre-line;">{{ $appointmentRequest->preferred_times ?: 'Not specified' }}</dd>
        </div>
    </dl>

    @if($appointmentRequest->note)
        <div style="margin-top:12px;font-size:13px;color:#374151;white-space:pre-line;">{{ $appointmentRequest->note }}</div>
    @endif
</div>
