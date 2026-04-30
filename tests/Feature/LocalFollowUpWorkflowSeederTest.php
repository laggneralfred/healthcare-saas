<?php

namespace Tests\Feature;

use App\Models\AppointmentRequest;
use App\Models\Patient;
use App\Models\Practice;
use App\Services\PatientCareStatusService;
use Database\Seeders\LocalFollowUpWorkflowSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class LocalFollowUpWorkflowSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_follow_up_workflow_seed_command_creates_demo_records(): void
    {
        $exitCode = Artisan::call('demo:seed-follow-up-workflow', [
            '--base-url' => 'http://127.0.0.1:8002',
        ]);

        $this->assertSame(0, $exitCode);

        $practice = Practice::query()->where('name', LocalFollowUpWorkflowSeeder::PRACTICE_NAME)->firstOrFail();

        $this->assertDatabaseHas('users', [
            'email' => LocalFollowUpWorkflowSeeder::ADMIN_EMAIL,
            'practice_id' => $practice->id,
        ]);

        $seededPatients = Patient::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->pluck('email', 'name');

        $this->assertSame(LocalFollowUpWorkflowSeeder::TEST_EMAIL, $seededPatients['English Followup Patient']);
        $this->assertSame(LocalFollowUpWorkflowSeeder::TEST_EMAIL, $seededPatients['Spanish Followup Patient']);
        $this->assertNull($seededPatients['No Email Test Patient']);

        Patient::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('name', '!=', 'No Email Test Patient')
            ->each(function (Patient $patient): void {
                $this->assertSame(LocalFollowUpWorkflowSeeder::TEST_EMAIL, $patient->email);
            });

        $this->assertSame(2, AppointmentRequest::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('status', AppointmentRequest::STATUS_PENDING)
            ->count());

        $statuses = Patient::withoutPracticeScope()
            ->with(['appointments', 'encounters'])
            ->where('practice_id', $practice->id)
            ->get()
            ->mapWithKeys(fn (Patient $patient): array => [
                $patient->name => app(PatientCareStatusService::class)->forPatient($patient)['key'],
            ]);

        $this->assertSame(PatientCareStatusService::STATUS_NEEDS_FOLLOW_UP, $statuses['English Followup Patient']);
        $this->assertSame(PatientCareStatusService::STATUS_COOLING, $statuses['Chinese Translation Patient']);
        $this->assertSame(PatientCareStatusService::STATUS_INACTIVE, $statuses['Inactive Patient']);
        $this->assertSame(PatientCareStatusService::STATUS_AT_RISK, $statuses['At Risk No-Show Patient']);
    }
}
