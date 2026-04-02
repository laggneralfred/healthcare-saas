<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\Practice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition(): array
    {
        return [
            'practice_id' => Practice::factory(),
            'first_name'  => fake()->firstName(),
            'last_name'   => fake()->lastName(),
            'name'        => fake()->name(), // overwritten by Patient::saving() event
            'email'       => fake()->unique()->safeEmail(),
            'phone'       => fake()->phoneNumber(),
            'notes'       => fake()->optional(0.3)->sentence(),
            'is_patient'  => true,
        ];
    }
}
