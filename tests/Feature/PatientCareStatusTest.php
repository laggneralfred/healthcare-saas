<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\States\Appointment\Cancelled;
use App\Models\States\Appointment\NoShow;
use App\Models\States\Appointment\Scheduled;
use App\Services\PatientCareStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PatientCareStatusTest extends TestCase
{
    use RefreshDatabase;

    private Practice $practice;
    private Practitioner $practitioner;
    private AppointmentType $appointmentType;
    private PatientCareStatusService $service;
    private Carbon $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->now = Carbon::parse('2026-04-30 10:00:00');
        Carbon::setTestNow($this->now);

        $this->practice = Practice::factory()->create();
        $this->practitioner = Practitioner::factory()->create(['practice_id' => $this->practice->id]);
        $this->appointmentType = AppointmentType::factory()->create(['practice_id' => $this->practice->id]);
        $this->service = app(PatientCareStatusService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_new_patient_without_completed_visits_is_new(): void
    {
        $patient = $this->patient();

        $status = $this->service->forPatient($patient, $this->now);

        $this->assertSame(PatientCareStatusService::STATUS_NEW, $status['key']);
        $this->assertSame('New', $status['label']);
    }

    public function test_patient_with_future_appointment_is_active(): void
    {
        $patient = $this->patient();

        $this->appointment($patient, [
            'status' => Scheduled::$name,
            'start_datetime' => $this->now->copy()->addDays(7),
            'end_datetime' => $this->now->copy()->addDays(7)->addHour(),
        ]);

        $status = $this->service->forPatient($patient, $this->now);

        $this->assertSame(PatientCareStatusService::STATUS_ACTIVE, $status['key']);
    }

    public function test_patient_with_recent_completed_visit_is_active(): void
    {
        $patient = $this->patient();

        $this->completedEncounter($patient, $this->now->copy()->subDays(10));

        $status = $this->service->forPatient($patient, $this->now);

        $this->assertSame(PatientCareStatusService::STATUS_ACTIVE, $status['key']);
    }

    public function test_patient_needs_follow_up_after_completed_visit_with_no_future_appointment(): void
    {
        $patient = $this->patient();

        $this->completedEncounter($patient, $this->now->copy()->subDays(35));

        $status = $this->service->forPatient($patient, $this->now);

        $this->assertSame(PatientCareStatusService::STATUS_NEEDS_FOLLOW_UP, $status['key']);
        $this->assertSame('Needs Follow-Up', $status['label']);
        $this->assertSame('warning', $status['color']);
    }

    public function test_patient_with_older_completed_visit_is_cooling(): void
    {
        $patient = $this->patient();

        $this->completedEncounter($patient, $this->now->copy()->subDays(60));

        $status = $this->service->forPatient($patient, $this->now);

        $this->assertSame(PatientCareStatusService::STATUS_COOLING, $status['key']);
    }

    public function test_patient_with_very_old_completed_visit_is_inactive(): void
    {
        $patient = $this->patient();

        $this->completedEncounter($patient, $this->now->copy()->subDays(120));

        $status = $this->service->forPatient($patient, $this->now);

        $this->assertSame(PatientCareStatusService::STATUS_INACTIVE, $status['key']);
    }

    public function test_patient_is_at_risk_after_recent_cancelled_or_no_show_appointment_with_no_future_appointment(): void
    {
        $cancelledPatient = $this->patient();
        $noShowPatient = $this->patient();

        $this->appointment($cancelledPatient, [
            'status' => Cancelled::$name,
            'start_datetime' => $this->now->copy()->subDays(3),
            'end_datetime' => $this->now->copy()->subDays(3)->addHour(),
        ]);

        $this->appointment($noShowPatient, [
            'status' => NoShow::$name,
            'start_datetime' => $this->now->copy()->subDays(2),
            'end_datetime' => $this->now->copy()->subDays(2)->addHour(),
        ]);

        $this->assertSame(
            PatientCareStatusService::STATUS_AT_RISK,
            $this->service->forPatient($cancelledPatient, $this->now)['key']
        );

        $this->assertSame(
            PatientCareStatusService::STATUS_AT_RISK,
            $this->service->forPatient($noShowPatient, $this->now)['key']
        );
    }

    public function test_future_appointment_overrides_follow_up_or_cooling_status(): void
    {
        $patient = $this->patient();

        $this->completedEncounter($patient, $this->now->copy()->subDays(60));
        $this->appointment($patient, [
            'status' => Scheduled::$name,
            'start_datetime' => $this->now->copy()->addDays(3),
            'end_datetime' => $this->now->copy()->addDays(3)->addHour(),
        ]);

        $status = $this->service->forPatient($patient, $this->now);

        $this->assertSame(PatientCareStatusService::STATUS_ACTIVE, $status['key']);
    }

    public function test_status_options_are_available_for_later_filters(): void
    {
        $this->assertSame([
            PatientCareStatusService::STATUS_NEW => 'New',
            PatientCareStatusService::STATUS_ACTIVE => 'Active',
            PatientCareStatusService::STATUS_NEEDS_FOLLOW_UP => 'Needs Follow-Up',
            PatientCareStatusService::STATUS_COOLING => 'Cooling',
            PatientCareStatusService::STATUS_INACTIVE => 'Inactive',
            PatientCareStatusService::STATUS_AT_RISK => 'At Risk',
        ], $this->service->options());

        $this->assertCount(6, $this->service->all());
    }

    private function patient(): Patient
    {
        return Patient::factory()->create(['practice_id' => $this->practice->id]);
    }

    private function appointment(Patient $patient, array $attributes): Appointment
    {
        return Appointment::factory()->create([
            'practice_id' => $this->practice->id,
            'patient_id' => $patient->id,
            'practitioner_id' => $this->practitioner->id,
            'appointment_type_id' => $this->appointmentType->id,
            ...$attributes,
        ]);
    }

    private function completedEncounter(Patient $patient, Carbon $completedAt): Encounter
    {
        return Encounter::factory()->create([
            'practice_id' => $this->practice->id,
            'patient_id' => $patient->id,
            'appointment_id' => null,
            'practitioner_id' => $this->practitioner->id,
            'status' => 'complete',
            'visit_date' => $completedAt->toDateString(),
            'completed_on' => $completedAt,
        ]);
    }
}
