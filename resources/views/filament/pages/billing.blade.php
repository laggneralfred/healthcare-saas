<x-filament-panels::page>

    <div style="margin: 0 auto; max-width: 64rem; padding: 1.5rem; display: flex; flex-direction: column; gap: 2rem;">

        {{--
            $hasActiveSubscription, $hasPastDueSubscription, $activePriceId,
            $currentPlanName, and $subscriptionEndsAt are Livewire public properties —
            they are reactive and always reflect the latest subscription state.
            $currentPlan and $allPlans come from getViewData().
        --}}

        {{-- Current Subscription Section --}}
        <div style="background-color: #f9fafb; border-radius: 0.5rem; padding: 1.5rem; border: 1px solid #e5e7eb;">
            <h2 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">Current Subscription</h2>

            @if ($hasActiveSubscription)
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <div style="display: flex; align-items: baseline; justify-content: space-between;">
                        <div>
                            <h3 style="font-size: 1.5rem; font-weight: 700; color: #111827;">
                                {{ $currentPlanName ?? 'Unknown Plan' }}
                            </h3>
                            <p style="font-size: 0.875rem; color: #4b5563; margin-top: 0.25rem;">
                                {{ $currentPlan?->monthlyDollars() ?? '—' }}/month
                                &middot;
                                {{ $currentPlan?->practitionerLimit() ?? '—' }} practitioners
                            </p>
                        </div>
                        <span style="display: inline-flex; align-items: center; border-radius: 9999px; background-color: #dcfce7; padding: 0.25rem 0.75rem; font-size: 0.875rem; font-weight: 500; color: #166534;">
                            ✓ Active
                        </span>
                    </div>

                    @if ($subscriptionEndsAt)
                        <p style="font-size: 0.875rem; color: #d97706;">
                            Cancels on {{ $subscriptionEndsAt }}
                        </p>
                    @endif

                    @if ($currentPlan && count($currentPlan->features ?? []))
                        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                            <p style="font-size: 0.875rem; font-weight: 500; color: #374151; margin-bottom: 0.75rem;">Included features:</p>
                            <ul style="display: flex; flex-direction: column; gap: 0.5rem; list-style: none; padding: 0; margin: 0;">
                                @foreach ($currentPlan->features as $feature)
                                    <li style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: #4b5563;">
                                        <span style="color: #16a34a; font-weight: 700;">✓</span>
                                        <span>{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

            @elseif ($hasPastDueSubscription)
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <p style="font-size: 1.125rem; font-weight: 500; color: #dc2626;">⚠ Payment Past Due</p>
                    <p style="font-size: 0.875rem; color: #4b5563;">Please update your payment method in the Stripe billing portal to keep your subscription active.</p>
                </div>

            @else
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <p style="font-size: 1.125rem; font-weight: 500; color: #4b5563;">No Active Subscription</p>
                    <p style="font-size: 0.875rem; color: #4b5563;">Select a plan below to get started with your practice management.</p>
                </div>
            @endif
        </div>

        {{-- Plans Section --}}
        <div>
            <h2 style="font-size: 1.125rem; font-weight: 600; color: #111827; margin-bottom: 1.5rem;">Choose Your Plan</h2>

            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-top: 1rem;">
                @foreach ($allPlans as $plan)
                    @php
                        // $activePriceId is a reactive Livewire property — always current.
                        $isCurrent     = $activePriceId && $plan->stripe_price_id && $plan->stripe_price_id === $activePriceId;
                        $isMostPopular = $plan->key === 'clinic';
                    @endphp

                    <div style="position: relative; display: flex; flex-direction: column; border-radius: 1.125rem; background-color: #ffffff; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); overflow: hidden; transition: all 0.3s ease; {{ $isCurrent ? 'border: 2px solid #3b82f6;' : ($isMostPopular ? 'border: 2px solid #3b82f6;' : 'border: 1px solid #e5e7eb;') }}" onmouseover="this.style.boxShadow='0 20px 25px -5px rgba(0,0,0,0.1)'; this.style.transform='scale(1.05)';" onmouseout="this.style.boxShadow='0 10px 15px -3px rgba(0,0,0,0.1)'; this.style.transform='scale(1)';">

                        {{-- Most Popular Badge --}}
                        @if ($isMostPopular)
                            <div style="position: absolute; top: -0.5rem; left: 50%; transform: translateX(-50%); display: inline-flex; align-items: center; border-radius: 9999px; background-color: #3b82f6; padding: 0.25rem 1rem; font-size: 0.75rem; font-weight: 700; color: #ffffff;">
                                MOST POPULAR
                            </div>
                        @endif

                        <div style="padding: 1.5rem; {{ $isMostPopular ? 'padding-top: 2.5rem;' : '' }} display: flex; flex-direction: column; flex-grow: 1; gap: 1.5rem;">

                            {{-- Plan Name --}}
                            <h3 style="font-size: 1.25rem; font-weight: 700; color: #111827;">
                                {{ $plan->name }}
                            </h3>

                            {{-- Price --}}
                            <div style="padding-bottom: 1.5rem; border-bottom: 1px solid #e5e7eb;">
                                <p style="font-size: 2.25rem; font-weight: 700; color: #111827;">
                                    {{ $plan->monthlyDollars() }}
                                </p>
                                <p style="font-size: 0.875rem; color: #4b5563; margin-top: 0.25rem;">per month</p>
                            </div>

                            {{-- Practitioners --}}
                            <p style="font-size: 0.875rem; font-weight: 500; color: #4b5563;">
                                {{ $plan->practitionerLimit() }} practitioners
                            </p>

                            {{-- Features --}}
                            <ul style="display: flex; flex-direction: column; gap: 0.75rem; list-style: none; padding: 0; margin: 0; flex-grow: 1;">
                                @foreach ($plan->features as $feature)
                                    <li style="display: flex; align-items: flex-start; gap: 0.75rem;">
                                        <span style="color: #16a34a; font-weight: 700; font-size: 1.125rem; flex-shrink: 0; margin-top: 0.125rem;">✓</span>
                                        <span style="font-size: 0.875rem; color: #4b5563;">{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>

                            {{-- CTA --}}
                            @if ($isCurrent)
                                <div style="padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                                    <span style="display: inline-flex; align-items: center; border-radius: 9999px; background-color: #dcfce7; padding: 0.35rem 1rem; font-size: 0.8rem; font-weight: 700; color: #166534;">
                                        ✓ Current Plan
                                    </span>
                                </div>
                            @else
                                <div style="padding-top: 1rem;">
                                    <button
                                        wire:click="subscribeToPlan('{{ $plan->key }}')"
                                        wire:loading.attr="disabled"
                                        style="width: 100%; border-radius: 0.5rem; {{ $isMostPopular ? 'background-color: #3b82f6; color: white;' : 'background-color: #f3f4f6; color: #111827;' }} font-weight: 600; padding: 0.75rem 1rem; transition: background-color 0.2s; cursor: pointer; border: none; font-size: 1rem;"
                                        onmouseover="this.style.backgroundColor='{{ $isMostPopular ? '#2563eb' : '#e5e7eb' }}'"
                                        onmouseout="this.style.backgroundColor='{{ $isMostPopular ? '#3b82f6' : '#f3f4f6' }}'">
                                        <span wire:loading.remove wire:target="subscribeToPlan('{{ $plan->key }}')">Subscribe Now</span>
                                        <span wire:loading wire:target="subscribeToPlan('{{ $plan->key }}')">Processing…</span>
                                    </button>
                                </div>
                            @endif

                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Subscription Required Notice --}}
        @if (session('subscription_required'))
            <div style="border-radius: 0.5rem; border: 1px solid #fcd34d; background-color: #fef3c7; padding: 1rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem; color: #b45309;">
                    <span style="font-size: 1.125rem; font-weight: 700;">⚠</span>
                    <span style="font-size: 0.875rem; font-weight: 500;">{{ session('subscription_required') }}</span>
                </div>
            </div>
        @endif

    </div>

</x-filament-panels::page>
