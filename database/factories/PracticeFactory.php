<?php

namespace Database\Factories;

use App\Models\Practice;
use App\Support\PracticeType;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Practice>
 */
class PracticeFactory extends Factory
{
    protected $model = Practice::class;

    public function definition(): array
    {
        $faker = FakerFactory::create();
        $name = $faker->company();
        return [
            'name'           => $name,
            'slug'           => Str::slug($name) . '-' . Str::lower(Str::random(6)),
            'timezone'       => $faker->randomElement(['America/New_York', 'America/Chicago', 'America/Los_Angeles', 'America/Denver']),
            'is_active'      => true,
            'is_demo'        => false,
            'insurance_billing_enabled' => false,
            'practice_type' => PracticeType::GENERAL_WELLNESS,
            'discipline'     => $faker->randomElement(['acupuncture', 'massage', 'chiropractic', 'physical_therapy', 'other']),
            'referral_source' => $faker->optional(0.5)->randomElement(['Google', 'Word of Mouth', 'Facebook', 'Yelp', 'Direct']),
            'trial_ends_at'  => now()->addDays(30),
        ];
    }
}
