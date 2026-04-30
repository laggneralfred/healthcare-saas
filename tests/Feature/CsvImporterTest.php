<?php

namespace Tests\Feature;

use App\Jobs\DryRunImportJob;
use App\Jobs\ImportPatientsJob;
use App\Jobs\ImportSessionJob;
use App\Models\ImportHistory;
use App\Models\ImportSession;
use App\Models\Patient;
use App\Models\Practice;
use App\Services\CsvColumnMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CsvImporterTest extends TestCase
{
    use RefreshDatabase;

    private Practice $practice;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->practice = Practice::factory()->create();
    }

    // ── CsvColumnMapper ───────────────────────────────────────────────────────

    public function test_mapper_suggests_exact_field_name(): void
    {
        $mapper = new CsvColumnMapper();
        $result = $mapper->suggest(['first_name', 'last_name', 'email']);

        $this->assertEquals('first_name', $result[0]['field']);
        $this->assertEquals('high', $result[0]['confidence']);
        $this->assertEquals('last_name', $result[1]['field']);
        $this->assertEquals('email', $result[2]['field']);
    }

    public function test_mapper_resolves_synonym(): void
    {
        $mapper = new CsvColumnMapper();
        $result = $mapper->suggest(['fname', 'surname', 'telephone', 'zip', 'dob', 'primary language']);

        $this->assertEquals('first_name',  $result[0]['field']);
        $this->assertEquals('last_name',   $result[1]['field']);
        $this->assertEquals('phone',       $result[2]['field']);
        $this->assertEquals('postal_code', $result[3]['field']);
        $this->assertEquals('dob',         $result[4]['field']);
        $this->assertEquals('preferred_language', $result[5]['field']);
        $this->assertEquals('high', $result[0]['confidence']);
    }

    public function test_mapper_normalises_hyphen_and_space(): void
    {
        $mapper = new CsvColumnMapper();
        $result = $mapper->suggest(['First Name', 'Last-Name', 'date of birth']);

        $this->assertEquals('first_name', $result[0]['field']);
        $this->assertEquals('last_name',  $result[1]['field']);
        $this->assertEquals('dob',        $result[2]['field']);
    }

    public function test_mapper_returns_null_for_unrecognised_column(): void
    {
        $mapper = new CsvColumnMapper();
        $result = $mapper->suggest(['notes', 'allergies', 'insurance_id']);

        $this->assertNull($result[0]['field']);
        $this->assertEquals('none', $result[0]['confidence']);
    }

    public function test_mapper_field_options_includes_skip(): void
    {
        $options = CsvColumnMapper::fieldOptions();

        $this->assertArrayHasKey('', $options);
        $this->assertEquals('(Skip)', $options['']);
        $this->assertArrayHasKey('first_name', $options);
        $this->assertArrayHasKey('postal_code', $options);
        $this->assertArrayHasKey('preferred_language', $options);
    }

    // ── ImportSession model ───────────────────────────────────────────────────

    public function test_import_session_can_be_created_with_pending_status(): void
    {
        $session = ImportSession::create([
            'practice_id'       => $this->practice->id,
            'status'            => 'pending',
            'file_path'         => 'imports/1/test.csv',
            'original_filename' => 'test.csv',
            'detected_headers'  => ['first_name', 'last_name', 'email'],
            'column_mappings'   => [0 => 'first_name', 1 => 'last_name', 2 => 'email'],
        ]);

        $this->assertDatabaseHas('import_sessions', [
            'id'          => $session->id,
            'status'      => 'pending',
            'practice_id' => $this->practice->id,
        ]);

        $this->assertIsArray($session->detected_headers);
        $this->assertEquals('first_name', $session->detected_headers[0]);
    }

    // ── DryRunImportJob ───────────────────────────────────────────────────────

    public function test_dry_run_counts_valid_duplicate_and_error_rows(): void
    {
        $csv = "first_name,last_name,email\n"
             . "Alice,Smith,alice@example.com\n"         // valid
             . "Bob,Jones,bob@example.com\n"             // valid
             . "Alice,Smith,alice@example.com\n"         // duplicate (same email in file)
             . "Charlie,,charlie@example.com\n"          // error: missing last_name
             . "Dana,Brown,not-an-email\n";              // error: invalid email

        Storage::disk('local')->put('imports/1/test.csv', $csv);

        $session = ImportSession::create([
            'practice_id'      => $this->practice->id,
            'status'           => 'pending',
            'file_path'        => 'imports/1/test.csv',
            'original_filename'=> 'test.csv',
            'detected_headers' => ['first_name', 'last_name', 'email'],
            'column_mappings'  => [0 => 'first_name', 1 => 'last_name', 2 => 'email'],
        ]);

        (new DryRunImportJob($session->id))->handle();

        $session->refresh();
        $this->assertEquals('ready', $session->status);
        $this->assertEquals(5, $session->total_rows);  // all 5 data rows (duplicates also counted)
        $this->assertEquals(2, $session->valid_rows);
        $this->assertEquals(1, $session->duplicate_rows);
        $this->assertEquals(2, $session->error_rows);
    }

    public function test_dry_run_detects_existing_practice_email_as_duplicate(): void
    {
        Patient::withoutPracticeScope()->create([
            'practice_id' => $this->practice->id,
            'first_name'  => 'Existing',
            'last_name'   => 'Patient',
            'name'        => 'Existing Patient',
            'email'       => 'existing@example.com',
            'is_patient'  => true,
        ]);

        $csv = "first_name,last_name,email\n"
             . "Existing,Patient,existing@example.com\n"  // duplicate (in DB)
             . "New,Person,new@example.com\n";            // valid

        Storage::disk('local')->put('imports/1/dup.csv', $csv);

        $session = ImportSession::create([
            'practice_id'      => $this->practice->id,
            'status'           => 'pending',
            'file_path'        => 'imports/1/dup.csv',
            'original_filename'=> 'dup.csv',
            'detected_headers' => ['first_name', 'last_name', 'email'],
            'column_mappings'  => [0 => 'first_name', 1 => 'last_name', 2 => 'email'],
        ]);

        (new DryRunImportJob($session->id))->handle();

        $session->refresh();
        $this->assertEquals(1, $session->valid_rows);
        $this->assertEquals(1, $session->duplicate_rows);
    }

    public function test_dry_run_does_not_create_patients(): void
    {
        $csv = "first_name,last_name,email\nJohn,Doe,john@example.com\n";
        Storage::disk('local')->put('imports/1/nodelete.csv', $csv);

        $session = ImportSession::create([
            'practice_id'      => $this->practice->id,
            'status'           => 'pending',
            'file_path'        => 'imports/1/nodelete.csv',
            'original_filename'=> 'nodelete.csv',
            'detected_headers' => ['first_name', 'last_name', 'email'],
            'column_mappings'  => [0 => 'first_name', 1 => 'last_name', 2 => 'email'],
        ]);

        (new DryRunImportJob($session->id))->handle();

        $this->assertEquals(0, Patient::withoutPracticeScope()->where('practice_id', $this->practice->id)->count());
    }

    public function test_dry_run_sets_status_failed_on_missing_file(): void
    {
        $session = ImportSession::create([
            'practice_id'      => $this->practice->id,
            'status'           => 'pending',
            'file_path'        => 'imports/1/nonexistent.csv',
            'original_filename'=> 'nonexistent.csv',
            'detected_headers' => [],
            'column_mappings'  => [],
        ]);

        (new DryRunImportJob($session->id))->handle();

        $session->refresh();
        $this->assertEquals('failed', $session->status);
    }

    // ── ImportSessionJob ──────────────────────────────────────────────────────

    public function test_import_session_job_creates_patients_from_csv(): void
    {
        $csv = "first_name,last_name,email,phone,dob,gender,preferred_language,address_line_1,address_line_2,city,state,postal_code,country,emergency_contact_name,occupation\n"
             . "Alice,Smith,alice@example.com,(707) 555-0101,1985-06-15,female,Spanish,123 Main St,Apt 4,San Rafael,CA,94901,USA,Mary Smith,Yoga Teacher\n"
             . "Bob,Jones,bob@example.com,(707) 555-0202,1990-03-22,male,en,456 Oak Ave,Suite B,Novato,CA,94945,USA,Sue Jones,Engineer\n";

        Storage::disk('local')->put('imports/1/import.csv', $csv);

        $session = ImportSession::create([
            'practice_id'      => $this->practice->id,
            'status'           => 'ready',
            'file_path'        => 'imports/1/import.csv',
            'original_filename'=> 'import.csv',
            'total_rows'       => 2,
            'valid_rows'       => 2,
            'detected_headers' => ['first_name', 'last_name', 'email', 'phone', 'dob', 'gender', 'preferred_language', 'address_line_1', 'address_line_2', 'city', 'state', 'postal_code', 'country', 'emergency_contact_name', 'occupation'],
            'column_mappings'  => [0 => 'first_name', 1 => 'last_name', 2 => 'email', 3 => 'phone', 4 => 'dob', 5 => 'gender', 6 => 'preferred_language', 7 => 'address_line_1', 8 => 'address_line_2', 9 => 'city', 10 => 'state', 11 => 'postal_code', 12 => 'country', 13 => 'emergency_contact_name', 14 => 'occupation'],
        ]);

        (new ImportSessionJob($session->id))->handle();

        $this->assertEquals(2, Patient::withoutPracticeScope()->where('practice_id', $this->practice->id)->count());

        $alice = Patient::withoutPracticeScope()
            ->where('practice_id', $this->practice->id)
            ->where('email', 'alice@example.com')
            ->first();

        $this->assertNotNull($alice);
        $this->assertEquals('Alice', $alice->first_name);
        $this->assertEquals('Smith', $alice->last_name);
        $this->assertEquals('Female', $alice->gender);
        $this->assertEquals('es', $alice->preferred_language);
        $this->assertEquals('1985-06-15', $alice->dob->format('Y-m-d'));
        $this->assertEquals('123 Main St', $alice->address_line_1);
        $this->assertEquals('Apt 4', $alice->address_line_2);
        $this->assertEquals('San Rafael', $alice->city);
        $this->assertEquals('CA', $alice->state);
        $this->assertEquals('94901', $alice->postal_code);
        $this->assertEquals('USA', $alice->country);
        $this->assertEquals('Mary Smith', $alice->emergency_contact_name);
        $this->assertEquals('Yoga Teacher', $alice->occupation);

        $session->refresh();
        $this->assertEquals('complete', $session->status);
        $this->assertEquals(2, $session->imported_rows);
    }

    public function test_import_session_job_skips_duplicates(): void
    {
        $csv = "first_name,last_name,email\n"
             . "Alice,Smith,alice@example.com\n"
             . "Alice,Smith,alice@example.com\n"  // duplicate within file
             . "Bob,Jones,bob@example.com\n";

        Storage::disk('local')->put('imports/1/dupimport.csv', $csv);

        $session = ImportSession::create([
            'practice_id'      => $this->practice->id,
            'status'           => 'ready',
            'file_path'        => 'imports/1/dupimport.csv',
            'original_filename'=> 'dupimport.csv',
            'total_rows'       => 3,
            'valid_rows'       => 2,
            'detected_headers' => ['first_name', 'last_name', 'email'],
            'column_mappings'  => [0 => 'first_name', 1 => 'last_name', 2 => 'email'],
        ]);

        (new ImportSessionJob($session->id))->handle();

        $this->assertEquals(2, Patient::withoutPracticeScope()->where('practice_id', $this->practice->id)->count());
    }

    public function test_import_session_job_creates_import_history_record(): void
    {
        $csv = "first_name,last_name,email\nAlice,Smith,alice@example.com\n";
        Storage::disk('local')->put('imports/1/hist.csv', $csv);

        $session = ImportSession::create([
            'practice_id'      => $this->practice->id,
            'status'           => 'ready',
            'file_path'        => 'imports/1/hist.csv',
            'original_filename'=> 'original.csv',
            'total_rows'       => 1,
            'valid_rows'       => 1,
            'detected_headers' => ['first_name', 'last_name', 'email'],
            'column_mappings'  => [0 => 'first_name', 1 => 'last_name', 2 => 'email'],
        ]);

        (new ImportSessionJob($session->id))->handle();

        $this->assertDatabaseHas('import_histories', [
            'practice_id' => $this->practice->id,
            'filename'    => 'original.csv',
            'imported'    => 1,
            'status'      => 'completed',
        ]);
    }

    public function test_import_session_job_isolates_practices(): void
    {
        $otherPractice = Practice::factory()->create();

        // Existing patient in OTHER practice — must NOT be treated as a duplicate
        Patient::withoutPracticeScope()->create([
            'practice_id' => $otherPractice->id,
            'first_name'  => 'Alice',
            'last_name'   => 'Smith',
            'name'        => 'Alice Smith',
            'email'       => 'alice@example.com',
            'is_patient'  => true,
        ]);

        $csv = "first_name,last_name,email\nAlice,Smith,alice@example.com\n";
        Storage::disk('local')->put('imports/1/isolated.csv', $csv);

        $session = ImportSession::create([
            'practice_id'      => $this->practice->id,
            'status'           => 'ready',
            'file_path'        => 'imports/1/isolated.csv',
            'original_filename'=> 'isolated.csv',
            'total_rows'       => 1,
            'valid_rows'       => 1,
            'detected_headers' => ['first_name', 'last_name', 'email'],
            'column_mappings'  => [0 => 'first_name', 1 => 'last_name', 2 => 'email'],
        ]);

        (new ImportSessionJob($session->id))->handle();

        $this->assertEquals(1, Patient::withoutPracticeScope()->where('practice_id', $this->practice->id)->count());
    }

    // ── Template download ─────────────────────────────────────────────────────

    public function test_template_download_returns_csv_file(): void
    {
        $user = \App\Models\User::factory()->create(['practice_id' => $this->practice->id]);

        $response = $this->actingAs($user)->get(route('import.template'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('first_name', $response->getContent());
        $this->assertStringContainsString('preferred_language', $response->getContent());
    }

    public function test_legacy_import_job_creates_patient_with_preferred_language(): void
    {
        $history = ImportHistory::create([
            'practice_id' => $this->practice->id,
            'filename' => 'legacy.csv',
            'total_rows' => 1,
            'status' => 'pending',
        ]);

        $rows = [[
            'first_name' => 'Language',
            'last_name' => 'Patient',
            'email' => 'language-patient@example.test',
            'preferred_language' => 'German',
        ]];

        (new ImportPatientsJob($this->practice->id, $history->id, $rows, []))->handle();

        $patient = Patient::withoutPracticeScope()
            ->where('practice_id', $this->practice->id)
            ->where('email', 'language-patient@example.test')
            ->firstOrFail();

        $this->assertEquals('de', $patient->preferred_language);
        $this->assertEquals('German', $patient->preferred_language_label);
    }
}
