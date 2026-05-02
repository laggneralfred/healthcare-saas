<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPractice;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientPortalToken extends Model
{
    use BelongsToPractice, HasFactory;

    public const PURPOSE_EXISTING_PATIENT_PORTAL = 'existing_patient_portal';
    public const PURPOSE_NEW_PATIENT_FORM = 'new_patient_form';
    public const PURPOSE_EXISTING_PATIENT_FORM = 'existing_patient_form';

    protected $fillable = [
        'practice_id',
        'patient_id',
        'new_patient_interest_id',
        'purpose',
        'token_hash',
        'expires_at',
        'used_at',
        'last_used_at',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function newPatientInterest(): BelongsTo
    {
        return $this->belongsTo(NewPatientInterest::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
