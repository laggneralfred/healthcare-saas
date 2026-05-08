<div>

@if(! ($practice ?? null))
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:0.75rem;padding:3rem 2rem;text-align:center;color:#94a3b8;font-size:0.9375rem;">
        No practice selected. Use the practice switcher in the top bar.
    </div>
@else

{{-- Complete Setup Banner --}}
@if($showSetupBanner ?? false)
<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:0.75rem;padding:1rem 1.5rem;margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;">
    <div style="display:flex;align-items:center;gap:0.75rem;">
        <span style="font-size:1.375rem;">🚀</span>
        <div>
            <p style="margin:0;font-size:0.9375rem;font-weight:600;color:#1e40af;">Complete your practice setup to get the most out of Practiq.</p>
            <p style="margin:0.125rem 0 0;font-size:0.8125rem;color:#3b82f6;">Takes just a few minutes — configure your profile, intake templates, and legal forms.</p>
        </div>
    </div>
    <div style="display:flex;align-items:center;gap:0.75rem;flex-shrink:0;">
        <a href="/onboarding" style="display:inline-block;padding:0.5rem 1.25rem;background:#2563eb;color:#ffffff;font-size:0.875rem;font-weight:600;border-radius:0.5rem;text-decoration:none;">
            Set up now →
        </a>
        <form method="POST" action="{{ route('admin.dismiss-setup-banner') }}" style="margin:0;">
            @csrf
            <button type="submit" style="background:none;border:none;padding:0.375rem;cursor:pointer;color:#6b7280;font-size:1.25rem;line-height:1;" title="Dismiss">×</button>
        </form>
    </div>
</div>
@endif

{{-- Header --}}
<div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:0.75rem;padding:1.5rem 2rem;margin-bottom:1.5rem;">
    <p style="margin:0;font-size:1.75rem;font-weight:700;color:#0f172a;">{{ $practice->name }}</p>
    <p style="margin:0.25rem 0 0;font-size:0.875rem;color:#64748b;">Reports &mdash; {{ now()->format('F Y') }}</p>
</div>

@include('filament.partials.practice-setup-checklist', ['setupChecklist' => $setupChecklist])

{{-- Today's Schedule Widget --}}
<div style="margin-bottom:1.5rem;">
    @livewire(\App\Filament\Widgets\TodaysScheduleWidget::class)
</div>

{{-- Key Metrics: 4-col desktop, 2-col tablet, 1-col mobile --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem;">

    {{-- Today's Appointments --}}
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:0.75rem;padding:1.25rem 1.5rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem;">
            <p style="margin:0;font-size:0.8125rem;font-weight:500;color:#64748b;">Today's Appointments</p>
            <span style="font-size:1.25rem;line-height:1;">⏰</span>
        </div>
        <p style="margin:0;font-size:2rem;font-weight:700;color:#0f172a;">{{ $appointmentsToday }}</p>
        <p style="margin:0.75rem 0 0;font-size:0.8125rem;color:#64748b;">
            <span style="color:#16a34a;font-weight:600;">{{ $appointmentsTodayCompleted }}</span> completed
        </p>
    </div>

    {{-- This Week's Revenue --}}
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:0.75rem;padding:1.25rem 1.5rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem;">
            <p style="margin:0;font-size:0.8125rem;font-weight:500;color:#64748b;">This Week's Revenue</p>
            <span style="font-size:1.25rem;line-height:1;">💰</span>
        </div>
        <p style="margin:0;font-size:2rem;font-weight:700;color:#0f172a;">{{ $formattedRevenueThisWeek }}</p>
        <p style="margin:0.75rem 0 0;font-size:0.8125rem;color:#64748b;">From paid appointments</p>
    </div>

    {{-- Total Patients --}}
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:0.75rem;padding:1.25rem 1.5rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem;">
            <p style="margin:0;font-size:0.8125rem;font-weight:500;color:#64748b;">Active Patients</p>
            <span style="font-size:1.25rem;line-height:1;">👥</span>
        </div>
        <p style="margin:0;font-size:2rem;font-weight:700;color:#0f172a;">{{ $totalPatients }}</p>
        <p style="margin:0.75rem 0 0;font-size:0.8125rem;color:#64748b;">
            <span style="color:#16a34a;font-weight:600;">+{{ $newPatientsThisMonth }}</span> new this month
        </p>
    </div>

    {{-- This Month's Appointments --}}
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:0.75rem;padding:1.25rem 1.5rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem;">
            <p style="margin:0;font-size:0.8125rem;font-weight:500;color:#64748b;">This Month's Appointments</p>
            <span style="font-size:1.25rem;line-height:1;">📅</span>
        </div>
        <p style="margin:0;font-size:2rem;font-weight:700;color:#0f172a;">{{ $appointmentsThisMonth }}</p>
        <div style="display:flex;gap:1rem;margin-top:0.75rem;font-size:0.8125rem;">
            <span style="color:#16a34a;">✓ {{ $appointmentsCompleted }} completed</span>
            <span style="color:#d97706;">⏳ {{ $appointmentsPending }} pending</span>
        </div>
    </div>

</div>

{{-- Financial Summary --}}
<div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:0.75rem;padding:1.5rem;margin-bottom:1.5rem;">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1.25rem;">
        <div>
            <p style="margin:0;font-size:1rem;font-weight:600;color:#0f172a;">Financial Summary</p>
            <p style="margin:0.25rem 0 0;font-size:0.8125rem;color:#64748b;">Collected revenue uses payment date from checkout payments. This is a bookkeeping summary, not full accounting.</p>
        </div>
        <form method="GET" style="display:flex;align-items:end;gap:0.5rem;flex-wrap:wrap;">
            <label style="display:flex;flex-direction:column;gap:0.25rem;font-size:0.75rem;font-weight:500;color:#475569;">
                Start
                <input type="date" name="financial_start" value="{{ $financialStartDate }}" style="border:1px solid #cbd5e1;border-radius:0.5rem;padding:0.375rem 0.5rem;font-size:0.8125rem;color:#0f172a;">
            </label>
            <label style="display:flex;flex-direction:column;gap:0.25rem;font-size:0.75rem;font-weight:500;color:#475569;">
                End
                <input type="date" name="financial_end" value="{{ $financialEndDate }}" style="border:1px solid #cbd5e1;border-radius:0.5rem;padding:0.375rem 0.5rem;font-size:0.8125rem;color:#0f172a;">
            </label>
            <button type="submit" style="border:0;border-radius:0.5rem;background:#0f766e;color:#ffffff;padding:0.5rem 0.875rem;font-size:0.8125rem;font-weight:600;cursor:pointer;">Apply</button>
        </form>
    </div>

    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:0.75rem;margin-bottom:1rem;">
        <div style="border:1px solid #e2e8f0;border-radius:0.5rem;padding:1rem;">
            <p style="margin:0;font-size:0.75rem;font-weight:500;color:#64748b;">Total Collected</p>
            <p style="margin:0.375rem 0 0;font-size:1.5rem;font-weight:700;color:#0f172a;">{{ $formattedFinancialTotalCollected }}</p>
        </div>
        <div style="border:1px solid #e2e8f0;border-radius:0.5rem;padding:1rem;">
            <p style="margin:0;font-size:0.75rem;font-weight:500;color:#64748b;">Paid Sessions</p>
            <p style="margin:0.375rem 0 0;font-size:1.5rem;font-weight:700;color:#0f172a;">{{ $financialSummary['paid_sessions_count'] }}</p>
        </div>
        <div style="border:1px solid #e2e8f0;border-radius:0.5rem;padding:1rem;">
            <p style="margin:0;font-size:0.75rem;font-weight:500;color:#64748b;">Sessions With Payments</p>
            <p style="margin:0.375rem 0 0;font-size:1.5rem;font-weight:700;color:#0f172a;">{{ $financialSummary['collected_sessions_count'] }}</p>
        </div>
        <div style="border:1px solid #e2e8f0;border-radius:0.5rem;padding:1rem;">
            <p style="margin:0;font-size:0.75rem;font-weight:500;color:#64748b;">Open / Payment Due</p>
            <p style="margin:0.375rem 0 0;font-size:1.5rem;font-weight:700;color:#0f172a;">{{ $financialSummary['unpaid_open_sessions_count'] }}</p>
            <p style="margin:0.25rem 0 0;font-size:0.75rem;color:#64748b;">{{ $formattedFinancialUnpaidOpenTotal }} outstanding</p>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;">
        <div>
            <p style="margin:0 0 0.5rem;font-size:0.875rem;font-weight:600;color:#0f172a;">Payment Methods</p>
            @forelse ($financialSummary['payment_method_totals'] as $row)
                <div style="display:flex;justify-content:space-between;gap:1rem;padding:0.375rem 0;border-bottom:1px solid #f1f5f9;font-size:0.8125rem;">
                    <span style="color:#475569;">{{ $row['label'] }} ({{ $row['count'] }})</span>
                    <span style="font-weight:600;color:#0f172a;">{{ \Illuminate\Support\Number::currency($row['total'], 'USD') }}</span>
                </div>
            @empty
                <p style="color:#94a3b8;font-size:0.8125rem;">No payments in this range.</p>
            @endforelse
        </div>
        <div>
            <p style="margin:0 0 0.5rem;font-size:0.875rem;font-weight:600;color:#0f172a;">Practitioners</p>
            @forelse ($financialSummary['practitioner_totals'] as $row)
                <div style="display:flex;justify-content:space-between;gap:1rem;padding:0.375rem 0;border-bottom:1px solid #f1f5f9;font-size:0.8125rem;">
                    <span style="color:#475569;">{{ $row['practitioner_name'] }}</span>
                    <span style="font-weight:600;color:#0f172a;">{{ \Illuminate\Support\Number::currency($row['total'], 'USD') }}</span>
                </div>
            @empty
                <p style="color:#94a3b8;font-size:0.8125rem;">No practitioner revenue in this range.</p>
            @endforelse
        </div>
        <div>
            <p style="margin:0 0 0.5rem;font-size:0.875rem;font-weight:600;color:#0f172a;">Line Types</p>
            @forelse ($financialSummary['line_type_totals'] as $row)
                <div style="display:flex;justify-content:space-between;gap:1rem;padding:0.375rem 0;border-bottom:1px solid #f1f5f9;font-size:0.8125rem;">
                    <span style="color:#475569;">{{ $row['label'] }} ({{ $row['line_count'] }})</span>
                    <span style="font-weight:600;color:#0f172a;">{{ \Illuminate\Support\Number::currency($row['total'], 'USD') }}</span>
                </div>
            @empty
                <p style="color:#94a3b8;font-size:0.8125rem;">No line items in this range.</p>
            @endforelse
        </div>
    </div>
</div>

{{-- Bottom row: status breakdown + revenue by practitioner --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">

    {{-- Appointment Status Breakdown --}}
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:0.75rem;padding:1.5rem;">
        <p style="margin:0 0 1rem;font-size:1rem;font-weight:600;color:#0f172a;">Appointment Status Breakdown</p>
        @php
            $statusMeta = [
                'scheduled'   => ['label' => 'Scheduled',   'color' => '#3b82f6', 'bg' => '#eff6ff'],
                'in_progress' => ['label' => 'In Progress', 'color' => '#d97706', 'bg' => '#fffbeb'],
                'completed'   => ['label' => 'Completed',   'color' => '#16a34a', 'bg' => '#f0fdf4'],
                'closed'      => ['label' => 'Closed',      'color' => '#6b7280', 'bg' => '#f9fafb'],
                'checkout'    => ['label' => 'Checkout',    'color' => '#7c3aed', 'bg' => '#f5f3ff'],
            ];
        @endphp
        @forelse ($appointmentsByStatus as $status => $count)
            @php $meta = $statusMeta[$status] ?? ['label' => ucfirst($status), 'color' => '#6b7280', 'bg' => '#f9fafb']; @endphp
            <div style="display:flex;align-items:center;justify-content:space-between;padding:0.5rem 0;border-bottom:1px solid #f1f5f9;">
                <span style="display:inline-block;padding:0.25rem 0.625rem;border-radius:9999px;font-size:0.8125rem;font-weight:500;background:{{ $meta['bg'] }};color:{{ $meta['color'] }};">
                    {{ $meta['label'] }}
                </span>
                <span style="font-size:1.5rem;font-weight:700;color:#0f172a;">{{ $count }}</span>
            </div>
        @empty
            <p style="color:#94a3b8;font-size:0.875rem;">No appointments yet.</p>
        @endforelse
    </div>

    {{-- Revenue by Practitioner --}}
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:0.75rem;padding:1.5rem;">
        <p style="margin:0 0 1rem;font-size:1rem;font-weight:600;color:#0f172a;">Revenue by Practitioner</p>
        @forelse ($revenueByPractitioner as $data)
            <div style="display:flex;align-items:center;justify-content:space-between;padding:0.625rem 0;border-bottom:1px solid #f1f5f9;">
                <div>
                    <p style="margin:0;font-size:0.9375rem;font-weight:500;color:#0f172a;">{{ $data['practitioner_name'] }}</p>
                    <p style="margin:0.1rem 0 0;font-size:0.8125rem;color:#64748b;">{{ $data['appointments'] }} appointment(s)</p>
                </div>
                <span style="font-size:1.125rem;font-weight:700;color:#7c3aed;">
                    {{ \Illuminate\Support\Number::currency($data['revenue'], 'USD') }}
                </span>
            </div>
        @empty
            <p style="color:#94a3b8;font-size:0.875rem;">No revenue data available.</p>
        @endforelse
    </div>

</div>

@endif

</div>
