<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPractice;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PractitionerReviewSubmission extends Model
{
    use BelongsToPractice;
    use HasFactory;

    protected $fillable = [
        'practice_id',
        'user_id',
        'practice_type',
        'clinic_size',
        'current_systems',
        'first_impression',
        'setup_clarity_rating',
        'setup_checklist_helpfulness',
        'confusing_setup_step',
        'website_links_feedback',
        'scheduling_preference',
        'online_forms_feedback',
        'notes_workflow',
        'ai_feedback',
        'follow_up_feedback',
        'pricing_feedback',
        'subscription_blockers',
        'most_useful',
        'most_confusing',
        'one_change',
        'may_contact',
        'contact_info',
        'discount_acknowledged',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'current_systems' => 'array',
            'setup_clarity_rating' => 'integer',
            'may_contact' => 'boolean',
            'discount_acknowledged' => 'boolean',
            'submitted_at' => 'datetime',
        ];
    }

    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
