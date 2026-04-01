<?php

namespace Tests\Feature\Communications;

use App\Models\MessageLog;
use App\Models\MessageTemplate;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTenancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_practice_a_cannot_see_practice_b_message_logs(): void
    {
        $practiceA = Practice::factory()->create();
        $practiceB = Practice::factory()->create();

        $userA    = User::factory()->create(['practice_id' => $practiceA->id]);
        $patientA = Patient::factory()->create(['practice_id' => $practiceA->id]);
        $patientB = Patient::factory()->create(['practice_id' => $practiceB->id]);

        // Create a log for practice B
        MessageLog::withoutPracticeScope()->create([
            'practice_id' => $practiceB->id,
            'patient_id'  => $patientB->id,
            'channel'     => 'email',
            'recipient'   => 'b@test.com',
            'body'        => 'Secret B',
            'status'      => 'sent',
        ]);

        // Query as practice A user — should see nothing from practice B
        $this->actingAs($userA);
        $logs = MessageLog::all();

        $this->assertCount(0, $logs);
    }

    public function test_practice_a_cannot_see_practice_b_templates(): void
    {
        $practiceA = Practice::factory()->create();
        $practiceB = Practice::factory()->create();
        $userA     = User::factory()->create(['practice_id' => $practiceA->id]);

        MessageTemplate::withoutPracticeScope()->create([
            'practice_id'   => $practiceB->id,
            'name'          => 'B Template',
            'channel'       => 'email',
            'trigger_event' => 'reminder_24h',
            'body'          => 'Secret',
            'is_active'     => true,
            'is_default'    => false,
        ]);

        $this->actingAs($userA);
        $templates = MessageTemplate::all();

        $this->assertCount(0, $templates);
    }

    public function test_patient_opt_out_is_respected(): void
    {
        // Covered in depth by SendReminderJobTest::test_skips_opted_out_patients
        // This test confirms the model helper works
        $practice = Practice::factory()->create();
        $patient  = Patient::factory()->create(['practice_id' => $practice->id]);

        $pref = \App\Models\PatientCommunicationPreference::withoutPracticeScope()->create([
            'practice_id'  => $practice->id,
            'patient_id'   => $patient->id,
            'email_opt_in' => false,
            'sms_opt_in'   => false,
            'opted_out_at' => now(),
        ]);

        $this->assertFalse($pref->canReceiveEmail());
        $this->assertFalse($pref->canReceiveSms());
        $this->assertTrue($pref->hasOptedOut());
    }
}
