<?php

namespace Tests\Feature;

use App\Filament\Pages\HipaaBaaAcknowledgementPage;
use App\Filament\Resources\Encounters\Pages\CreateEncounter;
use App\Filament\Resources\LegalAcceptances\LegalAcceptanceResource;
use App\Filament\Resources\Patients\Pages\CreatePatient;
use App\Models\LegalAcceptance;
use App\Models\Practice;
use App\Models\User;
use App\Services\PracticeContext;
use App\Services\PracticeSetupChecklistService;
use App\Support\PracticeAccessRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HipaaBaaAcknowledgementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        PracticeAccessRoles::ensureRoles();
    }

    public function test_hipaa_baa_legal_page_loads(): void
    {
        $this->get('/legal/hipaa-baa')
            ->assertStatus(200)
            ->assertSee('HIPAA / BAA Acknowledgement')
            ->assertSee('This page is not legal advice');
    }

    public function test_authenticated_staff_can_view_acknowledgement_page(): void
    {
        [, $admin] = $this->practiceWithAdmin();

        $this->actingAs($admin);

        Livewire::test(HipaaBaaAcknowledgementPage::class)
            ->assertSee('HIPAA / BAA Acknowledgement')
            ->assertSee(config('legal.documents.hipaa_baa_acknowledgement.version'))
            ->assertSee('I acknowledge the HIPAA/BAA responsibilities described above.');
    }

    public function test_submitting_acknowledgement_creates_current_version_legal_acceptance(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();

        $this->actingAs($admin);

        Livewire::withQueryParams([])
            ->test(HipaaBaaAcknowledgementPage::class)
            ->set('acknowledged', true)
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('legal_acceptances', [
            'practice_id' => $practice->id,
            'user_id' => $admin->id,
            'document_key' => 'hipaa_baa_acknowledgement',
            'document_version' => config('legal.documents.hipaa_baa_acknowledgement.version'),
            'source' => 'hipaa_baa_acknowledgement',
        ]);

        $this->assertNotNull(
            LegalAcceptance::withoutPracticeScope()
                ->where('practice_id', $practice->id)
                ->where('document_key', 'hipaa_baa_acknowledgement')
                ->value('accepted_at'),
        );
    }

    public function test_acknowledgement_is_not_duplicated_for_same_practice_and_version(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();

        $this->actingAs($admin);

        Livewire::test(HipaaBaaAcknowledgementPage::class)
            ->set('acknowledged', true)
            ->call('submit')
            ->assertHasNoErrors();

        Livewire::test(HipaaBaaAcknowledgementPage::class)
            ->set('acknowledged', true)
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(
            1,
            LegalAcceptance::withoutPracticeScope()
                ->where('practice_id', $practice->id)
                ->where('document_key', 'hipaa_baa_acknowledgement')
                ->where('document_version', config('legal.documents.hipaa_baa_acknowledgement.version'))
                ->count(),
        );

        Livewire::test(HipaaBaaAcknowledgementPage::class)
            ->assertSee('Acknowledgement recorded')
            ->assertDontSee('Record acknowledgement');
    }

    public function test_setup_checklist_tracks_hipaa_baa_acknowledgement(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();

        $checklist = app(PracticeSetupChecklistService::class)->forPractice($practice);
        $this->assertChecklistItem($checklist, 'hipaa_baa_acknowledgement', false);

        LegalAcceptance::factory()->create([
            'practice_id' => $practice->id,
            'user_id' => $admin->id,
            'document_key' => 'hipaa_baa_acknowledgement',
            'document_version' => config('legal.documents.hipaa_baa_acknowledgement.version'),
            'source' => 'hipaa_baa_acknowledgement',
        ]);

        $checklist = app(PracticeSetupChecklistService::class)->forPractice($practice);
        $this->assertChecklistItem($checklist, 'hipaa_baa_acknowledgement', true);
    }

    public function test_patient_and_clinical_create_pages_show_warning_when_acknowledgement_missing(): void
    {
        [, $admin] = $this->practiceWithAdmin();

        $this->actingAs($admin);

        Livewire::test(CreatePatient::class)
            ->assertSee('Before entering real patient or clinical data, please complete the HIPAA/BAA acknowledgement.');

        Livewire::test(CreateEncounter::class)
            ->assertSee('Before entering real patient or clinical data, please complete the HIPAA/BAA acknowledgement.');
    }

    public function test_practice_scoped_user_cannot_see_another_practices_hipaa_baa_acceptance(): void
    {
        [$practiceA, $adminA] = $this->practiceWithAdmin();
        [, $adminB] = $this->practiceWithAdmin();

        $acceptanceA = LegalAcceptance::factory()->create([
            'practice_id' => $practiceA->id,
            'user_id' => $adminA->id,
            'document_key' => 'hipaa_baa_acknowledgement',
            'document_version' => config('legal.documents.hipaa_baa_acknowledgement.version'),
        ]);

        $this->actingAs($adminB);

        $visibleIds = LegalAcceptanceResource::getEloquentQuery()
            ->pluck('id')
            ->all();

        $this->assertNotContains($acceptanceA->id, $visibleIds);
    }

    public function test_super_admin_visibility_includes_hipaa_baa_acceptances(): void
    {
        [$practiceA, $adminA] = $this->practiceWithAdmin();
        [$practiceB, $adminB] = $this->practiceWithAdmin();
        $superAdmin = User::factory()->create(['practice_id' => null]);
        $superAdmin->assignRole(User::ROLE_OWNER);

        $acceptanceA = LegalAcceptance::factory()->create([
            'practice_id' => $practiceA->id,
            'user_id' => $adminA->id,
            'document_key' => 'hipaa_baa_acknowledgement',
            'document_version' => config('legal.documents.hipaa_baa_acknowledgement.version'),
        ]);
        $acceptanceB = LegalAcceptance::factory()->create([
            'practice_id' => $practiceB->id,
            'user_id' => $adminB->id,
            'document_key' => 'hipaa_baa_acknowledgement',
            'document_version' => config('legal.documents.hipaa_baa_acknowledgement.version'),
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

    private function assertChecklistItem(array $checklist, string $key, bool $complete): void
    {
        $item = collect($checklist['items'])->firstWhere('key', $key);

        $this->assertNotNull($item, "Checklist item [{$key}] was not found.");
        $this->assertSame($complete, $item['complete']);
    }
}
