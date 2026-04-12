<?php

namespace Database\Factories;

use App\Models\Patient;
use Faker\Factory as FakerFactory;
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
        $faker = FakerFactory::create();
        return [
            'practice_id'                      => Practice::factory(),
            'first_name'                       => $faker->firstName(),
            'last_name'                        => $faker->lastName(),
            'middle_name'                      => $faker->optional(0.4)->firstName(),
            'preferred_name'                   => $faker->optional(0.2)->firstName(),
            'name'                             => $faker->name(), // overwritten by Patient::saving() event
            'email'                            => $faker->unique()->safeEmail(),
            'phone'                            => $faker->phoneNumber(),
            'dob'                              => $faker->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d'),
            'gender'                           => $faker->randomElement(['Male', 'Female', 'Other', 'Prefer not to say']),
            'pronouns'                         => $faker->optional(0.3)->randomElement(['He/Him', 'She/Her', 'They/Them', 'He/They', 'She/They']),
            'address_line_1'                   => $faker->streetAddress(),
            'address_line_2'                   => $faker->optional(0.3)->secondaryAddress(),
            'city'                             => $faker->city(),
            'state'                            => $faker->stateAbbr(),
            'postal_code'                      => $faker->postcode(),
            'country'                          => 'USA',
            'emergency_contact_name'           => $faker->name(),
            'emergency_contact_phone'          => $faker->phoneNumber(),
            'emergency_contact_relationship'   => $faker->randomElement(['Spouse', 'Parent', 'Child', 'Sibling', 'Friend', 'Other']),
            'occupation'                       => $faker->optional(0.7)->jobTitle(),
            'referred_by'                      => $faker->optional(0.4)->randomElement(['Internet search', 'Friend referral', 'Yelp', 'Google Maps', 'Social media', 'Other']),
            'notes'                            => $faker->optional(0.3)->sentence(),
            'is_patient'                       => true,
        ];
    }
}
