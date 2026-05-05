<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\User;
use App\Services\Scheduling\AppointmentRequestOptionsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentRequestOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_portal_options_include_only_active_same_practice_attached_appointment_types(): void
    {
        $practice = Practice::factory()->create();
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);
        [$validType] = $this->appointmentTypeWithPractitioner($practice, 'Follow-Up Visit', 'Dr. Valid');
        AppointmentType::factory()->create([
            'practice_id' => $practice->id,
            'name' => 'Inactive Visit',
            'is_active' => false,
        ]);
        AppointmentType::factory()->create([
            'practice_id' => $practice->id,
            'name' => 'Unstaffed Visit',
            'is_active' => true,
        ]);
        $otherPractice = Practice::factory()->create();
        $this->appointmentTypeWithPractitioner($otherPractice, 'Other Practice Visit', 'Other Provider');

        $types = app(AppointmentRequestOptionsService::class)->appointmentTypesForPortal($practice, $patient);

        $this->assertTrue($types->contains($validType));
        $this->assertSame(['Follow-Up Visit'], $types->pluck('name')->all());
    }

    public function test_practitioners_for_appointment_type_include_only_active_attached_same_practice_providers(): void
    {
        $practice = Practice::factory()->create();
        [$appointmentType, $validPractitioner] = $this->appointmentTypeWithPractitioner($practice, 'Massage Therapy', 'Morgan Massage');
        $inactivePractitioner = $this->practitioner($practice, 'Inactive Provider', false);
        $inactivePractitioner->appointmentTypes()->attach($appointmentType->id, [
            'practice_id' => $practice->id,
            'is_active' => true,
        ]);
        $inactivePivot = $this->practitioner($practice, 'Inactive Link', true);
        $inactivePivot->appointmentTypes()->attach($appointmentType->id, [
            'practice_id' => $practice->id,
            'is_active' => false,
        ]);

        $practitioners = app(AppointmentRequestOptionsService::class)->practitionersForAppointmentType($appointmentType);

        $this->assertTrue($practitioners->contains($validPractitioner));
        $this->assertSame(['Morgan Massage'], $practitioners->map(fn (Practitioner $practitioner) => $practitioner->user->name)->all());
    }

    public function test_appointment_types_for_practitioner_include_only_attached_active_types(): void
    {
        $practice = Practice::factory()->create();
        [$validType, $practitioner] = $this->appointmentTypeWithPractitioner($practice, 'Five Element Follow-Up', 'Dr. Five');
        $inactiveType = AppointmentType::factory()->create([
            'practice_id' => $practice->id,
            'name' => 'Inactive Type',
            'is_active' => false,
        ]);
        $practitioner->appointmentTypes()->attach($inactiveType->id, [
            'practice_id' => $practice->id,
            'is_active' => true,
        ]);

        $types = app(AppointmentRequestOptionsService::class)->appointmentTypesForPractitioner($practitioner);

        $this->assertTrue($types->contains($validType));
        $this->assertSame(['Five Element Follow-Up'], $types->pluck('name')->all());
    }

    public function test_suggested_practitioner_requires_prior_provider_to_offer_selected_type(): void
    {
        $practice = Practice::factory()->create();
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);
        [$selectedType, $validPractitioner] = $this->appointmentTypeWithPractitioner($practice, 'Follow-Up Acupuncture', 'Dr. Valid');
        [, $invalidForSelectedType] = $this->appointmentTypeWithPractitioner($practice, 'Massage Therapy', 'Morgan Massage');
        Appointment::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'appointment_type_id' => $selectedType->id,
            'practitioner_id' => $invalidForSelectedType->id,
            'start_datetime' => now()->subDay(),
        ]);

        $this->assertNull(app(AppointmentRequestOptionsService::class)->suggestedPractitioner($patient, $selectedType));

        Appointment::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'appointment_type_id' => $selectedType->id,
            'practitioner_id' => $validPractitioner->id,
            'start_datetime' => now(),
        ]);

        $this->assertTrue(app(AppointmentRequestOptionsService::class)
            ->suggestedPractitioner($patient, $selectedType)
            ->is($validPractitioner));
    }

    private function appointmentTypeWithPractitioner(Practice $practice, string $typeName, string $practitionerName): array
    {
        $appointmentType = AppointmentType::factory()->create([
            'practice_id' => $practice->id,
            'name' => $typeName,
            'is_active' => true,
        ]);
        $practitioner = $this->practitioner($practice, $practitionerName);
        $practitioner->appointmentTypes()->attach($appointmentType->id, [
            'practice_id' => $practice->id,
            'is_active' => true,
        ]);

        return [$appointmentType, $practitioner];
    }

    private function practitioner(Practice $practice, string $name, bool $active = true): Practitioner
    {
        $user = User::factory()->create([
            'practice_id' => $practice->id,
            'name' => $name,
        ]);

        return Practitioner::factory()->create([
            'practice_id' => $practice->id,
            'user_id' => $user->id,
            'is_active' => $active,
        ]);
    }
}
