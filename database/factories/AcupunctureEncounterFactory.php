<?php

namespace Database\Factories;

use App\Models\AcupunctureEncounter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcupunctureEncounter>
 */
class AcupunctureEncounterFactory extends Factory
{
    protected $model = AcupunctureEncounter::class;

    public function definition(): array
    {
        return [
            'tcm_diagnosis'      => null,
            'points_used'        => null,
            'meridians'          => null,
            'treatment_protocol' => null,
            'needle_count'       => null,
            'session_notes'      => null,
        ];
    }

    public function withClinicalData(): static
    {
        return $this->state(fn (array $attributes) => [
            'tcm_diagnosis' => fake()->randomElement([
                'Liver Qi Stagnation',
                'Kidney Yin Deficiency',
                'Spleen Qi Deficiency',
                'Heart Blood Deficiency',
                'Lung Qi Deficiency',
                'Stomach Yin Deficiency',
                'Damp-Heat in the Lower Jiao',
                'Blood Stasis with Qi Deficiency',
                'Wind-Cold Invasion',
                'Liver Yang Rising',
            ]),
            'points_used' => fake()->randomElement([
                'LR3, SP6, ST36, PC6, HT7',
                'GB20, LI4, ST44, BL62, KD3',
                'CV4, CV6, SP6, ST36, KD7',
                'BL15, BL20, HT7, SP10, PC6',
                'LU7, LI4, SP6, ST36, CV17',
                'ST21, CV12, PC6, SP4, ST44',
                'GB34, LR3, SP9, ST28, CV3',
                'BL40, GB30, GB34, BL60, SP10',
            ]),
            'meridians' => fake()->randomElement([
                'Liver, Spleen, Stomach',
                'Kidney, Bladder',
                'Heart, Pericardium',
                'Lung, Large Intestine',
                'Gallbladder, Liver',
                'Spleen, Stomach, Heart',
                'Kidney, Liver, Spleen',
                'Conception Vessel, Governing Vessel',
            ]),
            'treatment_protocol' => fake()->randomElement([
                'Tonify Liver Qi, regulate menstruation, calm Shen',
                'Nourish Kidney Yin, clear deficiency heat',
                'Tonify Spleen Qi, resolve dampness, improve digestion',
                'Nourish Heart Blood, calm the mind, improve sleep',
                'Tonify Lung Qi, regulate Wei Qi, stop sweating',
                'Dispel Wind-Cold, release the exterior, stop pain',
                'Move Liver Qi, resolve stagnation, relieve stress',
                'Clear Damp-Heat, benefit urination, relieve urgency',
            ]),
            'needle_count' => fake()->numberBetween(6, 24),
            'session_notes' => fake()->optional(0.6)->randomElement([
                'Patient tolerated all needles well. De-qi achieved at most points.',
                'Retained needles for 25 minutes. Added moxa on CV4 for warmth.',
                'Patient reported immediate relaxation. Mild soreness at LR3 expected.',
                'Electroacupuncture applied at BL25-BL40 pair at 2Hz for 20 min.',
                'Patient fell asleep during treatment — strong Shen response.',
                'Cupping applied to upper back after needling. Significant blood stasis pattern revealed.',
                'First treatment response: patient felt lightheaded post-treatment, resolved after rest.',
            ]),
        ]);
    }
}
