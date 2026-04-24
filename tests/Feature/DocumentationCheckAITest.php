<?php

use App\Filament\Resources\Encounters\Pages\EditEncounter;
use App\Filament\Resources\Encounters\Pages\CreateEncounter;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\User;
use App\Services\AI\AIService;
use App\Services\AI\AIUnavailableException;
use App\Services\PracticeContext;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function createEncounterForDocumentationCheck(Practice $practice, array $attributes = []): Encounter
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
        'visit_notes' => 'Neck tension 5/10. Acupuncture performed. Patient tolerated treatment.',
        ...$attributes,
    ]);
}

it('AIService returns a documentation completeness checklist when configured', function () {
    config([
        'services.ai.provider' => 'openai',
        'services.ai.openai.api_key' => 'test-key',
        'services.ai.openai.model' => 'gpt-test',
    ]);

    Http::fake([
        'api.openai.com/v1/responses' => Http::response([
            'output_text' => "- Onset/duration: not documented\n- Follow-up plan: not documented",
        ]),
    ]);

    $result = app(AIService::class)->checkMissingDocumentation('Neck tension 5/10. Acupuncture performed.');

    expect($result)->toContain('Onset/duration');

    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer test-key')
        && $request['model'] === 'gpt-test'
        && str_contains($request['instructions'], 'documentation completeness assistant')
        && str_contains($request['instructions'], 'Do not assign billing codes')
        && str_contains($request['input'], 'Encounter note to review:')
        && str_contains($request['input'], 'Neck tension 5/10'));
});

it('creates documentation check suggestion and usage log without changing visit notes', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => true]);
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $encounter = createEncounterForDocumentationCheck($practice, [
        'visit_notes' => 'Shoulder pain 6/10. Needles placed. Felt better.',
    ]);

    app()->instance(AIService::class, new class extends AIService {
        public function checkMissingDocumentation(string $note, array $context = []): string
        {
            return "- Onset/duration: not documented\n- Follow-up plan: not documented";
        }
    });

    $this->actingAs($user);

    Livewire::test(EditEncounter::class, ['record' => $encounter->id])
        ->set('data.visit_notes', 'Shoulder pain 6/10. Needles placed. Felt better.')
        ->call('checkMissingDocumentation');

    $encounter->refresh();
    expect($encounter->visit_notes)->toBe('Shoulder pain 6/10. Needles placed. Felt better.');

    $this->assertDatabaseHas('ai_suggestions', [
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'encounter_id' => $encounter->id,
        'feature' => 'documentation_check',
        'original_text' => 'Shoulder pain 6/10. Needles placed. Felt better.',
        'suggested_text' => "- Onset/duration: not documented\n- Follow-up plan: not documented",
        'status' => 'pending',
    ]);

    $this->assertDatabaseHas('ai_usage_logs', [
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'feature' => 'documentation_check',
        'status' => 'success',
    ]);
});

it('can check missing documentation on the create encounter screen without creating an encounter', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => true]);
    $user = User::factory()->create(['practice_id' => $practice->id]);

    app()->instance(AIService::class, new class extends AIService {
        public function checkMissingDocumentation(string $note, array $context = []): string
        {
            return "- Follow-up plan: not documented\n- Objective findings: not documented";
        }
    });

    $this->actingAs($user);

    Livewire::test(CreateEncounter::class)
        ->set('data.visit_notes', 'Neck tension 5/10. Acupuncture performed.')
        ->set('data.discipline', 'acupuncture')
        ->call('checkMissingDocumentation')
        ->assertSet('data.documentation_check_result', "- Follow-up plan: not documented\n- Objective findings: not documented");

    expect(Encounter::withoutPracticeScope()->where('practice_id', $practice->id)->count())->toBe(0);

    $this->assertDatabaseHas('ai_suggestions', [
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'encounter_id' => null,
        'feature' => 'documentation_check',
        'original_text' => 'Neck tension 5/10. Acupuncture performed.',
        'suggested_text' => "- Follow-up plan: not documented\n- Objective findings: not documented",
        'status' => 'pending',
    ]);

    $this->assertDatabaseHas('ai_usage_logs', [
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'feature' => 'documentation_check',
        'status' => 'success',
    ]);
});

it('hides and blocks documentation checks when insurance billing is disabled', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => false]);
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $encounter = createEncounterForDocumentationCheck($practice);

    app()->instance(AIService::class, new class extends AIService {
        public function checkMissingDocumentation(string $note, array $context = []): string
        {
            throw new RuntimeException('AI should not be called when insurance billing is disabled.');
        }
    });

    $this->actingAs($user);

    Livewire::test(EditEncounter::class, ['record' => $encounter->id])
        ->assertDontSee('Check Missing Documentation')
        ->set('data.visit_notes', 'Documentation note.')
        ->call('checkMissingDocumentation');

    $this->assertDatabaseMissing('ai_suggestions', [
        'practice_id' => $practice->id,
        'feature' => 'documentation_check',
    ]);

    $this->assertDatabaseMissing('ai_usage_logs', [
        'practice_id' => $practice->id,
        'feature' => 'documentation_check',
    ]);
});

it('shows documentation checks when insurance billing is enabled', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => true]);
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $encounter = createEncounterForDocumentationCheck($practice);

    $this->actingAs($user);

    Livewire::test(EditEncounter::class, ['record' => $encounter->id])
        ->assertSee('Check Missing Documentation');
});

it('uses selected practice context for documentation checks by super admin', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => true]);
    $otherPractice = Practice::factory()->create();
    $superAdmin = User::factory()->create(['practice_id' => null]);
    $encounter = createEncounterForDocumentationCheck($practice);

    app()->instance(AIService::class, new class extends AIService {
        public function checkMissingDocumentation(string $note, array $context = []): string
        {
            return '- Documentation appears adequate for the provided note.';
        }
    });

    $this->actingAs($superAdmin);
    PracticeContext::setCurrentPracticeId($practice->id);

    Livewire::test(EditEncounter::class, ['record' => $encounter->id])
        ->set('data.visit_notes', 'Selected practice documentation note.')
        ->call('checkMissingDocumentation');

    $this->assertDatabaseHas('ai_suggestions', [
        'practice_id' => $practice->id,
        'user_id' => $superAdmin->id,
        'encounter_id' => $encounter->id,
        'feature' => 'documentation_check',
        'suggested_text' => '- Documentation appears adequate for the provided note.',
    ]);

    $this->assertDatabaseMissing('ai_suggestions', [
        'practice_id' => $otherPractice->id,
        'user_id' => $superAdmin->id,
        'feature' => 'documentation_check',
    ]);
});

it('logs failed documentation checks cleanly', function () {
    $practice = Practice::factory()->create(['insurance_billing_enabled' => true]);
    $user = User::factory()->create(['practice_id' => $practice->id]);
    $encounter = createEncounterForDocumentationCheck($practice);

    app()->instance(AIService::class, new class extends AIService {
        public function checkMissingDocumentation(string $note, array $context = []): string
        {
            throw new AIUnavailableException('Documentation AI offline');
        }
    });

    $this->actingAs($user);

    Livewire::test(EditEncounter::class, ['record' => $encounter->id])
        ->set('data.visit_notes', 'Documentation check failure note.')
        ->call('checkMissingDocumentation');

    $encounter->refresh();
    expect($encounter->visit_notes)->toBe('Neck tension 5/10. Acupuncture performed. Patient tolerated treatment.');

    $this->assertDatabaseHas('ai_suggestions', [
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'encounter_id' => $encounter->id,
        'feature' => 'documentation_check',
        'original_text' => 'Documentation check failure note.',
        'suggested_text' => null,
        'status' => 'failed',
    ]);

    $this->assertDatabaseHas('ai_usage_logs', [
        'practice_id' => $practice->id,
        'user_id' => $user->id,
        'feature' => 'documentation_check',
        'status' => 'failed',
        'error_message' => 'Documentation AI offline',
    ]);
});
