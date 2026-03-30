<x-filament-panels::page>

    {{-- ── Step indicator ─────────────────────────────────────────────────── --}}
    @php
        $stepOrder = ['upload', 'map', 'confirm', 'importing', 'complete'];
        $stepLabels = ['Upload', 'Map Columns', 'Review', 'Importing', 'Done'];
        $currentIndex = array_search($step, $stepOrder);
    @endphp

    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
        @foreach ($stepLabels as $i => $label)
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <span style="
                    display: inline-flex; align-items: center; justify-content: center;
                    height: 1.75rem; width: 1.75rem; border-radius: 9999px;
                    font-size: 0.75rem; font-weight: 600;
                    background-color: {{ $i < $currentIndex ? '#16a34a' : ($i === $currentIndex ? '#2563eb' : '#e5e7eb') }};
                    color: {{ $i <= $currentIndex ? '#ffffff' : '#6b7280' }};
                ">{{ $i < $currentIndex ? '✓' : ($i + 1) }}</span>
                <span style="
                    font-size: 0.875rem; font-weight: 500;
                    color: {{ $i === $currentIndex ? '#2563eb' : '#6b7280' }};
                ">{{ $label }}</span>
            </div>
            @if ($i < count($stepLabels) - 1)
                <div style="height: 1px; width: 2rem; background-color: #d1d5db; margin: 0 0.25rem;"></div>
            @endif
        @endforeach
    </div>

    {{-- ─────────────────────────────────────────────────────────────────────
         STEP 1: UPLOAD
    ───────────────────────────────────────────────────────────────────────── --}}
    @if ($step === 'upload')
    <div style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1.5rem; box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05);">
        <h2 style="font-size: 1rem; font-weight: 600; color: #111827; margin-bottom: 0.25rem;">Upload CSV File</h2>
        <p style="font-size: 0.875rem; color: #4b5563; margin-bottom: 1rem;">
            Select a CSV file containing patient data. Column mapping happens in the next step.
        </p>

        <label for="file-upload" style="
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            border: 2px dashed #d1d5db; border-radius: 0.5rem; padding: 2rem;
            text-align: center; cursor: pointer;
        ">
            <svg style="height: 2.5rem; width: 2.5rem; color: #9ca3af; margin-bottom: 0.75rem;"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>

            <div wire:loading.remove wire:target="uploadedFile">
                <p style="font-size: 0.875rem; font-weight: 500; color: #374151;">Click to upload or drag and drop</p>
                <p style="font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem;">CSV files only, max 10 MB</p>
            </div>

            <div wire:loading wire:target="uploadedFile"
                style="font-size: 0.875rem; font-weight: 500; color: #2563eb;">
                Processing file…
            </div>

            <input id="file-upload" type="file" wire:model="uploadedFile"
                accept=".csv,text/csv,text/plain"
                style="position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
                       overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border-width: 0;">
        </label>

        @error('uploadedFile')
            <p style="font-size: 0.875rem; color: #dc2626; margin-top: 0.5rem;">{{ $message }}</p>
        @enderror

        <div style="margin-top: 1rem;">
            <button wire:click="downloadTemplate" type="button" style="
                display: inline-flex; align-items: center; gap: 0.5rem;
                padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500;
                color: #374151; background-color: #ffffff;
                border: 1px solid #d1d5db; border-radius: 0.5rem; cursor: pointer;
            ">
                <svg style="height: 1rem; width: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Download Template CSV
            </button>
        </div>
    </div>
    @endif

    {{-- ─────────────────────────────────────────────────────────────────────
         STEP 2: MAP COLUMNS
    ───────────────────────────────────────────────────────────────────────── --}}
    @if ($step === 'map')
    <div style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1.5rem; box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05);">
        <h2 style="font-size: 1rem; font-weight: 600; color: #111827; margin-bottom: 0.25rem;">Map Columns</h2>
        <p style="font-size: 0.875rem; color: #4b5563; margin-bottom: 1rem;">
            We detected {{ count($detectedHeaders) }} column{{ count($detectedHeaders) === 1 ? '' : 's' }}.
            Map each one to a patient field. Unmapped columns are ignored.
        </p>

        @foreach ($detectedHeaders as $i => $header)
        <div style="display: flex; align-items: center; gap: 1rem; padding: 0.75rem 0;
                    {{ !$loop->last ? 'border-bottom: 1px solid #f3f4f6;' : '' }}">
            <span style="width: 12rem; font-family: ui-monospace, monospace; font-size: 0.875rem;
                         color: #374151; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"
                title="{{ $header }}">{{ $header }}</span>
            <svg style="height: 1rem; width: 1rem; flex-shrink: 0; color: #9ca3af;"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
            </svg>
            <select wire:model.live="mappings.{{ $i }}" style="
                padding: 0.375rem 0.75rem; font-size: 0.875rem; color: #111827;
                background-color: #ffffff; border: 1px solid #d1d5db; border-radius: 0.375rem;
                min-width: 12rem; cursor: pointer;
            ">
                @foreach ($this->getFieldOptions() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        @endforeach

        @error('mappings')
            <p style="font-size: 0.875rem; color: #dc2626; margin-top: 0.75rem;">{{ $message }}</p>
        @enderror

        <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
            <button wire:click="analyze" type="button" style="
                display: inline-flex; align-items: center; gap: 0.5rem;
                padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600;
                color: #ffffff; background-color: #2563eb;
                border: none; border-radius: 0.5rem; cursor: pointer;
            ">Analyse →</button>
            <button wire:click="resetImport" type="button" style="
                display: inline-flex; align-items: center; gap: 0.5rem;
                padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500;
                color: #374151; background-color: #ffffff;
                border: 1px solid #d1d5db; border-radius: 0.5rem; cursor: pointer;
            ">← Back</button>
        </div>
    </div>
    @endif

    {{-- ─────────────────────────────────────────────────────────────────────
         STEP 3: REVIEW & CONFIRM  (polls every 2 s)
    ───────────────────────────────────────────────────────────────────────── --}}
    @if ($step === 'confirm')
    <div wire:poll.2000ms="checkDryRun">
        @if ($sessionStatus === 'analyzing' || $sessionStatus === 'pending')

        <div style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 0.5rem;
                    padding: 2rem; text-align: center; box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05);">
            <svg style="height: 2rem; width: 2rem; margin: 0 auto 0.75rem; animation: spin 1s linear infinite; color: #2563eb;"
                fill="none" viewBox="0 0 24 24">
                <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <style>@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }</style>
            <p style="font-size: 0.875rem; font-weight: 500; color: #374151;">Analysing your file…</p>
        </div>

        @elseif ($sessionStatus === 'failed')

        <div style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 0.5rem; padding: 1.5rem;">
            <h2 style="font-size: 1rem; font-weight: 600; color: #991b1b; margin-bottom: 0.25rem;">Analysis Failed</h2>
            <p style="font-size: 0.875rem; color: #b91c1c; margin-bottom: 1rem;">
                Something went wrong reading your file. Please check it is a valid CSV and try again.
            </p>
            <button wire:click="resetImport" type="button" style="
                padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500;
                color: #374151; background-color: #ffffff;
                border: 1px solid #d1d5db; border-radius: 0.5rem; cursor: pointer;
            ">← Start Over</button>
        </div>

        @else

        <div style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 0.5rem;
                    padding: 1.5rem; box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05);">
            <h2 style="font-size: 1rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">Import Preview</h2>

            {{-- Stats row --}}
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem; margin-bottom: 1.5rem;">
                <div style="background-color: #f9fafb; border-radius: 0.5rem; padding: 0.75rem; text-align: center;">
                    <p style="font-size: 1.5rem; font-weight: 700; color: #111827; margin-bottom: 0.125rem;">{{ $totalRows }}</p>
                    <p style="font-size: 0.75rem; color: #6b7280;">Total rows</p>
                </div>
                <div style="background-color: #f0fdf4; border-radius: 0.5rem; padding: 0.75rem; text-align: center;">
                    <p style="font-size: 1.5rem; font-weight: 700; color: #15803d; margin-bottom: 0.125rem;">{{ $validRows }}</p>
                    <p style="font-size: 0.75rem; color: #16a34a;">Will import</p>
                </div>
                <div style="background-color: #fefce8; border-radius: 0.5rem; padding: 0.75rem; text-align: center;">
                    <p style="font-size: 1.5rem; font-weight: 700; color: #a16207; margin-bottom: 0.125rem;">{{ $duplicateRows }}</p>
                    <p style="font-size: 0.75rem; color: #ca8a04;">Duplicates (skip)</p>
                </div>
                <div style="background-color: #fef2f2; border-radius: 0.5rem; padding: 0.75rem; text-align: center;">
                    <p style="font-size: 1.5rem; font-weight: 700; color: #dc2626; margin-bottom: 0.125rem;">{{ $errorRows }}</p>
                    <p style="font-size: 0.75rem; color: #ef4444;">Errors (skip)</p>
                </div>
            </div>

            {{-- Valid rows preview --}}
            @if (!empty($previewRows))
            <div style="margin-bottom: 1.5rem;">
                <h3 style="font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">
                    Sample valid rows (first {{ count($previewRows) }})
                </h3>
                <div style="overflow-x: auto; border: 1px solid #e5e7eb; border-radius: 0.5rem;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                        <thead>
                            <tr style="background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                                @foreach (array_keys($previewRows[0]) as $field)
                                <th style="padding: 0.5rem 0.75rem; text-align: left; font-size: 0.75rem;
                                           font-weight: 600; text-transform: uppercase; letter-spacing: 0.025em; color: #4b5563;">
                                    {{ \App\Services\CsvColumnMapper::PATIENT_FIELDS[$field] ?? $field }}
                                </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($previewRows as $row)
                            <tr style="border-bottom: 1px solid #f3f4f6;">
                                @foreach ($row as $value)
                                <td style="padding: 0.5rem 0.75rem; color: #374151;">{{ $value }}</td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Error preview --}}
            @if (!empty($errorPreview))
            <div style="margin-bottom: 1.5rem;">
                <h3 style="font-size: 0.875rem; font-weight: 600; color: #dc2626; margin-bottom: 0.5rem;">
                    Rows with errors (will be skipped)
                </h3>
                @foreach ($errorPreview as $errorRow)
                <div style="background-color: #fef2f2; border-radius: 0.375rem; padding: 0.5rem 0.75rem;
                            font-size: 0.75rem; color: #b91c1c; margin-bottom: 0.25rem;">
                    {{ implode(' · ', $errorRow['issues'] ?? []) }}
                </div>
                @endforeach
            </div>
            @endif

            @error('import')
                <p style="font-size: 0.875rem; color: #dc2626; margin-bottom: 0.75rem;">{{ $message }}</p>
            @enderror

            <div style="display: flex; gap: 0.75rem; align-items: center;">
                @if ($validRows > 0)
                <button wire:click="startImport" type="button" style="
                    display: inline-flex; align-items: center; gap: 0.5rem;
                    padding: 0.5rem 1.25rem; font-size: 0.875rem; font-weight: 600;
                    color: #ffffff; background-color: #2563eb;
                    border: none; border-radius: 0.5rem; cursor: pointer;
                ">Import {{ $validRows }} Patient{{ $validRows === 1 ? '' : 's' }} →</button>
                @else
                <p style="font-size: 0.875rem; color: #dc2626;">No valid rows found — nothing to import.</p>
                @endif
                <button wire:click="resetImport" type="button" style="
                    display: inline-flex; align-items: center; gap: 0.5rem;
                    padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500;
                    color: #374151; background-color: #ffffff;
                    border: 1px solid #d1d5db; border-radius: 0.5rem; cursor: pointer;
                ">← Start Over</button>
            </div>
        </div>

        @endif
    </div>
    @endif

    {{-- ─────────────────────────────────────────────────────────────────────
         STEP 4: IMPORTING  (polls every 2 s)
    ───────────────────────────────────────────────────────────────────────── --}}
    @if ($step === 'importing')
    <div wire:poll.2000ms="checkImport">
        <div style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 0.5rem;
                    padding: 2rem; text-align: center; box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05);">
            <svg style="height: 2rem; width: 2rem; margin: 0 auto 0.75rem; animation: spin 1s linear infinite; color: #2563eb;"
                fill="none" viewBox="0 0 24 24">
                <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <p style="font-size: 0.875rem; font-weight: 600; color: #374151;">Importing patients…</p>
            <p style="font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem;">This page will update automatically when done.</p>
        </div>
    </div>
    @endif

    {{-- ─────────────────────────────────────────────────────────────────────
         STEP 5: COMPLETE
    ───────────────────────────────────────────────────────────────────────── --}}
    @if ($step === 'complete')
    <div style="display: flex; flex-direction: column; gap: 1rem;">
        @if ($sessionStatus === 'failed')
        <div style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 0.5rem; padding: 1.5rem;">
            <h2 style="font-size: 1rem; font-weight: 600; color: #991b1b; margin-bottom: 0.25rem;">Import Failed</h2>
            <p style="font-size: 0.875rem; color: #b91c1c;">An error occurred while importing. Please try again.</p>
        </div>
        @else
        <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 0.5rem; padding: 1.5rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div style="display: flex; align-items: center; justify-content: center;
                            height: 2.5rem; width: 2.5rem; flex-shrink: 0;
                            background-color: #16a34a; border-radius: 9999px;">
                    <svg style="height: 1.25rem; width: 1.25rem; color: #ffffff;"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div>
                    <h2 style="font-size: 1rem; font-weight: 600; color: #166534;">Import Complete</h2>
                    <p style="font-size: 0.875rem; color: #15803d;">
                        {{ $importedRows }} patient{{ $importedRows === 1 ? '' : 's' }} imported successfully.
                    </p>
                </div>
            </div>
        </div>
        @endif

        <div style="display: flex; gap: 0.75rem;">
            <button wire:click="resetImport" type="button" style="
                display: inline-flex; align-items: center; gap: 0.5rem;
                padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 600;
                color: #ffffff; background-color: #2563eb;
                border: none; border-radius: 0.5rem; cursor: pointer;
            ">Import Another File</button>
            <a href="/admin/patients" style="
                display: inline-flex; align-items: center; gap: 0.5rem;
                padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500;
                color: #374151; background-color: #ffffff; text-decoration: none;
                border: 1px solid #d1d5db; border-radius: 0.5rem;
            ">View Patients →</a>
        </div>
    </div>
    @endif

    {{-- ─────────────────────────────────────────────────────────────────────
         IMPORT HISTORY (always visible)
    ───────────────────────────────────────────────────────────────────────── --}}
    <div style="margin-top: 1.5rem; background-color: #ffffff; border: 1px solid #e5e7eb;
                border-radius: 0.5rem; padding: 1.5rem; box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05);">
        <h3 style="font-size: 1rem; font-weight: 600; color: #111827; margin-bottom: 1rem;">Recent Imports</h3>

        @php $imports = $this->getRecentImports(); @endphp

        @if ($imports->isEmpty())
            <p style="font-size: 0.875rem; color: #6b7280;">No imports yet.</p>
        @else
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                <thead>
                    <tr style="background-color: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #4b5563;">File</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #4b5563;">Status</th>
                        <th style="padding: 0.5rem 1rem; text-align: center; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #4b5563;">Total</th>
                        <th style="padding: 0.5rem 1rem; text-align: center; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #4b5563;">Imported</th>
                        <th style="padding: 0.5rem 1rem; text-align: center; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #4b5563;">Skipped</th>
                        <th style="padding: 0.5rem 1rem; text-align: left; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: #4b5563;">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($imports as $import)
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 0.5rem 1rem; font-weight: 500; color: #111827;">{{ $import->filename }}</td>
                        <td style="padding: 0.5rem 1rem;">
                            @if ($import->status === 'completed')
                                <span style="display: inline-block; background-color: #dcfce7; color: #166534;
                                             padding: 0.125rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500;">
                                    Completed
                                </span>
                            @elseif ($import->status === 'processing')
                                <span style="display: inline-block; background-color: #dbeafe; color: #1e40af;
                                             padding: 0.125rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500;">
                                    Processing
                                </span>
                            @elseif ($import->status === 'failed')
                                <span style="display: inline-block; background-color: #fee2e2; color: #991b1b;
                                             padding: 0.125rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500;">
                                    Failed
                                </span>
                            @else
                                <span style="display: inline-block; background-color: #f3f4f6; color: #374151;
                                             padding: 0.125rem 0.625rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500;">
                                    {{ ucfirst($import->status) }}
                                </span>
                            @endif
                        </td>
                        <td style="padding: 0.5rem 1rem; text-align: center; color: #374151;">{{ $import->total_rows }}</td>
                        <td style="padding: 0.5rem 1rem; text-align: center; font-weight: 500; color: #15803d;">{{ $import->imported }}</td>
                        <td style="padding: 0.5rem 1rem; text-align: center; color: #a16207;">{{ $import->skipped }}</td>
                        <td style="padding: 0.5rem 1rem; color: #6b7280;">{{ $import->created_at->format('M d, Y H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</x-filament-panels::page>
