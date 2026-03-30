<?php

namespace App\Filament\Pages\Settings;

use App\Jobs\ImportPatientsJob;
use App\Models\ImportHistory;
use App\Services\CSVImportService;
use App\Services\PracticeContext;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use BackedEnum;
use Illuminate\Support\Str;

class ImportPatients extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $slug = 'settings/import-patients';
    protected static ?string $title = 'Import Patients';
    protected static ?string $navigationLabel = 'Import Patients';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUp;
    protected static bool $shouldRegisterNavigation = true;
    protected static ?int $navigationSort = 50;

    protected string $view = 'filament.pages.settings.import-patients';

    public ?array $data = [];
    public ?array $csvHeaders = [];
    public ?array $previewRows = [];
    public bool $showPreview = false;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function getFormSchema(): array
    {
        return [
            Section::make('CSV File')
                ->description('Upload a CSV file with patient data')
                ->schema([
                    FileUpload::make('csv_file')
                        ->label('CSV File')
                        ->acceptedFileTypes(['text/csv', 'text/plain'])
                        ->required()
                        ->columnSpanFull()
                        ->afterStateUpdated(fn () => $this->loadCSVPreview()),
                ])
                ->columnSpanFull(),

            Section::make('Column Mapping')
                ->description('Map CSV columns to patient fields')
                ->schema([
                    Repeater::make('column_mappings')
                        ->label('Column Mappings')
                        ->addable(false)
                        ->deletable(false)
                        ->schema([
                            TextInput::make('csv_column')
                                ->label('CSV Column')
                                ->readOnly(),
                            Select::make('patient_field')
                                ->label('Patient Field')
                                ->options([
                                    '' => '(Skip)',
                                    'first_name' => 'First Name',
                                    'last_name' => 'Last Name',
                                    'email' => 'Email',
                                    'phone' => 'Phone',
                                    'dob' => 'Date of Birth',
                                    'gender' => 'Gender',
                                    'address' => 'Address',
                                    'city' => 'City',
                                    'state' => 'State',
                                    'postal_code' => 'Postal Code',
                                ])
                                ->required(),
                        ])
                        ->columns(2)
                        ->columnSpanFull()
                        ->hidden(empty($this->csvHeaders)),
                ])
                ->columnSpanFull()
                ->hidden(empty($this->csvHeaders)),
        ];
    }

    public function loadCSVPreview(): void
    {
        try {
            $fileData = $this->form->getState()['csv_file'] ?? null;

            if (!$fileData) {
                return;
            }

            $file = is_array($fileData) ? ($fileData[0] ?? null) : $fileData;

            if (!$file || !($file instanceof UploadedFile)) {
                return;
            }

            // Read CSV headers
            $handle = fopen($file->getRealPath(), 'r');
            $headers = fgetcsv($handle);
            fclose($handle);

            if (!$headers) {
                return;
            }

            $this->csvHeaders = $headers;

            // Initialize column mappings
            $mappings = [];
            foreach ($headers as $header) {
                $trimmed = strtolower(trim($header));
                $suggestedField = $this->suggestField($trimmed);
                $mappings[] = [
                    'csv_column' => $header,
                    'patient_field' => $suggestedField,
                ];
            }

            $this->form->fill(['column_mappings' => $mappings]);

            // Load preview rows
            $this->loadPreviewRows($file, $headers);
            $this->showPreview = true;
        } catch (\Exception $e) {
            Log::error('CSV preview loading failed', ['error' => $e->getMessage()]);
        }
    }

    private function suggestField(string $header): string
    {
        $mapping = [
            'first_name' => 'first_name',
            'firstname' => 'first_name',
            'first' => 'first_name',
            'fname' => 'first_name',
            'last_name' => 'last_name',
            'lastname' => 'last_name',
            'last' => 'last_name',
            'lname' => 'last_name',
            'surname' => 'last_name',
            'email' => 'email',
            'e-mail' => 'email',
            'phone' => 'phone',
            'telephone' => 'phone',
            'mobile' => 'phone',
            'dob' => 'dob',
            'date_of_birth' => 'dob',
            'birth_date' => 'dob',
            'birthdate' => 'dob',
            'gender' => 'gender',
            'sex' => 'gender',
            'address' => 'address',
            'street' => 'address',
            'city' => 'city',
            'state' => 'state',
            'province' => 'state',
            'postal_code' => 'postal_code',
            'zip' => 'postal_code',
            'zip_code' => 'postal_code',
            'postcode' => 'postal_code',
        ];

        return $mapping[$header] ?? '';
    }

    private function loadPreviewRows(UploadedFile $file, array $headers): void
    {
        try {
            $handle = fopen($file->getRealPath(), 'r');
            $previewRows = [];
            $rowCount = 0;

            // Skip headers
            fgetcsv($handle);

            while (($data = fgetcsv($handle)) !== false && $rowCount < 5) {
                if (empty(array_filter($data))) {
                    continue;
                }

                $row = [];
                foreach ($headers as $index => $header) {
                    $row[$header] = $data[$index] ?? '';
                }
                $previewRows[] = $row;
                $rowCount++;
            }

            fclose($handle);
            $this->previewRows = $previewRows;
        } catch (\Exception $e) {
            Log::error('Preview rows loading failed', ['error' => $e->getMessage()]);
        }
    }

    public function downloadTemplate()
    {
        $template = CSVImportService::generateTemplate();

        return response()
            ->streamDownload(
                fn () => print($template),
                'patient_import_template.csv'
            );
    }

    public function importRows(): void
    {
        try {
            $data = $this->form->getState();

            if (!isset($data['csv_file']) || empty($data['csv_file'])) {
                $this->addError('csv_file', 'Please upload a CSV file');
                return;
            }

            $file = is_array($data['csv_file']) ? ($data['csv_file'][0] ?? null) : $data['csv_file'];

            if (!$file || !($file instanceof UploadedFile)) {
                $this->addError('csv_file', 'Invalid file');
                return;
            }

            // Build column mapping from form data
            $columnMap = [];
            if (isset($data['column_mappings']) && is_array($data['column_mappings'])) {
                foreach ($data['column_mappings'] as $index => $mapping) {
                    $patientField = $mapping['patient_field'] ?? null;
                    if ($patientField) {
                        $columnMap[$index] = $patientField;
                    }
                }
            }

            if (empty($columnMap)) {
                $this->addError('column_mappings', 'Please map at least one column');
                return;
            }

            // Parse the CSV file
            $rows = CSVImportService::parseUpload($file, $columnMap);

            if (empty($rows)) {
                $this->addError('csv_file', 'No valid rows found in CSV');
                return;
            }

            $practiceId = PracticeContext::currentPracticeId();

            if (!$practiceId) {
                $this->addError('csv_file', 'No practice selected');
                return;
            }

            // Create import history record
            $importHistory = ImportHistory::create([
                'practice_id' => $practiceId,
                'filename' => $file->getClientOriginalName(),
                'total_rows' => count($rows),
                'status' => 'pending',
            ]);

            // Dispatch the import job
            ImportPatientsJob::dispatch($practiceId, $importHistory->id, $rows, $columnMap);

            session()->flash('success', 'Import started. Processing ' . count($rows) . ' rows.');
            $this->form->fill([]);
            $this->csvHeaders = [];
            $this->previewRows = [];
            $this->showPreview = false;
        } catch (\Exception $e) {
            Log::error('ImportPatients: Import failed', ['error' => $e->getMessage()]);
            $this->addError('csv_file', 'Import failed: ' . $e->getMessage());
        }
    }
}
