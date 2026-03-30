# CSV Patient Import System

Complete documentation for the patient CSV import feature in healthcare-saas.

## Overview

The CSV patient importer provides bulk import functionality for patient records into your healthcare practice. It features:

- Automatic CSV header detection and column mapping
- Support for multiple date and phone formats
- Data validation and duplicate detection
- Queued job processing for performance
- Comprehensive import history tracking
- Preview of first 5 rows before import

## Architecture

### Database Schema

#### `import_histories` Table

Tracks all import operations:

```sql
CREATE TABLE import_histories (
    id BIGINT PRIMARY KEY,
    practice_id BIGINT NOT NULL FOREIGN KEY,
    filename VARCHAR(255),
    total_rows INT DEFAULT 0,
    imported INT DEFAULT 0,
    skipped INT DEFAULT 0,
    failed INT DEFAULT 0,
    status ENUM('pending', 'processing', 'completed', 'failed'),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX practice_id
);
```

#### `patients` Table Extensions

New columns added to support import:

```sql
ALTER TABLE patients ADD COLUMN (
    first_name VARCHAR(255) NULL,
    last_name VARCHAR(255) NULL,
    dob DATE NULL,
    gender ENUM('male', 'female', 'other', 'prefer_not_to_say') NULL,
    address VARCHAR(255) NULL,
    city VARCHAR(255) NULL,
    state VARCHAR(255) NULL,
    postal_code VARCHAR(255) NULL
);
```

### Core Components

#### 1. Models

**`App\Models\ImportHistory`**
- Tracks import operations
- Relationships: `practice()` (BelongsTo)
- Fillable fields: practice_id, filename, total_rows, imported, skipped, failed, status
- Uses `BelongsToPractice` trait for tenant isolation

**`App\Models\Patient`** (Extended)
- New fillable fields: first_name, last_name, dob, gender, address, city, state, postal_code
- Casts: dob as date
- Maintains backward compatibility with existing name field

#### 2. Services

**`App\Services\CSVImportService`**

Helper service for CSV operations:

- `parseDate($value)` - Parse dates in multiple formats
  - Supports: MM/DD/YYYY, DD/MM/YYYY, YYYY-MM-DD, M/D/YYYY, D/M/YYYY, DD.MM.YYYY, etc.
  - Returns: Carbon instance or null

- `formatPhone($value)` - Normalize phone numbers
  - Strips all non-digits
  - Returns: digit-only string or null

- `generateTemplate()` - Get CSV template headers
  - Returns: CSV string with standard headers

- `parseUpload(UploadedFile, columnMap)` - Parse CSV file
  - Parameters: file, column index to field name mapping
  - Returns: array of mapped row data
  - Handles: CSV parsing, column mapping, empty row skipping

- `isValidEmail(string)` - Validate email format
  - Returns: boolean

#### 3. Jobs

**`App\Jobs\ImportPatientsJob`** (Queued)

Processes patient import asynchronously:

```php
// Constructor
public function __construct(
    public readonly int $practiceId,
    public readonly int $importHistoryId,
    public readonly array $rows,
    public readonly array $columnMap,
)
```

Processing logic:

1. Loads ImportHistory record
2. Sets status to 'processing'
3. For each row:
   - Validates required fields (first_name, last_name)
   - Checks for duplicate emails in same practice
   - Validates email format
   - Parses dates in various formats
   - Formats phone numbers
   - Sanitizes gender values
   - Creates Patient record in transaction
4. Updates ImportHistory with counts
5. Logs all operations
6. Handles row-level errors gracefully

Key features:
- `DB::transaction()` for atomic inserts
- Row-level error handling with try-catch
- Comprehensive logging
- Skips duplicate emails
- Skips rows with missing required fields

#### 4. Filament Page

**`App\Filament\Pages\Settings\ImportPatients`**

Admin interface for CSV import:

Features:
- File upload with CSV validation
- Automatic CSV header detection
- Smart column mapping suggestions
- Preview of first 5 rows
- Column mapping UI
- Import action button
- Template download
- Import history table (last 5)

Methods:
- `mount()` - Initialize form
- `getFormSchema()` - Define form fields
- `loadCSVPreview()` - Load and analyze CSV file
- `loadPreviewRows()` - Load first 5 rows for preview
- `suggestField()` - Suggest field mapping based on column name
- `downloadTemplate()` - Download CSV template
- `importRows()` - Dispatch import job

#### 5. Blade View

**`resources/views/filament/pages/settings/import-patients.blade.php`**

User interface components:
- File upload form
- Column mapping repeater
- CSV preview table
- Action buttons (Import, Download Template)
- Import history table
- CSV format guide
- Help documentation

## CSV Format

### Supported Columns

| Column | Type | Required | Notes |
|--------|------|----------|-------|
| first_name | string | Yes | Patient's first name |
| last_name | string | Yes | Patient's last name |
| email | string | No | Must be valid email format if provided |
| phone | string | No | Stripped to digits only |
| dob | date | No | Accepts MM/DD/YYYY, YYYY-MM-DD, and other formats |
| gender | enum | No | One of: male, female, other, prefer_not_to_say |
| address | string | No | Street address |
| city | string | No | City/town name |
| state | string | No | State/province code |
| postal_code | string | No | ZIP or postal code |

### Example CSV

```csv
first_name,last_name,email,phone,dob,gender,address,city,state,postal_code
John,Doe,john@example.com,555-123-4567,01/15/1985,male,123 Main St,New York,NY,10001
Jane,Smith,jane@example.com,(555) 987-6543,1990-03-22,female,456 Oak Ave,Los Angeles,CA,90001
```

### Date Format Support

The system automatically detects and parses dates in these formats:

- MM/DD/YYYY (01/15/1985)
- YYYY-MM-DD (1985-01-15)
- DD/MM/YYYY (15/01/1985)
- DD.MM.YYYY (15.01.1985)
- M/D/YYYY (1/5/1985)
- D/M/YYYY (5/1/1985)
- And more (parsed via Carbon)

### Phone Format Support

Accepts phone numbers in any format:
- 5551234567
- 555-123-4567
- (555) 123-4567
- 555 123 4567

All are normalized to digit-only format: 5551234567

## Import Rules

1. **Required Fields**: first_name and last_name are mandatory
2. **Email Uniqueness**: Duplicate emails within the practice are skipped
3. **Email Validation**: Invalid email formats are rejected
4. **Phone Formatting**: All non-digits are stripped
5. **Date Parsing**: Multiple formats supported, invalid dates skipped
6. **Gender Normalization**: Values are lowercased; invalid values set to null
7. **Transactions**: All patient records created in atomic transactions
8. **Error Handling**: Row-level errors are logged; import continues
9. **Empty Rows**: Completely empty rows are skipped

## Usage

### For Users

1. Navigate to Admin Panel → Settings → Import Patients
2. Click "Download Template" to get CSV format
3. Upload your CSV file
4. Review column mappings (auto-detected)
5. Preview first 5 rows
6. Click "Import Patients"
7. Monitor import history below

### For Developers

#### Dispatch Import Programmatically

```php
use App\Jobs\ImportPatientsJob;
use App\Models\ImportHistory;
use App\Services\CSVImportService;

// Create import history
$importHistory = ImportHistory::create([
    'practice_id' => $practiceId,
    'filename' => 'patients.csv',
    'total_rows' => count($rows),
    'status' => 'pending',
]);

// Dispatch job
ImportPatientsJob::dispatch(
    $practiceId,
    $importHistory->id,
    $rows,
    $columnMap
);
```

#### Parse CSV Manually

```php
use App\Services\CSVImportService;

$file = request()->file('csv');
$columnMap = [
    0 => 'first_name',
    1 => 'last_name',
    2 => 'email',
];

$rows = CSVImportService::parseUpload($file, $columnMap);
```

#### Format Individual Values

```php
use App\Services\CSVImportService;

$dob = CSVImportService::parseDate('01/15/1985');
$phone = CSVImportService::formatPhone('555-123-4567');
$isValid = CSVImportService::isValidEmail('john@example.com');
```

## Import History

The import history table (`import_histories`) tracks all import operations:

- **Filename**: Original uploaded filename
- **Total Rows**: Total rows in CSV (excluding header)
- **Imported**: Successfully created patients
- **Skipped**: Rows skipped (duplicates, missing fields)
- **Failed**: Rows with errors
- **Status**: pending, processing, completed, failed
- **Timestamps**: Created/updated times

### Querying Import History

```php
use App\Models\ImportHistory;

// Get recent imports for practice
$imports = ImportHistory::where('practice_id', $practiceId)
    ->orderByDesc('created_at')
    ->limit(10)
    ->get();

// Get completed imports
$completed = ImportHistory::where('status', 'completed')
    ->where('practice_id', $practiceId)
    ->get();

// Total patients imported this month
$totalImported = ImportHistory::where('practice_id', $practiceId)
    ->whereMonth('created_at', now()->month)
    ->sum('imported');
```

## Data Validation

### Field-Level Validation

- **first_name**: Required, max 255 characters
- **last_name**: Required, max 255 characters
- **email**: Optional, must be valid format if provided
- **phone**: Optional, stripped to digits
- **dob**: Optional, parsed from multiple formats
- **gender**: Optional, validated against allowed values
- **address**: Optional, max 255 characters
- **city**: Optional, max 255 characters
- **state**: Optional, max 255 characters
- **postal_code**: Optional, max 255 characters

### Row-Level Validation

- Both first_name and last_name required
- Email must be unique per practice (if provided)
- Empty rows are skipped
- Rows with errors are logged

## Logging

All import operations are logged to `storage/logs/laravel.log`:

```
[2026-03-29 12:34:56] local.INFO: ImportPatientsJob: Row 0 imported successfully []
[2026-03-29 12:34:56] local.WARNING: ImportPatientsJob: Row 1 skipped - email already exists: john@example.com []
[2026-03-29 12:34:56] local.ERROR: ImportPatientsJob: Row 2 failed {"error":"Invalid data","data":[...]}
[2026-03-29 12:34:56] local.INFO: ImportPatientsJob: Completed {"practice_id":1,"import_history_id":5,"imported":2,"skipped":1,"failed":0}
```

## Performance Considerations

- **Queued Processing**: Imports run asynchronously via queue
- **Atomic Transactions**: All rows in transaction for consistency
- **Batch Processing**: No batch size limit; rows processed individually
- **Memory**: CSV parsing streams file; minimal memory overhead
- **Database**: One insert per patient; use queued processing

## Testing

Comprehensive test suite in `tests/Feature/CSVImportTest.php`:

```bash
php artisan test tests/Feature/CSVImportTest.php
```

Tests cover:
- CSV parsing and column mapping
- Date format parsing
- Phone number formatting
- Email validation
- Patient creation
- Duplicate detection
- Field validation
- Gender sanitization
- Import history tracking
- Error handling

## Troubleshooting

### Import Stuck on "Processing"

Check queue worker status:
```bash
php artisan queue:work
```

### Missing Patients After Import

1. Check import history status
2. Review logs: `tail -f storage/logs/laravel.log`
3. Verify column mapping
4. Check for duplicate emails

### Date Not Parsing

Ensure date format is in supported list:
- MM/DD/YYYY (US)
- DD/MM/YYYY (European)
- YYYY-MM-DD (ISO)
- Other formats parsed via Carbon

### Phone Numbers Appearing Empty

Phone field is optional. Ensure column is mapped correctly.

## File Locations

- Migration (extend): `/database/migrations/2026_03_27_extend_patients_table.php`
- Migration (history): `/database/migrations/2026_03_27_create_import_histories_table.php`
- Model: `/app/Models/ImportHistory.php`
- Service: `/app/Services/CSVImportService.php`
- Job: `/app/Jobs/ImportPatientsJob.php`
- Page: `/app/Filament/Pages/Settings/ImportPatients.php`
- View: `/resources/views/filament/pages/settings/import-patients.blade.php`
- Tests: `/tests/Feature/CSVImportTest.php`

## Migration Guide

To deploy this feature:

1. Run migrations:
   ```bash
   php artisan migrate
   ```

2. Clear cache:
   ```bash
   php artisan cache:clear
   ```

3. Test in development:
   ```bash
   php artisan test tests/Feature/CSVImportTest.php
   ```

4. Queue worker running in production:
   ```bash
   php artisan queue:work
   ```

## Security

- **Tenant Isolation**: Uses `BelongsToPractice` scope
- **File Validation**: CSV type checking
- **Input Sanitization**: Fields trimmed and validated
- **SQL Injection**: Protected via Eloquent ORM
- **Email Spoofing**: Email format validation

## Future Enhancements

- Bulk gender selection (if not in CSV)
- Custom field mapping storage
- Duplicate detection strategies (name matching, phone matching)
- Import error reports (downloadable)
- Scheduled imports from external sources
- Update existing patients option
- Merge duplicate patients
- Import history export
- Webhook notifications on completion
