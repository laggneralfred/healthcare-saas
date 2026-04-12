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
            'encounter_id'       => null,
            'tcm_diagnosis'      => null,
            'tongue_body'        => null,
            'tongue_coating'     => null,
            'pulse_quality'      => null,
            'zang_fu_diagnosis'  => null,
            'five_elements'      => null,
            'csor_color'         => null,
            'csor_sound'         => null,
            'csor_odor'          => null,
            'csor_emotion'       => null,
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
            'tcm_diagnosis' => $this->faker->randomElement([
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
            'tongue_body' => $this->faker->randomElement([
                'Pale', 'Red', 'Swollen', 'Dusky', 'Thin', 'Normal',
            ]),
            'tongue_coating' => $this->faker->randomElement([
                'Thin white', 'Thick yellow', 'Peeled', 'Greasy', 'No coat',
            ]),
            'pulse_quality' => $this->faker->randomElement([
                'Wiry', 'Slippery', 'Thready', 'Weak', 'Floating', 'Deep', 'Choppy',
            ]),
            'zang_fu_diagnosis' => $this->faker->randomElement([
                'Liver/Spleen Disharmony',
                'Kidney/Heart Not Communicating',
                'Lung/Kidney Yin Deficiency',
                'Spleen/Stomach Damp-Heat',
                'Heart/Liver Blood Deficiency',
            ]),
            'five_elements' => $this->faker->randomElements(['Wood', 'Fire', 'Earth', 'Metal', 'Water'], $this->faker->numberBetween(1, 2)),
            'csor_color' => $this->faker->randomElement(['Greenish', 'Reddish', 'Yellowish', 'Whiteish', 'Blueish/Blackish']),
            'csor_sound' => $this->faker->randomElement(['Shouting', 'Laughing', 'Singing', 'Weeping', 'Groaning']),
            'csor_odor' => $this->faker->randomElement(['Rancid', 'Scorched', 'Fragrant', 'Rotten', 'Putrid']),
            'csor_emotion' => $this->faker->randomElement(['Anger', 'Joy', 'Sympathy', 'Grief', 'Fear']),
            'points_used' => 'LI4, LV3, ST36, SP6',
            'meridians' => 'Large Intestine, Liver, Stomach, Spleen',
            'treatment_protocol' => 'Regulate Qi, tonify Spleen.',
            'needle_count' => $this->faker->numberBetween(6, 12),
            'session_notes' => $this->faker->randomElement([
                'Patient reported improved sleep since last visit.',
                'Initial hesitation to needles, but relaxed quickly.',
                'Cupping applied to upper back after needling.',
                'First treatment response: patient felt lightheaded post-treatment.',
            ]),
        ]);
    }
}
