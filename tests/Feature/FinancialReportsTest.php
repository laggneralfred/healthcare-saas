<?php

namespace Tests\Feature;

use App\Jobs\ExportPracticeDataJob;
use App\Models\CheckoutLine;
use App\Models\CheckoutPayment;
use App\Models\CheckoutSession;
use App\Models\Encounter;
use App\Models\ExportToken;
use App\Models\InventoryProduct;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\ServiceFee;
use App\Models\States\CheckoutSession\Open;
use App\Models\User;
use App\Services\Reports\PracticeFinancialSummaryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class FinancialReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_financial_summary_uses_checkout_payment_paid_at_date_basis(): void
    {
        $practice = Practice::factory()->create(['timezone' => 'UTC']);
        $practitioner = $this->practitioner($practice, 'Dr Date Basis');
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);

        $inside = $this->checkout($practice, $patient, $practitioner, 100);
        $this->payment($practice, $inside, 100, '2026-05-03 10:00:00');

        $outside = $this->checkout($practice, $patient, $practitioner, 75);
        $outside->update(['created_at' => '2026-05-03 10:00:00']);
        $this->payment($practice, $outside, 75, '2026-04-30 10:00:00');

        $summary = app(PracticeFinancialSummaryService::class)->summarize(
            $practice,
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-05-31'),
            'UTC',
        );

        $this->assertSame(100.0, $summary['total_collected']);
        $this->assertSame(1, $summary['collected_sessions_count']);
    }

    public function test_financial_summary_is_practice_scoped(): void
    {
        $practice = Practice::factory()->create(['timezone' => 'UTC']);
        $otherPractice = Practice::factory()->create(['timezone' => 'UTC']);

        $this->checkoutWithPayment($practice, 100, 'cash', '2026-05-03 10:00:00');
        $this->checkoutWithPayment($otherPractice, 900, 'cash', '2026-05-03 10:00:00');

        $summary = app(PracticeFinancialSummaryService::class)->summarize(
            $practice,
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-05-31'),
            'UTC',
        );

        $this->assertSame(100.0, $summary['total_collected']);
    }

    public function test_financial_summary_calculates_payment_method_practitioner_and_line_type_totals(): void
    {
        $practice = Practice::factory()->create(['timezone' => 'UTC']);
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);
        $practitionerA = $this->practitioner($practice, 'Alex Practitioner');
        $practitionerB = $this->practitioner($practice, 'Blair Practitioner');
        $serviceFee = ServiceFee::factory()->create(['practice_id' => $practice->id, 'default_price' => 100]);
        $product = InventoryProduct::factory()->create(['practice_id' => $practice->id, 'selling_price' => 30]);

        $sessionA = $this->checkout($practice, $patient, $practitionerA, 150);
        CheckoutLine::create([
            'practice_id' => $practice->id,
            'checkout_session_id' => $sessionA->id,
            'line_type' => CheckoutLine::TYPE_SERVICE,
            'service_fee_id' => $serviceFee->id,
            'description' => 'Treatment',
            'quantity' => 1,
            'unit_price' => 100,
            'amount' => 100,
        ]);
        CheckoutLine::create([
            'practice_id' => $practice->id,
            'checkout_session_id' => $sessionA->id,
            'line_type' => CheckoutLine::TYPE_INVENTORY,
            'inventory_product_id' => $product->id,
            'description' => 'Herbs',
            'quantity' => 1,
            'unit_price' => 50,
            'amount' => 50,
        ]);
        $this->payment($practice, $sessionA->refresh(), 150, '2026-05-03 10:00:00', CheckoutPayment::METHOD_CASH);

        $sessionB = $this->checkout($practice, $patient, $practitionerB, 25);
        CheckoutLine::create([
            'practice_id' => $practice->id,
            'checkout_session_id' => $sessionB->id,
            'line_type' => CheckoutLine::TYPE_CUSTOM,
            'description' => 'Custom item',
            'quantity' => 1,
            'unit_price' => 25,
            'amount' => 25,
        ]);
        $this->payment($practice, $sessionB->refresh(), 25, '2026-05-04 10:00:00', CheckoutPayment::METHOD_CARD_EXTERNAL);

        $summary = app(PracticeFinancialSummaryService::class)->summarize(
            $practice,
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-05-31'),
            'UTC',
        );

        $this->assertSame(175.0, $summary['total_collected']);
        $this->assertSame(150.0, $summary['payment_method_totals']->firstWhere('payment_method', CheckoutPayment::METHOD_CASH)['total']);
        $this->assertSame(25.0, $summary['payment_method_totals']->firstWhere('payment_method', CheckoutPayment::METHOD_CARD_EXTERNAL)['total']);
        $this->assertSame(150.0, $summary['practitioner_totals']->firstWhere('practitioner_name', 'Alex Practitioner')['total']);
        $this->assertSame(25.0, $summary['practitioner_totals']->firstWhere('practitioner_name', 'Blair Practitioner')['total']);
        $this->assertSame(100.0, $summary['line_type_totals']->firstWhere('line_type', CheckoutLine::TYPE_SERVICE)['total']);
        $this->assertSame(50.0, $summary['line_type_totals']->firstWhere('line_type', CheckoutLine::TYPE_INVENTORY)['total']);
        $this->assertSame(25.0, $summary['line_type_totals']->firstWhere('line_type', CheckoutLine::TYPE_CUSTOM)['total']);
    }

    public function test_financial_summary_counts_open_checkout_sessions(): void
    {
        $practice = Practice::factory()->create(['timezone' => 'UTC']);
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);
        $practitioner = $this->practitioner($practice, 'Open Session Practitioner');

        CheckoutSession::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'state' => Open::$name,
            'amount_total' => 120,
            'amount_paid' => 20,
            'created_at' => '2026-05-04 10:00:00',
        ]);

        $summary = app(PracticeFinancialSummaryService::class)->summarize(
            $practice,
            Carbon::parse('2026-05-01'),
            Carbon::parse('2026-05-31'),
            'UTC',
        );

        $this->assertSame(1, $summary['unpaid_open_sessions_count']);
        $this->assertSame(100.0, $summary['unpaid_open_sessions_total']);
    }

    public function test_dedicated_financial_export_includes_safe_headers_and_excludes_clinical_notes(): void
    {
        $practice = Practice::factory()->create(['timezone' => 'UTC']);
        $patient = Patient::factory()->create(['practice_id' => $practice->id, 'first_name' => 'Jamie', 'last_name' => 'Patient']);
        $practitioner = $this->practitioner($practice, 'Export Practitioner');

        $encounter = Encounter::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'appointment_id' => null,
            'practitioner_id' => $practitioner->id,
            'subjective' => 'CONFIDENTIAL CLINICAL NOTE TEXT',
        ]);

        $session = $this->checkout($practice, $patient, $practitioner, 100, $encounter->id);
        CheckoutLine::create([
            'practice_id' => $practice->id,
            'checkout_session_id' => $session->id,
            'line_type' => CheckoutLine::TYPE_CUSTOM,
            'description' => 'Office visit',
            'quantity' => 1,
            'unit_price' => 100,
            'amount' => 100,
        ]);
        $this->payment($practice, $session->refresh(), 100, '2026-05-04 10:00:00', CheckoutPayment::METHOD_CASH, 'CONFIDENTIAL PAYMENT NOTE');

        $token = ExportToken::create([
            'practice_id' => $practice->id,
            'format' => 'financial_csv',
            'status' => 'processing',
            'expires_at' => now()->addHours(24),
        ]);

        (new ExportPracticeDataJob($practice->id, $token->id, 'financial_csv', '2026-05-01', '2026-05-31'))->handle();

        $files = $this->zipFiles($token->refresh()->file_path);

        $this->assertArrayHasKey('financial_summary.csv', $files);
        $this->assertArrayHasKey('checkout_payments.csv', $files);
        $this->assertArrayHasKey('checkout_line_items.csv', $files);
        $this->assertStringContainsString('section,label,count,amount,start_date,end_date', $files['financial_summary.csv']);
        $this->assertStringContainsString('paid_at,amount,payment_method,reference,checkout_session_id,patient_id,patient_name,practitioner_name,created_by', $files['checkout_payments.csv']);
        $this->assertStringContainsString('checkout_session_id,date_basis,line_type,description,quantity,unit_price,amount,practitioner,appointment_type,service_fee,product', $files['checkout_line_items.csv']);

        $combined = implode("\n", $files);
        $this->assertStringNotContainsString('CONFIDENTIAL CLINICAL NOTE TEXT', $combined);
        $this->assertStringNotContainsString('CONFIDENTIAL PAYMENT NOTE', $combined);
    }

    public function test_financial_export_respects_date_range_filtering(): void
    {
        $practice = Practice::factory()->create(['timezone' => 'UTC']);

        $this->checkoutWithPayment($practice, 100, 'cash', '2026-05-04 10:00:00');
        $this->checkoutWithPayment($practice, 300, 'cash', '2026-06-04 10:00:00');

        $token = ExportToken::create([
            'practice_id' => $practice->id,
            'format' => 'financial_csv',
            'status' => 'processing',
            'expires_at' => now()->addHours(24),
        ]);

        (new ExportPracticeDataJob($practice->id, $token->id, 'financial_csv', '2026-05-01', '2026-05-31'))->handle();

        $files = $this->zipFiles($token->refresh()->file_path);

        $this->assertStringContainsString('total_collected,"Total collected",,100', str_replace("\r", '', $files['financial_summary.csv']));
        $this->assertStringNotContainsString(',300,', $files['checkout_payments.csv']);
    }

    private function checkoutWithPayment(Practice $practice, float $amount, string $method, string $paidAt): CheckoutSession
    {
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);
        $practitioner = $this->practitioner($practice, 'Scoped Practitioner');
        $session = $this->checkout($practice, $patient, $practitioner, $amount);
        $this->payment($practice, $session, $amount, $paidAt, $method);

        return $session->refresh();
    }

    private function checkout(Practice $practice, Patient $patient, Practitioner $practitioner, float $amount, ?int $encounterId = null): CheckoutSession
    {
        return CheckoutSession::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'encounter_id' => $encounterId,
            'appointment_id' => null,
            'amount_total' => $amount,
            'amount_paid' => 0,
            'state' => Open::$name,
            'created_at' => '2026-04-01 10:00:00',
        ]);
    }

    private function payment(
        Practice $practice,
        CheckoutSession $session,
        float $amount,
        string $paidAt,
        string $method = CheckoutPayment::METHOD_CASH,
        ?string $notes = null,
    ): CheckoutPayment {
        return CheckoutPayment::create([
            'practice_id' => $practice->id,
            'checkout_session_id' => $session->id,
            'amount' => $amount,
            'payment_method' => $method,
            'paid_at' => $paidAt,
            'reference' => 'ref-' . $session->id,
            'notes' => $notes,
        ]);
    }

    private function practitioner(Practice $practice, string $name): Practitioner
    {
        $user = User::factory()->create([
            'practice_id' => $practice->id,
            'name' => $name,
        ]);

        return Practitioner::factory()->create([
            'practice_id' => $practice->id,
            'user_id' => $user->id,
        ]);
    }

    private function zipFiles(string $path): array
    {
        $tempZipPath = tempnam(sys_get_temp_dir(), 'financial_zip_') . '.zip';
        file_put_contents($tempZipPath, Storage::get($path));

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($tempZipPath) === true);

        $files = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $files[$name] = $zip->getFromName($name);
        }

        $zip->close();
        unlink($tempZipPath);

        return $files;
    }
}
