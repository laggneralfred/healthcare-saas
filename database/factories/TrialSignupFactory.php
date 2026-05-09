<?php

namespace Database\Factories;

use App\Models\Practice;
use App\Models\TrialSignup;
use App\Models\User;
use App\Support\PracticeType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrialSignup>
 */
class TrialSignupFactory extends Factory
{
    protected $model = TrialSignup::class;

    public function definition(): array
    {
        return [
            'practice_id' => Practice::factory(),
            'user_id' => User::factory(),
            'name' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'phone' => $this->faker->optional()->phoneNumber(),
            'practice_name' => $this->faker->company(),
            'profession' => PracticeType::label(PracticeType::GENERAL_WELLNESS),
            'practice_type' => PracticeType::GENERAL_WELLNESS,
            'heard_about_us' => $this->faker->optional()->randomElement(['Google', 'Facebook', 'Colleague', 'Conference', 'Other']),
            'source' => 'register',
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'signed_up_at' => now(),
        ];
    }
}
