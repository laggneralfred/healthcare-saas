<?php

namespace Database\Factories;

use App\Models\Practice;
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
        $name = $this->faker->company();
        return [
            'name'           => $name,
            'slug'           => Str::slug($name),
            'timezone'       => $this->faker->randomElement(['America/New_York', 'America/Chicago', 'America/Los_Angeles', 'America/Denver']),
            'is_active'      => true,
            'is_demo'        => false,
            'discipline'     => $this->faker->randomElement(['acupuncture', 'massage', 'chiropractic', 'physical_therapy', 'other']),
            'referral_source' => $this->faker->optional(0.5)->randomElement(['Google', 'Word of Mouth', 'Facebook', 'Yelp', 'Direct']),
            'trial_ends_at'  => now()->addDays(30),
        ];
    }
}
