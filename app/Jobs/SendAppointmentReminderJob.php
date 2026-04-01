<?php

namespace App\Jobs;

use App\Mail\AppointmentReminderMail;
use App\Models\Appointment;
use App\Models\CommunicationRule;
use App\Models\MessageLog;
use App\Models\PatientCommunicationPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAppointmentReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly Appointment $appointment,
        public readonly CommunicationRule $rule
    ) {}

    public function handle(): void
    {
        $appointment = $this->appointment->load([
            'patient',
            'practitioner.user',
            'appointmentType',
            'practice',
        ]);

        $patient = $appointment->patient;

        if (! $patient) {
            return;
        }

        // Check opt-out preference
        $prefs = PatientCommunicationPreference::withoutPracticeScope()
            ->where('practice_id', $appointment->practice_id)
            ->where('patient_id', $patient->id)
            ->first();

        if ($prefs && ! $prefs->canReceiveEmail()) {
            MessageLog::withoutPracticeScope()->create([
                'practice_id'         => $appointment->practice_id,
                'patient_id'          => $patient->id,
                'appointment_id'      => $appointment->id,
                'practitioner_id'     => $appointment->practitioner_id,
                'message_template_id' => $this->rule->message_template_id,
                'channel'             => 'email',
                'recipient'           => $patient->email ?? '',
                'subject'             => null,
                'body'                => '',
                'status'              => 'opted_out',
            ]);
            return;
        }

        if (empty($patient->email)) {
            return;
        }

        $template = $this->rule->messageTemplate;
        if (! $template) {
            return;
        }

        $variables = [
            'patient_name'      => $patient->name ?? trim("{$patient->first_name} {$patient->last_name}"),
            'appointment_date'  => $appointment->start_datetime->format('l, F j, Y'),
            'appointment_time'  => $appointment->start_datetime->format('g:i A'),
            'practitioner_name' => $appointment->practitioner?->user?->name ?? 'Your practitioner',
            'practice_name'     => $appointment->practice?->name ?? 'Practiq',
            'appointment_type'  => $appointment->appointmentType?->name ?? 'Appointment',
        ];

        $subject = $template->renderSubject($variables);
        $body    = $template->renderBody($variables);

        $log = MessageLog::withoutPracticeScope()->create([
            'practice_id'         => $appointment->practice_id,
            'patient_id'          => $patient->id,
            'appointment_id'      => $appointment->id,
            'practitioner_id'     => $appointment->practitioner_id,
            'message_template_id' => $template->id,
            'channel'             => 'email',
            'recipient'           => $patient->email,
            'subject'             => $subject,
            'body'                => $body,
            'status'              => 'pending',
        ]);

        try {
            Mail::to($patient->email)->send(new AppointmentReminderMail($log));

            $log->update([
                'status'  => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $log->update([
                'status'         => 'failed',
                'failed_at'      => now(),
                'failure_reason' => $e->getMessage(),
            ]);

            Log::error('SendAppointmentReminderJob failed', [
                'appointment_id' => $appointment->id,
                'rule_id'        => $this->rule->id,
                'patient_email'  => $patient->email,
                'error'          => $e->getMessage(),
            ]);
        }
    }
}
