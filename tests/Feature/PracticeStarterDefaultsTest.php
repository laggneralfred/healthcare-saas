<?php

namespace Tests\Feature;

use App\Livewire\OnboardingWizard;
use App\Models\AppointmentType;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\PractitionerWorkingHour;
use App\Models\ServiceFee;
use App\Models\TrialSignup;
use App\Models\User;
use App\Services\Onboarding\PracticeStarterDefaultsService;
use App\Services\PracticeSetupChecklistService;
use App\Support\PracticeType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class PracticeStarterDefaultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_registration_gets_starter_defaults_and_guided_onboarding(): void
    {
        Mail::fake();

        $response = $this->post('/register', [
            'practice_name' => 'Starter Defaults Clinic',
            'first_name' => 'Avery',
            'last_name' => 'Owner',
            'email' => 'avery-owner@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'practice_type' => PracticeType::FIVE_ELEMENT_ACUPUNCTURE,
            'terms_accepted' => true,
        ]);

        $response->assertRedirect('/onboarding');

        $practice = Practice::where('name', 'Starter Defaults Clinic')->firstOrFail();
        $user = User::where('email', 'avery-owner@example.com')->firstOrFail();
        $practitioner = Practitioner::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->firstOrFail();

        $this->assertSame($user->id, $practitioner->user_id);
        $this->assertSame('Five Element Acupuncture', $practitioner->specialty);
        $this->assertTrue($practitioner->is_active);

        $this->assertSame(
            [1, 2, 3, 4, 5],
            PractitionerWorkingHour::withoutPracticeScope()
                ->where('practice_id', $practice->id)
                ->where('practitioner_id', $practitioner->id)
                ->orderBy('day_of_week')
                ->pluck('day_of_week')
                ->all(),
        );

        $initialVisit = AppointmentType::withoutPracticeScope()
            ->with('defaultServiceFee')
            ->where('practice_id', $practice->id)
            ->where('name', 'Initial Visit')
            ->firstOrFail();
        $followUpVisit = AppointmentType::withoutPracticeScope()
            ->with('defaultServiceFee')
            ->where('practice_id', $practice->id)
            ->where('name', 'Follow-up Visit')
            ->firstOrFail();

        $this->assertSame(60, $initialVisit->duration_minutes);
        $this->assertSame('125.00', (string) $initialVisit->defaultServiceFee->default_price);
        $this->assertSame(45, $followUpVisit->duration_minutes);
        $this->assertSame('90.00', (string) $followUpVisit->defaultServiceFee->default_price);

        $this->assertSame(2, DB::table('practitioner_appointment_type')
            ->where('practice_id', $practice->id)
            ->where('practitioner_id', $practitioner->id)
            ->where('is_active', true)
            ->count());

        $this->actingAs($user)
            ->get('/onboarding')
            ->assertSuccessful()
            ->assertSee('We created a few starter settings so you can try Practiq right away.')
            ->assertSee('Initial Visit')
            ->assertSee('Follow-up Visit')
            ->assertSee('Acknowledge HIPAA/BAA')
            ->assertSee('Go to Today');
    }

    public function test_starter_defaults_are_discipline_aware(): void
    {
        $cases = [
            PracticeType::TCM_ACUPUNCTURE => ['Acupuncture', '125.00', '90.00'],
            PracticeType::FIVE_ELEMENT_ACUPUNCTURE => ['Five Element Acupuncture', '125.00', '90.00'],
            PracticeType::MASSAGE_THERAPY => ['Massage Therapy', '100.00', '85.00'],
            PracticeType::CHIROPRACTIC => ['Chiropractic', '110.00', '75.00'],
            PracticeType::PHYSIOTHERAPY => ['Physiotherapy', '130.00', '95.00'],
            PracticeType::GENERAL_WELLNESS => ['Wellness', '100.00', '75.00'],
        ];

        foreach ($cases as $practiceType => [$specialty, $initialPrice, $followUpPrice]) {
            $practice = Practice::factory()->create([
                'practice_type' => $practiceType,
                'discipline' => PracticeType::disciplineFallback($practiceType),
            ]);
            $user = User::factory()->create(['practice_id' => $practice->id]);

            app(PracticeStarterDefaultsService::class)->seed($practice, $user);

            $practitioner = Practitioner::withoutPracticeScope()
                ->where('practice_id', $practice->id)
                ->firstOrFail();

            $prices = AppointmentType::withoutPracticeScope()
                ->with('defaultServiceFee')
                ->where('practice_id', $practice->id)
                ->orderByRaw("case when name = 'Initial Visit' then 0 else 1 end")
                ->get()
                ->map(fn (AppointmentType $type): string => (string) $type->defaultServiceFee->default_price)
                ->all();

            $this->assertSame($specialty, $practitioner->specialty);
            $this->assertSame([$initialPrice, $followUpPrice], $prices);
        }
    }

    public function test_starter_defaults_are_idempotent(): void
    {
        $practice = Practice::factory()->create(['practice_type' => PracticeType::MASSAGE_THERAPY]);
        $user = User::factory()->create(['practice_id' => $practice->id]);
        $service = app(PracticeStarterDefaultsService::class);

        $service->seed($practice, $user);
        $service->seed($practice, $user);

        $this->assertSame(1, Practitioner::withoutPracticeScope()->where('practice_id', $practice->id)->count());
        $this->assertSame(5, PractitionerWorkingHour::withoutPracticeScope()->where('practice_id', $practice->id)->count());
        $this->assertSame(2, AppointmentType::withoutPracticeScope()->where('practice_id', $practice->id)->count());
        $this->assertSame(2, ServiceFee::withoutPracticeScope()->where('practice_id', $practice->id)->count());
        $this->assertSame(2, DB::table('practitioner_appointment_type')->where('practice_id', $practice->id)->count());
    }

    public function test_starter_defaults_do_not_overwrite_existing_setup_data(): void
    {
        $practice = Practice::factory()->create(['practice_type' => PracticeType::CHIROPRACTIC]);
        $user = User::factory()->create(['practice_id' => $practice->id]);
        $practitioner = Practitioner::factory()->create([
            'practice_id' => $practice->id,
            'user_id' => $user->id,
            'specialty' => 'Custom Specialty',
        ]);
        PractitionerWorkingHour::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'practitioner_id' => $practitioner->id,
            'day_of_week' => 2,
            'start_time' => '10:00',
            'end_time' => '14:00',
            'is_active' => true,
        ]);
        AppointmentType::factory()->create([
            'practice_id' => $practice->id,
            'name' => 'Custom Visit',
            'duration_minutes' => 30,
        ]);

        app(PracticeStarterDefaultsService::class)->seed($practice, $user);

        $this->assertSame(1, Practitioner::withoutPracticeScope()->where('practice_id', $practice->id)->count());
        $this->assertSame('Custom Specialty', $practitioner->fresh()->specialty);
        $this->assertSame(1, PractitionerWorkingHour::withoutPracticeScope()->where('practice_id', $practice->id)->count());
        $this->assertSame(1, AppointmentType::withoutPracticeScope()->where('practice_id', $practice->id)->count());
        $this->assertDatabaseHas('appointment_types', [
            'practice_id' => $practice->id,
            'name' => 'Custom Visit',
            'duration_minutes' => 30,
        ]);
    }

    public function test_setup_checklist_recognizes_starter_defaults_but_not_legal_acknowledgements(): void
    {
        $practice = Practice::factory()->create(['practice_type' => PracticeType::PHYSIOTHERAPY]);
        $user = User::factory()->create(['practice_id' => $practice->id]);

        app(PracticeStarterDefaultsService::class)->seed($practice, $user);

        $checklist = app(PracticeSetupChecklistService::class)->forPractice($practice);

        $this->assertChecklistItem($checklist, 'practice_profile', true);
        $this->assertChecklistItem($checklist, 'active_practitioner', true);
        $this->assertChecklistItem($checklist, 'active_appointment_type', true);
        $this->assertChecklistItem($checklist, 'practitioner_appointment_type', true);
        $this->assertChecklistItem($checklist, 'working_hours', true);
        $this->assertChecklistItem($checklist, 'public_links', true);
        $this->assertChecklistItem($checklist, 'hipaa_baa_acknowledgement', false);
        $this->assertChecklistItem($checklist, 'ai_disclaimer_acknowledgement', false);
    }

    public function test_finish_setup_marks_onboarding_complete_and_redirects_to_today(): void
    {
        $practice = Practice::factory()->create(['setup_completed_at' => null]);
        $user = User::factory()->create(['practice_id' => $practice->id]);

        Livewire::actingAs($user)
            ->test(OnboardingWizard::class)
            ->call('finishSetup')
            ->assertRedirect('/admin/front-desk');

        $this->assertNotNull($practice->refresh()->setup_completed_at);
    }

    public function test_seed_starter_defaults_command_seeds_empty_practice(): void
    {
        $practice = Practice::factory()->create(['practice_type' => PracticeType::GENERAL_WELLNESS]);
        User::factory()->create(['practice_id' => $practice->id, 'email' => 'starter-command@example.com']);

        $this->artisan('practiq:seed-starter-defaults', ['practice_id' => $practice->id])
            ->expectsOutput("Starter defaults checked for {$practice->name}.")
            ->expectsOutput('Created practitioner: yes')
            ->assertExitCode(0);

        $this->assertSame(1, Practitioner::withoutPracticeScope()->where('practice_id', $practice->id)->count());
        $this->assertSame(2, AppointmentType::withoutPracticeScope()->where('practice_id', $practice->id)->count());
    }

    public function test_registration_still_records_trial_signup(): void
    {
        Mail::fake();

        $this->post('/register', [
            'practice_name' => 'Starter Signup Clinic',
            'first_name' => 'Starter',
            'last_name' => 'Signup',
            'email' => 'starter-signup@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'practice_type' => PracticeType::GENERAL_WELLNESS,
            'terms_accepted' => true,
        ]);

        $practice = Practice::where('name', 'Starter Signup Clinic')->firstOrFail();
        $user = User::where('email', 'starter-signup@example.com')->firstOrFail();

        $this->assertSame(1, TrialSignup::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('user_id', $user->id)
            ->count());
    }

    private function assertChecklistItem(array $checklist, string $key, bool $complete): void
    {
        $item = collect($checklist['items'])->firstWhere('key', $key);

        $this->assertNotNull($item, "Checklist item [{$key}] was not found.");
        $this->assertSame($complete, $item['complete'], "Checklist item [{$key}] completion mismatch.");
    }
}
