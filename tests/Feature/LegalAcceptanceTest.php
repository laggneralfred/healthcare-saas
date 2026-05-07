<?php

namespace Tests\Feature;

use App\Filament\Resources\LegalAcceptances\LegalAcceptanceResource;
use App\Models\LegalAcceptance;
use App\Models\Practice;
use App\Models\User;
use App\Services\PracticeContext;
use App\Support\PracticeAccessRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        PracticeAccessRoles::ensureRoles();
    }

    public function test_registration_creates_terms_and_privacy_acceptances(): void
    {
        $this->withServerVariables([
            'HTTP_USER_AGENT' => 'Legal Acceptance Test Browser',
            'REMOTE_ADDR' => '203.0.113.25',
        ])->post('/register', [
            'practice_name' => 'Acceptance Clinic',
            'first_name' => 'Acceptance',
            'last_name' => 'Owner',
            'email' => 'acceptance-owner@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'practice_type' => 'general_wellness',
            'terms_accepted' => true,
        ]);

        $practice = Practice::where('name', 'Acceptance Clinic')->firstOrFail();
        $user = User::where('email', 'acceptance-owner@example.test')->firstOrFail();

        foreach (['terms_of_service', 'privacy_policy'] as $documentKey) {
            $this->assertDatabaseHas('legal_acceptances', [
                'practice_id' => $practice->id,
                'user_id' => $user->id,
                'document_key' => $documentKey,
                'document_version' => config("legal.documents.{$documentKey}.version"),
                'source' => 'register',
                'user_agent' => 'Legal Acceptance Test Browser',
            ]);
        }

        $this->assertTrue(
            LegalAcceptance::withoutPracticeScope()
                ->where('practice_id', $practice->id)
                ->whereNotNull('accepted_at')
                ->count() === 2,
        );
    }

    public function test_missing_terms_acceptance_blocks_registration_and_creates_no_ledger_records(): void
    {
        $response = $this->post('/register', [
            'practice_name' => 'Blocked Legal Clinic',
            'first_name' => 'Blocked',
            'last_name' => 'Owner',
            'email' => 'blocked-owner@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'practice_type' => 'general_wellness',
            'terms_accepted' => false,
        ]);

        $response->assertSessionHasErrors('terms_accepted');
        $this->assertDatabaseMissing('practices', ['name' => 'Blocked Legal Clinic']);
        $this->assertSame(0, LegalAcceptance::withoutPracticeScope()->count());
    }

    public function test_privacy_policy_describes_single_database_multi_tenancy_accurately(): void
    {
        $response = $this->get('/privacy');

        $response->assertStatus(200);
        $response->assertDontSee('Isolated database per practice');
        $response->assertSee('single-database multi-tenancy with practice-level scoping and access controls');
    }

    public function test_practice_scoped_user_cannot_see_another_practices_legal_acceptances(): void
    {
        [$practiceA, $adminA] = $this->practiceWithAdmin();
        [, $adminB] = $this->practiceWithAdmin();

        $acceptanceA = LegalAcceptance::factory()->create([
            'practice_id' => $practiceA->id,
            'user_id' => $adminA->id,
        ]);

        $this->actingAs($adminB);

        $visibleIds = LegalAcceptanceResource::getEloquentQuery()
            ->pluck('id')
            ->all();

        $this->assertNotContains($acceptanceA->id, $visibleIds);
    }

    public function test_super_admin_can_see_all_legal_acceptances(): void
    {
        [$practiceA, $adminA] = $this->practiceWithAdmin();
        [$practiceB, $adminB] = $this->practiceWithAdmin();
        $superAdmin = User::factory()->create(['practice_id' => null]);
        $superAdmin->assignRole(User::ROLE_OWNER);

        $acceptanceA = LegalAcceptance::factory()->create([
            'practice_id' => $practiceA->id,
            'user_id' => $adminA->id,
        ]);
        $acceptanceB = LegalAcceptance::factory()->create([
            'practice_id' => $practiceB->id,
            'user_id' => $adminB->id,
            'document_key' => 'privacy_policy',
            'document_version' => config('legal.documents.privacy_policy.version'),
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
}
