<?php

namespace Tests\Feature;

use App\Filament\Resources\Encounters\Pages\EditEncounter;
use App\Models\AIUsageLog;
use App\Models\AISuggestion;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\User;
use App\Services\AI\AIAcknowledgementGate;
use App\Services\AI\AIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class EncounterAIFeatureTeaserTest extends TestCase
{
    use RefreshDatabase;

    public function test_starter_trigger_shows_teaser_path_and_does_not_generate_ai_suggestion(): void
    {
        $practice = Practice::factory()->create([
            'insurance_billing_enabled' => false,
            'plan_tier' => Practice::PLAN_TIER_STARTER,
        ]);
        $user = User::factory()->create(['practice_id' => $practice->id]);
        $encounter = $this->createEncounterForPractice($practice);
        $this->acknowledgeAiDisclaimer($practice, $user);

        $ai = Mockery::mock(AIService::class);
        $ai->shouldReceive('improveNote')->never();
        app()->instance(AIService::class, $ai);

        $this->actingAs($user);

        Livewire::test(EditEncounter::class, ['record' => $encounter->id])
            ->assertSee('AI Assist / Improve with AI')
            ->call('triggerImproveNote')
            ->assertSet('data.ai_suggestion', null);

        $this->assertSame(0, AISuggestion::query()->where('feature', 'improve_note')->count());
        $this->assertSame(0, AIUsageLog::query()->where('feature', 'improve_note')->count());
    }

    public function test_plus_trigger_keeps_normal_ai_flow(): void
    {
        $practice = Practice::factory()->create([
            'insurance_billing_enabled' => false,
            'plan_tier' => Practice::PLAN_TIER_PLUS,
        ]);
        $user = User::factory()->create(['practice_id' => $practice->id]);
        $encounter = $this->createEncounterForPractice($practice);
        $this->acknowledgeAiDisclaimer($practice, $user);

        $ai = Mockery::mock(AIService::class);
        $ai->shouldReceive('improveNote')
            ->once()
            ->andReturn("Chief Complaint:\nNeck tightness.\n\nTreatment Notes:\nTreatment was tolerated well.\n\nPlan / Follow-up:\nReturn as needed.");
        app()->instance(AIService::class, $ai);

        $ackGate = Mockery::mock(AIAcknowledgementGate::class);
        $ackGate->shouldReceive('ensureAcceptedForPractice')
            ->once()
            ->andReturnTrue();
        app()->instance(AIAcknowledgementGate::class, $ackGate);

        $this->actingAs($user);

        Livewire::test(EditEncounter::class, ['record' => $encounter->id])
            ->call('triggerImproveNote')
            ->assertSet('data.ai_suggestion', "Chief Complaint:\nNeck tightness.\n\nTreatment Notes:\nTreatment was tolerated well.\n\nPlan / Follow-up:\nReturn as needed.");

        $this->assertDatabaseHas('ai_suggestions', [
            'practice_id' => $practice->id,
            'user_id' => $user->id,
            'encounter_id' => $encounter->id,
            'feature' => 'improve_note',
            'status' => 'pending',
        ]);
    }

    private function acknowledgeAiDisclaimer(Practice $practice, User $user): void
    {
        DB::table('legal_acceptances')->insert([
            'practice_id' => $practice->id,
            'user_id' => $user->id,
            'document_key' => 'ai_disclaimer_acknowledgement',
            'document_version' => 'v1',
            'source' => 'ai_disclaimer_acknowledgement',
            'accepted_at' => now(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Feature Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createEncounterForPractice(Practice $practice): Encounter
    {
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);
        $practitioner = Practitioner::factory()->create(['practice_id' => $practice->id]);
        $appointmentType = AppointmentType::factory()->create(['practice_id' => $practice->id]);
        $appointment = Appointment::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'appointment_type_id' => $appointmentType->id,
        ]);

        return Encounter::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'appointment_id' => $appointment->id,
            'discipline' => 'acupuncture',
            'visit_notes' => 'pt says neck tight better after tx',
            'chief_complaint' => 'neck tight',
            'plan' => 'return prn',
        ]);
    }
}
