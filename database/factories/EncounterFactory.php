<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Encounter>
 */
class EncounterFactory extends Factory
{
    protected $model = Encounter::class;

    public function definition(): array
    {
        return [
            'practice_id'    => Practice::factory(),
            'patient_id'     => Patient::factory(),
            'appointment_id' => Appointment::factory(),
            'practitioner_id' => Practitioner::factory(),
            'status'         => 'draft',
            'visit_date'     => $this->faker->dateTimeBetween('-60 days', 'now')->format('Y-m-d'),
            'visit_notes'    => null,
            'completed_on'   => null,
        ];
    }

    public function complete(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'      => 'complete',
            'visit_notes' => $this->faker->randomElement([
                'Patient reports significant improvement in lower back pain. Responded well to treatment. Plan to continue weekly sessions.',
                'Chief concern: chronic headaches. Treatment focused on GB and LI meridians. Patient tolerated well.',
                'Follow-up visit for insomnia protocol. Patient sleeping 6-7 hours vs previous 4. Continuing treatment plan.',
                'New patient intake visit. Comprehensive assessment completed. Initial treatment administered with positive response.',
                'Maintenance treatment. Patient reports feeling balanced. Reduced appointment frequency to bi-weekly.',
                'Acute presentation: shoulder impingement. Local and distal points selected. Immediate pain relief noted.',
                'Stress and anxiety protocol. Patient responded well. Recommended lifestyle modifications discussed.',
            ]),
            'completed_on' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft', 'completed_on' => null]);
    }
}
