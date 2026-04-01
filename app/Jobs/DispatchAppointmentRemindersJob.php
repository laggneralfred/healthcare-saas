<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Models\CommunicationRule;
use App\Models\MessageLog;
use App\Models\States\Appointment\Scheduled;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchAppointmentRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $windowStart = now();
        $windowEnd   = now()->addMinutes(15);

        $rules = CommunicationRule::withoutPracticeScope()
            ->with('messageTemplate')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->get();

        foreach ($rules as $rule) {
            $this->processRule($rule, $windowStart, $windowEnd);
        }
    }

    private function processRule(CommunicationRule $rule, $windowStart, $windowEnd): void
    {
        // Invert the offset: if send_time = appt_time + offset,
        // then appt_time = send_time - offset.
        $offset = $rule->send_at_offset_minutes;

        $apptWindowStart = $windowStart->copy()->subMinutes($offset);
        $apptWindowEnd   = $windowEnd->copy()->subMinutes($offset);

        $appointments = Appointment::withoutPracticeScope()
            ->where('practice_id', $rule->practice_id)
            ->whereBetween('start_datetime', [$apptWindowStart, $apptWindowEnd])
            ->whereIn('status', [Scheduled::$name, 'confirmed'])
            ->when($rule->practitioner_id, fn ($q) => $q->where('practitioner_id', $rule->practitioner_id))
            ->when($rule->appointment_type_id, fn ($q) => $q->where('appointment_type_id', $rule->appointment_type_id))
            ->get();

        foreach ($appointments as $appointment) {
            $alreadySent = MessageLog::withoutPracticeScope()
                ->where('appointment_id', $appointment->id)
                ->where('message_template_id', $rule->message_template_id)
                ->whereIn('status', ['pending', 'sent', 'delivered'])
                ->exists();

            if ($alreadySent) {
                continue;
            }

            SendAppointmentReminderJob::dispatch($appointment, $rule);
        }
    }
}
