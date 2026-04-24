<?php

namespace Tests\Feature;

use App\Jobs\ExportPracticeDataJob;
use App\Models\ExportToken;
use App\Models\MedicalHistory;
use App\Models\Practice;
use App\Models\User;
use App\Services\PracticeContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

class ExportDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        // For tests that don't need custom practices, set up a default
        // But most tests will create their own
    }

    public function test_export_request_dispatches_job()
    {
        Queue::fake();

        $practice = Practice::factory()->create(['trial_ends_at' => now()->addDays(30)]);
        $user = User::factory()->create(['practice_id' => $practice->id]);

        $response = $this->actingAs($user)->withoutMiddleware()->post(route('export.request'), [
            'format' => 'csv',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('message');

        $token = ExportToken::where('practice_id', $practice->id)->first();
        $this->assertNotNull($token);
        $this->assertEquals('csv', $token->format);
        $this->assertEquals('processing', $token->status);

        Queue::assertPushed(ExportPracticeDataJob::class);
    }

    public function test_selected_practice_export_request_dispatches_job_for_super_admin()
    {
        Queue::fake();

        $selectedPractice = Practice::factory()->create(['trial_ends_at' => now()->addDays(30)]);
        $otherPractice = Practice::factory()->create(['trial_ends_at' => now()->addDays(30)]);
        $user = User::factory()->create(['practice_id' => null]);

        $this->actingAs($user);
        PracticeContext::setCurrentPracticeId($selectedPractice->id);

        $response = $this->withoutMiddleware()->post(route('export.request'), [
            'format' => 'csv',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('message');

        $token = ExportToken::where('practice_id', $selectedPractice->id)->first();
        $this->assertNotNull($token);
        $this->assertEquals('csv', $token->format);
        $this->assertNull(ExportToken::where('practice_id', $otherPractice->id)->first());

        Queue::assertPushed(ExportPracticeDataJob::class, function (ExportPracticeDataJob $job) use ($selectedPractice) {
            return $job->practiceId === $selectedPractice->id;
        });
    }

    public function test_export_validates_format()
    {
        $practice = Practice::factory()->create(['trial_ends_at' => now()->addDays(30)]);
        $user = User::factory()->create(['practice_id' => $practice->id]);

        $response = $this->actingAs($user)->post(route('export.request'), [
            'format' => 'invalid',
        ]);

        $response->assertSessionHasErrors('format');
    }

    public function test_csv_zip_contains_all_expected_files()
    {
        $practice = Practice::factory()->create(['trial_ends_at' => now()->addDays(30)]);
        $user = User::factory()->create(['practice_id' => $practice->id]);

        // Create sample data
        $practice->practitioners()->create([
            'user_id' => $user->id,
            'license_number' => 'L12345',
            'specialty' => 'Acupuncture',
            'is_active' => true,
        ]);

        $practice->patients()->create([
            'name' => 'John Doe',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'is_patient' => true,
        ]);

        // Run export job synchronously
        $token = ExportToken::create([
            'practice_id' => $practice->id,
            'format' => 'csv',
            'status' => 'processing',
            'expires_at' => now()->addHours(24),
        ]);

        (new ExportPracticeDataJob($practice->id, $token->id, 'csv'))->handle();

        $token->refresh();
        $this->assertEquals('ready', $token->status);
        $this->assertNotNull($token->file_path);

        // Write the fake-storage content to a real temp file so ZipArchive can open it
        $zipContent = Storage::get($token->file_path);
        $this->assertNotNull($zipContent, 'ZIP file content should not be null');

        $tempZipPath = tempnam(sys_get_temp_dir(), 'test_zip_') . '.zip';
        file_put_contents($tempZipPath, $zipContent);

        $zip = new ZipArchive();
        $result = $zip->open($tempZipPath);
        $this->assertTrue($result === true, "ZipArchive failed to open: error code {$result}");

        $expectedFiles = [
            'practice.csv',
            'practitioners.csv',
            'patients.csv',
            'medical_historys.csv',
            'consent_records.csv',
            'appointments.csv',
            'encounters.csv',
            'acupuncture_encounters.csv',
            'checkout_sessions.csv',
            'checkout_lines.csv',
            'service_fees.csv',
            'appointment_types.csv',
            'inventory_products.csv',
            'inventory_movements.csv',
        ];

        foreach ($expectedFiles as $file) {
            $this->assertTrue($zip->locateName($file) !== false, "Missing file: {$file}");
        }

        $zip->close();
        unlink($tempZipPath);
    }

    public function test_csv_export_json_encodes_array_cast_columns()
    {
        $practice = Practice::factory()->create(['trial_ends_at' => now()->addDays(30)]);
        $user = User::factory()->create(['practice_id' => $practice->id]);

        $patient = $practice->patients()->create([
            'name' => 'John Doe',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'is_patient' => true,
        ]);

        MedicalHistory::create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'status' => 'complete',
            'discipline' => 'acupuncture',
            'discipline_responses' => [
                'tcm' => [
                    'sleep_issues' => ['staying_asleep'],
                    'previous_acupuncture' => false,
                ],
            ],
        ]);

        $token = ExportToken::create([
            'practice_id' => $practice->id,
            'format' => 'csv',
            'status' => 'processing',
            'expires_at' => now()->addHours(24),
        ]);

        (new ExportPracticeDataJob($practice->id, $token->id, 'csv'))->handle();

        $token->refresh();
        $this->assertEquals('ready', $token->status);
        $this->assertNotNull($token->file_path);

        $zipContent = Storage::get($token->file_path);
        $tempZipPath = tempnam(sys_get_temp_dir(), 'test_zip_') . '.zip';
        file_put_contents($tempZipPath, $zipContent);

        $zip = new ZipArchive();
        $result = $zip->open($tempZipPath);
        $this->assertTrue($result === true, "ZipArchive failed to open: error code {$result}");

        $csv = $zip->getFromName('medical_historys.csv');

        $this->assertIsString($csv);
        $this->assertStringContainsString('discipline_responses', $csv);
        $this->assertStringContainsString('"{""tcm"":{""sleep_issues"":[""staying_asleep""],""previous_acupuncture"":false}}"', $csv);

        $zip->close();
        unlink($tempZipPath);
    }

    public function test_json_export_contains_all_expected_keys()
    {
        $practice = Practice::factory()->create(['trial_ends_at' => now()->addDays(30)]);
        $user = User::factory()->create(['practice_id' => $practice->id]);

        $token = ExportToken::create([
            'practice_id' => $practice->id,
            'format' => 'json',
            'status' => 'processing',
            'expires_at' => now()->addHours(24),
        ]);

        (new ExportPracticeDataJob($practice->id, $token->id, 'json'))->handle();

        $token->refresh();
        $this->assertEquals('ready', $token->status);

        $this->assertTrue(Storage::exists($token->file_path));
        $data = json_decode(Storage::get($token->file_path), true);

        $expectedKeys = [
            'exported_at',
            'practice',
            'practitioners',
            'patients',
            'checkout_sessions',
            'inventory_products',
            'service_fees',
            'appointment_types',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $data, "Missing key: {$key}");
        }

        $this->assertIsString($data['exported_at']);
        $this->assertIsArray($data['practice']);
        $this->assertIsArray($data['practitioners']);
        $this->assertIsArray($data['patients']);
    }

    public function test_export_token_expires_after_24_hours()
    {
        $practice = Practice::factory()->create();
        $user = User::factory()->create(['practice_id' => $practice->id]);

        $token = ExportToken::create([
            'practice_id' => $practice->id,
            'format' => 'csv',
            'file_path' => 'exports/1/test.zip',
            'status' => 'ready',
            'expires_at' => now()->subMinutes(1),
        ]);

        $response = $this->actingAs($user)->get(route('export.download', $token->id));

        $response->assertStatus(410);
    }

    public function test_practice_a_cannot_download_practice_b_export()
    {
        $practiceA = Practice::factory()->create();
        $userA = User::factory()->create(['practice_id' => $practiceA->id]);

        $practiceB = Practice::factory()->create();
        $userB = User::factory()->create(['practice_id' => $practiceB->id]);

        $tokenB = ExportToken::create([
            'practice_id' => $practiceB->id,
            'format' => 'csv',
            'file_path' => 'exports/2/test.zip',
            'status' => 'ready',
            'expires_at' => now()->addHours(24),
        ]);

        // UserA tries to download UserB's export
        $response = $this->actingAs($userA)->get(route('export.download', $tokenB->id));

        $response->assertStatus(404);
    }

    public function test_selected_practice_super_admin_cannot_download_other_practice_export()
    {
        $selectedPractice = Practice::factory()->create();
        $otherPractice = Practice::factory()->create();
        $user = User::factory()->create(['practice_id' => null]);

        PracticeContext::setCurrentPracticeId($selectedPractice->id);

        $token = ExportToken::create([
            'practice_id' => $otherPractice->id,
            'format' => 'csv',
            'file_path' => "exports/{$otherPractice->id}/test.zip",
            'status' => 'ready',
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->actingAs($user)->get(route('export.download', $token->id));

        $response->assertStatus(404);
    }

    public function test_expired_trial_can_request_export()
    {
        $practice = Practice::factory()->create([
            'trial_ends_at' => now()->subDay(),
        ]);
        $user = User::factory()->create(['practice_id' => $practice->id]);

        Queue::fake();

        $response = $this->actingAs($user)->post(route('export.request'), [
            'format' => 'csv',
        ]);

        $response->assertRedirect();
        Queue::assertPushed(ExportPracticeDataJob::class);
    }

    public function test_export_past_30_day_grace_period_is_denied()
    {
        $practice = Practice::factory()->create(['trial_ends_at' => now()->subDays(31)]);
        $user = User::factory()->create(['practice_id' => $practice->id]);

        $response = $this->actingAs($user)->post(route('export.request'), [
            'format' => 'csv',
        ]);

        $response->assertStatus(403);
    }

    public function test_file_deleted_after_download()
    {
        $practice = Practice::factory()->create();
        $user = User::factory()->create(['practice_id' => $practice->id]);

        // Create a test file
        $filePath = "exports/{$practice->id}/test.zip";
        Storage::put($filePath, 'test content');

        $token = ExportToken::create([
            'practice_id' => $practice->id,
            'format' => 'csv',
            'file_path' => $filePath,
            'status' => 'ready',
            'expires_at' => now()->addHours(24),
        ]);

        $this->assertTrue(Storage::exists($filePath));

        $response = $this->actingAs($user)->get(route('export.download', $token->id));

        $response->assertStatus(200);
        // File should be deleted after download
        $this->assertFalse(Storage::exists($filePath));
    }

    public function test_export_token_status_updated_to_downloaded_after_download()
    {
        $practice = Practice::factory()->create();
        $user = User::factory()->create(['practice_id' => $practice->id]);

        $filePath = "exports/{$practice->id}/test.zip";
        Storage::put($filePath, 'test content');

        $token = ExportToken::create([
            'practice_id' => $practice->id,
            'format' => 'csv',
            'file_path' => $filePath,
            'status' => 'ready',
            'expires_at' => now()->addHours(24),
        ]);

        $this->actingAs($user)->get(route('export.download', $token->id));

        $token->refresh();
        $this->assertEquals('downloaded', $token->status);
        $this->assertNotNull($token->downloaded_at);
    }

    public function test_export_page_accessible_from_settings()
    {
        $practice = Practice::factory()->create();
        $user = User::factory()->create(['practice_id' => $practice->id]);

        $response = $this->actingAs($user)->get('/admin/export-data');

        $response->assertStatus(200);
        $response->assertSeeText('Export Your Data');
    }

    public function test_export_page_livewire_action_uses_selected_practice_for_super_admin()
    {
        Queue::fake();

        $selectedPractice = Practice::factory()->create(['trial_ends_at' => now()->addDays(30)]);
        $otherPractice = Practice::factory()->create(['trial_ends_at' => now()->addDays(30)]);
        $user = User::factory()->create(['practice_id' => null]);

        $this->actingAs($user);
        PracticeContext::setCurrentPracticeId($selectedPractice->id);

        Livewire::test(\App\Filament\Pages\ExportDataPage::class)
            ->call('requestExport', 'json');

        $token = ExportToken::where('practice_id', $selectedPractice->id)->first();
        $this->assertNotNull($token);
        $this->assertEquals('json', $token->format);
        $this->assertNull(ExportToken::where('practice_id', $otherPractice->id)->first());

        Queue::assertPushed(ExportPracticeDataJob::class, function (ExportPracticeDataJob $job) use ($selectedPractice) {
            return $job->practiceId === $selectedPractice->id;
        });
    }

    public function test_active_subscriber_can_export()
    {
        $practice = Practice::factory()->create(['stripe_id' => 'cus_12345']);
        $user = User::factory()->create(['practice_id' => $practice->id]);

        // Create subscription
        $practice->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_12345',
            'stripe_status' => 'active',
            'stripe_price' => 'price_12345',
            'quantity' => 1,
            'trial_ends_at' => null,
        ]);

        Queue::fake();

        $response = $this->actingAs($user)->post(route('export.request'), [
            'format' => 'json',
        ]);

        $response->assertRedirect();
        Queue::assertPushed(ExportPracticeDataJob::class);
    }
}
