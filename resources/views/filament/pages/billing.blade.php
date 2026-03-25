<x-filament-panels::page>

    {{-- Subscription required notice --}}
    @if (session('subscription_required'))
        <x-filament::section>
            <div class="flex items-center gap-3 text-warning-600">
                <x-heroicon-o-exclamation-triangle class="h-5 w-5" />
                <span class="font-medium">{{ session('subscription_required') }}</span>
            </div>
        </x-filament::section>
    @endif

    {{-- Current plan card --}}
    <x-filament::section heading="Current Subscription">
        @if ($subscription && $subscription->stripe_status === 'active')
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ $currentPlan?->name ?? 'Unknown Plan' }}
                        </span>
                        <x-filament::badge color="success">Active</x-filament::badge>
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $currentPlan?->monthlyDollars() ?? '—' }}/month
                        · {{ $currentPlan?->practitionerLimit() ?? '—' }} practitioners
                    </p>
                    @if ($subscription->ends_at)
                        <p class="mt-1 text-sm text-warning-600">
                            Cancels on {{ $subscription->ends_at->format('M d, Y') }}
                        </p>
                    @endif
                </div>

                @if ($currentPlan)
                    <ul class="text-sm text-gray-600 dark:text-gray-300 space-y-1">
                        @foreach ($currentPlan->features as $feature)
                            <li class="flex items-center gap-1.5">
                                <x-heroicon-o-check-circle class="h-4 w-4 text-success-500" />
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @elseif ($subscription && $subscription->stripe_status === 'past_due')
            <x-filament::badge color="danger">Payment Past Due</x-filament::badge>
            <p class="mt-2 text-sm text-gray-500">Please update your payment method in the Stripe billing portal.</p>
        @else
            <div class="flex items-center gap-2">
                <x-filament::badge color="gray">No Active Subscription</x-filament::badge>
            </div>
            <p class="mt-2 text-sm text-gray-500">Select a plan below to get started.</p>
        @endif
    </x-filament::section>

    {{-- Plan comparison --}}
    <x-filament::section heading="Available Plans">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            @foreach ($allPlans as $plan)
                @php $isCurrent = $currentPlan?->key === $plan->key; @endphp
                <div @class([
                    'rounded-xl border p-5 flex flex-col gap-3',
                    'border-primary-500 bg-primary-50 dark:bg-primary-950 ring-2 ring-primary-500' => $isCurrent,
                    'border-gray-200 dark:border-gray-700' => ! $isCurrent,
                ])>
                    <div class="flex items-center justify-between">
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $plan->name }}</span>
                        @if ($isCurrent)
                            <x-filament::badge color="primary" size="sm">Current</x-filament::badge>
                        @endif
                    </div>

                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $plan->monthlyDollars() }}
                        <span class="text-sm font-normal text-gray-500">/month</span>
                    </p>

                    <p class="text-sm text-gray-500">{{ $plan->practitionerLimit() }} practitioners</p>

                    <ul class="space-y-1.5 text-sm text-gray-600 dark:text-gray-300">
                        @foreach ($plan->features as $feature)
                            <li class="flex items-center gap-1.5">
                                <x-heroicon-o-check class="h-4 w-4 text-success-500 shrink-0" />
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </x-filament::section>

</x-filament-panels::page>
