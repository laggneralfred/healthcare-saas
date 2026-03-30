# Patient CSV Importer - Complete Files Reference

## File Locations and Summary

### 1. Migrations

#### `/database/migrations/2026_03_27_extend_patients_table.php`
- **Purpose**: Extends the patients table with new fields
- **Changes**:
  - Adds: first_name, last_name, dob, gender, address, city, state, postal_code
  - All fields nullable
- **Rollback**: Drops all new columns
- **Dependencies**: Existing patients table

#### `/database/migrations/2026_03_27_create_import_histories_table.php`
- **Purpose**: Creates table to track all import operations
- **Fields**:
  - id (BIGINT PK)
  - practice_id (FK → practices)
  - filename (VARCHAR 255)
  - total_rows, imported, skipped, failed (INT)
  - status (ENUM: pending, processing, completed, failed)
  - timestamps
- **Indexes**: practice_id
- **Constraints**: cascadeOnDelete with practices

### 2. Models

#### `/app/Models/ImportHistory.php`
- **Namespace**: App\Models
- **Traits**: HasFactory, BelongsToPractice
- **Fillable**: practice_id, filename, total_rows, imported, skipped, failed, status
- **Casts**: All numeric fields to integer, timestamps to datetime
- **Relations**: practice() → BelongsTo(Practice)
- **Usage**: Track and query import operations

#### `/app/Models/Patient.php` (Updated)
- **Changes Made**:
  - Extended $fillable array with: first_name, last_name, dob, gender, address, city, state, postal_code
  - Added casts for dob (date) and timestamps (datetime)
- **Backward Compatible**: Existing name field still present
- **Usage**: Create/update patients with full demographic data

### 3. Services

#### `/app/Services/CSVImportService.php`
- **Namespace**: App\Services
- **Static Methods**:

  1. **parseDate($value): ?Carbon**
     - Parses dates in multiple formats
     - Supported: MM/DD/YYYY, YYYY-MM-DD, DD/MM/YYYY, DD.MM.YYYY, etc.
     - Returns: Carbon instance or null

  2. **formatPhone($value): ?string**
     - Strips all non-digit characters
     - Returns: digit-only string or null

  3. **generateTemplate(): string**
     - Returns CSV header row
     - Headers: first_name, last_name, email, phone, dob, gender, address, city, state, postal_code

  4. **parseUpload(UploadedFile, array): array**
     - Reads CSV file and maps columns
     - Parameters: file, columnMap (index → field name)
     - Returns: array of mapped rows
     - Skips empty rows

  5. **isValidEmail(string): bool**
     - Validates email format using filter_var
     - Returns: boolean

- **Dependencies**: Carbon for date parsing

### 4. Jobs

#### `/app/Jobs/ImportPatientsJob.php`
- **Namespace**: App\Jobs
- **Implements**: ShouldQueue (async processing)
- **Constructor Parameters**:
  - practiceId: int
  - importHistoryId: int
  - rows: array
  - columnMap: array

- **handle() Method**:
  1. Loads ImportHistory record
  2. Sets status to 'processing'
  3. For each row:
     - Validates required fields (first_name, last_name)
     - Checks for duplicate emails
     - Validates email format
     - Parses dates
     - Formats phones
     - Sanitizes gender
     - Creates Patient in transaction
  4. Updates ImportHistory with counts
  5. Logs all operations
  6. Handles errors gracefully

- **Data Integrity**:
  - DB::transaction() for atomic inserts
  - Row-level error handling
  - Comprehensive logging

- **Dependencies**: Patient, ImportHistory, CSVImportService, DB, Log

### 5. Filament Page

#### `/app/Filament/Pages/Settings/ImportPatients.php`
- **Namespace**: App\Filament\Pages\Settings
- **Extends**: Page (implements HasForms)
- **Route Slug**: settings/import-patients
- **Navigation**: Settings section (sort 50)

- **Properties**:
  - data: ?array (form data)
  - csvHeaders: ?array (detected CSV headers)
  - previewRows: ?array (first 5 rows)
  - showPreview: bool (toggle preview visibility)

- **Methods**:
  1. **mount()**: Initialize form
  2. **getFormSchema()**: Define form components
     - FileUpload for CSV
     - Repeater for column mappings
  3. **loadCSVPreview()**: Detect headers and load preview
  4. **loadPreviewRows()**: Load first 5 rows
  5. **suggestField()**: Auto-suggest field mapping
  6. **downloadTemplate()**: Stream template CSV
  7. **importRows()**: Dispatch import job

- **Form Schema**:
  - csv_file: FileUpload (text/csv, text/plain)
  - column_mappings: Repeater with:
    - csv_column: TextInput (readOnly)
    - patient_field: Select (with skip option)

- **Dependencies**: ImportHistory, ImportPatientsJob, CSVImportService, PracticeContext

### 6. Blade View

#### `/resources/views/filament/pages/settings/import-patients.blade.php`
- **Extends**: x-filament-panels::page
- **Components**:
  1. Form (wire:submit="importRows")
     - File upload
     - Column mapping repeater
  2. CSV Preview Table (conditional)
     - Shows first 5 rows
     - Headers and data columns
  3. Action Buttons
     - Import Patients button
     - Download Template button
  4. Success Message (session flash)
  5. CSV Format Guide Section
     - Supported columns with descriptions
     - Example CSV
     - Import rules
  6. Import History Table
     - Recent imports (last 5)
     - Columns: filename, status, counts, date
     - Status badges (pending, processing, completed, failed)

- **Dynamic Elements**:
  - Preview shown only when CSV loaded
  - Column mappings shown only when headers detected
  - Import history queried from database

- **Styling**: Tailwind CSS with dark mode support

### 7. Tests

#### `/tests/Feature/CSVImportTest.php`
- **Extends**: TestCase (uses RefreshDatabase)
- **Test Methods** (16 total):

  **CSV Parsing Tests**:
  - it_parses_csv_with_valid_data()
  - it_handles_missing_optional_fields()

  **Data Format Tests**:
  - it_parses_various_date_formats()
  - it_formats_phone_numbers()
  - it_validates_email_format()

  **Template Test**:
  - it_generates_csv_template()

  **Import Job Tests**:
  - import_job_creates_patients_successfully()
  - import_job_skips_duplicate_emails()
  - import_job_skips_rows_missing_required_fields()
  - import_job_validates_email_format()
  - import_job_sanitizes_gender_values()

  **Model Tests**:
  - import_history_tracks_import_progress()
  - patient_model_is_fillable_with_new_fields()

- **Setup**: Creates Practice factory for each test
- **Dependencies**: Practice, ImportHistory, Patient models

### 8. Documentation

#### `/CSV_IMPORT_GUIDE.md`
- Complete system documentation (500+ lines)
- Sections:
  - Overview and features
  - Architecture (DB schema, components)
  - CSV format specification
  - Import rules and validation
  - Usage (user and developer)
  - Logging
  - Performance considerations
  - Troubleshooting
  - Security
  - Future enhancements

#### `/PATIENT_IMPORT_README.md`
- Implementation summary
- Quick reference of all files
- Key features overview
- Usage examples
- Database schema summary
- Import rules checklist
- Testing guide
- Deployment checklist

#### `/IMPORTER_FILES_REFERENCE.md` (this file)
- Detailed file-by-file reference
- Code locations
- Method signatures
- Dependencies
- Implementation details

## Complete File Listing

```
database/migrations/
  2026_03_27_extend_patients_table.php
  2026_03_27_create_import_histories_table.php

app/Models/
  ImportHistory.php
  Patient.php (updated)

app/Services/
  CSVImportService.php

app/Jobs/
  ImportPatientsJob.php

app/Filament/Pages/Settings/
  ImportPatients.php

resources/views/filament/pages/settings/
  import-patients.blade.php

tests/Feature/
  CSVImportTest.php

Documentation/
  CSV_IMPORT_GUIDE.md
  PATIENT_IMPORT_README.md
  IMPORTER_FILES_REFERENCE.md (this file)
```

## Integration Points

### With Existing System
- Uses `BelongsToPractice` trait for tenant isolation
- Follows Filament v5 conventions
- Maintains compatibility with existing Patient model
- Uses Laravel 13 + Livewire 4 patterns
- Uses Spatie queue for async processing
- Integrates with PracticeContext service

### Database
- practice_id on all records for multi-tenancy
- Follows existing naming conventions
- Uses PostgreSQL-compatible syntax
- Includes proper indexes

### Authentication & Authorization
- Inherits Filament page authorization
- Access via admin panel only
- Tenant-scoped via practice_id

## Deployment Steps

1. **Run migrations**:
   ```bash
   php artisan migrate
   ```

2. **Test functionality**:
   ```bash
   php artisan test tests/Feature/CSVImportTest.php
   ```

3. **Start queue worker**:
   ```bash
   php artisan queue:work
   ```

4. **Access Filament page**:
   - Navigate to: `/admin/settings/import-patients`

5. **Clear cache**:
   ```bash
   php artisan cache:clear
   ```

## Dependencies

### PHP Packages
- Laravel 13 (framework)
- Filament v5 (admin panel)
- Livewire v4 (reactivity)
- Carbon (date handling)
- Spatie queue (async jobs)

### External Libraries
- filter_var (PHP built-in for email validation)
- fgetcsv (PHP built-in for CSV parsing)

## Performance Notes

- CSV parsing: Streams file (low memory)
- Import processing: Queued (non-blocking)
- Database: One insert per patient
- No batch limits
- Atomic transactions for consistency

## Security Features

- Tenant isolation via BelongsToPractice
- File type validation (CSV only)
- Email format validation
- Input sanitization (trim, lowercasing)
- SQL injection protection (Eloquent ORM)
- No hardcoded secrets or credentials

## Future Enhancement Opportunities

- Scheduled imports
- Duplicate merge strategies
- Custom field mapping storage
- Import error reports (downloadable)
- Webhook notifications
- Update existing patients option
- Batch gender selection
- External data source integration
