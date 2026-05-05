<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentRequest;
use App\Models\AppointmentType;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\PractitionerTimeBlock;
use App\Models\PractitionerWorkingHour;
use App\Models\User;
use App\Services\Scheduling\AppointmentAvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentAvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_available_slots_are_generated_inside_working_hours_only(): void
    {
        [$practice, $appointmentType, $practitioner] = $this->availabilityContext();
        $this->workingHour($practice, $practitioner, 2, '09:00', '11:00');

        $slots = $this->service()->availableSlots(
            $practice,
            $appointmentType,
            $practitioner,
            Carbon::parse('2026-05-05 08:00', 'America/Los_Angeles'),
            Carbon::parse('2026-05-05 12:00', 'America/Los_Angeles'),
        );

        $this->assertTrue($slots->isNotEmpty());
        $this->assertTrue($slots->every(fn (array $slot): bool => $slot['starts_at']->gte(Carbon::parse('2026-05-05 09:00', 'America/Los_Angeles'))));
        $this->assertTrue($slots->every(fn (array $slot): bool => $slot['ends_at']->lte(Carbon::parse('2026-05-05 11:00', 'America/Los_Angeles'))));
        $this->assertFalse($slots->contains(fn (array $slot): bool => $slot['starts_at']->format('H:i') === '08:45'));
    }

    public function test_slots_overlapping_time_blocks_are_not_shown(): void
    {
        [$practice, $appointmentType, $practitioner] = $this->availabilityContext();
        $this->workingHour($practice, $practitioner, 2, '09:00', '12:00');
        PractitionerTimeBlock::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'practitioner_id' => $practitioner->id,
            'starts_at' => Carbon::parse('2026-05-05 10:00', 'America/Los_Angeles'),
            'ends_at' => Carbon::parse('2026-05-05 11:00', 'America/Los_Angeles'),
            'block_type' => PractitionerTimeBlock::TYPE_ADMIN,
            'reason' => 'Admin block',
        ]);

        $slots = $this->slotsForDay($practice, $appointmentType, $practitioner);

        $this->assertFalse($slots->contains(fn (array $slot): bool => $slot['starts_at']->lt(Carbon::parse('2026-05-05 11:00', 'America/Los_Angeles'))
            && $slot['ends_at']->gt(Carbon::parse('2026-05-05 10:00', 'America/Los_Angeles'))));
    }

    public function test_slots_overlapping_existing_busy_appointments_are_not_shown(): void
    {
        [$practice, $appointmentType, $practitioner] = $this->availabilityContext();
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);
        $this->workingHour($practice, $practitioner, 2, '09:00', '12:00');
        Appointment::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'appointment_type_id' => $appointmentType->id,
            'status' => 'scheduled',
            'start_datetime' => '2026-05-05 09:30:00',
            'end_datetime' => '2026-05-05 10:30:00',
        ]);

        $slots = $this->slotsForDay($practice, $appointmentType, $practitioner);

        $this->assertFalse($slots->contains(fn (array $slot): bool => $slot['starts_at']->lt(Carbon::parse('2026-05-05 10:30', 'America/Los_Angeles'))
            && $slot['ends_at']->gt(Carbon::parse('2026-05-05 09:30', 'America/Los_Angeles'))));
        $this->assertTrue($slots->contains(fn (array $slot): bool => $slot['starts_at']->format('H:i') === '10:30'));
    }

    public function test_request_with_practitioner_returns_only_that_practitioners_slots(): void
    {
        [$practice, $appointmentType, $practitioner] = $this->availabilityContext('Dr. Requested');
        $otherPractitioner = $this->practitionerFor($practice, 'Dr. Other');
        $this->attachType($practice, $otherPractitioner, $appointmentType);
        $this->workingHour($practice, $practitioner, 2, '09:00', '10:00');
        $this->workingHour($practice, $otherPractitioner, 2, '09:00', '10:00');
        $request = $this->appointmentRequestFor($practice, $appointmentType, $practitioner);

        $slots = $this->service()->availableSlotsForRequest(
            $request,
            Carbon::parse('2026-05-05 08:00', 'America/Los_Angeles'),
            Carbon::parse('2026-05-05 12:00', 'America/Los_Angeles'),
        );

        $this->assertTrue($slots->isNotEmpty());
        $this->assertTrue($slots->every(fn (array $slot): bool => $slot['practitioner_id'] === $practitioner->id));
        $this->assertTrue($slots->every(fn (array $slot): bool => $slot['practitioner_name'] === 'Dr. Requested'));
    }

    public function test_no_preference_request_returns_slots_across_valid_practitioners(): void
    {
        [$practice, $appointmentType, $firstPractitioner] = $this->availabilityContext('Dr. First');
        $secondPractitioner = $this->practitionerFor($practice, 'Dr. Second');
        $invalidPractitioner = $this->practitionerFor($practice, 'Dr. Invalid');
        $this->attachType($practice, $secondPractitioner, $appointmentType);
        $this->workingHour($practice, $firstPractitioner, 2, '09:00', '10:00');
        $this->workingHour($practice, $secondPractitioner, 2, '10:00', '11:00');
        $this->workingHour($practice, $invalidPractitioner, 2, '11:00', '12:00');
        $request = $this->appointmentRequestFor($practice, $appointmentType, null);

        $slots = $this->service()->availableSlotsForRequest(
            $request,
            Carbon::parse('2026-05-05 08:00', 'America/Los_Angeles'),
            Carbon::parse('2026-05-05 12:00', 'America/Los_Angeles'),
        );

        $this->assertContains($firstPractitioner->id, $slots->pluck('practitioner_id')->all());
        $this->assertContains($secondPractitioner->id, $slots->pluck('practitioner_id')->all());
        $this->assertNotContains($invalidPractitioner->id, $slots->pluck('practitioner_id')->all());
    }

    public function test_cross_practice_appointments_are_ignored(): void
    {
        [$practice, $appointmentType, $practitioner] = $this->availabilityContext();
        [$otherPractice, $otherAppointmentType, $otherPractitioner] = $this->availabilityContext('Dr. Other Practice');
        $patient = Patient::factory()->create(['practice_id' => $otherPractice->id]);
        $this->workingHour($practice, $practitioner, 2, '09:00', '11:00');
        Appointment::withoutPracticeScope()->create([
            'practice_id' => $otherPractice->id,
            'patient_id' => $patient->id,
            'practitioner_id' => $otherPractitioner->id,
            'appointment_type_id' => $otherAppointmentType->id,
            'status' => 'scheduled',
            'start_datetime' => '2026-05-05 09:00:00',
            'end_datetime' => '2026-05-05 10:00:00',
        ]);

        $slots = $this->slotsForDay($practice, $appointmentType, $practitioner);

        $this->assertTrue($slots->contains(fn (array $slot): bool => $slot['starts_at']->format('H:i') === '09:00'));
    }

    private function availabilityContext(string $name = 'Dr. Available'): array
    {
        $practice = Practice::factory()->create([
            'timezone' => 'America/Los_Angeles',
            'default_appointment_duration' => 60,
        ]);
        $appointmentType = AppointmentType::factory()->create([
            'practice_id' => $practice->id,
            'duration_minutes' => 60,
        ]);
        $practitioner = $this->practitionerFor($practice, $name);
        $this->attachType($practice, $practitioner, $appointmentType);

        return [$practice, $appointmentType, $practitioner];
    }

    private function practitionerFor(Practice $practice, string $name): Practitioner
    {
        $user = User::factory()->create([
            'practice_id' => $practice->id,
            'name' => $name,
        ]);
        $user->assignRole(User::ROLE_PRACTITIONER);

        return Practitioner::factory()->create([
            'practice_id' => $practice->id,
            'user_id' => $user->id,
            'is_active' => true,
        ]);
    }

    private function attachType(Practice $practice, Practitioner $practitioner, AppointmentType $appointmentType): void
    {
        $practitioner->appointmentTypes()->attach($appointmentType->id, [
            'practice_id' => $practice->id,
            'is_active' => true,
        ]);
    }

    private function workingHour(Practice $practice, Practitioner $practitioner, int $dayOfWeek, string $startTime, string $endTime): void
    {
        PractitionerWorkingHour::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'practitioner_id' => $practitioner->id,
            'day_of_week' => $dayOfWeek,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'is_active' => true,
        ]);
    }

    private function appointmentRequestFor(Practice $practice, AppointmentType $appointmentType, ?Practitioner $practitioner): AppointmentRequest
    {
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);

        return AppointmentRequest::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'token_hash' => hash('sha256', 'availability-' . uniqid()),
            'status' => AppointmentRequest::STATUS_PENDING,
            'appointment_type_id' => $appointmentType->id,
            'practitioner_id' => $practitioner?->id,
            'preferred_times' => 'Any morning',
            'submitted_at' => now(),
        ]);
    }

    private function slotsForDay(Practice $practice, AppointmentType $appointmentType, Practitioner $practitioner)
    {
        return $this->service()->availableSlots(
            $practice,
            $appointmentType,
            $practitioner,
            Carbon::parse('2026-05-05 08:00', 'America/Los_Angeles'),
            Carbon::parse('2026-05-05 12:00', 'America/Los_Angeles'),
        );
    }

    private function service(): AppointmentAvailabilityService
    {
        return app(AppointmentAvailabilityService::class);
    }
}
