<?php

namespace Database\Factories;

use App\Models\CheckoutPayment;
use App\Models\CheckoutSession;
use App\Models\Practice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CheckoutPayment>
 */
class CheckoutPaymentFactory extends Factory
{
    protected $model = CheckoutPayment::class;

    public function definition(): array
    {
        return [
            'practice_id' => Practice::factory(),
            'checkout_session_id' => CheckoutSession::factory(),
            'amount' => $this->faker->randomFloat(2, 25, 200),
            'payment_method' => CheckoutPayment::METHOD_CARD_EXTERNAL,
            'paid_at' => now(),
            'reference' => null,
            'notes' => null,
            'created_by_user_id' => User::factory(),
        ];
    }
}
