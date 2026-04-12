<?php

namespace Database\Factories;

use App\Models\AppointmentType;
use App\Models\Practice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppointmentType>
 */
class AppointmentTypeFactory extends Factory
{
    protected $model = AppointmentType::class;

    public function definition(): array
    {
        return [
            'practice_id'            => Practice::factory(),
            'name'                   => fake()->randomElement([
                'Initial Consultation', 'Follow-up Treatment', 'Cupping Session',
                'Herbal Consultation', 'Swedish Massage', 'Deep Tissue Massage',
                'Sports Massage', 'Hot Stone Therapy', 'Acupuncture Treatment',
            ]),
            'duration_minutes'       => fake()->randomElement([30, 45, 60, 75, 90]),
            'default_service_fee_id' => null,
            'is_active'              => true,
        ];
    }
}
