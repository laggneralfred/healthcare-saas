<?php

namespace Database\Factories;

use App\Models\CheckoutLine;
use Faker\Factory as FakerFactory;
use App\Models\CheckoutSession;
use App\Models\Practice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CheckoutLine>
 */
class CheckoutLineFactory extends Factory
{
    protected $model = CheckoutLine::class;

    private static array $descriptions = [
        'Initial Consultation',
        'Follow-up Treatment',
        'Acupuncture Session (60 min)',
        'Cupping Therapy',
        'Herbal Consultation',
        'Swedish Massage (60 min)',
        'Deep Tissue Massage',
        'Sports Recovery Massage',
        'Hot Stone Therapy',
        'Extended Session (90 min)',
        'Late Cancellation Fee',
        'Supplies & Materials',
        'Intake Assessment',
    ];

    public function definition(): array
    {
        $faker = FakerFactory::create();
        return [
            'checkout_session_id'  => CheckoutSession::factory(),
            'practice_id'          => Practice::factory(),
            'sequence'             => 0,
            'line_type'            => CheckoutLine::TYPE_CUSTOM,
            'service_fee_id'       => null,
            'description'          => $faker->randomElement(self::$descriptions),
            'amount'               => $faker->randomFloat(2, 50, 175),
            'unit_price'           => null,
            'inventory_product_id' => null,
            'quantity'             => null,
        ];
    }
}
