<?php

namespace Tests\Feature;

use App\Filament\Resources\NewPatientInterests\NewPatientInterestResource;
use App\Filament\Resources\NewPatientInterests\Pages\ViewNewPatientInterest;
use App\Models\NewPatientInterest;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\User;
use App\Support\PracticeAccessRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NewPatientInterestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        PracticeAccessRoles::ensureRoles();
    }

    public function test_public_interest_form_renders_for_single_active_live_practice(): void
    {
        Practice::factory()->create(['name' => 'Warm Clinic']);

        $this->get(route('new-patient.interest'))
            ->assertOk()
            ->assertSee('Request to become a new patient')
            ->assertSee('Warm Clinic')
            ->assertSee('Preferred service');
    }

    public function test_valid_submission_creates_interest_without_creating_patient(): void
    {
        $practice = Practice::factory()->create(['name' => 'Warm Clinic']);

        $this->post(route('new-patient.interest.store'), $this->validPayload())
            ->assertRedirect(route('new-patient.thanks'));

        $this->assertDatabaseHas('new_patient_interests', [
            'practice_id' => $practice->id,
            'first_name' => 'Nora',
            'last_name' => 'Interested',
            'email' => 'nora@example.test',
            'status' => NewPatientInterest::STATUS_NEW,
        ]);
        $this->assertSame(0, Patient::withoutPracticeScope()->count());

        $this->get(route('new-patient.thanks'))
            ->assertOk()
            ->assertSee('The clinic has received your request.');
    }

    public function test_invalid_email_and_required_fields_fail_validation(): void
    {
        Practice::factory()->create();

        $this->post(route('new-patient.interest.store'), [
            'first_name' => '',
            'last_name' => '',
            'email' => 'not-an-email',
            'contact_acknowledgement' => null,
        ])
            ->assertSessionHasErrors(['first_name', 'last_name', 'email', 'contact_acknowledgement']);

        $this->assertSame(0, NewPatientInterest::withoutPracticeScope()->count());
    }

    public function test_submission_does_not_reveal_existing_patient_email(): void
    {
        $practice = Practice::factory()->create(['name' => 'Warm Clinic']);
        Patient::factory()->create([
            'practice_id' => $practice->id,
            'email' => 'nora@example.test',
        ]);

        $this->post(route('new-patient.interest.store'), $this->validPayload())
            ->assertRedirect(route('new-patient.thanks'));

        $this->get(route('new-patient.thanks'))
            ->assertOk()
            ->assertSee('The clinic has received your request.')
            ->assertDontSee('already')
            ->assertDontSee('existing');

        $this->assertSame(1, Patient::withoutPracticeScope()->count());
        $this->assertSame(1, NewPatientInterest::withoutPracticeScope()->count());
    }

    public function test_public_interest_form_is_unavailable_when_practice_cannot_be_resolved(): void
    {
        Practice::factory()->create(['name' => 'Clinic One']);
        Practice::factory()->create(['name' => 'Clinic Two']);

        $this->get(route('new-patient.interest'))
            ->assertOk()
            ->assertSee('New patient requests are not available right now.');
    }

    public function test_staff_can_see_own_practice_interests_only(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();
        $otherPractice = Practice::factory()->create();

        NewPatientInterest::withoutPracticeScope()->create($this->interestData($practice, [
            'first_name' => 'Local',
            'last_name' => 'Interest',
            'email' => 'local@example.test',
        ]));
        NewPatientInterest::withoutPracticeScope()->create($this->interestData($otherPractice, [
            'first_name' => 'Other',
            'last_name' => 'Interest',
            'email' => 'other@example.test',
        ]));

        $this->actingAs($admin);

        $this->get(NewPatientInterestResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Local Interest')
            ->assertDontSee('Other Interest');
    }

    public function test_status_actions_update_interest(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();
        $interest = NewPatientInterest::withoutPracticeScope()->create($this->interestData($practice));

        $this->actingAs($admin);

        Livewire::test(ViewNewPatientInterest::class, ['record' => $interest->id])
            ->callAction('mark_reviewing')
            ->assertHasNoActionErrors();
        $this->assertSame(NewPatientInterest::STATUS_REVIEWING, $interest->refresh()->status);
        $this->assertNull($interest->responded_at);

        Livewire::test(ViewNewPatientInterest::class, ['record' => $interest->id])
            ->callAction('mark_declined')
            ->assertHasNoActionErrors();
        $this->assertSame(NewPatientInterest::STATUS_DECLINED, $interest->refresh()->status);
        $this->assertNotNull($interest->responded_at);
        $this->assertSame($admin->id, $interest->responded_by_user_id);

        Livewire::test(ViewNewPatientInterest::class, ['record' => $interest->id])
            ->callAction('mark_closed')
            ->assertHasNoActionErrors();
        $this->assertSame(NewPatientInterest::STATUS_CLOSED, $interest->refresh()->status);
        $this->assertSame($admin->id, $interest->responded_by_user_id);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Nora',
            'last_name' => 'Interested',
            'email' => 'nora@example.test',
            'phone' => '(555) 010-1234',
            'preferred_service' => 'Acupuncture',
            'preferred_days_times' => 'Tuesday mornings',
            'message' => 'Looking for gentle care.',
            'contact_acknowledgement' => '1',
        ], $overrides);
    }

    private function practiceWithAdmin(): array
    {
        $practice = Practice::factory()->create();
        $admin = User::factory()->create(['practice_id' => $practice->id]);
        $admin->assignRole(User::ROLE_ADMINISTRATOR);

        return [$practice, $admin];
    }

    private function interestData(Practice $practice, array $overrides = []): array
    {
        return array_merge([
            'practice_id' => $practice->id,
            'first_name' => 'Nora',
            'last_name' => 'Interested',
            'email' => 'nora@example.test',
            'phone' => '(555) 010-1234',
            'preferred_service' => 'Massage',
            'preferred_days_times' => 'Friday afternoons',
            'message' => 'Would like to know if the clinic is accepting new patients.',
            'status' => NewPatientInterest::STATUS_NEW,
        ], $overrides);
    }
}
