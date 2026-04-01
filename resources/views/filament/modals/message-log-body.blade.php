<div style="padding: 0 4px;">
    @if($log->subject)
        <p style="margin: 0 0 12px 0; font-weight: 600; color: #111827;">Subject: {{ $log->subject }}</p>
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin-bottom: 16px;">
    @endif
    <pre style="white-space: pre-wrap; font-family: inherit; font-size: 14px; color: #374151; margin: 0;">{{ $log->body }}</pre>
    <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid #e5e7eb; font-size: 12px; color: #9ca3af;">
        Sent to: {{ $log->recipient }} &middot;
        Status: {{ $log->status }} &middot;
        {{ $log->sent_at?->format('M j, Y g:i A') ?? 'Not sent yet' }}
    </div>
</div>
