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

    {{-- AI reminder draft --}}
    <div style="background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin-bottom: 24px;">
        <h3 style="margin: 0 0 6px; font-size: 15px; font-weight: 600; color: #111827;">AI Reminder Draft</h3>
        <p style="margin: 0 0 16px; font-size: 13px; color: #6b7280;">
            Draft only. Review and edit this text, then send through the existing message workflow when ready.
        </p>

        <div style="display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr) auto; gap: 12px; align-items: end;">
            <label style="display: flex; flex-direction: column; gap: 6px; font-size: 13px; color: #374151;">
                Appointment
                <select wire:model="selectedAppointmentId" style="padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;">
                    <option value="">General reminder</option>
                    @foreach($this->getUpcomingAppointments() as $appointment)
                        <option value="{{ $appointment->id }}">
                            {{ $appointment->patient?->name ?? 'Patient' }} · {{ $appointment->start_datetime->format('M j, g:i A') }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label style="display: flex; flex-direction: column; gap: 6px; font-size: 13px; color: #374151;">
                Reason
                <input wire:model="reminderReason" type="text" style="padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;">
            </label>

            <button wire:click="draftAIReminder" type="button" style="
                padding: 9px 14px; font-size: 14px; font-weight: 600;
                color: #ffffff; background: #2563eb; border: 0; border-radius: 6px; cursor: pointer;
            ">
                Draft AI Reminder
            </button>
        </div>

        <div wire:loading wire:target="draftAIReminder" style="margin-top: 12px; font-size: 13px; color: #2563eb;">
            Drafting reminder…
        </div>

        @if($aiReminderDraft)
            <label style="display: flex; flex-direction: column; gap: 6px; margin-top: 16px; font-size: 13px; color: #374151;">
                Draft message
                <textarea wire:model.live="aiReminderDraft" rows="4" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; color: #111827;"></textarea>
            </label>
        @endif
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
