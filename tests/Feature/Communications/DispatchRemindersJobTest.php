<?php

namespace Tests\Feature\Communications;

use App\Jobs\DispatchAppointmentRemindersJob;
use App\Jobs\SendAppointmentReminderJob;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\CommunicationRule;
use App\Models\MessageLog;
use App\Models\MessageTemplate;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DispatchRemindersJobTest extends TestCase
{
    use RefreshDatabase;

    private function setup24hRule(): array
    {
        $practice        = Practice::factory()->create();
        $user            = User::factory()->create(['practice_id' => $practice->id]);
        $practitioner    = Practitioner::factory()->create(['practice_id' => $practice->id, 'user_id' => $user->id]);
        $appointmentType = AppointmentType::factory()->create(['practice_id' => $practice->id]);
        $patient         = Patient::factory()->create(['practice_id' => $practice->id, 'email' => 'p@test.com']);

        $template = MessageTemplate::withoutPracticeScope()->create([
            'practice_id'   => $practice->id, 'name' => 'R', 'channel' => 'email',
            'trigger_event' => 'reminder_24h', 'body' => 'Hi', 'is_active' => true, 'is_default' => false,
        ]);

        $rule = CommunicationRule::withoutPracticeScope()->create([
            'practice_id'            => $practice->id,
            'message_template_id'    => $template->id,
            'is_active'              => true,
            'send_at_offset_minutes' => -1440, // 24h before
        ]);

        return compact('practice', 'practitioner', 'appointmentType', 'patient', 'rule', 'template');
    }

    public function test_dispatches_job_for_appointment_in_window(): void
    {
        Queue::fake();
        $data = $this->setup24hRule();

        // Appointment is 24h from now — send window is now, so appt should be ~now+1440min
        Appointment::withoutPracticeScope()->create([
            'practice_id'         => $data['practice']->id,
            'patient_id'          => $data['patient']->id,
            'practitioner_id'     => $data['practitioner']->id,
            'appointment_type_id' => $data['appointmentType']->id,
            'status'              => 'scheduled',
            'start_datetime'      => now()->addMinutes(1440 + 7), // in the 15-min window
            'end_datetime'        => now()->addMinutes(1440 + 67),
        ]);

        (new DispatchAppointmentRemindersJob())->handle();

        Queue::assertPushed(SendAppointmentReminderJob::class);
    }

    public function test_does_not_dispatch_when_outside_window(): void
    {
        Queue::fake();
        $data = $this->setup24hRule();

        // Appointment is 48h away — way outside the 15-min window for a 24h rule
        Appointment::withoutPracticeScope()->create([
            'practice_id'         => $data['practice']->id,
            'patient_id'          => $data['patient']->id,
            'practitioner_id'     => $data['practitioner']->id,
            'appointment_type_id' => $data['appointmentType']->id,
            'status'              => 'scheduled',
            'start_datetime'      => now()->addHours(48),
            'end_datetime'        => now()->addHours(49),
        ]);

        (new DispatchAppointmentRemindersJob())->handle();

        Queue::assertNotPushed(SendAppointmentReminderJob::class);
    }

    public function test_does_not_send_duplicate(): void
    {
        Queue::fake();
        $data = $this->setup24hRule();

        $appointment = Appointment::withoutPracticeScope()->create([
            'practice_id'         => $data['practice']->id,
            'patient_id'          => $data['patient']->id,
            'practitioner_id'     => $data['practitioner']->id,
            'appointment_type_id' => $data['appointmentType']->id,
            'status'              => 'scheduled',
            'start_datetime'      => now()->addMinutes(1440 + 7),
            'end_datetime'        => now()->addMinutes(1440 + 67),
        ]);

        // Pre-existing log for this appointment + template
        MessageLog::withoutPracticeScope()->create([
            'practice_id'         => $data['practice']->id,
            'patient_id'          => $data['patient']->id,
            'appointment_id'      => $appointment->id,
            'message_template_id' => $data['template']->id,
            'channel'             => 'email',
            'recipient'           => 'p@test.com',
            'body'                => 'already sent',
            'status'              => 'sent',
        ]);

        (new DispatchAppointmentRemindersJob())->handle();

        Queue::assertNotPushed(SendAppointmentReminderJob::class);
    }
}
