<?php

namespace Tests\Feature;

use App\Filament\Pages\AiDisclaimerAcknowledgementPage;
use App\Filament\Resources\Encounters\Pages\Concerns\HandlesEncounterAIActions;
use App\Filament\Resources\LegalAcceptances\LegalAcceptanceResource;
use App\Models\AISuggestion;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Encounter;
use App\Models\LegalAcceptance;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\User;
use App\Services\AI\AIService;
use App\Services\PracticeContext;
use App\Services\PracticeSetupChecklistService;
use App\Support\PracticeAccessRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AiDisclaimerAcknowledgementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        PracticeAccessRoles::ensureRoles();
    }

    public function test_ai_disclaimer_legal_page_loads(): void
    {
        $this->get('/legal/ai-disclaimer')
            ->assertStatus(200)
            ->assertSee('AI Disclaimer')
            ->assertSee('AI-generated content may be incomplete, inaccurate, or inappropriate')
            ->assertSee('This page is not legal advice');
    }

    public function test_authenticated_staff_can_view_acknowledgement_page(): void
    {
        [, $admin] = $this->practiceWithAdmin();

        $this->actingAs($admin);

        Livewire::test(AiDisclaimerAcknowledgementPage::class)
            ->assertSee('AI Disclaimer')
            ->assertSee(config('legal.documents.ai_disclaimer_acknowledgement.version'))
            ->assertSee('I acknowledge that AI output must be reviewed');
    }

    public function test_submitting_acknowledgement_creates_current_version_legal_acceptance(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();

        $this->actingAs($admin);

        Livewire::test(AiDisclaimerAcknowledgementPage::class)
            ->set('acknowledged', true)
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('legal_acceptances', [
            'practice_id' => $practice->id,
            'user_id' => $admin->id,
            'document_key' => 'ai_disclaimer_acknowledgement',
            'document_version' => config('legal.documents.ai_disclaimer_acknowledgement.version'),
            'source' => 'ai_disclaimer_acknowledgement',
        ]);

        $this->assertNotNull(
            LegalAcceptance::withoutPracticeScope()
                ->where('practice_id', $practice->id)
                ->where('document_key', 'ai_disclaimer_acknowledgement')
                ->value('accepted_at'),
        );
    }

    public function test_duplicate_acknowledgement_is_not_created_for_same_practice_and_version(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();

        $this->actingAs($admin);

        Livewire::test(AiDisclaimerAcknowledgementPage::class)
            ->set('acknowledged', true)
            ->call('submit')
            ->assertHasNoErrors();

        Livewire::test(AiDisclaimerAcknowledgementPage::class)
            ->set('acknowledged', true)
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(
            1,
            LegalAcceptance::withoutPracticeScope()
                ->where('practice_id', $practice->id)
                ->where('document_key', 'ai_disclaimer_acknowledgement')
                ->where('document_version', config('legal.documents.ai_disclaimer_acknowledgement.version'))
                ->count(),
        );

        Livewire::test(AiDisclaimerAcknowledgementPage::class)
            ->assertSee('Acknowledgement recorded')
            ->assertDontSee('Record acknowledgement');
    }

    public function test_setup_checklist_tracks_ai_disclaimer_acknowledgement(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();

        $checklist = app(PracticeSetupChecklistService::class)->forPractice($practice);
        $this->assertChecklistItem($checklist, 'ai_disclaimer_acknowledgement', false);

        LegalAcceptance::factory()->create([
            'practice_id' => $practice->id,
            'user_id' => $admin->id,
            'document_key' => 'ai_disclaimer_acknowledgement',
            'document_version' => config('legal.documents.ai_disclaimer_acknowledgement.version'),
            'source' => 'ai_disclaimer_acknowledgement',
        ]);

        $checklist = app(PracticeSetupChecklistService::class)->forPractice($practice);
        $this->assertChecklistItem($checklist, 'ai_disclaimer_acknowledgement', true);
    }

    public function test_ai_action_is_blocked_before_acknowledgement_and_does_not_call_ai_service(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();
        $encounter = $this->encounterForPractice($practice);
        $ai = new class extends AIService {
            public int $calls = 0;

            public function improveNote(string $note, array $context = []): string
            {
                $this->calls++;

                return 'Should not be used.';
            }
        };

        $this->actingAs($admin);

        $component = $this->encounterAIHarness($encounter);
        $component->improveNote($ai);

        $this->assertSame(0, $ai->calls);
        $this->assertSame(0, AISuggestion::withoutPracticeScope()->where('practice_id', $practice->id)->count());
    }

    public function test_ai_action_is_allowed_after_acknowledgement(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();
        $encounter = $this->encounterForPractice($practice);
        $ai = new class extends AIService {
            public int $calls = 0;

            public function improveNote(string $note, array $context = []): string
            {
                $this->calls++;

                return 'Patient reports neck tension improved after treatment.';
            }
        };

        LegalAcceptance::factory()->create([
            'practice_id' => $practice->id,
            'user_id' => $admin->id,
            'document_key' => 'ai_disclaimer_acknowledgement',
            'document_version' => config('legal.documents.ai_disclaimer_acknowledgement.version'),
            'source' => 'ai_disclaimer_acknowledgement',
        ]);

        $this->actingAs($admin);

        $component = $this->encounterAIHarness($encounter);
        $component->improveNote($ai);

        $this->assertSame(1, $ai->calls);
        $this->assertSame('Patient reports neck tension improved after treatment.', $component->data['ai_suggestion']);
        $this->assertDatabaseHas('ai_suggestions', [
            'practice_id' => $practice->id,
            'feature' => 'improve_note',
            'status' => 'pending',
        ]);
    }

    public function test_practice_scoped_user_cannot_see_another_practices_ai_disclaimer_acceptance(): void
    {
        [$practiceA, $adminA] = $this->practiceWithAdmin();
        [, $adminB] = $this->practiceWithAdmin();

        $acceptanceA = LegalAcceptance::factory()->create([
            'practice_id' => $practiceA->id,
            'user_id' => $adminA->id,
            'document_key' => 'ai_disclaimer_acknowledgement',
            'document_version' => config('legal.documents.ai_disclaimer_acknowledgement.version'),
        ]);

        $this->actingAs($adminB);

        $visibleIds = LegalAcceptanceResource::getEloquentQuery()
            ->pluck('id')
            ->all();

        $this->assertNotContains($acceptanceA->id, $visibleIds);
    }

    public function test_super_admin_visibility_includes_ai_disclaimer_acceptances(): void
    {
        [$practiceA, $adminA] = $this->practiceWithAdmin();
        [$practiceB, $adminB] = $this->practiceWithAdmin();
        $superAdmin = User::factory()->create(['practice_id' => null]);
        $superAdmin->assignRole(User::ROLE_OWNER);

        $acceptanceA = LegalAcceptance::factory()->create([
            'practice_id' => $practiceA->id,
            'user_id' => $adminA->id,
            'document_key' => 'ai_disclaimer_acknowledgement',
            'document_version' => config('legal.documents.ai_disclaimer_acknowledgement.version'),
        ]);
        $acceptanceB = LegalAcceptance::factory()->create([
            'practice_id' => $practiceB->id,
            'user_id' => $adminB->id,
            'document_key' => 'ai_disclaimer_acknowledgement',
            'document_version' => config('legal.documents.ai_disclaimer_acknowledgement.version'),
        ]);

        $this->actingAs($superAdmin);
        PracticeContext::setCurrentPracticeId($practiceA->id);

        $visibleIds = LegalAcceptanceResource::getEloquentQuery()
            ->pluck('id')
            ->all();

        $this->assertContains($acceptanceA->id, $visibleIds);
        $this->assertContains($acceptanceB->id, $visibleIds);
    }

    private function practiceWithAdmin(): array
    {
        $practice = Practice::factory()->create();
        $admin = User::factory()->create(['practice_id' => $practice->id]);
        $admin->assignRole(User::ROLE_ADMINISTRATOR);

        return [$practice, $admin];
    }

    private function encounterForPractice(Practice $practice): Encounter
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
            'visit_notes' => 'Patient reports neck tension.',
        ]);
    }

    private function encounterAIHarness(Encounter $encounter): object
    {
        return new class($encounter)
        {
            use HandlesEncounterAIActions;

            public array $data = [
                'visit_notes' => 'Patient reports neck tension.',
            ];

            public object $form;

            public function __construct(public Encounter $record)
            {
                $this->form = new class
                {
                    public function fillPartially(
                        array $state,
                        array $paths,
                        bool $shouldCallHydrationHooks = false,
                        bool $shouldFillStateWithNull = false,
                    ): void {
                        //
                    }
                };
            }
        };
    }

    private function assertChecklistItem(array $checklist, string $key, bool $complete): void
    {
        $item = collect($checklist['items'])->firstWhere('key', $key);

        $this->assertNotNull($item, "Checklist item [{$key}] was not found.");
        $this->assertSame($complete, $item['complete']);
    }
}
