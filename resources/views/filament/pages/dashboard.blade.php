<x-filament-widgets::page>
    <div class="space-y-6">
        {{-- Header --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $practice->name }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Dashboard for {{ now()->format('F Y') }}
            </p>
        </div>

        {{-- Key Metrics Row 1 --}}
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
            {{-- Appointments This Month --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Appointments This Month</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                            {{ $appointmentsThisMonth }}
                        </p>
                    </div>
                    <div class="rounded-lg bg-blue-100 p-3 dark:bg-blue-900">
                        <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4 flex gap-4 text-sm">
                    <div>
                        <span class="text-green-600 dark:text-green-400">✓ {{ $appointmentsCompleted }}</span>
                        <p class="text-gray-500 dark:text-gray-400">Completed</p>
                    </div>
                    <div>
                        <span class="text-yellow-600 dark:text-yellow-400">⏱ {{ $appointmentsPending }}</span>
                        <p class="text-gray-500 dark:text-gray-400">Pending</p>
                    </div>
                </div>
            </div>

            {{-- Total Patients --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Patients</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                            {{ $totalPatients }}
                        </p>
                    </div>
                    <div class="rounded-lg bg-green-100 p-3 dark:bg-green-900">
                        <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3.645A1.645 1.645 0 012 19.355V5.645A1.645 1.645 0 013.645 4h7.71m6 0a4 4 0 110 5.292M21 21H9.645A1.645 1.645 0 019 19.355V5.645A1.645 1.645 0 019.645 4h7.71"></path>
                        </svg>
                    </div>
                </div>
                <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                    <span class="font-semibold text-green-600 dark:text-green-400">+{{ $newPatientsThisMonth }}</span>
                    new this month
                </p>
            </div>

            {{-- Total Revenue --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Revenue This Month</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                            {{ $formattedRevenue }}
                        </p>
                    </div>
                    <div class="rounded-lg bg-purple-100 p-3 dark:bg-purple-900">
                        <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Completed Sessions:</span>
                    <span class="font-semibold">{{ $checkoutSessionsCompleted }}</span>
                </div>
            </div>

            {{-- Pending Revenue --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Pending Revenue</p>
                        <p class="mt-2 text-3xl font-bold text-orange-600 dark:text-orange-400">
                            {{ $formattedPendingRevenue }}
                        </p>
                    </div>
                    <div class="rounded-lg bg-orange-100 p-3 dark:bg-orange-900">
                        <svg class="h-6 w-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                    Awaiting payment from patients
                </p>
            </div>
        </div>

        {{-- Appointment Status & Revenue by Practitioner --}}
        <div class="grid gap-6 lg:grid-cols-2">
            {{-- Appointment Status Breakdown --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Appointment Status Breakdown</h2>
                <div class="mt-6 space-y-4">
                    @php
                        $statusColors = [
                            'scheduled' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'dark_text' => 'dark:text-blue-300', 'label' => 'Scheduled'],
                            'in_progress' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700', 'dark_text' => 'dark:text-yellow-300', 'label' => 'In Progress'],
                            'completed' => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'dark_text' => 'dark:text-green-300', 'label' => 'Completed'],
                            'closed' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'dark_text' => 'dark:text-gray-300', 'label' => 'Closed'],
                            'checkout' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-700', 'dark_text' => 'dark:text-purple-300', 'label' => 'Checkout'],
                        ];
                    @endphp
                    @forelse ($appointmentsByStatus as $status => $count)
                        @php
                            $colors = $statusColors[$status] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-700', 'dark_text' => 'dark:text-gray-300', 'label' => ucfirst($status)];
                        @endphp
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="rounded {{ $colors['bg'] }}">
                                    <span class="inline-block rounded px-2 py-1 text-sm font-medium {{ $colors['text'] }} dark:{{ ltrim($colors['dark_text'], 'dark:') }}">
                                        {{ $colors['label'] }}
                                    </span>
                                </div>
                            </div>
                            <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $count }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">No appointments yet</p>
                    @endforelse
                </div>
            </div>

            {{-- Revenue by Practitioner --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Revenue by Practitioner</h2>
                <div class="mt-6 space-y-4">
                    @forelse ($revenueByPractitioner as $data)
                        <div class="flex items-center justify-between border-b border-gray-200 pb-4 last:border-b-0 dark:border-gray-700">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ $data['practitioner_name'] }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $data['appointments'] }} appointment(s)</p>
                            </div>
                            <p class="text-lg font-bold text-purple-600 dark:text-purple-400">
                                {{ \Illuminate\Support\Number::currency($data['revenue'] / 100, 'USD') }}
                            </p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">No revenue data available</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::page>
