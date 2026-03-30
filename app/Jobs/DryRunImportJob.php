<?php

namespace App\Jobs;

use App\Models\ImportSession;
use App\Models\Patient;
use App\Services\CSVImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DryRunImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $importSessionId) {}

    public function handle(): void
    {
        $session = ImportSession::withoutPracticeScope()->find($this->importSessionId);

        if (!$session) {
            Log::error('DryRunImportJob: ImportSession not found', ['id' => $this->importSessionId]);
            return;
        }

        try {
            $session->update(['status' => 'analyzing']);

            $filePath = Storage::disk('local')->path($session->file_path);
            $handle   = fopen($filePath, 'r');

            if (!$handle) {
                throw new \RuntimeException("Cannot open CSV file: {$session->file_path}");
            }

            // Skip header row — mappings are index-based
            fgetcsv($handle);

            $mappings = $session->column_mappings ?? [];

            // Pre-load existing emails for this practice (lowercase for case-insensitive comparison)
            $existingEmails = Patient::withoutPracticeScope()
                ->where('practice_id', $session->practice_id)
                ->whereNotNull('email')
                ->pluck('email')
                ->mapWithKeys(fn($e) => [strtolower($e) => true])
                ->toArray();

            $seenInFile = [];
            $totalRows  = 0;
            $valid      = [];
            $duplicates = [];
            $errors     = [];

            while (($data = fgetcsv($handle)) !== false) {
                if (empty(array_filter($data))) {
                    continue;
                }

                $totalRows++;

                // Apply column mappings
                $row = [];
                foreach ($mappings as $index => $field) {
                    if ($field && isset($data[$index])) {
                        $row[$field] = trim($data[$index]);
                    }
                }

                // Validate required fields
                $issues = [];

                if (empty($row['first_name'])) {
                    $issues[] = 'Missing first name';
                }
                if (empty($row['last_name'])) {
                    $issues[] = 'Missing last name';
                }

                // Validate and deduplicate email
                if (!empty($row['email'])) {
                    $emailLower = strtolower($row['email']);

                    if (!CSVImportService::isValidEmail($row['email'])) {
                        $issues[] = "Invalid email: {$row['email']}";
                    } elseif (isset($existingEmails[$emailLower])) {
                        $duplicates[] = [
                            'row'    => $row,
                            'reason' => 'Email already exists in practice',
                        ];
                        continue;
                    } elseif (isset($seenInFile[$emailLower])) {
                        $duplicates[] = [
                            'row'    => $row,
                            'reason' => 'Duplicate email within file',
                        ];
                        continue;
                    } else {
                        $seenInFile[$emailLower] = true;
                    }
                }

                if (!empty($issues)) {
                    $errors[] = ['row' => $row, 'issues' => $issues];
                } else {
                    $valid[] = $row;
                }
            }

            fclose($handle);

            $session->update([
                'status'          => 'ready',
                'total_rows'      => $totalRows,
                'valid_rows'      => count($valid),
                'duplicate_rows'  => count($duplicates),
                'error_rows'      => count($errors),
                'dry_run_results' => [
                    'valid'      => array_slice($valid, 0, 10),
                    'errors'     => array_slice($errors, 0, 10),
                    'duplicates' => array_slice($duplicates, 0, 10),
                ],
            ]);

            Log::info('DryRunImportJob: Complete', [
                'session_id'  => $this->importSessionId,
                'total'       => $totalRows,
                'valid'       => count($valid),
                'duplicates'  => count($duplicates),
                'errors'      => count($errors),
            ]);
        } catch (\Exception $e) {
            Log::error('DryRunImportJob: Failed', [
                'session_id' => $this->importSessionId,
                'error'      => $e->getMessage(),
            ]);

            try {
                $session->update(['status' => 'failed']);
            } catch (\Exception) {
                // Swallow secondary failure
            }
        }
    }
}
