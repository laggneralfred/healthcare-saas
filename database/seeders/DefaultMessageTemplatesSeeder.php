<?php

namespace Database\Seeders;

use App\Models\CommunicationRule;
use App\Models\MessageTemplate;
use App\Models\Practice;
use Illuminate\Database\Seeder;

class DefaultMessageTemplatesSeeder extends Seeder
{
    public function run(?Practice $practice = null): void
    {
        $practices = $practice ? collect([$practice]) : Practice::withoutPracticeScope()->get();

        foreach ($practices as $p) {
            $this->seedForPractice($p);
        }
    }

    public function seedForPractice(Practice $practice): void
    {
        $definitions = [
            [
                'trigger_event'  => 'appointment_booked',
                'name'           => 'Appointment Confirmation',
                'subject'        => 'Your appointment at {{practice_name}} is confirmed',
                'body'           => "Hi {{patient_name}},\n\nYour appointment has been confirmed.\n\nDate: {{appointment_date}}\nTime: {{appointment_time}}\nPractitioner: {{practitioner_name}}\nService: {{appointment_type}}\n\nLocation: {{practice_name}}\n\nIf you need to reschedule or cancel, please contact us as soon as possible.\n\nSee you soon,\n{{practice_name}}",
                'offset_minutes' => 0,
            ],
            [
                'trigger_event'  => 'reminder_48h',
                'name'           => '48-Hour Reminder',
                'subject'        => 'Reminder: Your appointment in 2 days — {{practice_name}}',
                'body'           => "Hi {{patient_name}},\n\nThis is a friendly reminder that you have an appointment in 2 days.\n\nDate: {{appointment_date}}\nTime: {{appointment_time}}\nPractitioner: {{practitioner_name}}\n\nPlease reply to this email if you need to reschedule.\n\nSee you soon,\n{{practice_name}}",
                'offset_minutes' => -2880,
            ],
            [
                'trigger_event'  => 'reminder_24h',
                'name'           => '24-Hour Reminder',
                'subject'        => 'Reminder: Your appointment tomorrow — {{practice_name}}',
                'body'           => "Hi {{patient_name}},\n\nA reminder that your appointment is tomorrow.\n\nDate: {{appointment_date}}\nTime: {{appointment_time}}\nPractitioner: {{practitioner_name}}\n\nSee you tomorrow,\n{{practice_name}}",
                'offset_minutes' => -1440,
            ],
            [
                'trigger_event'  => 'appointment_followup',
                'name'           => 'Follow-Up Thank You',
                'subject'        => 'Thank you for visiting {{practice_name}}',
                'body'           => "Hi {{patient_name}},\n\nThank you for your visit yesterday with {{practitioner_name}}.\n\nWe hope your treatment went well. If you have any questions or would like to schedule your next appointment, please don't hesitate to contact us.\n\nWarm regards,\n{{practice_name}}",
                'offset_minutes' => 1440,
            ],
            [
                'trigger_event'  => 'missed_appointment',
                'name'           => 'Missed Appointment',
                'subject'        => 'We missed you today — {{practice_name}}',
                'body'           => "Hi {{patient_name}},\n\nWe noticed you were unable to make your appointment today with {{practitioner_name}}.\n\nWe would love to reschedule at a time that works for you. Please contact us to book a new appointment.\n\n{{practice_name}}",
                'offset_minutes' => 120,
            ],
        ];

        foreach ($definitions as $def) {
            $offset = $def['offset_minutes'];

            $template = MessageTemplate::withoutPracticeScope()->firstOrCreate(
                [
                    'practice_id'   => $practice->id,
                    'trigger_event' => $def['trigger_event'],
                    'is_default'    => true,
                ],
                [
                    'practice_id'   => $practice->id,
                    'name'          => $def['name'],
                    'channel'       => 'email',
                    'trigger_event' => $def['trigger_event'],
                    'subject'       => $def['subject'],
                    'body'          => $def['body'],
                    'is_active'     => true,
                    'is_default'    => true,
                ]
            );

            CommunicationRule::withoutPracticeScope()->firstOrCreate(
                [
                    'practice_id'         => $practice->id,
                    'message_template_id' => $template->id,
                    'practitioner_id'     => null,
                    'appointment_type_id' => null,
                ],
                [
                    'practice_id'             => $practice->id,
                    'message_template_id'     => $template->id,
                    'is_active'               => true,
                    'send_at_offset_minutes'  => $offset,
                ]
            );
        }
    }
}
