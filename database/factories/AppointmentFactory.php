<?php

namespace Database\Factories;

use App\Models\Appointment;
use Faker\Factory as FakerFactory;
use App\Models\AppointmentType;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\States\Appointment\Scheduled;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        $faker = FakerFactory::create();
        $start = $faker->dateTimeBetween('-60 days', '+30 days');
        $end   = (clone $start)->modify('+' . $faker->randomElement([30, 45, 60, 90]) . ' minutes');

        return [
            'practice_id'         => Practice::factory(),
            'patient_id'          => Patient::factory(),
            'practitioner_id'     => Practitioner::factory(),
            'appointment_type_id' => AppointmentType::factory(),
            'status'              => Scheduled::$name,
            'start_datetime'      => $start,
            'end_datetime'        => $end,
            'needs_follow_up'     => false,
            'notes'               => $faker->optional(0.4)->sentence(),
        ];
    }

    public function scheduled(): static
    {
        return $this->state(['status' => 'scheduled']);
    }

    public function inProgress(): static
    {
        return $this->state(['status' => 'in_progress']);
    }

    public function completed(): static
    {
        return $this->state(['status' => 'completed']);
    }

    public function closed(): static
    {
        return $this->state(['status' => 'closed']);
    }

    public function checkout(): static
    {
        return $this->state(['status' => 'checkout']);
    }
}
