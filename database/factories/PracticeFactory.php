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
        $name = fake()->company();
        return [
            'name'      => $name,
            'slug'      => Str::slug($name),
            'timezone'  => fake()->randomElement(['America/New_York', 'America/Chicago', 'America/Los_Angeles', 'America/Denver']),
            'is_active'      => true,
            'trial_ends_at'  => now()->addDays(30),
        ];
    }
}
