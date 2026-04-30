<?php

namespace App\Jobs;

use App\Models\ImportHistory;
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

class ImportSessionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $importSessionId) {}

    public function handle(): void
    {
        $session = ImportSession::withoutPracticeScope()->find($this->importSessionId);

        if (!$session) {
            Log::error('ImportSessionJob: ImportSession not found', ['id' => $this->importSessionId]);
            return;
        }

        try {
            $filePath   = Storage::disk('local')->path($session->file_path);
            $handle     = fopen($filePath, 'r');

            if (!$handle) {
                throw new \RuntimeException("Cannot open CSV file: {$session->file_path}");
            }

            fgetcsv($handle); // skip header row

            $mappings    = $session->column_mappings ?? [];
            $practiceId  = $session->practice_id;

            // Pre-load existing emails for duplicate detection
            $existingEmails = Patient::withoutPracticeScope()
                ->where('practice_id', $practiceId)
                ->whereNotNull('email')
                ->pluck('email')
                ->mapWithKeys(fn($e) => [strtolower($e) => true])
                ->toArray();

            $seenInFile = [];
            $imported   = 0;
            $skipped    = 0;
            $failed     = 0;

            while (($data = fgetcsv($handle)) !== false) {
                if (empty(array_filter($data))) {
                    continue;
                }

                try {
                    // Apply column mappings
                    $row = [];
                    foreach ($mappings as $index => $field) {
                        if ($field && isset($data[$index])) {
                            $row[$field] = trim($data[$index]);
                        }
                    }

                    $firstName = $row['first_name'] ?? null;
                    $lastName  = $row['last_name'] ?? null;
                    $email     = $row['email'] ?? null;

                    if (!$firstName || !$lastName) {
                        $skipped++;
                        continue;
                    }

                    if ($email) {
                        $emailLower = strtolower($email);

                        if (!CSVImportService::isValidEmail($email)) {
                            $skipped++;
                            continue;
                        }

                        if (isset($existingEmails[$emailLower]) || isset($seenInFile[$emailLower])) {
                            $skipped++;
                            continue;
                        }

                        $seenInFile[$emailLower] = true;
                    }

                    $dobParsed   = ($row['dob'] ?? $row['date_of_birth'] ?? null)
                        ? CSVImportService::parseDate($row['dob'] ?? $row['date_of_birth'])
                        : null;
                    $phoneParsed = ($row['phone'] ?? null) ? CSVImportService::formatPhone($row['phone']) : null;
                    $genderMap   = [
                        'male' => 'Male', 'm' => 'Male',
                        'female' => 'Female', 'f' => 'Female',
                        'non-binary' => 'Non-binary', 'nonbinary' => 'Non-binary', 'non_binary' => 'Non-binary',
                        'prefer_not_to_say' => 'Prefer not to say', 'prefer not to say' => 'Prefer not to say',
                        'other' => 'Other',
                    ];
                    $genderLower = isset($row['gender'])
                        ? ($genderMap[strtolower(trim($row['gender']))] ?? null)
                        : null;

                    Patient::withoutPracticeScope()->create([
                        'practice_id'   => $practiceId,
                        'first_name'    => $firstName,
                        'last_name'     => $lastName,
                        'name'          => "{$firstName} {$lastName}",
                        'email'         => $email ?: null,
                        'phone'         => $phoneParsed,
                        'dob'           => $dobParsed,
                        'gender'        => $genderLower,
                        'preferred_language' => Patient::normalizePreferredLanguage($row['preferred_language'] ?? null),
                        'address_line_1' => ($row['address_line_1'] ?? $row['address'] ?? null) ?: null,
                        'address_line_2' => ($row['address_line_2'] ?? null) ?: null,
                        'city'          => ($row['city'] ?? null) ?: null,
                        'state'         => ($row['state'] ?? null) ?: null,
                        'postal_code'   => ($row['postal_code'] ?? $row['zip'] ?? null) ?: null,
                        'country'       => ($row['country'] ?? null) ?: null,
                        'emergency_contact_name' => ($row['emergency_contact_name'] ?? null) ?: null,
                        'occupation'    => ($row['occupation'] ?? null) ?: null,
                        'is_patient'    => true,
                    ]);

                    $imported++;
                } catch (\Exception $e) {
                    Log::error('ImportSessionJob: Row failed', ['error' => $e->getMessage()]);
                    $failed++;
                }
            }

            fclose($handle);

            // Clean up the uploaded file
            Storage::disk('local')->delete($session->file_path);

            $session->update([
                'status'        => 'complete',
                'imported_rows' => $imported,
            ]);

            // Create ImportHistory record (backward compat with existing admin UI)
            ImportHistory::create([
                'practice_id' => $practiceId,
                'filename'    => $session->original_filename ?? 'import.csv',
                'total_rows'  => $session->total_rows,
                'imported'    => $imported,
                'skipped'     => $skipped,
                'failed'      => $failed,
                'status'      => 'completed',
            ]);

            Log::info('ImportSessionJob: Complete', [
                'session_id' => $this->importSessionId,
                'imported'   => $imported,
                'skipped'    => $skipped,
                'failed'     => $failed,
            ]);
        } catch (\Exception $e) {
            Log::error('ImportSessionJob: Fatal error', [
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
