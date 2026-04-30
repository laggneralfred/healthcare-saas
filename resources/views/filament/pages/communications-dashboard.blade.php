<x-filament-panels::page>
    <div style="margin-bottom:16px;">
        <p style="margin:0;font-size:13px;color:#6b7280;">These patients may benefit from a gentle check-in or invitation to return.</p>
    </div>

    {{-- Follow-up list --}}
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:24px;overflow:hidden;">
        <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb;">
            <h3 style="margin:0;font-size:15px;font-weight:600;color:#111827;">Follow-Up</h3>
            <p style="margin:4px 0 0;font-size:13px;color:#6b7280;">Patients who may need a gentle follow-up will appear here.</p>
            <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:12px;">
                @foreach($this->getFollowUpStatusFilterOptions() as $value => $label)
                    @php
                        $active = $this->followUpStatusFilter === $value;
                    @endphp
                    <button wire:click="$set('followUpStatusFilter', '{{ $value }}')" type="button" style="padding:5px 9px;border:1px solid {{ $active ? '#2563eb' : '#d1d5db' }};border-radius:9999px;background:{{ $active ? '#eff6ff' : '#ffffff' }};color:{{ $active ? '#1d4ed8' : '#374151' }};font-size:12px;font-weight:600;cursor:pointer;">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px;padding:16px;">
            @forelse($this->getFollowUpPatients() as $patient)
                @php
                    $careStatus = $patient->getAttribute('care_status_summary');
                    $statusStyle = match($careStatus['color'] ?? 'gray') {
                        'success' => 'background:#d1fae5;color:#065f46;',
                        'warning' => 'background:#fef3c7;color:#92400e;',
                        'danger' => 'background:#fee2e2;color:#991b1b;',
                        'info' => 'background:#e0f2fe;color:#0c4a6e;',
                        'primary' => 'background:#dbeafe;color:#1e40af;',
                        default => 'background:#f3f4f6;color:#374151;',
                    };
                @endphp

                <div style="border:1px solid #e5e7eb;border-radius:8px;padding:14px;display:flex;flex-direction:column;gap:12px;">
                    <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
                        <div style="min-width:0;flex:1 1 180px;">
                            <div style="font-size:15px;font-weight:600;color:#111827;">{{ $patient->name }}</div>
                            <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:6px;">
                                <span style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:9999px;font-size:11px;font-weight:600;{{ $statusStyle }}">
                                    {{ $careStatus['label'] }}
                                </span>
                                <span style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:9999px;font-size:11px;font-weight:600;background:#f3f4f6;color:#374151;">
                                    {{ $patient->preferred_language_label }}
                                </span>
                            </div>
                        </div>

                        <button wire:click="openInviteBackPreview({{ $patient->id }})" type="button" style="white-space:nowrap;padding:7px 10px;border:1px solid #d1d5db;border-radius:6px;background:#fff;color:#374151;font-size:12px;font-weight:600;cursor:pointer;">
                            Invite Back
                        </button>
                    </div>

                    <div style="font-size:13px;color:#4b5563;line-height:1.4;">{{ $careStatus['helper'] }}</div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:12px;color:#6b7280;">
                        <div>
                            <div style="font-weight:600;color:#374151;">Last completed visit</div>
                            <div>{{ $this->getLastCompletedVisitDate($patient) ?? 'No completed visit' }}</div>
                        </div>
                        <div>
                            <div style="font-weight:600;color:#374151;">Next appointment</div>
                            <div>{{ $this->getNextAppointmentDate($patient) ?? 'Not scheduled' }}</div>
                        </div>
                    </div>
                </div>
            @empty
                <div style="grid-column:1 / -1;padding:24px;text-align:center;color:#9ca3af;font-size:13px;">
                    No patients need follow-up right now.
                </div>
            @endforelse
        </div>
    </div>

    @if($inviteBackPatient = $this->getInviteBackPatient())
        @php($inviteBackDraft = $this->getInviteBackDraft())
        @php($inviteBackEmailAvailability = $this->getInviteBackEmailAvailability())
        <div style="position:fixed;inset:0;background:rgba(17,24,39,0.45);z-index:50;display:flex;align-items:center;justify-content:center;padding:16px;">
            <div style="width:min(680px,100%);max-height:calc(100vh - 32px);background:#fff;border-radius:8px;box-shadow:0 20px 25px -5px rgba(0,0,0,0.1);overflow:auto;">
                <div style="padding:16px 20px;border-bottom:1px solid #e5e7eb;">
                    <h3 style="margin:0;font-size:16px;font-weight:600;color:#111827;">Invite Back</h3>
                    <p style="margin:4px 0 0;font-size:13px;color:#6b7280;">
                        Preferred Language: {{ $inviteBackDraft['language_label'] ?? $inviteBackPatient->preferred_language_label }}
                    </p>
                </div>

                <div style="padding:20px;">
                    <p style="margin:0 0 8px;font-size:13px;color:#6b7280;">Review this message before sending.</p>
                    @if($inviteBackPatient->email)
                        <p style="margin:0 0 8px;font-size:13px;color:#374151;background:#f9fafb;border-radius:6px;padding:8px 10px;">
                            Sending will email the patient at {{ $inviteBackPatient->email }}.
                        </p>
                    @endif
                    @if($inviteBackEmailAvailability['helper'] ?? null)
                        <p style="margin:0 0 8px;font-size:13px;color:#92400e;background:#fef3c7;border-radius:6px;padding:8px 10px;">
                            {{ $inviteBackEmailAvailability['helper'] }}
                        </p>
                    @endif
                    @if($inviteBackDraft['fallback_used'] ?? false)
                        <p style="margin:0 0 8px;font-size:13px;color:#92400e;background:#fef3c7;border-radius:6px;padding:8px 10px;">
                            A translated draft is not available yet, so this preview is shown in English.
                        </p>
                    @endif
                    @if($inviteBackTranslationError)
                        <p style="margin:0 0 8px;font-size:13px;color:#991b1b;background:#fee2e2;border-radius:6px;padding:8px 10px;">
                            {{ $inviteBackTranslationError }}
                        </p>
                    @endif
                    <div style="margin:0 0 10px;font-size:12px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:0.04em;">
                        Preview message
                    </div>
                    <label style="display:flex;flex-direction:column;gap:6px;font-size:13px;color:#374151;margin-bottom:12px;">
                        Subject
                        <input readonly value="{{ $inviteBackDraft['subject'] ?? 'Checking in' }}" style="width:100%;padding:9px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;color:#111827;">
                    </label>
                    <label style="display:flex;flex-direction:column;gap:6px;font-size:13px;color:#374151;">
                        Message body
                        <textarea readonly rows="8" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;line-height:1.5;color:#111827;">{{ $inviteBackDraft['body'] ?? '' }}</textarea>
                    </label>
                    @if($inviteBackTranslatedBody)
                        <label style="display:flex;flex-direction:column;gap:6px;font-size:13px;color:#374151;margin-top:12px;">
                            Translated preview
                            <textarea readonly rows="8" style="width:100%;padding:10px;border:1px solid #bfdbfe;border-radius:6px;font-size:14px;line-height:1.5;color:#111827;background:#eff6ff;">{{ $inviteBackTranslatedBody }}</textarea>
                        </label>
                        <p style="margin:8px 0 0;font-size:13px;color:#6b7280;">
                            Please review the translation before using it.
                        </p>
                    @endif
                    <div style="margin-top:14px;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:8px;">
                        <p style="margin:0;font-size:12px;color:#6b7280;background:#f9fafb;border-radius:6px;padding:8px 10px;">
                            Saving a draft does not contact the patient.
                        </p>
                        @if($inviteBackEmailAvailability['can_send'] ?? false)
                            <p style="margin:0;font-size:12px;color:#065f46;background:#ecfdf5;border-radius:6px;padding:8px 10px;">
                                Send Email will contact the patient now.
                            </p>
                        @endif
                    </div>
                    @if($inviteBackEmailAvailability['can_send'] ?? false)
                        <label style="margin-top:12px;display:flex;gap:8px;align-items:flex-start;font-size:13px;color:#374151;">
                            <input wire:model="includeInviteBackRequestLink" type="checkbox" style="margin-top:3px;">
                            <span>
                                Include appointment request link
                                <span style="display:block;margin-top:2px;font-size:12px;color:#6b7280;">Patients can request preferred days or times. Staff still schedules manually.</span>
                            </span>
                        </label>
                    @endif
                </div>

                <div style="padding:14px 20px;border-top:1px solid #e5e7eb;display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap;">
                    <button wire:click="closeInviteBackPreview" type="button" style="padding:8px 12px;border:1px solid #d1d5db;border-radius:6px;background:#fff;color:#374151;font-size:13px;font-weight:600;cursor:pointer;">
                        Close
                    </button>
                    @if($this->canTranslateInviteBackDraft())
                        <button wire:click="translateInviteBackDraft" type="button" style="padding:8px 12px;border:1px solid #bfdbfe;border-radius:6px;background:#eff6ff;color:#1d4ed8;font-size:13px;font-weight:600;cursor:pointer;">
                            Translate for Patient
                        </button>
                    @endif
                    <button wire:click="saveInviteBackDraft" type="button" style="padding:8px 12px;border:0;border-radius:6px;background:#2563eb;color:#ffffff;font-size:13px;font-weight:600;cursor:pointer;">
                        Save Draft
                    </button>
                    @if($inviteBackEmailAvailability['can_send'] ?? false)
                        <button wire:click="sendInviteBackEmail" type="button" style="padding:8px 12px;border:0;border-radius:6px;background:#065f46;color:#ffffff;font-size:13px;font-weight:700;cursor:pointer;">
                            Send Email
                        </button>
                    @endif
                    <button type="button" disabled style="padding:8px 12px;border:0;border-radius:6px;background:#e5e7eb;color:#6b7280;font-size:13px;font-weight:600;cursor:not-allowed;">
                        Preview Only
                    </button>
                </div>
            </div>
        </div>
    @endif

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

            <div style="display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 12px; align-items: end; margin-top: 16px;">
                <label style="display: flex; flex-direction: column; gap: 6px; font-size: 13px; color: #374151;">
                    Target language
                    <select wire:model="targetLanguage" style="padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;">
                        @foreach($this->getTranslationLanguageOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <button wire:click="translateReminderDraft" type="button" style="
                    padding: 9px 14px; font-size: 14px; font-weight: 600;
                    color: #ffffff; background: #0f766e; border: 0; border-radius: 6px; cursor: pointer;
                ">
                    Translate Draft
                </button>
            </div>

            <div wire:loading wire:target="translateReminderDraft" style="margin-top: 12px; font-size: 13px; color: #0f766e;">
                Translating draft…
            </div>

            @if($translatedReminderDraft)
                <label style="display: flex; flex-direction: column; gap: 6px; margin-top: 16px; font-size: 13px; color: #374151;">
                    Translated draft
                    <textarea wire:model.live="translatedReminderDraft" rows="4" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; color: #111827;"></textarea>
                </label>
            @endif
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
