<?php

namespace App\Jobs;

use App\Models\AppointmentType;
use App\Models\AcupunctureEncounter;
use App\Models\CheckoutLine;
use App\Models\CheckoutPayment;
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
use App\Services\Reports\PracticeFinancialSummaryService;
use Carbon\Carbon;
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
        public readonly ?string $startDate = null,
        public readonly ?string $endDate = null,
    ) {}

    public function handle(): void
    {
        $token = ExportToken::withoutPracticeScope()->find($this->exportTokenId);

        if (!$token) {
            Log::error("ExportPracticeDataJob: ExportToken {$this->exportTokenId} not found");
            return;
        }

        try {
            $filePath = match ($this->format) {
                'csv' => $this->generateCsvZip(),
                'financial_csv' => $this->generateFinancialCsvZip(),
                default => $this->generateJson(),
            };

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

            // Checkout payments
            $checkoutPayments = CheckoutPayment::withoutPracticeScope()
                ->where('practice_id', $this->practiceId)
                ->get();
            $this->addCsvToZip($zip, 'checkout_payments.csv', $checkoutPayments);

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

    private function generateFinancialCsvZip(): string
    {
        $practice = Practice::findOrFail($this->practiceId);
        $timezone = $practice->timezone ?: config('app.timezone', 'UTC');
        $start = $this->startDate
            ? Carbon::parse($this->startDate, $timezone)
            : now($timezone)->startOfMonth();
        $end = $this->endDate
            ? Carbon::parse($this->endDate, $timezone)
            : now($timezone)->endOfMonth();

        $service = app(PracticeFinancialSummaryService::class);
        $summary = $service->summarize($practice, $start, $end, $timezone);

        $tempZipPath = tempnam(sys_get_temp_dir(), 'financial_export_') . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception("Failed to create ZIP archive at {$tempZipPath}");
        }

        try {
            $this->addArrayCsvToZip($zip, 'financial_summary.csv', $this->financialSummaryRows($summary));
            $this->addArrayCsvToZip($zip, 'checkout_payments.csv', $this->checkoutPaymentRows($service->paymentsForExport($practice, $start, $end, $timezone), $timezone));
            $this->addArrayCsvToZip($zip, 'checkout_line_items.csv', $this->checkoutLineItemRows($service->lineItemsForExport($practice, $start, $end, $timezone), $timezone));

            $zip->close();

            $storagePath = "exports/{$this->practiceId}/financial_export_{$this->exportTokenId}.zip";
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

    private function financialSummaryRows(array $summary): array
    {
        $rows = [[
            'section' => 'total_collected',
            'label' => 'Total collected',
            'count' => '',
            'amount' => $summary['total_collected'],
            'start_date' => $summary['period']['start'],
            'end_date' => $summary['period']['end'],
        ]];

        foreach ($summary['payment_method_totals'] as $row) {
            $rows[] = [
                'section' => 'payment_method',
                'label' => $row['label'],
                'count' => $row['count'],
                'amount' => $row['total'],
                'start_date' => $summary['period']['start'],
                'end_date' => $summary['period']['end'],
            ];
        }

        foreach ($summary['practitioner_totals'] as $row) {
            $rows[] = [
                'section' => 'practitioner',
                'label' => $row['practitioner_name'],
                'count' => $row['payment_count'],
                'amount' => $row['total'],
                'start_date' => $summary['period']['start'],
                'end_date' => $summary['period']['end'],
            ];
        }

        foreach ($summary['line_type_totals'] as $row) {
            $rows[] = [
                'section' => 'line_type',
                'label' => $row['label'],
                'count' => $row['line_count'],
                'amount' => $row['total'],
                'start_date' => $summary['period']['start'],
                'end_date' => $summary['period']['end'],
            ];
        }

        $rows[] = [
            'section' => 'sessions',
            'label' => 'Paid checkout sessions',
            'count' => $summary['paid_sessions_count'],
            'amount' => '',
            'start_date' => $summary['period']['start'],
            'end_date' => $summary['period']['end'],
        ];

        $rows[] = [
            'section' => 'sessions',
            'label' => 'Open/payment due checkout sessions',
            'count' => $summary['unpaid_open_sessions_count'],
            'amount' => $summary['unpaid_open_sessions_total'],
            'start_date' => $summary['period']['start'],
            'end_date' => $summary['period']['end'],
        ];

        return $rows;
    }

    private function checkoutPaymentRows($payments, string $timezone): array
    {
        return $payments->map(function (CheckoutPayment $payment) use ($timezone) {
            $session = $payment->checkoutSession;
            $patient = $session?->patient;
            $practitioner = $session?->practitioner;

            return [
                'paid_at' => $payment->paid_at?->copy()->timezone($timezone)->format('Y-m-d H:i:s'),
                'amount' => (float) $payment->amount,
                'payment_method' => CheckoutPayment::METHODS[$payment->payment_method] ?? $payment->payment_method,
                'reference' => $payment->reference,
                'checkout_session_id' => $payment->checkout_session_id,
                'patient_id' => $patient?->id,
                'patient_name' => $patient?->name,
                'practitioner_name' => $practitioner?->user?->name,
                'created_by' => $payment->createdBy?->name,
            ];
        })->all();
    }

    private function checkoutLineItemRows($lines, string $timezone): array
    {
        return $lines->map(function (CheckoutLine $line) use ($timezone) {
            $session = $line->checkoutSession;
            $paymentDate = $session?->checkoutPayments
                ->sortBy('paid_at')
                ->first()?->paid_at;

            return [
                'checkout_session_id' => $line->checkout_session_id,
                'date_basis' => $paymentDate?->copy()->timezone($timezone)->format('Y-m-d H:i:s'),
                'line_type' => CheckoutLine::TYPES[$line->line_type] ?? $line->line_type,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'amount' => $line->amount,
                'practitioner' => $session?->practitioner?->user?->name,
                'appointment_type' => $session?->appointment?->appointmentType?->name,
                'service_fee' => $line->serviceFee?->name,
                'product' => $line->inventoryProduct?->name,
            ];
        })->all();
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

    private function addArrayCsvToZip(ZipArchive $zip, string $filename, array $rows): void
    {
        $handle = fopen('php://temp', 'w+');

        $headers = array_keys($rows[0] ?? match ($filename) {
            'financial_summary.csv' => [
                'section' => null,
                'label' => null,
                'count' => null,
                'amount' => null,
                'start_date' => null,
                'end_date' => null,
            ],
            'checkout_payments.csv' => [
                'paid_at' => null,
                'amount' => null,
                'payment_method' => null,
                'reference' => null,
                'checkout_session_id' => null,
                'patient_id' => null,
                'patient_name' => null,
                'practitioner_name' => null,
                'created_by' => null,
            ],
            default => [
                'checkout_session_id' => null,
                'date_basis' => null,
                'line_type' => null,
                'description' => null,
                'quantity' => null,
                'unit_price' => null,
                'amount' => null,
                'practitioner' => null,
                'appointment_type' => null,
                'service_fee' => null,
                'product' => null,
            ],
        });

        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            fputcsv($handle, array_map(
                fn (string $header) => $this->formatCsvValue($row[$header] ?? ''),
                $headers,
            ));
        }

        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        $zip->addFromString($filename, $csvContent);
    }

    private function formatCsvValue(mixed $value): string
    {
        if ($value instanceof \DateTime) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
        }

        return (string) $value;
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

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
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
