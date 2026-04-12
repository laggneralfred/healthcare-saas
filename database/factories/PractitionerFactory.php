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
            'license_number' => strtoupper($this->faker->bothify('??-#####')),
            'specialty'      => $this->faker->randomElement([
                'Acupuncture', 'Traditional Chinese Medicine', 'Cupping Therapy', 'Herbal Medicine',
                'Swedish Massage', 'Deep Tissue', 'Sports Massage', 'Trigger Point',
                'Physical Therapy', 'Chiropractic', 'Massage Therapy',
            ]),
            'is_active' => true,
        ];
    }
}
