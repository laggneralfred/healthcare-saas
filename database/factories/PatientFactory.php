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
            'first_name'                       => \fake()->firstName(),
            'last_name'                        => \fake()->lastName(),
            'middle_name'                      => \fake()->optional(0.4)->firstName(),
            'preferred_name'                   => \fake()->optional(0.2)->firstName(),
            'name'                             => \fake()->name(), // overwritten by Patient::saving() event
            'email'                            => \fake()->unique()->safeEmail(),
            'phone'                            => \fake()->phoneNumber(),
            'dob'                              => \fake()->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d'),
            'gender'                           => \fake()->randomElement(['Male', 'Female', 'Other', 'Prefer not to say']),
            'pronouns'                         => \fake()->optional(0.3)->randomElement(['He/Him', 'She/Her', 'They/Them', 'He/They', 'She/They']),
            'address_line_1'                   => \fake()->streetAddress(),
            'address_line_2'                   => \fake()->optional(0.3)->secondaryAddress(),
            'city'                             => \fake()->city(),
            'state'                            => \fake()->stateAbbr(),
            'postal_code'                      => \fake()->postcode(),
            'country'                          => 'USA',
            'emergency_contact_name'           => \fake()->name(),
            'emergency_contact_phone'          => \fake()->phoneNumber(),
            'emergency_contact_relationship'   => \fake()->randomElement(['Spouse', 'Parent', 'Child', 'Sibling', 'Friend', 'Other']),
            'occupation'                       => \fake()->optional(0.7)->jobTitle(),
            'referred_by'                      => \fake()->optional(0.4)->randomElement(['Internet search', 'Friend referral', 'Yelp', 'Google Maps', 'Social media', 'Other']),
            'notes'                            => \fake()->optional(0.3)->sentence(),
            'is_patient'                       => true,
        ];
    }
}
