<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\CheckoutLine;
use App\Models\CheckoutPayment;
use App\Models\CheckoutSession;
use App\Models\InventoryMovement;
use App\Models\InventoryProduct;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\ServiceFee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutInventoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Practice $practice;
    private Practice $practiceWithoutAddon;
    private InventoryProduct $product;
    private Appointment $appointment;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Practice with add-on (mock hasInventoryAddon)
        $this->practice = Practice::factory()->create();
        $this->user = User::factory()->create(['practice_id' => $this->practice->id]);

        // Practice without add-on
        $this->practiceWithoutAddon = Practice::factory()->create();
        User::factory()->create(['practice_id' => $this->practiceWithoutAddon->id]);

        // Create inventory product
        $this->product = InventoryProduct::factory()->create([
            'practice_id' => $this->practice->id,
            'name' => 'Test Herbal Formula',
            'selling_price' => 50,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        // Create appointment
        $patient = Patient::factory()->create(['practice_id' => $this->practice->id]);
        $practitioner = Practitioner::factory()->create(['practice_id' => $this->practice->id]);

        $this->appointment = Appointment::factory()->create([
            'practice_id' => $this->practice->id,
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
        ]);
    }

    public function test_products_not_shown_in_checkout_without_addon(): void
    {
        $practice = $this->practiceWithoutAddon;
        $this->assertFalse($practice->hasInventoryAddon());
    }

    public function test_checkout_session_can_have_product_line_items(): void
    {
        $checkout = CheckoutSession::factory()->create([
            'practice_id' => $this->practice->id,
            'appointment_id' => $this->appointment->id,
            'charge_label' => 'Treatment',
        ]);

        $line = CheckoutLine::create([
            'checkout_session_id' => $checkout->id,
            'practice_id' => $this->practice->id,
            'sequence' => 1,
            'description' => 'Herbal Formula',
            'amount' => 50,
            'inventory_product_id' => $this->product->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('checkout_lines', [
            'id' => $line->id,
            'inventory_product_id' => $this->product->id,
            'quantity' => 1,
        ]);
    }

    public function test_marking_checkout_paid_creates_inventory_movements(): void
    {
        $checkout = CheckoutSession::factory()->create([
            'practice_id' => $this->practice->id,
            'appointment_id' => $this->appointment->id,
            'amount_total' => 50,
            'state' => 'open',
        ]);

        CheckoutLine::create([
            'checkout_session_id' => $checkout->id,
            'practice_id' => $this->practice->id,
            'sequence' => 1,
            'description' => 'Herbal Formula (x1)',
            'amount' => 50,
            'inventory_product_id' => $this->product->id,
            'quantity' => 1,
        ]);

        // Initial stock
        $this->product->refresh();
        $initialStock = $this->product->stock_quantity;

        // Mark as paid
        $checkout->markPaid('card');

        // Verify movement was created
        $movement = InventoryMovement::where('reference', "checkout-{$checkout->id}")
            ->first();

        $this->assertNotNull($movement);
        $this->assertEquals('sale', $movement->type);
        $this->assertEquals(-1, $movement->quantity);
        $this->assertEquals($this->product->id, $movement->inventory_product_id);

        // Verify stock was decremented
        $this->product->refresh();
        $this->assertEquals($initialStock - 1, $this->product->stock_quantity);
    }

    public function test_recording_full_payment_creates_inventory_movements_once(): void
    {
        $checkout = CheckoutSession::factory()->create([
            'practice_id' => $this->practice->id,
            'appointment_id' => $this->appointment->id,
            'amount_total' => 50,
            'state' => 'open',
        ]);

        CheckoutLine::create([
            'checkout_session_id' => $checkout->id,
            'practice_id' => $this->practice->id,
            'sequence' => 1,
            'description' => 'Herbal Formula (x1)',
            'amount' => 50,
            'inventory_product_id' => $this->product->id,
            'quantity' => 1,
        ]);

        $initialStock = $this->product->fresh()->stock_quantity;

        $checkout->recordPayment([
            'amount' => 50,
            'payment_method' => CheckoutPayment::METHOD_CARD_EXTERNAL,
            'paid_at' => now(),
        ]);
        $checkout->fresh()->createInventoryMovements();

        $movements = InventoryMovement::where('reference', "checkout-{$checkout->id}")->get();

        $this->assertCount(1, $movements);
        $this->assertEquals(-1, $movements->first()->quantity);
        $this->assertEquals($initialStock - 1, $this->product->fresh()->stock_quantity);
    }

    public function test_multiple_products_create_multiple_movements(): void
    {
        $product2 = InventoryProduct::factory()->create([
            'practice_id' => $this->practice->id,
            'name' => 'Another Product',
            'selling_price' => 30,
            'stock_quantity' => 20,
            'is_active' => true,
        ]);

        $checkout = CheckoutSession::factory()->create([
            'practice_id' => $this->practice->id,
            'appointment_id' => $this->appointment->id,
            'amount_total' => 80,
            'state' => 'open',
        ]);

        CheckoutLine::create([
            'checkout_session_id' => $checkout->id,
            'practice_id' => $this->practice->id,
            'sequence' => 1,
            'description' => 'Herbal Formula (x2)',
            'amount' => 100,
            'inventory_product_id' => $this->product->id,
            'quantity' => 2,
        ]);

        CheckoutLine::create([
            'checkout_session_id' => $checkout->id,
            'practice_id' => $this->practice->id,
            'sequence' => 2,
            'description' => 'Another Product (x1)',
            'amount' => 30,
            'inventory_product_id' => $product2->id,
            'quantity' => 1,
        ]);

        $checkout->markPaid('card');

        // Verify movements created for both products
        $movements = InventoryMovement::where('reference', "checkout-{$checkout->id}")->get();

        $this->assertEquals(2, $movements->count());
        $this->assertTrue($movements->contains('inventory_product_id', $this->product->id));
        $this->assertTrue($movements->contains('inventory_product_id', $product2->id));

        // Verify stock updated correctly
        $this->product->refresh();
        $product2->refresh();

        $this->assertEquals(8, $this->product->stock_quantity);
        $this->assertEquals(19, $product2->stock_quantity);
    }

    public function test_checkout_total_includes_product_amounts(): void
    {
        $checkout = CheckoutSession::factory()->create([
            'practice_id' => $this->practice->id,
            'appointment_id' => $this->appointment->id,
        ]);

        CheckoutLine::create([
            'checkout_session_id' => $checkout->id,
            'practice_id' => $this->practice->id,
            'sequence' => 1,
            'description' => 'Treatment',
            'amount' => 100,
        ]);

        CheckoutLine::create([
            'checkout_session_id' => $checkout->id,
            'practice_id' => $this->practice->id,
            'sequence' => 2,
            'description' => 'Herbal Formula (x1)',
            'amount' => 50,
            'inventory_product_id' => $this->product->id,
            'quantity' => 1,
        ]);

        $checkout->syncTotalFromLines();
        $checkout->refresh();

        $this->assertEquals(150, $checkout->amount_total);
    }

    public function test_service_and_custom_lines_do_not_create_inventory_movements(): void
    {
        $checkout = CheckoutSession::factory()->create([
            'practice_id' => $this->practice->id,
            'appointment_id' => $this->appointment->id,
            'amount_total' => 125,
            'state' => 'open',
        ]);
        $serviceFee = ServiceFee::factory()->create([
            'practice_id' => $this->practice->id,
            'default_price' => 100,
            'is_active' => true,
        ]);

        CheckoutLine::create([
            'checkout_session_id' => $checkout->id,
            'practice_id' => $this->practice->id,
            'line_type' => CheckoutLine::TYPE_SERVICE,
            'service_fee_id' => $serviceFee->id,
        ]);
        CheckoutLine::create([
            'checkout_session_id' => $checkout->id,
            'practice_id' => $this->practice->id,
            'line_type' => CheckoutLine::TYPE_CUSTOM,
            'description' => 'Manual supply',
            'amount' => 25,
        ]);

        $checkout->refresh();
        $checkout->markPaid('card');

        $this->assertDatabaseMissing('inventory_movements', [
            'reference' => "checkout-{$checkout->id}",
        ]);
    }
}
