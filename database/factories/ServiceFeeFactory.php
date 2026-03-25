<?php

namespace Database\Factories;

use App\Models\Practice;
use App\Models\ServiceFee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceFee>
 */
class ServiceFeeFactory extends Factory
{
    protected $model = ServiceFee::class;

    private static array $fees = [
        ['name' => 'Initial Consultation',     'price' => 150.00, 'desc' => 'First visit — comprehensive assessment'],
        ['name' => 'Follow-up Treatment',       'price' => 95.00,  'desc' => 'Standard follow-up session (60 min)'],
        ['name' => 'Extended Session',          'price' => 130.00, 'desc' => 'Extended treatment session (90 min)'],
        ['name' => 'Cupping Therapy',           'price' => 80.00,  'desc' => 'Cupping therapy add-on or standalone'],
        ['name' => 'Herbal Consultation',       'price' => 75.00,  'desc' => 'TCM herbal formula consultation'],
        ['name' => 'Swedish Massage (60 min)',  'price' => 90.00,  'desc' => 'Relaxation massage — 60 minutes'],
        ['name' => 'Deep Tissue Massage',       'price' => 110.00, 'desc' => 'Deep tissue therapeutic massage'],
        ['name' => 'Sports Recovery Massage',   'price' => 100.00, 'desc' => 'Sports recovery and injury prevention'],
        ['name' => 'Hot Stone Therapy',         'price' => 120.00, 'desc' => 'Hot stone massage therapy'],
        ['name' => 'Late Cancellation Fee',     'price' => 50.00,  'desc' => 'Fee for late cancellation (< 24 hrs)'],
    ];

    public function definition(): array
    {
        $fee = fake()->randomElement(self::$fees);

        return [
            'practice_id'       => Practice::factory(),
            'name'              => $fee['name'],
            'short_description' => $fee['desc'],
            'default_price'     => $fee['price'],
            'is_active'         => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
