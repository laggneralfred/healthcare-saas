<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\CheckoutSession;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\States\CheckoutSession\Draft;
use App\Models\States\CheckoutSession\Paid;
use App\Models\States\CheckoutSession\PaymentDue;
use App\Models\User;
use Tests\TestCase;

class CheckoutSessionTest extends TestCase
{
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
            ->state('open')
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
    }

    public function test_can_mark_payment_due()
    {
        $session = CheckoutSession::factory()
            ->state('open')
            ->create([
                'practice_id'  => $this->practice->id,
                'amount_total' => 10000,
                'amount_paid'  => 5000,
                'tender_type'  => 'card',
            ]);

        $session->markPaymentDue();
        $session->refresh();

        $this->assertInstanceOf(PaymentDue::class, $session->state);
        $this->assertEquals(0, $session->amount_paid);
        $this->assertNull($session->tender_type);
        $this->assertNull($session->paid_on);
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
        CheckoutSession::factory()->state('paid')->count(2)->create(['practice_id' => $this->practice->id]);
        CheckoutSession::factory()->state('open')->count(3)->create(['practice_id' => $this->practice->id]);

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
