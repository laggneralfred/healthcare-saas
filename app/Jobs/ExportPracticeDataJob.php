<?php

namespace App\Jobs;

use App\Models\AppointmentType;
use App\Models\AcupunctureEncounter;
use App\Models\CheckoutLine;
use App\Models\CheckoutSession;
use App\Models\ConsentRecord;
use App\Models\Encounter;
use App\Models\ExportToken;
use App\Models\MedicalHistory;
use App\Models\InventoryMovement;
use App\Models\InventoryProduct;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\ServiceFee;
use App\Models\User;
use App\Notifications\ExportReadyNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ExportPracticeDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $practiceId,
        public readonly string $exportTokenId,
        public readonly string $format,
    ) {}

    public function handle(): void
    {
        $token = ExportToken::withoutPracticeScope()->find($this->exportTokenId);

        if (!$token) {
            Log::error("ExportPracticeDataJob: ExportToken {$this->exportTokenId} not found");
            return;
        }

        try {
            $filePath = $this->format === 'csv'
                ? $this->generateCsvZip()
                : $this->generateJson();

            $token->update([
                'status' => 'ready',
                'file_path' => $filePath,
            ]);

            // Notify all users in the practice
            $users = User::where('practice_id', $this->practiceId)->get();
            foreach ($users as $user) {
                $user->notify(new ExportReadyNotification($token));
            }

            Log::info("ExportPracticeDataJob: Completed for practice {$this->practiceId}, format {$this->format}");
        } catch (\Exception $e) {
            Log::error(
                "ExportPracticeDataJob: Fatal error",
                ['error' => $e->getMessage(), 'practice_id' => $this->practiceId, 'token_id' => $this->exportTokenId]
            );
            try {
                $token->update(['status' => 'failed']);
            } catch (\Exception $updateException) {
                // Transaction may be aborted (e.g. in test environments) — reconnect and retry
                \Illuminate\Support\Facades\DB::reconnect();
                ExportToken::withoutPracticeScope()
                    ->where('id', $this->exportTokenId)
                    ->update(['status' => 'failed']);
            }
        }
    }

    private function generateCsvZip(): string
    {
        // Build zip in the real temp dir so ZipArchive has an actual filesystem path.
        // Storage::fake() intercepts Storage API calls but ZipArchive bypasses it.
        $tempZipPath = tempnam(sys_get_temp_dir(), 'export_') . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception("Failed to create ZIP archive at {$tempZipPath}");
        }

        try {
            // Practice data
            $practice = Practice::find($this->practiceId);
            $this->addCsvToZip($zip, 'practice.csv', collect([$practice]));

            // Practitioners
            $practitioners = Practitioner::withoutPracticeScope()
                ->where('practice_id', $this->practiceId)
                ->get();
            $this->addCsvToZip($zip, 'practitioners.csv', $practitioners);

            // Patients
            $patients = Patient::withoutPracticeScope()
                ->where('practice_id', $this->practiceId)
                ->get();
            $this->addCsvToZip($zip, 'patients.csv', $patients);

            // Intake submissions
            $medicalHistories = MedicalHistory::withoutPracticeScope()
                ->where('practice_id', $this->practiceId)
                ->get();
            $this->addCsvToZip($zip, 'medical_historys.csv', $medicalHistories);

            // Consent records
            $consentRecords = ConsentRecord::withoutPracticeScope()
                ->where('practice_id', $this->practiceId)
                ->get();
            $this->addCsvToZip($zip, 'consent_records.csv', $consentRecords);

            // Appointments
            $appointments = Appointment::withoutPracticeScope()
                ->where('practice_id', $this->practiceId)
                ->get();
            $this->addCsvToZip($zip, 'appointments.csv', $appointments);

            // Encounters
            $encounters = Encounter::withoutPracticeScope()
                ->where('practice_id', $this->practiceId)
                ->get();
            $this->addCsvToZip($zip, 'encounters.csv', $encounters);

            // Acupuncture encounters (no practice_id — join via encounter IDs)
            $encounterIds = $encounters->pluck('id')->toArray();
            $acupunctureEncounters = empty($encounterIds)
                ? collect()
                : AcupunctureEncounter::whereIn('encounter_id', $encounterIds)->get();
            $this->addCsvToZip($zip, 'acupuncture_encounters.csv', $acupunctureEncounters);

            // Checkout sessions
            $checkoutSessions = CheckoutSession::withoutPracticeScope()
                ->where('practice_id', $this->practiceId)
                ->get();
            $this->addCsvToZip($zip, 'checkout_sessions.csv', $checkoutSessions);

            // Checkout lines
            $checkoutLines = CheckoutLine::withoutPracticeScope()
                ->where('practice_id', $this->practiceId)
                ->get();
            $this->addCsvToZip($zip, 'checkout_lines.csv', $checkoutLines);

            // Service fees
            $serviceFees = ServiceFee::withoutPracticeScope()
                ->where('practice_id', $this->practiceId)
                ->get();
            $this->addCsvToZip($zip, 'service_fees.csv', $serviceFees);

            // Appointment types
            $appointmentTypes = AppointmentType::withoutPracticeScope()
                ->where('practice_id', $this->practiceId)
                ->get();
            $this->addCsvToZip($zip, 'appointment_types.csv', $appointmentTypes);

            // Inventory products
            $inventoryProducts = InventoryProduct::withoutPracticeScope()
                ->where('practice_id', $this->practiceId)
                ->get();
            $this->addCsvToZip($zip, 'inventory_products.csv', $inventoryProducts);

            // Inventory movements
            $inventoryMovements = InventoryMovement::withoutPracticeScope()
                ->where('practice_id', $this->practiceId)
                ->get();
            $this->addCsvToZip($zip, 'inventory_movements.csv', $inventoryMovements);

            $zip->close();

            // Copy from real temp dir into Storage (works with both fake and real drivers)
            $storagePath = "exports/{$this->practiceId}/export_{$this->exportTokenId}.zip";
            Storage::put($storagePath, file_get_contents($tempZipPath));

            return $storagePath;
        } catch (\Exception $e) {
            $zip->close();
            throw $e;
        } finally {
            if (file_exists($tempZipPath)) {
                unlink($tempZipPath);
            }
        }
    }

    private function addCsvToZip(ZipArchive $zip, string $filename, $collection): void
    {
        // Use php://temp so we stay in-memory and avoid addFile()+unlink() race condition.
        // Always add the file — even empty — so the ZIP contains all expected entries.
        $handle = fopen('php://temp', 'w+');

        if (!$collection->isEmpty()) {
            $firstRow = $collection->first();
            $headers = array_keys($firstRow->getAttributes());
            fputcsv($handle, $headers);

            foreach ($collection as $row) {
                $rowData = [];
                foreach ($headers as $header) {
                    $rowData[] = $this->getColumnValue($row, $header);
                }
                fputcsv($handle, $rowData);
            }
        }

        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        $zip->addFromString($filename, $csvContent);
    }

    private function getColumnValue($row, string $header): string
    {
        $value = $row->{$header} ?? '';

        // Cast state machine values to string
        if ($value instanceof \Spatie\ModelStates\State) {
            return (string) $value;
        }

        // Format datetime values
        if ($value instanceof \DateTime) {
            return $value->format('Y-m-d H:i:s');
        }

        // Convert booleans to 1/0
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }

    private function generateJson(): string
    {
        $exportDir = "exports/{$this->practiceId}";
        Storage::makeDirectory($exportDir, 0755, true);

        $practice = Practice::find($this->practiceId);

        // Load all data with relationships
        $practitioners = Practitioner::withoutPracticeScope()
            ->where('practice_id', $this->practiceId)
            ->get()
            ->toArray();

        $patients = Patient::withoutPracticeScope()
            ->where('practice_id', $this->practiceId)
            ->with(['medicalHistories', 'consentRecords', 'appointments.encounter.acupunctureEncounter'])
            ->get()
            ->map(fn ($patient) => [
                ...$patient->toArray(),
                'medical_historys' => $patient->medicalHistories->toArray(),
                'consent_records' => $patient->consentRecords->toArray(),
                'appointments' => $patient->appointments->map(fn ($apt) => [
                    ...$apt->toArray(),
                    'encounter' => $apt->encounter ? [
                        ...$apt->encounter->toArray(),
                        'acupuncture_encounter' => $apt->encounter->acupunctureEncounter?->toArray(),
                    ] : null,
                ])->toArray(),
            ])
            ->toArray();

        $checkoutSessions = CheckoutSession::withoutPracticeScope()
            ->where('practice_id', $this->practiceId)
            ->with('checkoutLines')
            ->get()
            ->map(fn ($session) => [
                ...$session->toArray(),
                'lines' => $session->checkoutLines->toArray(),
            ])
            ->toArray();

        $inventoryProducts = InventoryProduct::withoutPracticeScope()
            ->where('practice_id', $this->practiceId)
            ->with('movements')
            ->get()
            ->map(fn ($product) => [
                ...$product->toArray(),
                'movements' => $product->movements->toArray(),
            ])
            ->toArray();

        $serviceFees = ServiceFee::withoutPracticeScope()
            ->where('practice_id', $this->practiceId)
            ->get()
            ->toArray();

        $appointmentTypes = AppointmentType::withoutPracticeScope()
            ->where('practice_id', $this->practiceId)
            ->get()
            ->toArray();

        $data = [
            'exported_at' => now()->toIso8601String(),
            'practice' => $practice->toArray(),
            'practitioners' => $practitioners,
            'patients' => $patients,
            'checkout_sessions' => $checkoutSessions,
            'inventory_products' => $inventoryProducts,
            'service_fees' => $serviceFees,
            'appointment_types' => $appointmentTypes,
        ];

        $jsonPath = "exports/{$this->practiceId}/export_{$this->exportTokenId}.json";
        Storage::put($jsonPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $jsonPath;
    }
}
