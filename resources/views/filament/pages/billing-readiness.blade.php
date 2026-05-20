<x-filament-panels::page>
    @php
        $yesNo = fn (bool $value): string => $value ? 'Yes' : 'No';
        $statusStyle = fn (bool $value): string => $value
            ? 'background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;'
            : 'background:#fef2f2;color:#991b1b;border:1px solid #fecaca;';
    @endphp

    <div style="display:grid;gap:1rem;max-width:960px;">
        <div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;padding:1rem;">
            <h2 style="font-size:1rem;font-weight:800;margin:0 0 .75rem;color:#111827;">Stripe Configuration</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.75rem;">
                @foreach ([
                    'Stripe public key configured' => $readiness['stripe_public_key_configured'],
                    'Stripe secret configured' => $readiness['stripe_secret_configured'],
                    'Stripe webhook secret configured' => $readiness['stripe_webhook_secret_configured'],
                    'Starter price ID configured' => $readiness['configured_price_ids']['solo'] ?? false,
                    'Plus price ID configured' => $readiness['configured_price_ids']['clinic'] ?? false,
                    'Clinic price ID configured' => $readiness['configured_price_ids']['enterprise'] ?? false,
                    'Subscription plan rows exist' => $readiness['subscription_plan_rows_exist'],
                    'Active plans have price IDs' => $readiness['active_plan_price_ids_present'],
                ] as $label => $value)
                    <div style="border-radius:8px;padding:.75rem;{{ $statusStyle((bool) $value) }}">
                        <div style="font-size:.8rem;font-weight:700;">{{ $label }}</div>
                        <div style="font-size:1.1rem;font-weight:900;margin-top:.25rem;">{{ $yesNo((bool) $value) }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;padding:1rem;">
            <h2 style="font-size:1rem;font-weight:800;margin:0 0 .75rem;color:#111827;">Subscription Plans</h2>
            @if ($readiness['plans']->isEmpty())
                <p style="margin:0;color:#6b7280;">No subscription plan rows exist. Run <code>php artisan billing:sync-stripe-prices</code>.</p>
            @else
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
                        <thead>
                            <tr style="border-bottom:1px solid #e5e7eb;text-align:left;color:#374151;">
                                <th style="padding:.5rem;">Key</th>
                                <th style="padding:.5rem;">Name</th>
                                <th style="padding:.5rem;">Price</th>
                                <th style="padding:.5rem;">Interval</th>
                                <th style="padding:.5rem;">Active</th>
                                <th style="padding:.5rem;">Stripe Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($readiness['plans'] as $plan)
                                <tr style="border-bottom:1px solid #f3f4f6;">
                                    <td style="padding:.5rem;font-weight:700;">{{ $plan->key }}</td>
                                    <td style="padding:.5rem;">{{ $plan->name }}</td>
                                    <td style="padding:.5rem;">{{ $plan->monthlyDollars() }}/month</td>
                                    <td style="padding:.5rem;">Monthly</td>
                                    <td style="padding:.5rem;">{{ $yesNo((bool) $plan->is_active) }}</td>
                                    <td style="padding:.5rem;">{{ $catalog->mask($plan->stripe_price_id) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
