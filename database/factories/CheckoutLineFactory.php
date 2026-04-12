<?php

namespace Database\Factories;

use App\Models\CheckoutLine;
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
        return [
            'checkout_session_id'  => CheckoutSession::factory(),
            'practice_id'          => Practice::factory(),
            'sequence'             => 0,
            'description'          => \fake()->randomElement(self::$descriptions),
            'amount'               => \fake()->randomFloat(2, 50, 175),
            'inventory_product_id' => null,
            'quantity'             => null,
        ];
    }
}
