@php
    use Filament\Support\Enums\MaxWidth;
@endphp

<x-filament-panels::page>
    <form wire:submit="importRows" class="space-y-6">
        {{ $this->form }}

        <!-- Preview Section -->
        @if ($showPreview && !empty($previewRows))
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">
                    CSV Preview (First 5 Rows)
                </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                @foreach ($csvHeaders as $header)
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-900 dark:text-white">
                                        {{ $header }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($previewRows as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                    @foreach ($csvHeaders as $header)
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            {{ $row[$header] ?? '' }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Action Buttons -->
        <div class="flex gap-3">
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:ring-offset-gray-900"
            >
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Import Patients
            </button>
            <button
                type="button"
                wire:click="downloadTemplate"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:ring-offset-gray-900"
            >
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Download Template
            </button>
        </div>

        @if (session()->has('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-700 dark:bg-green-900 dark:text-green-200">
                {{ session('success') }}
            </div>
        @endif
    </form>

    <div class="space-y-6">
        <!-- CSV Format Help Section -->
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">
                CSV Format Guide
            </h3>
            <div class="space-y-4">
                <div>
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                        Supported Columns
                    </h4>
                    <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                        <li><span class="font-mono font-semibold">first_name</span> - Patient's first name (required)</li>
                        <li><span class="font-mono font-semibold">last_name</span> - Patient's last name (required)</li>
                        <li><span class="font-mono font-semibold">email</span> - Patient's email address (optional)</li>
                        <li><span class="font-mono font-semibold">phone</span> - Patient's phone number (optional)</li>
                        <li><span class="font-mono font-semibold">dob</span> - Date of birth in MM/DD/YYYY, DD/MM/YYYY, or YYYY-MM-DD format (optional)</li>
                        <li><span class="font-mono font-semibold">gender</span> - One of: male, female, other, prefer_not_to_say (optional)</li>
                        <li><span class="font-mono font-semibold">address</span> - Street address (optional)</li>
                        <li><span class="font-mono font-semibold">city</span> - City (optional)</li>
                        <li><span class="font-mono font-semibold">state</span> - State or province (optional)</li>
                        <li><span class="font-mono font-semibold">postal_code</span> - ZIP or postal code (optional)</li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                        Example CSV Format
                    </h4>
                    <div class="rounded-md bg-gray-100 p-3 dark:bg-gray-800">
                        <pre class="font-mono text-xs text-gray-800 dark:text-gray-200"><code>first_name,last_name,email,phone,dob,gender,city,state
John,Doe,john@example.com,555-123-4567,01/15/1985,male,New York,NY
Jane,Smith,jane@example.com,555-987-6543,03/22/1990,female,Los Angeles,CA</code></pre>
                    </div>
                </div>

                <div>
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                        Import Rules
                    </h4>
                    <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-400 list-disc list-inside">
                        <li>First name and last name are required for each row</li>
                        <li>Duplicate email addresses within your practice will be skipped</li>
                        <li>Phone numbers will be stripped of non-digit characters</li>
                        <li>Dates are parsed automatically in multiple formats</li>
                        <li>Empty or invalid rows will be skipped during import</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Import History Section -->
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">
                Recent Import History
            </h3>
            @php
                $practiceId = \App\Services\PracticeContext::currentPracticeId();
                $imports = $practiceId
                    ? \App\Models\ImportHistory::withoutPracticeScope()
                        ->where('practice_id', $practiceId)
                        ->orderByDesc('created_at')
                        ->limit(5)
                        ->get()
                    : collect();
            @endphp

            @if ($imports->isEmpty())
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    No import history yet. Upload a CSV file to get started.
                </p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-900 dark:text-white">
                                    Filename
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-900 dark:text-white">
                                    Status
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-900 dark:text-white">
                                    Total
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-900 dark:text-white">
                                    Imported
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-900 dark:text-white">
                                    Skipped
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-900 dark:text-white">
                                    Failed
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-900 dark:text-white">
                                    Date
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($imports as $import)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white font-medium">
                                        {{ $import->filename }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        @if ($import->status === 'completed')
                                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">
                                                Completed
                                            </span>
                                        @elseif ($import->status === 'processing')
                                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                Processing
                                            </span>
                                        @elseif ($import->status === 'failed')
                                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-200">
                                                Failed
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                                Pending
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-center text-gray-900 dark:text-white">
                                        {{ $import->total_rows }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-center text-green-600 dark:text-green-400 font-medium">
                                        {{ $import->imported }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-center text-yellow-600 dark:text-yellow-400 font-medium">
                                        {{ $import->skipped }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-center text-red-600 dark:text-red-400 font-medium">
                                        {{ $import->failed }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                        {{ $import->created_at->format('M d, Y H:i') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
