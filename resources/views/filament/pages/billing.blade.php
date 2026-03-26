<x-filament-panels::page>

    <div class="mx-auto max-w-5xl px-4 py-6 space-y-8">

        {{-- Current Subscription Section --}}
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Current Subscription</h2>

            @if ($subscription && $subscription->stripe_status === 'active')
                <div class="space-y-3">
                    <div class="flex items-baseline justify-between">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $currentPlan?->name ?? 'Unknown Plan' }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ $currentPlan?->monthlyDollars() ?? '—' }}/month · {{ $currentPlan?->practitionerLimit() ?? '—' }} practitioners
                            </p>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-700 dark:bg-green-900 dark:text-green-200">
                            ✓ Active
                        </span>
                    </div>

                    @if ($subscription->ends_at)
                        <p class="text-sm text-amber-600 dark:text-amber-400">
                            Cancels on {{ $subscription->ends_at->format('M d, Y') }}
                        </p>
                    @endif

                    @if ($currentPlan)
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Included features:</p>
                            <ul class="space-y-2">
                                @foreach ($currentPlan->features as $feature)
                                    <li class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                        <span class="text-green-500 font-bold">✓</span>
                                        <span>{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @elseif ($subscription && $subscription->stripe_status === 'past_due')
                <div class="space-y-3">
                    <p class="text-lg font-medium text-red-600 dark:text-red-400">⚠ Payment Past Due</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Please update your payment method in the Stripe billing portal to keep your subscription active.</p>
                </div>
            @else
                <div class="space-y-3">
                    <p class="text-lg font-medium text-gray-600 dark:text-gray-400">No Active Subscription</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Select a plan below to get started with your practice management.</p>
                </div>
            @endif
        </div>

        {{-- Plans Section --}}
        <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Choose Your Plan</h2>

            <div class="grid gap-6 grid-cols-1 md:grid-cols-3">
                @foreach ($allPlans as $plan)
                    @php
                        $isCurrent = $currentPlan && $currentPlan->key === $plan->key;
                        $isMostPopular = $plan->key === 'clinic';
                    @endphp

                    <div class="relative flex flex-col rounded-xl bg-white dark:bg-gray-900 shadow-lg overflow-hidden transition-transform duration-200 hover:shadow-xl hover:scale-105 @if($isCurrent) ring-2 ring-blue-500 @elseif($isMostPopular) border-2 border-blue-500 @else border border-gray-200 dark:border-gray-700 @endif">

                        {{-- Most Popular Badge --}}
                        @if ($isMostPopular)
                            <div class="absolute -top-2 left-1/2 -translate-x-1/2">
                                <span class="inline-flex items-center rounded-full bg-blue-500 px-4 py-1 text-xs font-bold text-white">
                                    MOST POPULAR
                                </span>
                            </div>
                        @endif

                        <div class="p-6 @if($isMostPopular) pt-10 @endif flex flex-col flex-grow space-y-6">

                            {{-- Plan Name --}}
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                                    {{ $plan->name }}
                                </h3>
                            </div>

                            {{-- Price --}}
                            <div class="pb-6 border-b border-gray-200 dark:border-gray-700">
                                <p class="text-4xl font-bold text-gray-900 dark:text-white">
                                    {{ $plan->monthlyDollars() }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">per month</p>
                            </div>

                            {{-- Practitioners --}}
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                    {{ $plan->practitionerLimit() }} practitioners
                                </p>
                            </div>

                            {{-- Features --}}
                            <div class="flex-grow">
                                <ul class="space-y-3">
                                    @foreach ($plan->features as $feature)
                                        <li class="flex items-start gap-3">
                                            <span class="text-green-500 font-bold text-lg leading-none flex-shrink-0 mt-0.5">✓</span>
                                            <span class="text-sm text-gray-600 dark:text-gray-300">{{ $feature }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            {{-- Current Badge --}}
                            @if ($isCurrent)
                                <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <span class="inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-900 px-3 py-1 text-xs font-bold text-blue-700 dark:text-blue-200">
                                        ✓ Current Plan
                                    </span>
                                </div>
                            @else
                                {{-- Subscribe Button --}}
                                <div class="pt-4">
                                    <button class="w-full rounded-lg @if($isMostPopular) bg-blue-500 hover:bg-blue-600 text-white @else bg-gray-100 hover:bg-gray-200 text-gray-900 dark:bg-gray-800 dark:hover:bg-gray-700 dark:text-white @endif font-semibold py-3 px-4 transition-colors duration-200">
                                        Subscribe Now
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
            <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 dark:border-amber-600 dark:bg-amber-950">
                <div class="flex items-center gap-3 text-amber-600 dark:text-amber-400">
                    <span class="text-lg font-bold">⚠</span>
                    <span class="text-sm font-medium">{{ session('subscription_required') }}</span>
                </div>
            </div>
        @endif

    </div>

</x-filament-panels::page>
