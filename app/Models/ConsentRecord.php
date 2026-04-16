<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPractice;
use App\Models\Traits\HasAuditLog;
use App\Traits\HasAccessToken;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentRecord extends Model
{
    use HasFactory, HasAccessToken, BelongsToPractice, HasAuditLog;

    protected $fillable = [
        'practice_id', 'patient_id', 'appointment_id',
        'status', 'signed_on', 'access_token',
        'consent_given_by', 'consent_summary', 'notes',
        'signed_at_ip', 'signed_at_user_agent',
    ];

    protected function casts(): array
    {
        return ['signed_on' => 'datetime'];
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
