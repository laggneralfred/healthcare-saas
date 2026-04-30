<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\PatientCommunication;
use App\Models\Practice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientCommunicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_communication_records_follow_up_draft_activity(): void
    {
        $practice = Practice::factory()->create();
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);
        $user = User::factory()->create(['practice_id' => $practice->id]);

        $communication = PatientCommunication::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'type' => PatientCommunication::TYPE_INVITE_BACK,
            'channel' => PatientCommunication::CHANNEL_PREVIEW,
            'language' => 'en',
            'subject' => 'Checking in',
            'body' => 'Draft body',
            'status' => PatientCommunication::STATUS_DRAFT,
            'created_by' => $user->id,
        ]);

        $this->assertSame('Invite Back', $communication->type_label);
        $this->assertSame('Draft', $communication->status_label);
        $this->assertTrue($communication->practice->is($practice));
        $this->assertTrue($communication->patient->is($patient));
        $this->assertTrue($communication->creator->is($user));
    }
}
