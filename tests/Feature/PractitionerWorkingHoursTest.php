<?php

namespace Tests\Feature;

use App\Filament\Resources\Appointments\Pages\CreateAppointment;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Patient;
use App\Filament\Resources\Practitioners\PractitionerResource;
use App\Filament\Resources\Practitioners\RelationManagers\TimeBlocksRelationManager;
use App\Filament\Resources\Practitioners\RelationManagers\WorkingHoursRelationManager;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\PractitionerTimeBlock;
use App\Models\PractitionerWorkingHour;
use App\Models\User;
use App\Services\Scheduling\PractitionerScheduleService;
use App\Support\PracticeAccessRoles;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PractitionerWorkingHoursTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        PracticeAccessRoles::ensureRoles();
    }

    public function test_staff_can_manage_practitioner_schedule_from_practitioner_edit_page(): void
    {
        [$practice, $admin, $practitioner] = $this->practiceAdminAndPractitioner();

        $this->actingAs($admin)
            ->get(PractitionerResource::getUrl('edit', ['record' => $practitioner]))
            ->assertOk();

        $this->assertContains(WorkingHoursRelationManager::class, PractitionerResource::getRelations());
        $this->assertContains(TimeBlocksRelationManager::class, PractitionerResource::getRelations());

        $workingHour = PractitionerWorkingHour::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'practitioner_id' => $practitioner->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '13:00',
            'is_active' => true,
        ]);

        $timeBlock = PractitionerTimeBlock::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'practitioner_id' => $practitioner->id,
            'starts_at' => Carbon::parse('2026-05-04 11:00', 'America/Los_Angeles'),
            'ends_at' => Carbon::parse('2026-05-04 12:00', 'America/Los_Angeles'),
            'block_type' => PractitionerTimeBlock::TYPE_LUNCH,
            'reason' => 'Lunch',
        ]);

        $this->assertSame($practitioner->id, $workingHour->practitioner_id);
        $this->assertSame($practice->id, $workingHour->practice_id);
        $this->assertSame($practitioner->id, $timeBlock->practitioner_id);
        $this->assertSame($practice->id, $timeBlock->practice_id);
    }

    public function test_validation_rejects_invalid_working_hour_ranges(): void
    {
        [$practice, , $practitioner] = $this->practiceAdminAndPractitioner();

        $this->expectException(ValidationException::class);

        PractitionerWorkingHour::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'practitioner_id' => $practitioner->id,
            'day_of_week' => 1,
            'start_time' => '13:00',
            'end_time' => '09:00',
            'is_active' => true,
        ]);
    }

    public function test_validation_rejects_invalid_time_block_ranges(): void
    {
        [$practice, , $practitioner] = $this->practiceAdminAndPractitioner();

        $this->expectException(ValidationException::class);

        PractitionerTimeBlock::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'practitioner_id' => $practitioner->id,
            'starts_at' => Carbon::parse('2026-05-04 12:00', 'America/Los_Angeles'),
            'ends_at' => Carbon::parse('2026-05-04 11:00', 'America/Los_Angeles'),
            'block_type' => PractitionerTimeBlock::TYPE_UNAVAILABLE,
        ]);
    }

    public function test_working_windows_for_date_returns_active_windows_in_practice_timezone(): void
    {
        [$practice, , $practitioner] = $this->practiceAdminAndPractitioner('America/Los_Angeles');
        PractitionerWorkingHour::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'practitioner_id' => $practitioner->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '13:00',
            'is_active' => true,
        ]);
        PractitionerWorkingHour::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'practitioner_id' => $practitioner->id,
            'day_of_week' => 1,
            'start_time' => '14:00',
            'end_time' => '16:00',
            'is_active' => false,
        ]);

        $windows = app(PractitionerScheduleService::class)
            ->workingWindowsForDate($practitioner, Carbon::parse('2026-05-04 08:00', 'UTC'));

        $this->assertCount(1, $windows);
        $this->assertSame('America/Los_Angeles', $windows->first()['start']->timezoneName);
        $this->assertSame('2026-05-04 09:00', $windows->first()['start']->format('Y-m-d H:i'));
        $this->assertSame('2026-05-04 13:00', $windows->first()['end']->format('Y-m-d H:i'));
    }

    public function test_is_working_at_respects_working_hours_and_time_blocks(): void
    {
        [$practice, , $practitioner] = $this->practiceAdminAndPractitioner('America/Los_Angeles');
        PractitionerWorkingHour::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'practitioner_id' => $practitioner->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '13:00',
            'is_active' => true,
        ]);
        PractitionerTimeBlock::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'practitioner_id' => $practitioner->id,
            'starts_at' => Carbon::parse('2026-05-04 11:00', 'America/Los_Angeles'),
            'ends_at' => Carbon::parse('2026-05-04 12:00', 'America/Los_Angeles'),
            'block_type' => PractitionerTimeBlock::TYPE_LUNCH,
            'reason' => 'Lunch',
        ]);

        $service = app(PractitionerScheduleService::class);

        $this->assertTrue($service->isWorkingAt($practitioner, Carbon::parse('2026-05-04 10:00', 'America/Los_Angeles')));
        $this->assertFalse($service->isWorkingAt($practitioner, Carbon::parse('2026-05-04 08:59', 'America/Los_Angeles')));
        $this->assertFalse($service->isWorkingAt($practitioner, Carbon::parse('2026-05-04 11:30', 'America/Los_Angeles')));
        $this->assertFalse($service->isWorkingAt($practitioner, Carbon::parse('2026-05-04 13:00', 'America/Los_Angeles')));
    }

    public function test_is_working_for_range_respects_working_hours_and_time_blocks(): void
    {
        [$practice, , $practitioner] = $this->practiceAdminAndPractitioner('America/Los_Angeles');
        $this->workingHour($practice, $practitioner, 1, '09:00', '13:00');
        PractitionerTimeBlock::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'practitioner_id' => $practitioner->id,
            'starts_at' => Carbon::parse('2026-05-04 11:00', 'America/Los_Angeles'),
            'ends_at' => Carbon::parse('2026-05-04 12:00', 'America/Los_Angeles'),
            'block_type' => PractitionerTimeBlock::TYPE_LUNCH,
            'reason' => 'Lunch',
        ]);

        $service = app(PractitionerScheduleService::class);

        $this->assertTrue($service->isWorkingForRange(
            $practitioner,
            Carbon::parse('2026-05-04 09:00', 'America/Los_Angeles'),
            Carbon::parse('2026-05-04 10:00', 'America/Los_Angeles'),
        ));
        $this->assertFalse($service->isWorkingForRange(
            $practitioner,
            Carbon::parse('2026-05-04 08:30', 'America/Los_Angeles'),
            Carbon::parse('2026-05-04 09:30', 'America/Los_Angeles'),
        ));
        $this->assertFalse($service->isWorkingForRange(
            $practitioner,
            Carbon::parse('2026-05-04 10:30', 'America/Los_Angeles'),
            Carbon::parse('2026-05-04 11:30', 'America/Los_Angeles'),
        ));
    }

    public function test_appointment_creation_outside_working_hours_is_rejected(): void
    {
        [$practice, $admin, $practitioner] = $this->practiceAdminAndPractitioner('America/Los_Angeles');
        $this->workingHour($practice, $practitioner, 1, '09:00', '13:00');

        $this->actingAs($admin);

        Livewire::test(CreateAppointment::class)
            ->fillForm($this->appointmentFormData($practice, $practitioner, '2026-05-04 08:30:00', '2026-05-04 09:30:00'))
            ->call('create')
            ->assertHasErrors(['start_datetime']);

        $this->assertSame(0, Appointment::withoutPracticeScope()->where('practice_id', $practice->id)->count());
    }

    public function test_appointment_creation_inside_time_block_is_rejected(): void
    {
        [$practice, $admin, $practitioner] = $this->practiceAdminAndPractitioner('America/Los_Angeles');
        $this->workingHour($practice, $practitioner, 1, '09:00', '13:00');
        PractitionerTimeBlock::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'practitioner_id' => $practitioner->id,
            'starts_at' => Carbon::parse('2026-05-04 11:00', 'America/Los_Angeles'),
            'ends_at' => Carbon::parse('2026-05-04 12:00', 'America/Los_Angeles'),
            'block_type' => PractitionerTimeBlock::TYPE_ADMIN,
            'reason' => 'Staff meeting',
        ]);

        $this->actingAs($admin);

        Livewire::test(CreateAppointment::class)
            ->fillForm($this->appointmentFormData($practice, $practitioner, '2026-05-04 10:30:00', '2026-05-04 11:30:00'))
            ->call('create')
            ->assertHasErrors(['start_datetime']);

        $this->assertSame(0, Appointment::withoutPracticeScope()->where('practice_id', $practice->id)->count());
    }

    public function test_valid_working_hour_appointment_creation_succeeds(): void
    {
        [$practice, $admin, $practitioner] = $this->practiceAdminAndPractitioner('America/Los_Angeles');
        $this->workingHour($practice, $practitioner, 1, '09:00', '13:00');

        $this->actingAs($admin);

        Livewire::test(CreateAppointment::class)
            ->fillForm($this->appointmentFormData($practice, $practitioner, '2026-05-04 09:30:00', '2026-05-04 10:30:00'))
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('appointments', [
            'practice_id' => $practice->id,
            'practitioner_id' => $practitioner->id,
            'start_datetime' => '2026-05-04 09:30:00',
            'end_datetime' => '2026-05-04 10:30:00',
        ]);
    }

    public function test_blocks_for_range_returns_overlapping_blocks(): void
    {
        [$practice, , $practitioner] = $this->practiceAdminAndPractitioner();
        $overlapping = PractitionerTimeBlock::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'practitioner_id' => $practitioner->id,
            'starts_at' => Carbon::parse('2026-05-04 11:00', 'America/Los_Angeles'),
            'ends_at' => Carbon::parse('2026-05-04 12:00', 'America/Los_Angeles'),
            'block_type' => PractitionerTimeBlock::TYPE_ADMIN,
            'reason' => 'Charting',
        ]);
        PractitionerTimeBlock::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'practitioner_id' => $practitioner->id,
            'starts_at' => Carbon::parse('2026-05-04 14:00', 'America/Los_Angeles'),
            'ends_at' => Carbon::parse('2026-05-04 15:00', 'America/Los_Angeles'),
            'block_type' => PractitionerTimeBlock::TYPE_ADMIN,
            'reason' => 'Meeting',
        ]);

        $blocks = app(PractitionerScheduleService::class)->blocksForRange(
            $practitioner,
            Carbon::parse('2026-05-04 10:30', 'America/Los_Angeles'),
            Carbon::parse('2026-05-04 11:30', 'America/Los_Angeles'),
        );

        $this->assertTrue($blocks->contains($overlapping));
        $this->assertCount(1, $blocks);
    }

    public function test_cross_practice_staff_cannot_manage_another_practice_schedule(): void
    {
        [, $admin] = $this->practiceAdminAndPractitioner();
        [$otherPractice, , $otherPractitioner] = $this->practiceAdminAndPractitioner();

        $this->actingAs($admin)
            ->get(PractitionerResource::getUrl('edit', ['record' => $otherPractitioner]))
            ->assertNotFound();

        $this->expectException(ValidationException::class);

        PractitionerWorkingHour::withoutPracticeScope()->create([
            'practice_id' => $otherPractice->id,
            'practitioner_id' => Practitioner::withoutPracticeScope()
                ->where('practice_id', '!=', $otherPractice->id)
                ->firstOrFail()
                ->id,
            'day_of_week' => 1,
            'start_time' => '09:00',
            'end_time' => '13:00',
            'is_active' => true,
        ]);
    }

    private function practiceAdminAndPractitioner(string $timezone = 'America/Los_Angeles'): array
    {
        $practice = Practice::factory()->create(['timezone' => $timezone]);
        $admin = User::factory()->create(['practice_id' => $practice->id]);
        PracticeAccessRoles::assignOwner($admin);

        $practitionerUser = User::factory()->create([
            'practice_id' => $practice->id,
            'name' => 'Dr. Schedule',
        ]);
        $practitionerUser->assignRole(User::ROLE_PRACTITIONER);

        $practitioner = Practitioner::factory()->create([
            'practice_id' => $practice->id,
            'user_id' => $practitionerUser->id,
            'is_active' => true,
        ]);

        return [$practice, $admin, $practitioner];
    }

    private function workingHour(Practice $practice, Practitioner $practitioner, int $dayOfWeek, string $startTime, string $endTime): PractitionerWorkingHour
    {
        return PractitionerWorkingHour::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'practitioner_id' => $practitioner->id,
            'day_of_week' => $dayOfWeek,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'is_active' => true,
        ]);
    }

    private function appointmentFormData(Practice $practice, Practitioner $practitioner, string $start, string $end): array
    {
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);
        $appointmentType = AppointmentType::factory()->create([
            'practice_id' => $practice->id,
            'duration_minutes' => 60,
        ]);

        return [
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'appointment_type_id' => $appointmentType->id,
            'start_datetime' => $start,
            'end_datetime' => $end,
        ];
    }
}
