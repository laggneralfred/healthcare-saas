<?php

namespace Database\Factories;

use App\Models\CheckoutPayment;
use App\Models\Practice;
use App\Models\PracticePaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PracticePaymentMethod>
 */
class PracticePaymentMethodFactory extends Factory
{
    protected $model = PracticePaymentMethod::class;

    public function definition(): array
    {
        $methodKey = $this->faker->randomElement(array_keys(CheckoutPayment::METHODS));

        return [
            'practice_id' => Practice::factory(),
            'method_key' => $methodKey,
            'display_name' => CheckoutPayment::METHODS[$methodKey],
            'enabled' => true,
            'sort_order' => PracticePaymentMethod::defaultSortOrder($methodKey),
        ];
    }
}
