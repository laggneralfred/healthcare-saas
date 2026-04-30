<?php

namespace App\Jobs;

use App\Models\ImportHistory;
use App\Models\Patient;
use App\Services\CSVImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportPatientsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $practiceId,
        public readonly int $importHistoryId,
        public readonly array $rows,
        public readonly array $columnMap,
    ) {}

    public function handle(): void
    {
        $importHistory = ImportHistory::find($this->importHistoryId);

        if (!$importHistory) {
            Log::error("ImportPatientsJob: ImportHistory {$this->importHistoryId} not found");
            return;
        }

        try {
            // Update status to processing
            $importHistory->update(['status' => 'processing']);

            $imported = 0;
            $skipped = 0;
            $failed = 0;

            // Process each row in a transaction for data integrity
            DB::transaction(function () use (&$imported, &$skipped, &$failed) {
                foreach ($this->rows as $rowIndex => $rowData) {
                    try {
                        // Extract and validate patient data
                        $firstName = $rowData['first_name'] ?? null;
                        $lastName = $rowData['last_name'] ?? null;
                        $email = $rowData['email'] ?? null;
                        $phone = $rowData['phone'] ?? null;
                        $dob = $rowData['dob'] ?? $rowData['date_of_birth'] ?? null;
                        $gender = $rowData['gender'] ?? null;
                        $preferredLanguage = $rowData['preferred_language'] ?? null;
                        $address = $rowData['address_line_1'] ?? $rowData['address'] ?? null;
                        $city = $rowData['city'] ?? null;
                        $state = $rowData['state'] ?? null;
                        $postalCode = $rowData['postal_code'] ?? $rowData['zip'] ?? null;

                        // Validate required fields: first_name and last_name
                        if (!$firstName || !$lastName) {
                            Log::warning("ImportPatientsJob: Row {$rowIndex} missing first_name or last_name");
                            $skipped++;
                            continue;
                        }

                        // Skip if email already exists in this practice (when email is provided)
                        if ($email) {
                            if (!CSVImportService::isValidEmail($email)) {
                                Log::warning("ImportPatientsJob: Row {$rowIndex} has invalid email format: {$email}");
                                $skipped++;
                                continue;
                            }

                            $existingPatient = Patient::withoutPracticeScope()
                                ->where('practice_id', $this->practiceId)
                                ->where('email', $email)
                                ->exists();

                            if ($existingPatient) {
                                Log::info("ImportPatientsJob: Row {$rowIndex} skipped - email already exists: {$email}");
                                $skipped++;
                                continue;
                            }
                        }

                        // Parse date of birth
                        $dobParsed = null;
                        if ($dob) {
                            $dobParsed = CSVImportService::parseDate($dob);
                        }

                        // Format phone
                        $phoneParsed = null;
                        if ($phone) {
                            $phoneParsed = CSVImportService::formatPhone($phone);
                        }

                        // Normalize gender to canonical display values
                        $genderMap = [
                            'male' => 'Male', 'm' => 'Male',
                            'female' => 'Female', 'f' => 'Female',
                            'non-binary' => 'Non-binary', 'nonbinary' => 'Non-binary', 'non_binary' => 'Non-binary',
                            'prefer_not_to_say' => 'Prefer not to say', 'prefer not to say' => 'Prefer not to say',
                            'other' => 'Other',
                        ];
                        $genderLower = $gender ? ($genderMap[strtolower(trim($gender))] ?? null) : null;

                        // Create patient
                        Patient::withoutPracticeScope()->create([
                            'practice_id' => $this->practiceId,
                            'first_name' => $firstName,
                            'last_name' => $lastName,
                            'name' => "{$firstName} {$lastName}",
                            'email' => $email ?: null,
                            'phone' => $phoneParsed,
                            'dob' => $dobParsed,
                            'gender' => $genderLower,
                            'preferred_language' => Patient::normalizePreferredLanguage($preferredLanguage),
                            'address_line_1' => $address ?: null,
                            'city'           => $city ?: null,
                            'state'          => $state ?: null,
                            'postal_code'    => $postalCode ?: null,
                            'is_patient' => true,
                        ]);

                        $imported++;
                        Log::info("ImportPatientsJob: Row {$rowIndex} imported successfully");
                    } catch (\Exception $e) {
                        $failed++;
                        Log::error(
                            "ImportPatientsJob: Row {$rowIndex} failed",
                            ['error' => $e->getMessage(), 'data' => $rowData]
                        );
                    }
                }
            });

            // Update import history with results
            $importHistory->update([
                'status' => 'completed',
                'imported' => $imported,
                'skipped' => $skipped,
                'failed' => $failed,
            ]);

            Log::info(
                "ImportPatientsJob: Completed",
                [
                    'practice_id' => $this->practiceId,
                    'import_history_id' => $this->importHistoryId,
                    'imported' => $imported,
                    'skipped' => $skipped,
                    'failed' => $failed,
                ]
            );
        } catch (\Exception $e) {
            $importHistory->update(['status' => 'failed']);
            Log::error(
                "ImportPatientsJob: Fatal error",
                ['error' => $e->getMessage(), 'import_history_id' => $this->importHistoryId]
            );
        }
    }
}
