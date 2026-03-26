<x-filament-panels::page>

    <div class="mx-auto max-w-4xl space-y-6">

        {{-- Subscription required notice --}}
        @if (session('subscription_required'))
            <div class="rounded-lg border border-warning-300 bg-warning-50 p-4 dark:border-warning-600 dark:bg-warning-950">
                <div class="flex items-center gap-3 text-warning-600 dark:text-warning-400">
                    <span class="text-lg font-bold">⚠</span>
                    <span class="font-medium">{{ session('subscription_required') }}</span>
                </div>
            </div>
        @endif

        {{-- Current plan card --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Current Subscription</h2>

            @if ($subscription && $subscription->stripe_status === 'active')
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $currentPlan?->name ?? 'Unknown Plan' }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ $currentPlan?->monthlyDollars() ?? '—' }}/month
                                · {{ $currentPlan?->practitionerLimit() ?? '—' }} practitioners
                            </p>
                        </div>
                        <div class="rounded-full bg-success-100 px-4 py-2 dark:bg-success-900">
                            <span class="text-xs font-semibold text-success-700 dark:text-success-300">Active</span>
                        </div>
                    </div>

                    @if ($subscription->ends_at)
                        <p class="text-sm text-warning-600 dark:text-warning-400">
                            Cancels on {{ $subscription->ends_at->format('M d, Y') }}
                        </p>
                    @endif

                    @if ($currentPlan)
                        <ul class="mt-4 space-y-2">
                            @foreach ($currentPlan->features as $feature)
                                <li class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                    <span class="text-success-500 font-bold flex-shrink-0">✓</span>
                                    <span>{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @elseif ($subscription && $subscription->stripe_status === 'past_due')
                <div class="space-y-3">
                    <div class="rounded-full bg-danger-100 px-4 py-2 inline-block dark:bg-danger-900">
                        <span class="text-xs font-semibold text-danger-700 dark:text-danger-300">Payment Past Due</span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Please update your payment method in the Stripe billing portal.</p>
                </div>
            @else
                <div class="space-y-3">
                    <div class="rounded-full bg-gray-100 px-4 py-2 inline-block dark:bg-gray-800">
                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">No Active Subscription</span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Select a plan below to get started.</p>
                </div>
            @endif
        </div>

        {{-- Plan comparison grid --}}
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Choose Your Plan</h2>
            <div class="grid gap-6 grid-cols-1 md:grid-cols-3">
                @foreach ($allPlans as $plan)
                    @php $isCurrent = $currentPlan && $currentPlan->key === $plan->key; @endphp
                    <div @class([
                        'rounded-lg border p-6 flex flex-col gap-4',
                        'border-primary-500 bg-primary-50 dark:bg-primary-950 ring-2 ring-primary-500' => $isCurrent,
                        'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900' => ! $isCurrent,
                    ])>
                        {{-- Plan header --}}
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ $plan->name }}</h3>
                            @if ($isCurrent)
                                <div class="rounded-full bg-primary-100 px-2 py-1 dark:bg-primary-900">
                                    <span class="text-xs font-semibold text-primary-700 dark:text-primary-300">Current</span>
                                </div>
                            @endif
                        </div>

                        {{-- Price --}}
                        <div>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">
                                {{ $plan->monthlyDollars() }}
                                <span class="text-sm font-normal text-gray-500 dark:text-gray-400">/mo</span>
                            </p>
                        </div>

                        {{-- Practitioner limit --}}
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            {{ $plan->practitionerLimit() }} practitioners
                        </p>

                        {{-- Features list --}}
                        <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300 flex-grow">
                            @foreach ($plan->features as $feature)
                                <li class="flex items-start gap-2">
                                    <span class="text-success-500 font-bold flex-shrink-0 mt-0.5">✓</span>
                                    <span>{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>

    </div>

</x-filament-panels::page>
