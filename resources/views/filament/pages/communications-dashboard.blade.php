<x-filament-panels::page>
    {{-- Stats row --}}
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px;">

        <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px;">
            <div style="font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Sent This Month</div>
            <div style="font-size: 28px; font-weight: 700; color: #111827; margin-top: 4px;">{{ $this->sentThisMonth }}</div>
        </div>

        <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px;">
            <div style="font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Delivery Rate</div>
            <div style="font-size: 28px; font-weight: 700; color: #059669; margin-top: 4px;">{{ $this->deliveryRate }}%</div>
        </div>

        <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px;">
            <div style="font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Failed</div>
            <div style="font-size: 28px; font-weight: 700; color: #dc2626; margin-top: 4px;">{{ $this->failedCount }}</div>
        </div>

        <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px;">
            <div style="font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Opt-Outs This Month</div>
            <div style="font-size: 28px; font-weight: 700; color: #d97706; margin-top: 4px;">{{ $this->optedOutCount }}</div>
        </div>

    </div>

    {{-- Recent log --}}
    <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
        <div style="padding: 16px 20px; border-bottom: 1px solid #e5e7eb;">
            <h3 style="margin: 0; font-size: 15px; font-weight: 600; color: #111827;">Recent Messages (last 20)</h3>
        </div>
        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
            <thead>
                <tr style="background: #f9fafb;">
                    <th style="text-align: left; padding: 10px 16px; color: #6b7280; font-weight: 500;">Patient</th>
                    <th style="text-align: left; padding: 10px 16px; color: #6b7280; font-weight: 500;">Template</th>
                    <th style="text-align: left; padding: 10px 16px; color: #6b7280; font-weight: 500;">Status</th>
                    <th style="text-align: left; padding: 10px 16px; color: #6b7280; font-weight: 500;">Sent At</th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->getRecentLogs() as $log)
                    <tr style="border-top: 1px solid #f3f4f6;">
                        <td style="padding: 10px 16px; color: #111827;">{{ $log->patient?->name ?? '—' }}</td>
                        <td style="padding: 10px 16px; color: #374151;">{{ $log->messageTemplate?->name ?? '—' }}</td>
                        <td style="padding: 10px 16px;">
                            <span style="display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 11px; font-weight: 600;
                                background: {{ match($log->status) { 'sent','delivered' => '#d1fae5', 'failed','bounced' => '#fee2e2', 'opted_out' => '#fef3c7', default => '#f3f4f6' } }};
                                color: {{ match($log->status) { 'sent','delivered' => '#065f46', 'failed','bounced' => '#991b1b', 'opted_out' => '#92400e', default => '#374151' } }};">
                                {{ ucfirst(str_replace('_', ' ', $log->status)) }}
                            </span>
                        </td>
                        <td style="padding: 10px 16px; color: #6b7280;">{{ $log->sent_at?->format('M j, g:i A') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="padding: 24px 16px; text-align: center; color: #9ca3af;">No messages sent yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
