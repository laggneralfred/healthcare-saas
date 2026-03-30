<?php

namespace Tests\Feature;

use App\Jobs\ImportPatientsJob;
use App\Models\ImportHistory;
use App\Models\Patient;
use App\Models\Practice;
use App\Services\CSVImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CSVImportTest extends TestCase
{
    use RefreshDatabase;

    private Practice $practice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->practice = Practice::factory()->create();
    }

    /** @test */
    public function it_parses_csv_with_valid_data(): void
    {
        $csvContent = "first_name,last_name,email,phone,dob,gender,city,state\n";
        $csvContent .= "John,Doe,john@example.com,555-123-4567,01/15/1985,male,New York,NY\n";
        $csvContent .= "Jane,Smith,jane@example.com,555-987-6543,03/22/1990,female,Los Angeles,CA\n";

        $file = UploadedFile::createFromBase(
            new \Symfony\Component\HttpFoundation\File\File(
                tap(tempnam(sys_get_temp_dir(), 'csv'), function ($path) use ($csvContent) {
                    file_put_contents($path, $csvContent);
                })
            )
        );

        $columnMap = [0 => 'first_name', 1 => 'last_name', 2 => 'email', 3 => 'phone', 4 => 'dob', 5 => 'gender', 6 => 'city', 7 => 'state'];
        $rows = CSVImportService::parseUpload($file, $columnMap);

        $this->assertCount(2, $rows);
        $this->assertEquals('John', $rows[0]['first_name']);
        $this->assertEquals('Doe', $rows[0]['last_name']);
        $this->assertEquals('john@example.com', $rows[0]['email']);
    }

    /** @test */
    public function it_handles_missing_optional_fields(): void
    {
        $csvContent = "first_name,last_name,email\n";
        $csvContent .= "John,Doe,john@example.com\n";

        $file = UploadedFile::createFromBase(
            new \Symfony\Component\HttpFoundation\File\File(
                tap(tempnam(sys_get_temp_dir(), 'csv'), function ($path) use ($csvContent) {
                    file_put_contents($path, $csvContent);
                })
            )
        );

        $columnMap = [0 => 'first_name', 1 => 'last_name', 2 => 'email'];
        $rows = CSVImportService::parseUpload($file, $columnMap);

        $this->assertCount(1, $rows);
        $this->assertEquals('John', $rows[0]['first_name']);
        $this->assertArrayNotHasKey('phone', $rows[0]);
    }

    /** @test */
    public function it_parses_various_date_formats(): void
    {
        // MM/DD/YYYY
        $date1 = CSVImportService::parseDate('01/15/1985');
        $this->assertEquals('1985-01-15', $date1->format('Y-m-d'));

        // YYYY-MM-DD
        $date2 = CSVImportService::parseDate('1985-01-15');
        $this->assertEquals('1985-01-15', $date2->format('Y-m-d'));

        // DD.MM.YYYY
        $date3 = CSVImportService::parseDate('15.01.1985');
        $this->assertEquals('1985-01-15', $date3->format('Y-m-d'));

        // Empty
        $date4 = CSVImportService::parseDate('');
        $this->assertNull($date4);
    }

    /** @test */
    public function it_formats_phone_numbers(): void
    {
        // Standard format with dashes
        $phone1 = CSVImportService::formatPhone('555-123-4567');
        $this->assertEquals('5551234567', $phone1);

        // Format with parentheses
        $phone2 = CSVImportService::formatPhone('(555) 123-4567');
        $this->assertEquals('5551234567', $phone2);

        // Format with spaces
        $phone3 = CSVImportService::formatPhone('555 123 4567');
        $this->assertEquals('5551234567', $phone3);

        // Empty
        $phone4 = CSVImportService::formatPhone('');
        $this->assertNull($phone4);
    }

    /** @test */
    public function it_validates_email_format(): void
    {
        $this->assertTrue(CSVImportService::isValidEmail('john@example.com'));
        $this->assertTrue(CSVImportService::isValidEmail('john.doe@example.co.uk'));
        $this->assertFalse(CSVImportService::isValidEmail('invalid-email'));
        $this->assertFalse(CSVImportService::isValidEmail('john@'));
    }

    /** @test */
    public function it_generates_csv_template(): void
    {
        $template = CSVImportService::generateTemplate();

        $this->assertStringContainsString('first_name', $template);
        $this->assertStringContainsString('last_name', $template);
        $this->assertStringContainsString('email', $template);
        $this->assertStringContainsString('phone', $template);
        $this->assertStringContainsString('dob', $template);
        $this->assertStringContainsString('gender', $template);
        $this->assertStringContainsString('address', $template);
        $this->assertStringContainsString('city', $template);
        $this->assertStringContainsString('state', $template);
        $this->assertStringContainsString('postal_code', $template);
    }

    /** @test */
    public function import_job_creates_patients_successfully(): void
    {
        $rows = [
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john@example.com',
                'phone' => '555-123-4567',
                'dob' => '01/15/1985',
                'gender' => 'male',
                'city' => 'New York',
                'state' => 'NY',
            ],
            [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'email' => 'jane@example.com',
                'phone' => '555-987-6543',
                'dob' => '03/22/1990',
                'gender' => 'female',
                'city' => 'Los Angeles',
                'state' => 'CA',
            ],
        ];

        $importHistory = ImportHistory::create([
            'practice_id' => $this->practice->id,
            'filename' => 'test.csv',
            'total_rows' => 2,
            'status' => 'pending',
        ]);

        $columnMap = ['first_name', 'last_name', 'email', 'phone', 'dob', 'gender', '', 'city', 'state'];
        $job = new ImportPatientsJob($this->practice->id, $importHistory->id, $rows, $columnMap);
        $job->handle();

        $this->assertEquals(2, Patient::withoutPracticeScope()->where('practice_id', $this->practice->id)->count());

        $patient1 = Patient::withoutPracticeScope()
            ->where('practice_id', $this->practice->id)
            ->where('email', 'john@example.com')
            ->first();

        $this->assertNotNull($patient1);
        $this->assertEquals('John', $patient1->first_name);
        $this->assertEquals('Doe', $patient1->last_name);
        $this->assertEquals('5551234567', $patient1->phone);
        $this->assertEquals('1985-01-15', $patient1->dob->format('Y-m-d'));
        $this->assertEquals('male', $patient1->gender);

        $importHistory->refresh();
        $this->assertEquals('completed', $importHistory->status);
        $this->assertEquals(2, $importHistory->imported);
    }

    /** @test */
    public function import_job_skips_duplicate_emails(): void
    {
        // Create existing patient
        Patient::withoutPracticeScope()->create([
            'practice_id' => $this->practice->id,
            'name' => 'John Existing',
            'first_name' => 'John',
            'last_name' => 'Existing',
            'email' => 'john@example.com',
            'is_patient' => true,
        ]);

        $rows = [
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john@example.com', // Duplicate
            ],
            [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'email' => 'jane@example.com',
            ],
        ];

        $importHistory = ImportHistory::create([
            'practice_id' => $this->practice->id,
            'filename' => 'test.csv',
            'total_rows' => 2,
            'status' => 'pending',
        ]);

        $columnMap = [0 => 'first_name', 1 => 'last_name', 2 => 'email'];
        $job = new ImportPatientsJob($this->practice->id, $importHistory->id, $rows, $columnMap);
        $job->handle();

        $importHistory->refresh();
        $this->assertEquals(1, $importHistory->imported);
        $this->assertEquals(1, $importHistory->skipped);
        $this->assertEquals(0, $importHistory->failed);
    }

    /** @test */
    public function import_job_skips_rows_missing_required_fields(): void
    {
        $rows = [
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john@example.com',
            ],
            [
                'first_name' => 'Jane',
                // Missing last_name
                'email' => 'jane@example.com',
            ],
            [
                // Missing first_name
                'last_name' => 'Brown',
                'email' => 'brown@example.com',
            ],
        ];

        $importHistory = ImportHistory::create([
            'practice_id' => $this->practice->id,
            'filename' => 'test.csv',
            'total_rows' => 3,
            'status' => 'pending',
        ]);

        $columnMap = [0 => 'first_name', 1 => 'last_name', 2 => 'email'];
        $job = new ImportPatientsJob($this->practice->id, $importHistory->id, $rows, $columnMap);
        $job->handle();

        $importHistory->refresh();
        $this->assertEquals(1, $importHistory->imported);
        $this->assertEquals(2, $importHistory->skipped);
    }

    /** @test */
    public function import_job_validates_email_format(): void
    {
        $rows = [
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'invalid-email',
            ],
            [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'email' => 'jane@example.com',
            ],
        ];

        $importHistory = ImportHistory::create([
            'practice_id' => $this->practice->id,
            'filename' => 'test.csv',
            'total_rows' => 2,
            'status' => 'pending',
        ]);

        $columnMap = [0 => 'first_name', 1 => 'last_name', 2 => 'email'];
        $job = new ImportPatientsJob($this->practice->id, $importHistory->id, $rows, $columnMap);
        $job->handle();

        $importHistory->refresh();
        $this->assertEquals(1, $importHistory->imported);
        $this->assertEquals(1, $importHistory->skipped);
    }

    /** @test */
    public function import_job_sanitizes_gender_values(): void
    {
        $rows = [
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john@example.com',
                'gender' => 'MALE',
            ],
            [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'email' => 'jane@example.com',
                'gender' => 'Female',
            ],
            [
                'first_name' => 'Alex',
                'last_name' => 'Jordan',
                'email' => 'alex@example.com',
                'gender' => 'invalid',
            ],
        ];

        $importHistory = ImportHistory::create([
            'practice_id' => $this->practice->id,
            'filename' => 'test.csv',
            'total_rows' => 3,
            'status' => 'pending',
        ]);

        $columnMap = [0 => 'first_name', 1 => 'last_name', 2 => 'email', 3 => 'gender'];
        $job = new ImportPatientsJob($this->practice->id, $importHistory->id, $rows, $columnMap);
        $job->handle();

        $john = Patient::withoutPracticeScope()
            ->where('practice_id', $this->practice->id)
            ->where('email', 'john@example.com')
            ->first();
        $this->assertEquals('male', $john->gender);

        $jane = Patient::withoutPracticeScope()
            ->where('practice_id', $this->practice->id)
            ->where('email', 'jane@example.com')
            ->first();
        $this->assertEquals('female', $jane->gender);

        $alex = Patient::withoutPracticeScope()
            ->where('practice_id', $this->practice->id)
            ->where('email', 'alex@example.com')
            ->first();
        $this->assertNull($alex->gender);
    }

    /** @test */
    public function import_history_tracks_import_progress(): void
    {
        $importHistory = ImportHistory::create([
            'practice_id' => $this->practice->id,
            'filename' => 'test.csv',
            'total_rows' => 3,
            'status' => 'pending',
        ]);

        $this->assertEquals('pending', $importHistory->status);
        $this->assertEquals(0, $importHistory->imported);

        $importHistory->update([
            'status' => 'processing',
            'imported' => 2,
            'skipped' => 1,
        ]);

        $this->assertEquals('processing', $importHistory->status);
        $this->assertEquals(2, $importHistory->imported);
        $this->assertEquals(1, $importHistory->skipped);
    }

    /** @test */
    public function patient_model_is_fillable_with_new_fields(): void
    {
        $patient = Patient::withoutPracticeScope()->create([
            'practice_id' => $this->practice->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '5551234567',
            'dob' => '1985-01-15',
            'gender' => 'male',
            'address' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
        ]);

        $this->assertEquals('John', $patient->first_name);
        $this->assertEquals('Doe', $patient->last_name);
        $this->assertEquals('1985-01-15', $patient->dob->format('Y-m-d'));
        $this->assertEquals('male', $patient->gender);
        $this->assertEquals('123 Main St', $patient->address);
        $this->assertEquals('New York', $patient->city);
        $this->assertEquals('NY', $patient->state);
        $this->assertEquals('10001', $patient->postal_code);
    }
}
