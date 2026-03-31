<?php

namespace Tests\Feature;

use App\Models\AcupunctureEncounter;
use App\Models\Encounter;
use App\Models\Practice;
use App\Models\Patient;
use App\Models\Practitioner;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcupunctureExtensionTest extends TestCase
{
    use RefreshDatabase;

    protected function createEncounter(Practice $practice): Encounter
    {
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);
        $practitioner = Practitioner::factory()->create(['practice_id' => $practice->id]);
        $appointment = Appointment::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
        ]);

        return Encounter::create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'appointment_id' => $appointment->id,
            'visit_date' => now(),
            'chief_complaint' => 'Test complaint',
            'status' => 'final',
        ]);
    }

    public function test_can_create_acupuncture_encounter_with_worsley_fields(): void
    {
        $practice = Practice::factory()->create(['discipline' => 'Acupuncture']);
        $encounter = $this->createEncounter($practice);

        $acupunctureData = [
            'encounter_id' => $encounter->id,
            'tcm_diagnosis' => 'Liver Qi Stagnation',
            'five_elements' => ['Wood', 'Fire'],
            'csor_color' => 'Greenish',
            'csor_sound' => 'Shouting',
            'csor_odor' => 'Rancid',
            'csor_emotion' => 'Anger',
        ];

        $acupuncture = AcupunctureEncounter::create($acupunctureData);

        $this->assertDatabaseHas('acupuncture_encounters', [
            'encounter_id' => $encounter->id,
            'tcm_diagnosis' => 'Liver Qi Stagnation',
            'csor_color' => 'Greenish',
            'csor_sound' => 'Shouting',
            'csor_odor' => 'Rancid',
            'csor_emotion' => 'Anger',
        ]);
        
        $this->assertEquals(['Wood', 'Fire'], AcupunctureEncounter::find($acupuncture->id)->five_elements);
    }

    public function test_can_create_acupuncture_encounter_with_tcm_fields(): void
    {
        $practice = Practice::factory()->create(['discipline' => 'Acupuncture']);
        $encounter = $this->createEncounter($practice);

        $acupunctureData = [
            'encounter_id' => $encounter->id,
            'tcm_diagnosis' => 'Spleen Qi Deficiency',
            'tongue_body' => 'Pale, swollen',
            'tongue_coating' => 'Thin white',
            'pulse_quality' => 'Weak, slippery',
            'zang_fu_diagnosis' => 'Spleen/Stomach Disharmony',
        ];

        $acupuncture = AcupunctureEncounter::create($acupunctureData);

        $this->assertDatabaseHas('acupuncture_encounters', [
            'encounter_id' => $encounter->id,
            'tcm_diagnosis' => 'Spleen Qi Deficiency',
            'tongue_body' => 'Pale, swollen',
            'tongue_coating' => 'Thin white',
            'pulse_quality' => 'Weak, slippery',
            'zang_fu_diagnosis' => 'Spleen/Stomach Disharmony',
        ]);
    }

    public function test_can_retrieve_extension_from_base_encounter(): void
    {
        $practice = Practice::factory()->create(['discipline' => 'Acupuncture']);
        $encounter = $this->createEncounter($practice);

        AcupunctureEncounter::create([
            'encounter_id' => $encounter->id,
            'five_elements' => ['Earth'],
        ]);

        $this->assertInstanceOf(AcupunctureEncounter::class, $encounter->acupunctureEncounter);
        $this->assertEquals(['Earth'], $encounter->refresh()->acupunctureEncounter->five_elements);
    }

    public function test_acupuncture_factory_includes_clinical_fields(): void
    {
        $practice = Practice::factory()->create(['discipline' => 'Acupuncture']);
        $encounter = $this->createEncounter($practice);

        $acupuncture = AcupunctureEncounter::factory()->withClinicalData()->create([
            'encounter_id' => $encounter->id
        ]);

        $this->assertNotNull($acupuncture->five_elements);
        $this->assertIsArray($acupuncture->five_elements);
        $this->assertNotNull($acupuncture->csor_color);
        $this->assertNotNull($acupuncture->tongue_body);
        $this->assertNotNull($acupuncture->pulse_quality);
    }
}
