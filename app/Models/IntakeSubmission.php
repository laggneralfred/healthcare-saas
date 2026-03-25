<?php

namespace App\Models;

use App\Traits\HasAccessToken;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntakeSubmission extends Model
{
    use HasFactory, HasAccessToken;

    protected $fillable = [
        'practice_id', 'patient_id', 'appointment_id',
        'status', 'submitted_on', 'access_token',
        'reason_for_visit', 'current_concerns', 'relevant_history',
        'medications', 'notes', 'summary_text',
    ];

    protected function casts(): array
    {
        return ['submitted_on' => 'datetime'];
    }

    public function isComplete(): bool
    {
        return $this->status === 'complete';
    }

    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
