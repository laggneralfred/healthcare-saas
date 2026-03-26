<div>

@if(! ($practice ?? null))
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:0.75rem;padding:3rem 2rem;text-align:center;color:#94a3b8;font-size:0.9375rem;">
        No practice selected. Use the practice switcher in the top bar.
    </div>
@else

{{-- Header --}}
<div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:0.75rem;padding:1.5rem 2rem;margin-bottom:1.5rem;">
    <p style="margin:0;font-size:1.75rem;font-weight:700;color:#0f172a;">{{ $practice->name }}</p>
    <p style="margin:0.25rem 0 0;font-size:0.875rem;color:#64748b;">Dashboard &mdash; {{ now()->format('F Y') }}</p>
</div>

{{-- Key Metrics: 4-col desktop, 2-col tablet, 1-col mobile --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem;">

    {{-- Appointments This Month --}}
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:0.75rem;padding:1.25rem 1.5rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem;">
            <p style="margin:0;font-size:0.8125rem;font-weight:500;color:#64748b;">Appointments This Month</p>
            <span style="font-size:1.25rem;line-height:1;">📅</span>
        </div>
        <p style="margin:0;font-size:2rem;font-weight:700;color:#0f172a;">{{ $appointmentsThisMonth }}</p>
        <div style="display:flex;gap:1rem;margin-top:0.75rem;font-size:0.8125rem;">
            <span style="color:#16a34a;">&#10003; {{ $appointmentsCompleted }} completed</span>
            <span style="color:#d97706;">&#9201; {{ $appointmentsPending }} pending</span>
        </div>
    </div>

    {{-- Total Patients --}}
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:0.75rem;padding:1.25rem 1.5rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem;">
            <p style="margin:0;font-size:0.8125rem;font-weight:500;color:#64748b;">Total Patients</p>
            <span style="font-size:1.25rem;line-height:1;">👥</span>
        </div>
        <p style="margin:0;font-size:2rem;font-weight:700;color:#0f172a;">{{ $totalPatients }}</p>
        <p style="margin:0.75rem 0 0;font-size:0.8125rem;color:#64748b;">
            <span style="color:#16a34a;font-weight:600;">+{{ $newPatientsThisMonth }}</span> new this month
        </p>
    </div>

    {{-- Revenue This Month --}}
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:0.75rem;padding:1.25rem 1.5rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem;">
            <p style="margin:0;font-size:0.8125rem;font-weight:500;color:#64748b;">Revenue This Month</p>
            <span style="font-size:1.25rem;line-height:1;">💰</span>
        </div>
        <p style="margin:0;font-size:2rem;font-weight:700;color:#0f172a;">{{ $formattedRevenue }}</p>
        <p style="margin:0.75rem 0 0;font-size:0.8125rem;color:#64748b;">
            {{ $checkoutSessionsCompleted }} completed session(s)
        </p>
    </div>

    {{-- Pending Revenue --}}
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:0.75rem;padding:1.25rem 1.5rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem;">
            <p style="margin:0;font-size:0.8125rem;font-weight:500;color:#64748b;">Pending Revenue</p>
            <span style="font-size:1.25rem;line-height:1;">⏳</span>
        </div>
        <p style="margin:0;font-size:2rem;font-weight:700;color:#ea580c;">{{ $formattedPendingRevenue }}</p>
        <p style="margin:0.75rem 0 0;font-size:0.8125rem;color:#64748b;">Awaiting payment</p>
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
                    {{ \Illuminate\Support\Number::currency($data['revenue'] / 100, 'USD') }}
                </span>
            </div>
        @empty
            <p style="color:#94a3b8;font-size:0.875rem;">No revenue data available.</p>
        @endforelse
    </div>

</div>

@endif

</div>
