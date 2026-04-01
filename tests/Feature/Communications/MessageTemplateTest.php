<?php

namespace Tests\Feature\Communications;

use App\Models\MessageTemplate;
use App\Models\Practice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_body_variables(): void
    {
        $practice = Practice::factory()->create();
        $template = MessageTemplate::withoutPracticeScope()->create([
            'practice_id'   => $practice->id,
            'name'          => 'Test',
            'channel'       => 'email',
            'trigger_event' => 'reminder_24h',
            'subject'       => 'Hi {{patient_name}}',
            'body'          => "Hi {{patient_name}},\nYour appointment is on {{appointment_date}} at {{appointment_time}} with {{practitioner_name}} at {{practice_name}}.",
            'is_active'     => true,
            'is_default'    => false,
        ]);

        $vars = [
            'patient_name'      => 'Jane Doe',
            'appointment_date'  => 'Monday, April 7, 2026',
            'appointment_time'  => '2:00 PM',
            'practitioner_name' => 'Dr. Smith',
            'practice_name'     => 'Wellness Clinic',
            'appointment_type'  => 'Follow-up',
        ];

        $body = $template->renderBody($vars);

        $this->assertStringContainsString('Jane Doe', $body);
        $this->assertStringContainsString('Monday, April 7, 2026', $body);
        $this->assertStringContainsString('Dr. Smith', $body);
        $this->assertStringNotContainsString('{{patient_name}}', $body);
    }

    public function test_renders_subject_variables(): void
    {
        $practice = Practice::factory()->create();
        $template = MessageTemplate::withoutPracticeScope()->create([
            'practice_id'   => $practice->id,
            'name'          => 'Test',
            'channel'       => 'email',
            'trigger_event' => 'reminder_24h',
            'subject'       => 'Reminder from {{practice_name}}',
            'body'          => 'Body',
            'is_active'     => true,
            'is_default'    => false,
        ]);

        $subject = $template->renderSubject(['practice_name' => 'Serenity Clinic']);

        $this->assertSame('Reminder from Serenity Clinic', $subject);
    }

    public function test_communication_rule_calculates_send_time(): void
    {
        $practice = Practice::factory()->create();
        $template = MessageTemplate::withoutPracticeScope()->create([
            'practice_id'   => $practice->id,
            'name'          => 'T',
            'channel'       => 'email',
            'trigger_event' => 'reminder_48h',
            'body'          => 'Body',
            'is_active'     => true,
            'is_default'    => false,
        ]);

        $rule = \App\Models\CommunicationRule::withoutPracticeScope()->create([
            'practice_id'            => $practice->id,
            'message_template_id'    => $template->id,
            'is_active'              => true,
            'send_at_offset_minutes' => -2880,
        ]);

        $apptTime = now()->addDays(3);
        $sendAt   = $rule->getSendAtDateTime($apptTime);

        $this->assertEquals(
            $apptTime->copy()->subMinutes(2880)->timestamp,
            $sendAt->timestamp
        );
    }

    public function test_timing_description_formats_correctly(): void
    {
        $practice = Practice::factory()->create();
        $template = MessageTemplate::withoutPracticeScope()->create([
            'practice_id' => $practice->id, 'name' => 'T', 'channel' => 'email',
            'trigger_event' => 'custom', 'body' => 'B', 'is_active' => true, 'is_default' => false,
        ]);

        $cases = [
            [0, 'At booking'],
            [-120, '2 hours before'],
            [-1440, '1 day before'],
            [-2880, '2 days before'],
            [-10080, '1 week before'],
            [1440, '1 day after'],
        ];

        foreach ($cases as [$offset, $expected]) {
            $rule = \App\Models\CommunicationRule::withoutPracticeScope()->make([
                'practice_id'            => $practice->id,
                'message_template_id'    => $template->id,
                'send_at_offset_minutes' => $offset,
            ]);
            $this->assertSame($expected, $rule->getTimingDescription(), "Offset {$offset}");
        }
    }
}
