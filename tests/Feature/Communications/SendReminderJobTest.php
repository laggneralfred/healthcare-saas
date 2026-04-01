<?php

namespace Tests\Feature\Communications;

use App\Jobs\SendAppointmentReminderJob;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\CommunicationRule;
use App\Models\MessageLog;
use App\Models\MessageTemplate;
use App\Models\Patient;
use App\Models\PatientCommunicationPreference;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendReminderJobTest extends TestCase
{
    use RefreshDatabase;

    private function makeAppointmentAndRule(array $patientOverrides = []): array
    {
        $practice        = Practice::factory()->create();
        $user            = User::factory()->create(['practice_id' => $practice->id]);
        $practitioner    = Practitioner::factory()->create(['practice_id' => $practice->id, 'user_id' => $user->id]);
        $appointmentType = AppointmentType::factory()->create(['practice_id' => $practice->id]);
        $patient         = Patient::factory()->create(array_merge(['practice_id' => $practice->id, 'email' => 'patient@test.com'], $patientOverrides));

        $template = MessageTemplate::withoutPracticeScope()->create([
            'practice_id'   => $practice->id,
            'name'          => 'Reminder',
            'channel'       => 'email',
            'trigger_event' => 'reminder_24h',
            'subject'       => 'Reminder for {{patient_name}}',
            'body'          => 'Hi {{patient_name}}, see you on {{appointment_date}}.',
            'is_active'     => true,
            'is_default'    => false,
        ]);

        $rule = CommunicationRule::withoutPracticeScope()->create([
            'practice_id'            => $practice->id,
            'message_template_id'    => $template->id,
            'is_active'              => true,
            'send_at_offset_minutes' => -1440,
        ]);

        $appointment = Appointment::withoutPracticeScope()->create([
            'practice_id'         => $practice->id,
            'patient_id'          => $patient->id,
            'practitioner_id'     => $practitioner->id,
            'appointment_type_id' => $appointmentType->id,
            'status'              => 'scheduled',
            'start_datetime'      => now()->addDay(),
            'end_datetime'        => now()->addDay()->addHour(),
        ]);

        return compact('appointment', 'rule', 'patient', 'practice');
    }

    public function test_creates_message_log_on_success(): void
    {
        Mail::fake();
        ['appointment' => $appointment, 'rule' => $rule] = $this->makeAppointmentAndRule();

        (new SendAppointmentReminderJob($appointment, $rule))->handle();

        $log = MessageLog::withoutPracticeScope()->first();
        $this->assertNotNull($log);
        $this->assertSame('sent', $log->status);
        $this->assertNotNull($log->sent_at);
    }

    public function test_marks_failed_on_mail_error(): void
    {
        Mail::fake();
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP error'));

        ['appointment' => $appointment, 'rule' => $rule] = $this->makeAppointmentAndRule();

        (new SendAppointmentReminderJob($appointment, $rule))->handle();

        $log = MessageLog::withoutPracticeScope()->first();
        $this->assertSame('failed', $log->status);
        $this->assertNotNull($log->failed_at);
        $this->assertStringContainsString('SMTP error', $log->failure_reason);
    }

    public function test_skips_opted_out_patients(): void
    {
        Mail::fake();
        ['appointment' => $appointment, 'rule' => $rule, 'patient' => $patient, 'practice' => $practice] = $this->makeAppointmentAndRule();

        PatientCommunicationPreference::withoutPracticeScope()->create([
            'practice_id'  => $practice->id,
            'patient_id'   => $patient->id,
            'email_opt_in' => false,
            'sms_opt_in'   => false,
            'opted_out_at' => now(),
        ]);

        (new SendAppointmentReminderJob($appointment, $rule))->handle();

        $log = MessageLog::withoutPracticeScope()->first();
        $this->assertSame('opted_out', $log->status);
        Mail::assertNothingSent();
    }

    public function test_skips_patients_without_email(): void
    {
        Mail::fake();
        ['appointment' => $appointment, 'rule' => $rule] = $this->makeAppointmentAndRule(['email' => null]);

        (new SendAppointmentReminderJob($appointment, $rule))->handle();

        $this->assertDatabaseCount('message_logs', 0);
        Mail::assertNothingSent();
    }
}
