<?php

namespace Database\Factories;

use App\Models\Appointment;
use Faker\Factory as FakerFactory;
use App\Models\CheckoutSession;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\States\CheckoutSession\Draft;
use App\Models\States\CheckoutSession\Open;
use App\Models\States\CheckoutSession\Paid;
use App\Models\States\CheckoutSession\PaymentDue;
use App\Models\States\CheckoutSession\Voided;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CheckoutSession>
 */
class CheckoutSessionFactory extends Factory
{
    protected $model = CheckoutSession::class;

    public function definition(): array
    {
        $faker = FakerFactory::create();
        return [
            'practice_id'    => Practice::factory(),
            'appointment_id' => Appointment::factory(),
            'patient_id'     => Patient::factory(),
            'practitioner_id' => Practitioner::factory(),
            'state'          => Draft::$name,
            'charge_label'   => 'Visit Charges',
            'amount_total'   => 0,
            'amount_paid'    => 0,
            'tender_type'    => null,
            'started_on'     => now(),
            'paid_on'        => null,
            'payment_note'   => null,
            'notes'          => null,
        ];
    }

    public function open(): static
    {
        $faker = FakerFactory::create();
        return $this->state([
            'state'        => Open::$name,
            'amount_total' => $faker->randomFloat(2, 75, 200),
        ]);
    }

    public function paid(string $tenderType = 'card'): static
    {
        $faker = FakerFactory::create();
        $total = $faker->randomFloat(2, 75, 200);

        return $this->state([
            'state'        => Paid::$name,
            'amount_total' => $total,
            'amount_paid'  => $total,
            'tender_type'  => $tenderType,
            'paid_on'      => $faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    public function paidCash(): static
    {
        return $this->paid('cash');
    }

    public function paidCard(): static
    {
        return $this->paid('card');
    }

    public function paymentDue(): static
    {
        return $this->state([
            'state'        => PaymentDue::$name,
            'amount_total' => $faker->randomFloat(2, 75, 200),
            'amount_paid'  => 0,
            'tender_type'  => null,
            'paid_on'      => null,
        ]);
    }

    public function voided(): static
    {
        return $this->state(['state' => Voided::$name]);
    }
}
