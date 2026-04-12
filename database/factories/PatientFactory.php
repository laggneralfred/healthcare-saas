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
            'practice_id'                      => Practice::factory(),
            'first_name'                       => $this->faker->firstName(),
            'last_name'                        => $this->faker->lastName(),
            'middle_name'                      => $this->faker->optional(0.4)->firstName(),
            'preferred_name'                   => $this->faker->optional(0.2)->firstName(),
            'name'                             => $this->faker->name(), // overwritten by Patient::saving() event
            'email'                            => $this->faker->unique()->safeEmail(),
            'phone'                            => $this->faker->phoneNumber(),
            'dob'                              => $this->faker->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d'),
            'gender'                           => $this->faker->randomElement(['Male', 'Female', 'Other', 'Prefer not to say']),
            'pronouns'                         => $this->faker->optional(0.3)->randomElement(['He/Him', 'She/Her', 'They/Them', 'He/They', 'She/They']),
            'address_line_1'                   => $this->faker->streetAddress(),
            'address_line_2'                   => $this->faker->optional(0.3)->secondaryAddress(),
            'city'                             => $this->faker->city(),
            'state'                            => $this->faker->stateAbbr(),
            'postal_code'                      => $this->faker->postcode(),
            'country'                          => 'USA',
            'emergency_contact_name'           => $this->faker->name(),
            'emergency_contact_phone'          => $this->faker->phoneNumber(),
            'emergency_contact_relationship'   => $this->faker->randomElement(['Spouse', 'Parent', 'Child', 'Sibling', 'Friend', 'Other']),
            'occupation'                       => $this->faker->optional(0.7)->jobTitle(),
            'referred_by'                      => $this->faker->optional(0.4)->randomElement(['Internet search', 'Friend referral', 'Yelp', 'Google Maps', 'Social media', 'Other']),
            'notes'                            => $this->faker->optional(0.3)->sentence(),
            'is_patient'                       => true,
        ];
    }
}
