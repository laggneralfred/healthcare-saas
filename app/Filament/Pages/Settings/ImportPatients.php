<?php

namespace App\Filament\Pages\Settings;

use App\Jobs\DryRunImportJob;
use App\Jobs\ImportSessionJob;
use App\Models\AISuggestion;
use App\Models\AIUsageLog;
use App\Models\ImportHistory;
use App\Models\ImportSession;
use App\Services\CSVImportService;
use App\Services\CsvColumnMapper;
use App\Services\AI\AIService;
use App\Services\PracticeContext;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use BackedEnum;
use Throwable;

class ImportPatients extends Page
{
    use WithFileUploads;

    protected static ?string $slug = 'settings/import-patients';
    protected static ?string $title = 'Import Patients';
    protected static ?string $navigationLabel = 'Import Patients';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUp;
    protected static bool $shouldRegisterNavigation = true;
    protected static ?int $navigationSort = 50;

    protected string $view = 'filament.pages.settings.import-patients';

    // ── Wizard step ───────────────────────────────────────────────────────────
    // upload | map | confirm | importing | complete
    public string $step = 'upload';

    // ── Upload step ───────────────────────────────────────────────────────────
    public $uploadedFile = null;

    // ── Map step ──────────────────────────────────────────────────────────────
    public array $detectedHeaders = [];
    public array $mappings = []; // [column_index => field_key]
    public ?string $aiMappingSuggestion = null;

    // ── Session tracking ──────────────────────────────────────────────────────
    public ?int $importSessionId = null;

    // ── Confirm step (populated by polling) ───────────────────────────────────
    public string $sessionStatus = 'pending';
    public int $totalRows = 0;
    public int $validRows = 0;
    public int $duplicateRows = 0;
    public int $errorRows = 0;
    public array $previewRows = [];
    public array $errorPreview = [];

    // ── Complete step ─────────────────────────────────────────────────────────
    public int $importedRows = 0;

    // ── Validation rules (Livewire v3 requires this) ──────────────────────────
    protected function rules(): array
    {
        return [
            'mappings'   => 'nullable|array',
            'mappings.*' => 'nullable|string',
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Lifecycle hooks
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Fires automatically when the user selects a file (Livewire\WithFileUploads).
     * Reads only the header row — fast even for large files.
     */
    public function updatedUploadedFile(): void
    {
        if (!$this->uploadedFile) {
            return;
        }

        try {
            $filePath = $this->uploadedFile->getRealPath();
            $handle   = fopen($filePath, 'r');

            if (!$handle) {
                $this->addError('uploadedFile', 'Could not open the uploaded file.');
                return;
            }

            $headers = fgetcsv($handle);
            fclose($handle);

            if (!$headers || empty(array_filter($headers))) {
                $this->addError('uploadedFile', 'Could not read CSV headers. Make sure the file is a valid CSV.');
                return;
            }

            $this->detectedHeaders = array_map('trim', $headers);

            // Auto-suggest mappings via service
            $mapper      = new CsvColumnMapper();
            $suggestions = $mapper->suggest($this->detectedHeaders);
            $this->mappings = array_map(fn($s) => $s['field'] ?? '', $suggestions);

            // Persist file to local storage so the background job can read it
            $practiceId = PracticeContext::currentPracticeId();
            $originalName = $this->uploadedFile->getClientOriginalName() ?? 'import.csv';
            $storedPath = $this->uploadedFile->storeAs(
                "imports/{$practiceId}",
                date('Y-m-d_His_') . $originalName,
                'local'
            );

            // Create ImportSession record
            $session = ImportSession::create([
                'practice_id'      => $practiceId,
                'status'           => 'pending',
                'file_path'        => $storedPath,
                'original_filename'=> $originalName,
                'detected_headers' => $this->detectedHeaders,
                'column_mappings'  => [],
            ]);

            $this->importSessionId = $session->id;
            $this->step = 'map';
        } catch (\Exception $e) {
            Log::error('ImportPatients: Upload processing failed', ['error' => $e->getMessage()]);
            $this->addError('uploadedFile', 'Failed to process file: ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Step actions
    // ─────────────────────────────────────────────────────────────────────────

    /** Called from the Map step — validates and dispatches dry-run analysis. */
    public function analyze(): void
    {
        if (!in_array('first_name', $this->mappings, true) || !in_array('last_name', $this->mappings, true)) {
            $this->addError('mappings', 'Please map at least the First Name and Last Name columns before continuing.');
            return;
        }

        $session = ImportSession::withoutPracticeScope()->find($this->importSessionId);

        if (!$session) {
            $this->addError('mappings', 'Session expired. Please start over.');
            return;
        }

        $session->update([
            'column_mappings' => $this->mappings,
            'status'          => 'analyzing',
        ]);

        DryRunImportJob::dispatch($this->importSessionId);

        $this->sessionStatus = 'analyzing';
        $this->step = 'confirm';
    }

    public function suggestColumnMapping(AIService $ai): void
    {
        $practiceId = PracticeContext::currentPracticeId();

        if (! $practiceId) {
            Notification::make()
                ->title('Select a practice before using AI.')
                ->danger()
                ->send();

            return;
        }

        if ($this->detectedHeaders === []) {
            $this->addError('mappings', 'Upload a CSV file before requesting AI mapping suggestions.');
            return;
        }

        $supportedFields = array_keys(CsvColumnMapper::PATIENT_FIELDS);
        $originalText = json_encode([
            'headers' => $this->detectedHeaders,
            'supported_fields' => $supportedFields,
        ], JSON_PRETTY_PRINT);

        $suggestion = AISuggestion::create([
            'practice_id' => $practiceId,
            'user_id' => auth()->id(),
            'feature' => 'import_mapping',
            'original_text' => $originalText,
            'status' => 'pending',
        ]);

        try {
            $mapping = $ai->suggestImportMapping($this->detectedHeaders, $supportedFields);
            $suggestedText = $this->formatAISuggestion($mapping);

            $suggestion->update([
                'suggested_text' => $suggestedText,
                'status' => 'pending',
            ]);

            AIUsageLog::create([
                'practice_id' => $practiceId,
                'user_id' => auth()->id(),
                'feature' => 'import_mapping',
                'status' => 'success',
            ]);

            $this->aiMappingSuggestion = $suggestedText;

            Notification::make()
                ->title('AI mapping suggestion ready.')
                ->success()
                ->send();
        } catch (Throwable $exception) {
            $suggestion->update([
                'status' => 'failed',
            ]);

            AIUsageLog::create([
                'practice_id' => $practiceId,
                'user_id' => auth()->id(),
                'feature' => 'import_mapping',
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            Notification::make()
                ->title('AI mapping suggestion is unavailable.')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    /** Polled every 2 s on the Confirm step to check dry-run completion. */
    public function checkDryRun(): void
    {
        if ($this->step !== 'confirm' || !$this->importSessionId) {
            return;
        }

        $session = ImportSession::withoutPracticeScope()->find($this->importSessionId);

        if (!$session) {
            return;
        }

        $this->sessionStatus = $session->status;

        if (in_array($session->status, ['ready', 'failed'])) {
            $this->totalRows     = $session->total_rows;
            $this->validRows     = $session->valid_rows;
            $this->duplicateRows = $session->duplicate_rows;
            $this->errorRows     = $session->error_rows;

            $results            = $session->dry_run_results ?? [];
            $this->previewRows  = $results['valid'] ?? [];
            $this->errorPreview = $results['errors'] ?? [];
        }
    }

    /** Called when the user clicks "Import N Patients" on the Confirm step. */
    public function startImport(): void
    {
        $session = ImportSession::withoutPracticeScope()->find($this->importSessionId);

        if (!$session || $session->status !== 'ready') {
            return;
        }

        if ($this->validRows === 0) {
            $this->addError('import', 'No valid rows to import.');
            return;
        }

        $session->update(['status' => 'importing']);
        ImportSessionJob::dispatch($this->importSessionId);

        $this->sessionStatus = 'importing';
        $this->step = 'importing';
    }

    /** Polled every 2 s on the Importing step to check job completion. */
    public function checkImport(): void
    {
        if ($this->step !== 'importing' || !$this->importSessionId) {
            return;
        }

        $session = ImportSession::withoutPracticeScope()->find($this->importSessionId);

        if (!$session) {
            return;
        }

        $this->sessionStatus = $session->status;

        if (in_array($session->status, ['complete', 'failed'])) {
            $this->importedRows = $session->imported_rows;
            $this->step = 'complete';
        }
    }

    /** Reset the wizard back to the Upload step. */
    public function resetImport(): void
    {
        $this->step            = 'upload';
        $this->uploadedFile    = null;
        $this->detectedHeaders = [];
        $this->mappings        = [];
        $this->aiMappingSuggestion = null;
        $this->importSessionId = null;
        $this->sessionStatus   = 'pending';
        $this->totalRows       = 0;
        $this->validRows       = 0;
        $this->duplicateRows   = 0;
        $this->errorRows       = 0;
        $this->importedRows    = 0;
        $this->previewRows     = [];
        $this->errorPreview    = [];
        $this->resetErrorBag();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Template download (inline — no separate controller needed)
    // ─────────────────────────────────────────────────────────────────────────

    public function downloadTemplate(): mixed
    {
        $template = CSVImportService::generateTemplate();

        return response()->streamDownload(
            fn() => print($template),
            'patient_import_template.csv',
            ['Content-Type' => 'text/csv']
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers for the view
    // ─────────────────────────────────────────────────────────────────────────

    public function getFieldOptions(): array
    {
        return CsvColumnMapper::fieldOptions();
    }

    public function getRecentImports(): \Illuminate\Database\Eloquent\Collection
    {
        $practiceId = PracticeContext::currentPracticeId();

        if (!$practiceId) {
            return collect();
        }

        return ImportHistory::withoutPracticeScope()
            ->where('practice_id', $practiceId)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
    }

    private function formatAISuggestion(array|string $mapping): string
    {
        if (is_array($mapping)) {
            return json_encode($mapping, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        return trim($mapping);
    }
}
