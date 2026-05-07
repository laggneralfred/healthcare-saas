<?php

namespace Tests\Feature;

use App\Filament\Pages\FrontDeskDashboard;
use App\Filament\Pages\PractitionerReviewPage;
use App\Filament\Resources\PractitionerReviewSubmissions\PractitionerReviewSubmissionResource;
use App\Models\Practice;
use App\Models\PractitionerReviewSubmission;
use App\Models\User;
use App\Services\PracticeContext;
use App\Support\PracticeAccessRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PractitionerReviewSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        PracticeAccessRoles::ensureRoles();
    }

    public function test_practitioner_review_page_loads_for_authenticated_staff(): void
    {
        [, $admin] = $this->practiceWithAdmin();

        $this->actingAs($admin);

        Livewire::test(PractitionerReviewPage::class)
            ->assertSee('Founding Practitioner Review Program')
            ->assertSee('50% off their first 3 paid months')
            ->assertSee('This is not a lifetime discount')
            ->assertSee('submitting feedback does not require subscription');
    }

    public function test_staff_can_submit_questionnaire(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();

        $this->actingAs($admin);

        Livewire::test(PractitionerReviewPage::class)
            ->set('practice_type', 'Acupuncture')
            ->set('clinic_size', 'Solo practitioner')
            ->set('current_systems', "Jane\nPaper forms")
            ->set('first_impression', 'The setup checklist made the first pass clear.')
            ->set('setup_clarity_rating', 4)
            ->set('setup_checklist_helpfulness', 'Helpful')
            ->set('confusing_setup_step', 'Working hours')
            ->set('website_links_feedback', 'The public links made sense.')
            ->set('scheduling_preference', 'Requests reviewed by staff')
            ->set('online_forms_feedback', 'Useful before the first visit.')
            ->set('notes_workflow', 'Short SOAP notes are enough.')
            ->set('ai_feedback', 'Useful only when clearly reviewed.')
            ->set('follow_up_feedback', 'Good fit for missed follow-ups.')
            ->set('pricing_feedback', 'The monthly price is understandable.')
            ->set('subscription_blockers', 'I need confidence in setup.')
            ->set('most_useful', 'Website links')
            ->set('most_confusing', 'Practitioner compatibility')
            ->set('one_change', 'Make first-week setup faster.')
            ->set('may_contact', true)
            ->set('contact_info', 'owner@example.test')
            ->set('discount_acknowledged', true)
            ->call('submit')
            ->assertHasNoErrors();

        $submission = PractitionerReviewSubmission::withoutPracticeScope()->sole();

        $this->assertSame($practice->id, $submission->practice_id);
        $this->assertSame($admin->id, $submission->user_id);
        $this->assertSame(['Jane', 'Paper forms'], $submission->current_systems);
        $this->assertTrue($submission->may_contact);
        $this->assertTrue($submission->discount_acknowledged);
        $this->assertNotNull($submission->submitted_at);
    }

    public function test_discount_acknowledgement_is_required(): void
    {
        [, $admin] = $this->practiceWithAdmin();

        $this->actingAs($admin);

        Livewire::test(PractitionerReviewPage::class)
            ->set('most_useful', 'Appointment requests')
            ->set('discount_acknowledged', false)
            ->call('submit')
            ->assertHasErrors(['discount_acknowledged' => 'accepted']);

        $this->assertSame(0, PractitionerReviewSubmission::withoutPracticeScope()->count());
    }

    public function test_latest_submission_is_visible_to_same_practice(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();

        PractitionerReviewSubmission::factory()->create([
            'practice_id' => $practice->id,
            'user_id' => $admin->id,
            'most_useful' => 'Older feedback',
            'submitted_at' => now()->subDay(),
        ]);

        PractitionerReviewSubmission::factory()->create([
            'practice_id' => $practice->id,
            'user_id' => $admin->id,
            'most_useful' => 'Newer scheduling feedback',
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin);

        Livewire::test(PractitionerReviewPage::class)
            ->assertSee('Latest submitted response')
            ->assertSee('Newer scheduling feedback')
            ->assertDontSee('Older feedback');
    }

    public function test_cross_practice_user_cannot_see_another_practice_submission(): void
    {
        [$practiceA, $adminA] = $this->practiceWithAdmin();
        [, $adminB] = $this->practiceWithAdmin();

        $submissionA = PractitionerReviewSubmission::factory()->create([
            'practice_id' => $practiceA->id,
            'user_id' => $adminA->id,
            'most_useful' => 'Private practice A feedback',
        ]);

        $this->actingAs($adminB);

        $visibleIds = PractitionerReviewSubmissionResource::getEloquentQuery()
            ->pluck('id')
            ->all();

        $this->assertNotContains($submissionA->id, $visibleIds);
    }

    public function test_super_admin_can_see_all_practitioner_review_submissions(): void
    {
        [$practiceA, $adminA] = $this->practiceWithAdmin();
        [$practiceB, $adminB] = $this->practiceWithAdmin();
        $superAdmin = User::factory()->create(['practice_id' => null]);
        $superAdmin->assignRole(User::ROLE_OWNER);

        $submissionA = PractitionerReviewSubmission::factory()->create([
            'practice_id' => $practiceA->id,
            'user_id' => $adminA->id,
        ]);
        $submissionB = PractitionerReviewSubmission::factory()->create([
            'practice_id' => $practiceB->id,
            'user_id' => $adminB->id,
        ]);

        $this->actingAs($superAdmin);
        PracticeContext::setCurrentPracticeId($practiceA->id);

        $visibleIds = PractitionerReviewSubmissionResource::getEloquentQuery()
            ->pluck('id')
            ->all();

        $this->assertContains($submissionA->id, $visibleIds);
        $this->assertContains($submissionB->id, $visibleIds);
    }

    public function test_dashboard_setup_checklist_links_to_review_when_not_submitted(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();

        $this->actingAs($admin);

        Livewire::test(FrontDeskDashboard::class)
            ->assertSee('Help shape Practiq')
            ->assertSee('Practitioner Review Questionnaire');

        PractitionerReviewSubmission::factory()->create([
            'practice_id' => $practice->id,
            'user_id' => $admin->id,
        ]);

        Livewire::test(FrontDeskDashboard::class)
            ->assertDontSee('Help shape Practiq');
    }

    private function practiceWithAdmin(): array
    {
        $practice = Practice::factory()->create(['timezone' => 'America/Los_Angeles']);
        $admin = User::factory()->create(['practice_id' => $practice->id]);
        $admin->assignRole(User::ROLE_ADMINISTRATOR);

        return [$practice, $admin];
    }
}
