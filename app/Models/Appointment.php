<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPractice;
use App\Models\States\Appointment\AppointmentState;
use App\Models\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\ModelStates\HasStates;

class Appointment extends Model
{
    use HasFactory, HasStates, BelongsToPractice, HasAuditLog;

    protected $fillable = [
        'practice_id',
        'patient_id',
        'practitioner_id',
        'appointment_type_id',
        'status',
        'start_datetime',
        'end_datetime',
        'needs_follow_up',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status'          => AppointmentState::class,
            'start_datetime'  => 'datetime',
            'end_datetime'    => 'datetime',
            'needs_follow_up' => 'boolean',
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

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(Practitioner::class);
    }

    public function appointmentType(): BelongsTo
    {
        return $this->belongsTo(AppointmentType::class);
    }

    public function checkoutSession(): HasOne
    {
        return $this->hasOne(CheckoutSession::class);
    }

    public function medicalHistory(): HasOne
    {
        return $this->hasOne(MedicalHistory::class);
    }

    public function consentRecord(): HasOne
    {
        return $this->hasOne(ConsentRecord::class);
    }

    public function encounter(): HasOne
    {
        return $this->hasOne(Encounter::class);
    }
}
