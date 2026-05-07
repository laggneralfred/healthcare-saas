<?php

namespace Tests\Feature;

use App\Filament\Pages\FrontDeskDashboard;
use App\Filament\Pages\PracticeSetupChecklistPage;
use App\Models\AppointmentType;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\PractitionerWorkingHour;
use App\Models\User;
use App\Services\PracticeSetupChecklistService;
use App\Support\PracticeAccessRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PracticeSetupChecklistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        PracticeAccessRoles::ensureRoles();
    }

    public function test_checklist_marks_missing_setup_items_incomplete(): void
    {
        $practice = Practice::factory()->create([
            'name' => 'Trial Clinic',
            'timezone' => 'America/Los_Angeles',
            'slug' => '',
        ]);

        $checklist = app(PracticeSetupChecklistService::class)->forPractice($practice);

        $this->assertFalse($checklist['is_complete']);
        $this->assertSame(0, $checklist['complete_count']);
        $this->assertChecklistItem($checklist, 'practice_profile', false);
        $this->assertChecklistItem($checklist, 'active_practitioner', false);
        $this->assertChecklistItem($checklist, 'active_appointment_type', false);
        $this->assertChecklistItem($checklist, 'practitioner_appointment_type', false);
        $this->assertChecklistItem($checklist, 'working_hours', false);
        $this->assertChecklistItem($checklist, 'public_links', false);
        $this->assertSame(
            'Patients will not see visit types until practitioners are attached to treatment types.',
            collect($checklist['items'])->firstWhere('key', 'practitioner_appointment_type')['warning'],
        );
    }

    public function test_checklist_marks_complete_setup_ready(): void
    {
        [$practice, $practitioner, $appointmentType] = $this->readyPractice();

        $practitioner->appointmentTypes()->attach($appointmentType->id, [
            'practice_id' => $practice->id,
            'is_active' => true,
        ]);

        PractitionerWorkingHour::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'practitioner_id' => $practitioner->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '17:00',
            'is_active' => true,
        ]);

        $checklist = app(PracticeSetupChecklistService::class)->forPractice($practice);

        $this->assertTrue($checklist['is_complete']);
        $this->assertSame(6, $checklist['complete_count']);
        $this->assertChecklistItem($checklist, 'practice_profile', true);
        $this->assertChecklistItem($checklist, 'active_practitioner', true);
        $this->assertChecklistItem($checklist, 'active_appointment_type', true);
        $this->assertChecklistItem($checklist, 'practitioner_appointment_type', true);
        $this->assertChecklistItem($checklist, 'working_hours', true);
        $this->assertChecklistItem($checklist, 'public_links', true);
    }

    public function test_inactive_compatibility_does_not_count_as_ready(): void
    {
        [$practice, $practitioner, $appointmentType] = $this->readyPractice();

        $practitioner->appointmentTypes()->attach($appointmentType->id, [
            'practice_id' => $practice->id,
            'is_active' => false,
        ]);

        $checklist = app(PracticeSetupChecklistService::class)->forPractice($practice);

        $this->assertChecklistItem($checklist, 'practitioner_appointment_type', false);
    }

    public function test_checklist_is_visible_on_today_and_settings_page(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();

        $this->actingAs($admin);

        Livewire::test(FrontDeskDashboard::class)
            ->assertSee('Setup Checklist')
            ->assertSee('Patients will not see visit types until practitioners are attached to treatment types.')
            ->assertSee('Edit practice profile')
            ->assertSee('Manage practitioners');

        Livewire::test(PracticeSetupChecklistPage::class)
            ->assertSee('Setup Checklist')
            ->assertSee('Public website links')
            ->assertSee('View website links');
    }

    private function readyPractice(): array
    {
        $practice = Practice::factory()->create([
            'name' => 'Ready Clinic',
            'timezone' => 'America/Los_Angeles',
            'slug' => 'ready-clinic',
        ]);
        $practitioner = Practitioner::factory()->create([
            'practice_id' => $practice->id,
            'is_active' => true,
        ]);
        $appointmentType = AppointmentType::factory()->create([
            'practice_id' => $practice->id,
            'is_active' => true,
        ]);

        return [$practice, $practitioner, $appointmentType];
    }

    private function practiceWithAdmin(): array
    {
        $practice = Practice::factory()->create(['timezone' => 'UTC']);
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
