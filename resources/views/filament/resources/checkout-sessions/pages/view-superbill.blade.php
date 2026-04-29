<x-filament-panels::page>
    @php
        $checkout = $record->loadMissing([
            'practice',
            'patient',
            'practitioner.user',
            'appointment',
            'encounter',
            'checkoutLines',
            'checkoutPayments',
        ]);
        $dateOfService = $checkout->appointment?->start_datetime ?? $checkout->encounter?->visit_date ?? $checkout->created_at;
    @endphp

    <div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;padding:24px;color:#111827;">
        <div style="display:flex;justify-content:space-between;gap:24px;border-bottom:1px solid #e5e7eb;padding-bottom:16px;margin-bottom:18px;">
            <div>
                <h1 style="font-size:22px;font-weight:700;margin:0 0 6px;">Superbill</h1>
                <p style="margin:0;color:#6b7280;font-size:13px;">Patient documentation for possible insurance reimbursement. Reimbursement is not guaranteed.</p>
            </div>
            <div style="text-align:right;font-size:13px;color:#374151;">
                <div style="font-weight:700;">{{ $checkout->practice?->name ?? 'Practice' }}</div>
                <div>{{ $dateOfService?->format('M j, Y') ?? 'Date not set' }}</div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;margin-bottom:20px;">
            <section>
                <h2 style="font-size:14px;font-weight:700;margin:0 0 8px;">Patient</h2>
                <div style="font-size:13px;line-height:1.5;">
                    <div>{{ $checkout->patient?->name ?? 'Patient' }}</div>
                    @if($checkout->patient?->dob)<div>DOB: {{ $checkout->patient->dob->format('M j, Y') }}</div>@endif
                    @if($checkout->patient?->email)<div>{{ $checkout->patient->email }}</div>@endif
                    @if($checkout->patient?->phone)<div>{{ $checkout->patient->phone }}</div>@endif
                </div>
            </section>

            <section>
                <h2 style="font-size:14px;font-weight:700;margin:0 0 8px;">Provider</h2>
                <div style="font-size:13px;line-height:1.5;">
                    <div>{{ $checkout->practitioner?->user?->name ?? $checkout->practice?->name ?? 'Provider' }}</div>
                    @if($checkout->practitioner?->license_number)<div>License: {{ $checkout->practitioner->license_number }}</div>@endif
                    <div>{{ $checkout->practice?->name ?? 'Practice' }}</div>
                </div>
            </section>
        </div>

        <section style="margin-bottom:20px;">
            <h2 style="font-size:14px;font-weight:700;margin:0 0 8px;">Service</h2>
            <div style="font-size:13px;line-height:1.5;">
                <div>Date of service: {{ $dateOfService?->format('M j, Y') ?? 'Date not set' }}</div>
                <div>Visit type: {{ $checkout->appointment_id ? 'Appointment visit' : 'Direct visit' }}</div>
                <div>Charge label: {{ $checkout->charge_label }}</div>
                @if($checkout->diagnosis_codes)<div>Diagnosis codes: {{ $checkout->diagnosis_codes }}</div>@endif
                @if($checkout->procedure_codes)<div>Procedure/CPT codes: {{ $checkout->procedure_codes }}</div>@endif
            </div>
        </section>

        <section style="margin-bottom:20px;">
            <h2 style="font-size:14px;font-weight:700;margin:0 0 8px;">Charges</h2>
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="border-bottom:1px solid #e5e7eb;color:#6b7280;">
                        <th style="text-align:left;padding:8px;">Description</th>
                        <th style="text-align:right;padding:8px;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($checkout->checkoutLines as $line)
                        <tr style="border-bottom:1px solid #f3f4f6;">
                            <td style="padding:8px;">{{ $line->description }}</td>
                            <td style="padding:8px;text-align:right;">${{ number_format((float) $line->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td style="padding:8px;">{{ $checkout->charge_label }}</td><td style="padding:8px;text-align:right;">${{ number_format((float) $checkout->amount_total, 2) }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section style="margin-bottom:20px;">
            <h2 style="font-size:14px;font-weight:700;margin:0 0 8px;">Payments</h2>
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <tbody>
                    @forelse($checkout->checkoutPayments as $payment)
                        <tr style="border-bottom:1px solid #f3f4f6;">
                            <td style="padding:8px;">{{ $payment->paid_at?->format('M j, Y') ?? 'Date not set' }}</td>
                            <td style="padding:8px;">{{ \App\Models\CheckoutPayment::METHODS[$payment->payment_method] ?? $payment->payment_method }}</td>
                            <td style="padding:8px;text-align:right;">${{ number_format((float) $payment->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td style="padding:8px;color:#6b7280;">No payments recorded.</td><td></td><td></td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;font-size:13px;border-top:1px solid #e5e7eb;padding-top:16px;">
            <div><strong>Total:</strong> ${{ number_format((float) $checkout->amount_total, 2) }}</div>
            <div><strong>Paid:</strong> ${{ number_format((float) $checkout->amount_paid, 2) }}</div>
            <div><strong>Balance:</strong> ${{ number_format((float) $checkout->amount_due, 2) }}</div>
        </div>
    </div>
</x-filament-panels::page>
