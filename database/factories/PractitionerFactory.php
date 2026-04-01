<?php

namespace Database\Factories;

use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Practitioner>
 */
class PractitionerFactory extends Factory
{
    protected $model = Practitioner::class;

    public function definition(): array
    {
        return [
            'practice_id'    => Practice::factory(),
            'user_id'        => User::factory(),
            'license_number' => strtoupper(fake()->bothify('??-#####')),
            'specialty'      => fake()->randomElement([
                'Acupuncture', 'TCM', 'Cupping Therapy', 'Herbal Medicine',
                'Swedish Massage', 'Deep Tissue', 'Sports Massage', 'Trigger Point',
            ]),
            'bio' => fake()->paragraph(),
            'is_active' => true,
        ];
    }
}
