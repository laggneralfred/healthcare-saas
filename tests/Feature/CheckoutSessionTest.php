<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\CheckoutLine;
use App\Models\CheckoutPayment;
use App\Models\CheckoutSession;
use App\Models\InventoryProduct;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\PracticePaymentMethod;
use App\Models\Practitioner;
use App\Models\ServiceFee;
use App\Models\States\CheckoutSession\Draft;
use App\Models\States\CheckoutSession\Open;
use App\Models\States\CheckoutSession\Paid;
use App\Models\States\CheckoutSession\PaymentDue;
use App\Models\States\CheckoutSession\Voided;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

class CheckoutSessionTest extends TestCase
{
    use RefreshDatabase;
    protected Practice $practice;
    protected User $user;
    protected Patient $patient;
    protected Practitioner $practitioner;
    protected Appointment $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->practice = Practice::factory()->create();
        $this->user = User::factory()->create(['practice_id' => $this->practice->id]);
        $this->patient = Patient::factory()->create(['practice_id' => $this->practice->id]);
        $this->practitioner = Practitioner::factory()->create(['practice_id' => $this->practice->id]);
        $this->appointment = Appointment::factory()->create([
            'practice_id'     => $this->practice->id,
            'patient_id'      => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'status'          => 'checkout',
        ]);
    }

    public function test_can_create_checkout_session()
    {
        $session = CheckoutSession::create([
            'practice_id'     => $this->practice->id,
            'appointment_id'  => $this->appointment->id,
            'patient_id'      => $this->patient->id,
            'practitioner_id' => $this->practitioner->id,
            'charge_label'    => 'Treatment',
            'amount_total'    => 10000, // $100.00
        ]);

        $this->assertNotNull($session->id);
        $this->assertInstanceOf(Draft::class, $session->state);
        $this->assertNotNull($session->started_on);
    }

    public function test_checkout_lines_support_service_references_line_type_and_nullable_unit_price()
    {
        $this->assertTrue(Schema::hasColumn('checkout_lines', 'service_fee_id'));
        $this->assertTrue(Schema::hasColumn('checkout_lines', 'line_type'));
        $this->assertTrue(Schema::hasColumn('checkout_lines', 'unit_price'));

        $session = CheckoutSession::factory()->create(['practice_id' => $this->practice->id]);

        $line = CheckoutLine::create([
            'checkout_session_id' => $session->id,
            'practice_id' => $this->practice->id,
            'description' => 'Manual item',
            'amount' => 25,
        ]);

        $line->refresh();

        $this->assertSame(CheckoutLine::TYPE_CUSTOM, $line->line_type);
        $this->assertNull($line->service_fee_id);
        $this->assertNull($line->unit_price);
        $this->assertTrue($line->isCustom());
    }

    public function test_service_checkout_line_uses_same_practice_active_service_fee()
    {
        $session = CheckoutSession::factory()->create(['practice_id' => $this->practice->id]);
        $serviceFee = ServiceFee::factory()->create([
            'practice_id' => $this->practice->id,
            'name' => 'Follow-up Treatment',
            'default_price' => 95,
            'is_active' => true,
        ]);

        $line = CheckoutLine::create([
            'checkout_session_id' => $session->id,
            'practice_id' => $this->practice->id,
            'line_type' => CheckoutLine::TYPE_SERVICE,
            'service_fee_id' => $serviceFee->id,
        ]);

        $line->refresh();
        $session->refresh();

        $this->assertTrue($line->isService());
        $this->assertSame($serviceFee->id, $line->service_fee_id);
        $this->assertNull($line->inventory_product_id);
        $this->assertSame('Follow-up Treatment', $line->description);
        $this->assertEquals(95, $line->unit_price);
        $this->assertEquals(95, $line->amount);
        $this->assertEquals(95, $session->amount_total);
    }

    public function test_cross_practice_service_fee_is_rejected_for_checkout_line()
    {
        $otherPractice = Practice::factory()->create();
        $session = CheckoutSession::factory()->create(['practice_id' => $this->practice->id]);
        $serviceFee = ServiceFee::factory()->create([
            'practice_id' => $otherPractice->id,
            'is_active' => true,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Choose an active service fee for the current practice.');

        CheckoutLine::create([
            'checkout_session_id' => $session->id,
            'practice_id' => $this->practice->id,
            'line_type' => CheckoutLine::TYPE_SERVICE,
            'service_fee_id' => $serviceFee->id,
        ]);
    }

    public function test_inactive_service_fee_is_rejected_for_new_checkout_line()
    {
        $session = CheckoutSession::factory()->create(['practice_id' => $this->practice->id]);
        $serviceFee = ServiceFee::factory()->inactive()->create([
            'practice_id' => $this->practice->id,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Choose an active service fee for the current practice.');

        CheckoutLine::create([
            'checkout_session_id' => $session->id,
            'practice_id' => $this->practice->id,
            'line_type' => CheckoutLine::TYPE_SERVICE,
            'service_fee_id' => $serviceFee->id,
        ]);
    }

    public function test_inventory_checkout_line_uses_same_practice_active_product_and_calculates_amount()
    {
        $session = CheckoutSession::factory()->create(['practice_id' => $this->practice->id]);
        $product = InventoryProduct::factory()->create([
            'practice_id' => $this->practice->id,
            'name' => 'Herbal Formula',
            'selling_price' => 30,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $line = CheckoutLine::create([
            'checkout_session_id' => $session->id,
            'practice_id' => $this->practice->id,
            'line_type' => CheckoutLine::TYPE_INVENTORY,
            'inventory_product_id' => $product->id,
            'quantity' => 2,
        ]);

        $line->refresh();

        $this->assertTrue($line->isInventory());
        $this->assertSame($product->id, $line->inventory_product_id);
        $this->assertNull($line->service_fee_id);
        $this->assertSame('Herbal Formula (x2)', $line->description);
        $this->assertEquals(30, $line->unit_price);
        $this->assertEquals(60, $line->amount);
    }

    public function test_cross_practice_inventory_product_is_rejected_for_checkout_line()
    {
        $otherPractice = Practice::factory()->create();
        $session = CheckoutSession::factory()->create(['practice_id' => $this->practice->id]);
        $product = InventoryProduct::factory()->create([
            'practice_id' => $otherPractice->id,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Choose an active inventory product for the current practice.');

        CheckoutLine::create([
            'checkout_session_id' => $session->id,
            'practice_id' => $this->practice->id,
            'line_type' => CheckoutLine::TYPE_INVENTORY,
            'inventory_product_id' => $product->id,
            'quantity' => 1,
        ]);
    }

    public function test_service_inventory_and_custom_lines_sum_into_checkout_total()
    {
        $session = CheckoutSession::factory()->create(['practice_id' => $this->practice->id]);
        $serviceFee = ServiceFee::factory()->create([
            'practice_id' => $this->practice->id,
            'default_price' => 95,
            'is_active' => true,
        ]);
        $product = InventoryProduct::factory()->create([
            'practice_id' => $this->practice->id,
            'selling_price' => 30,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        CheckoutLine::create([
            'checkout_session_id' => $session->id,
            'practice_id' => $this->practice->id,
            'line_type' => CheckoutLine::TYPE_SERVICE,
            'service_fee_id' => $serviceFee->id,
        ]);
        CheckoutLine::create([
            'checkout_session_id' => $session->id,
            'practice_id' => $this->practice->id,
            'line_type' => CheckoutLine::TYPE_INVENTORY,
            'inventory_product_id' => $product->id,
            'quantity' => 2,
        ]);
        CheckoutLine::create([
            'checkout_session_id' => $session->id,
            'practice_id' => $this->practice->id,
            'line_type' => CheckoutLine::TYPE_CUSTOM,
            'description' => 'Manual supply',
            'amount' => 15,
        ]);

        $session->refresh();

        $this->assertEquals(170, $session->amount_total);
    }

    public function test_new_practice_creation_seeds_default_payment_methods()
    {
        $practice = Practice::factory()->create();

        $methods = PracticePaymentMethod::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->orderBy('sort_order')
            ->pluck('display_name', 'method_key')
            ->all();

        $this->assertSame(CheckoutPayment::METHODS, $methods);
    }

    public function test_existing_practice_can_be_repaired_with_default_payment_methods()
    {
        PracticePaymentMethod::withoutPracticeScope()
            ->where('practice_id', $this->practice->id)
            ->delete();

        PracticePaymentMethod::ensureDefaultsForPractice($this->practice);

        $this->assertSame(
            array_keys(CheckoutPayment::METHODS),
            PracticePaymentMethod::withoutPracticeScope()
                ->where('practice_id', $this->practice->id)
                ->orderBy('sort_order')
                ->pluck('method_key')
                ->all(),
        );
    }

    public function test_amount_due_calculated_correctly()
    {
        $session = CheckoutSession::factory()->create([
            'practice_id'  => $this->practice->id,
            'amount_total' => 10000,
            'amount_paid'  => 5000,
        ]);

        $this->assertEquals(5000, $session->amount_due);
    }

    public function test_is_fully_paid_attribute()
    {
        $unpaid = CheckoutSession::factory()->create([
            'amount_total' => 10000,
            'amount_paid'  => 0,
        ]);

        $partial = CheckoutSession::factory()->create([
            'amount_total' => 10000,
            'amount_paid'  => 5000,
        ]);

        $full = CheckoutSession::factory()->create([
            'amount_total' => 10000,
            'amount_paid'  => 10000,
        ]);

        $this->assertFalse($unpaid->is_fully_paid);
        $this->assertFalse($partial->is_fully_paid);
        $this->assertTrue($full->is_fully_paid);
    }

    public function test_is_partially_paid_attribute()
    {
        $unpaid = CheckoutSession::factory()->create([
            'amount_total' => 10000,
            'amount_paid'  => 0,
        ]);

        $partial = CheckoutSession::factory()->create([
            'amount_total' => 10000,
            'amount_paid'  => 5000,
        ]);

        $this->assertFalse($unpaid->is_partially_paid);
        $this->assertTrue($partial->is_partially_paid);
    }

    public function test_amount_paid_cannot_exceed_total()
    {
        $session = CheckoutSession::factory()->create([
            'amount_total' => 10000,
            'amount_paid'  => 15000, // Attempt to overpay
        ]);

        // Should be capped at amount_total
        $this->assertEquals(10000, $session->amount_paid);
    }

    public function test_can_mark_paid()
    {
        $session = CheckoutSession::factory()
            ->open()
            ->create([
                'practice_id'  => $this->practice->id,
                'amount_total' => 10000,
                'amount_paid'  => 0,
            ]);

        $session->markPaid('card');
        $session->refresh();

        $this->assertInstanceOf(Paid::class, $session->state);
        $this->assertEquals(10000, $session->amount_paid);
        $this->assertEquals('card', $session->tender_type);
        $this->assertNotNull($session->paid_on);
        $this->assertDatabaseHas('checkout_payments', [
            'checkout_session_id' => $session->id,
            'practice_id' => $this->practice->id,
            'amount' => 10000,
            'payment_method' => CheckoutPayment::METHOD_CARD_EXTERNAL,
        ]);
    }

    public function test_can_mark_payment_due_without_discarding_recorded_payments()
    {
        $session = CheckoutSession::factory()
            ->open()
            ->create([
                'practice_id'  => $this->practice->id,
                'amount_total' => 10000,
                'amount_paid'  => 0,
                'tender_type'  => 'card',
            ]);
        $session->recordPayment([
            'amount' => 5000,
            'payment_method' => CheckoutPayment::METHOD_CASH,
            'paid_at' => now(),
        ]);

        $session->markPaymentDue();
        $session->refresh();

        $this->assertInstanceOf(PaymentDue::class, $session->state);
        $this->assertEquals(5000, $session->amount_paid);
        $this->assertNull($session->tender_type);
        $this->assertNull($session->paid_on);
    }

    public function test_recording_full_payment_marks_checkout_session_paid()
    {
        $session = CheckoutSession::factory()
            ->open()
            ->create([
                'practice_id' => $this->practice->id,
                'amount_total' => 10000,
                'amount_paid' => 0,
            ]);

        $payment = $session->recordPayment([
            'amount' => 10000,
            'payment_method' => CheckoutPayment::METHOD_CASH,
            'paid_at' => now(),
            'reference' => 'cash drawer',
            'created_by_user_id' => $this->user->id,
        ]);

        $session->refresh();

        $this->assertInstanceOf(Paid::class, $session->state);
        $this->assertEquals(10000, $session->amount_paid);
        $this->assertEquals(0, $session->amount_due);
        $this->assertSame($this->practice->id, $payment->practice_id);
        $this->assertSame($this->user->id, $payment->created_by_user_id);
    }

    public function test_recording_partial_payment_keeps_checkout_open_with_balance_due()
    {
        $session = CheckoutSession::factory()
            ->open()
            ->create([
                'practice_id' => $this->practice->id,
                'amount_total' => 10000,
                'amount_paid' => 0,
            ]);

        $session->recordPayment([
            'amount' => 4000,
            'payment_method' => CheckoutPayment::METHOD_CHECK,
            'paid_at' => now(),
        ]);

        $session->refresh();

        $this->assertInstanceOf(Open::class, $session->state);
        $this->assertEquals(4000, $session->amount_paid);
        $this->assertEquals(6000, $session->amount_due);
    }

    public function test_recording_payment_requires_valid_method_and_positive_amount()
    {
        $session = CheckoutSession::factory()
            ->open()
            ->create([
                'practice_id' => $this->practice->id,
                'amount_total' => 10000,
            ]);

        $this->expectException(InvalidArgumentException::class);
        $session->recordPayment([
            'amount' => 0,
            'payment_method' => CheckoutPayment::METHOD_CASH,
            'paid_at' => now(),
        ]);
    }

    public function test_recording_payment_rejects_invalid_method()
    {
        $session = CheckoutSession::factory()
            ->open()
            ->create([
                'practice_id' => $this->practice->id,
                'amount_total' => 10000,
            ]);

        $this->expectException(InvalidArgumentException::class);
        $session->recordPayment([
            'amount' => 10000,
            'payment_method' => 'stripe',
            'paid_at' => now(),
        ]);
    }

    public function test_recording_payment_rejects_disabled_practice_payment_method()
    {
        PracticePaymentMethod::withoutPracticeScope()
            ->where('practice_id', $this->practice->id)
            ->where('method_key', CheckoutPayment::METHOD_CASH)
            ->update(['enabled' => false]);

        $session = CheckoutSession::factory()
            ->open()
            ->create([
                'practice_id' => $this->practice->id,
                'amount_total' => 10000,
            ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This payment method is not enabled for this practice.');

        try {
            $session->recordPayment([
                'amount' => 10000,
                'payment_method' => CheckoutPayment::METHOD_CASH,
                'paid_at' => now(),
            ]);
        } finally {
            $this->assertSame(0, $session->checkoutPayments()->count());
        }
    }

    public function test_recording_payment_rejects_amount_above_balance_due()
    {
        $session = CheckoutSession::factory()
            ->open()
            ->create([
                'practice_id' => $this->practice->id,
                'amount_total' => 10000,
                'amount_paid' => 0,
            ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment amount cannot exceed the balance due.');

        try {
            $session->recordPayment([
                'amount' => 10001,
                'payment_method' => CheckoutPayment::METHOD_CASH,
                'paid_at' => now(),
            ]);
        } finally {
            $this->assertSame(0, $session->checkoutPayments()->count());
        }
    }

    public function test_mark_paid_after_partial_payment_records_remaining_balance_only()
    {
        $session = CheckoutSession::factory()
            ->open()
            ->create([
                'practice_id' => $this->practice->id,
                'amount_total' => 10000,
                'amount_paid' => 0,
            ]);

        $session->recordPayment([
            'amount' => 4000,
            'payment_method' => CheckoutPayment::METHOD_CASH,
            'paid_at' => now()->subMinute(),
        ]);
        $session->refresh();

        $session->markPaid(CheckoutPayment::METHOD_CHECK);
        $session->refresh();

        $this->assertInstanceOf(Paid::class, $session->state);
        $this->assertEquals(10000, $session->amount_paid);
        $this->assertEquals([4000.0, 6000.0], $session->checkoutPayments()->pluck('amount')->map(fn ($amount) => (float) $amount)->all());
    }

    public function test_zero_balance_open_checkout_cannot_mark_paid_with_disabled_method()
    {
        PracticePaymentMethod::withoutPracticeScope()
            ->where('practice_id', $this->practice->id)
            ->where('method_key', CheckoutPayment::METHOD_CASH)
            ->update(['enabled' => false]);

        $session = CheckoutSession::factory()
            ->open()
            ->create([
                'practice_id' => $this->practice->id,
                'amount_total' => 10000,
                'amount_paid' => 10000,
                'tender_type' => null,
                'paid_on' => null,
            ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This payment method is not enabled for this practice.');

        try {
            $session->markPaid(CheckoutPayment::METHOD_CASH);
        } finally {
            $session->refresh();

            $this->assertInstanceOf(Open::class, $session->state);
            $this->assertNull($session->tender_type);
            $this->assertNull($session->paid_on);
        }
    }

    public function test_zero_comped_payment_is_only_allowed_for_zero_total_checkout()
    {
        $session = CheckoutSession::factory()
            ->open()
            ->create([
                'practice_id' => $this->practice->id,
                'amount_total' => 10000,
            ]);

        $this->expectException(InvalidArgumentException::class);
        $session->recordPayment([
            'amount' => 0,
            'payment_method' => CheckoutPayment::METHOD_COMPED,
            'paid_at' => now(),
        ]);
    }

    public function test_zero_checkout_can_be_marked_paid_as_comped()
    {
        $session = CheckoutSession::factory()
            ->open()
            ->create([
                'practice_id' => $this->practice->id,
                'amount_total' => 0,
                'amount_paid' => 0,
            ]);

        $session->markPaid(CheckoutPayment::METHOD_COMPED);
        $session->refresh();

        $this->assertInstanceOf(Paid::class, $session->state);
        $this->assertEquals(0, $session->amount_paid);
        $this->assertDatabaseHas('checkout_payments', [
            'checkout_session_id' => $session->id,
            'practice_id' => $this->practice->id,
            'amount' => 0,
            'payment_method' => CheckoutPayment::METHOD_COMPED,
        ]);
    }

    public function test_zero_checkout_mark_paid_uses_comped_even_if_another_enabled_method_is_submitted()
    {
        $session = CheckoutSession::factory()
            ->open()
            ->create([
                'practice_id' => $this->practice->id,
                'amount_total' => 0,
                'amount_paid' => 0,
            ]);

        $session->markPaid(CheckoutPayment::METHOD_CASH);
        $session->refresh();

        $this->assertInstanceOf(Paid::class, $session->state);
        $this->assertDatabaseHas('checkout_payments', [
            'checkout_session_id' => $session->id,
            'practice_id' => $this->practice->id,
            'amount' => 0,
            'payment_method' => CheckoutPayment::METHOD_COMPED,
        ]);
    }

    public function test_comped_payment_is_rejected_when_disabled_for_practice()
    {
        PracticePaymentMethod::withoutPracticeScope()
            ->where('practice_id', $this->practice->id)
            ->where('method_key', CheckoutPayment::METHOD_COMPED)
            ->update(['enabled' => false]);

        $session = CheckoutSession::factory()
            ->open()
            ->create([
                'practice_id' => $this->practice->id,
                'amount_total' => 0,
                'amount_paid' => 0,
            ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This payment method is not enabled for this practice.');

        $session->markPaid(CheckoutPayment::METHOD_COMPED);
    }

    public function test_payment_method_configuration_is_practice_scoped()
    {
        $otherPractice = Practice::factory()->create();

        PracticePaymentMethod::withoutPracticeScope()
            ->where('practice_id', $this->practice->id)
            ->where('method_key', CheckoutPayment::METHOD_CHECK)
            ->update(['enabled' => false]);

        $this->assertArrayNotHasKey(
            CheckoutPayment::METHOD_CHECK,
            PracticePaymentMethod::enabledOptionsForPractice($this->practice->id),
        );
        $this->assertArrayHasKey(
            CheckoutPayment::METHOD_CHECK,
            PracticePaymentMethod::enabledOptionsForPractice($otherPractice->id),
        );
    }

    public function test_payment_records_are_practice_scoped()
    {
        $session = CheckoutSession::factory()
            ->open()
            ->create([
                'practice_id' => $this->practice->id,
                'amount_total' => 10000,
            ]);

        $payment = $session->recordPayment([
            'amount' => 2500,
            'payment_method' => CheckoutPayment::METHOD_OTHER,
            'paid_at' => now(),
        ]);

        $this->assertSame($session->practice_id, $payment->practice_id);
    }

    public function test_voided_checkout_cannot_accept_new_payment()
    {
        $session = CheckoutSession::factory()
            ->create([
                'practice_id' => $this->practice->id,
                'state' => Voided::$name,
                'amount_total' => 10000,
            ]);

        $this->expectException(InvalidArgumentException::class);
        $session->recordPayment([
            'amount' => 10000,
            'payment_method' => CheckoutPayment::METHOD_CASH,
            'paid_at' => now(),
        ]);
    }

    public function test_query_scope_by_practice()
    {
        $practice2 = Practice::factory()->create();

        CheckoutSession::factory()->count(3)->create(['practice_id' => $this->practice->id]);
        CheckoutSession::factory()->count(2)->create(['practice_id' => $practice2->id]);

        $results = CheckoutSession::byPractice($this->practice->id)->get();

        $this->assertCount(3, $results);
        $this->assertTrue($results->every(fn ($s) => $s->practice_id === $this->practice->id));
    }

    public function test_query_scope_paid()
    {
        CheckoutSession::factory()->paid()->count(2)->create(['practice_id' => $this->practice->id]);
        CheckoutSession::factory()->open()->count(3)->create(['practice_id' => $this->practice->id]);

        $results = CheckoutSession::byPractice($this->practice->id)->paid()->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($s) => $s->state instanceof Paid));
    }

    public function test_sync_total_from_lines()
    {
        $session = CheckoutSession::factory()->create(['practice_id' => $this->practice->id]);

        // Clear any existing lines
        $session->checkoutLines()->delete();

        // Create line items
        $session->checkoutLines()->createMany([
            ['practice_id' => $this->practice->id, 'sequence' => 0, 'description' => 'Service', 'amount' => 5000],
            ['practice_id' => $this->practice->id, 'sequence' => 1, 'description' => 'Supply', 'amount' => 1500],
        ]);

        $session->syncTotalFromLines();
        $session->refresh();

        $this->assertEquals(6500, $session->amount_total);
    }
}
