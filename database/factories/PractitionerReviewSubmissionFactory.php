<?php

namespace Database\Factories;

use App\Models\Practice;
use App\Models\PractitionerReviewSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PractitionerReviewSubmission>
 */
class PractitionerReviewSubmissionFactory extends Factory
{
    protected $model = PractitionerReviewSubmission::class;

    public function definition(): array
    {
        return [
            'practice_id' => Practice::factory(),
            'user_id' => User::factory(),
            'practice_type' => 'Acupuncture',
            'clinic_size' => 'Solo practitioner',
            'current_systems' => ['Paper notes', 'Google Calendar'],
            'first_impression' => 'Clear enough to start.',
            'setup_clarity_rating' => 4,
            'setup_checklist_helpfulness' => 'Helpful',
            'confusing_setup_step' => 'Working hours',
            'website_links_feedback' => 'The links made sense.',
            'scheduling_preference' => 'Requests reviewed by staff',
            'online_forms_feedback' => 'Useful for intake.',
            'notes_workflow' => 'Simple notes fit best.',
            'ai_feedback' => 'Useful if reviewed.',
            'follow_up_feedback' => 'Helpful reminder workflow.',
            'pricing_feedback' => 'Reasonable for solo practice.',
            'subscription_blockers' => 'Need more confidence in setup.',
            'most_useful' => 'Appointment requests',
            'most_confusing' => 'Practitioner compatibility',
            'one_change' => 'Make setup faster.',
            'may_contact' => true,
            'contact_info' => 'reviewer@example.test',
            'discount_acknowledged' => true,
            'submitted_at' => now(),
        ];
    }
}
