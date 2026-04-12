<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPractice;
use App\Models\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Encounter extends Model
{
    use HasFactory, BelongsToPractice, HasAuditLog;

    protected $fillable = [
        'practice_id',
        'patient_id',
        'appointment_id',
        'practitioner_id',
        'status',
        'visit_date',
        'chief_complaint',
        'subjective',
        'objective',
        'assessment',
        'plan',
        'visit_notes',
        'completed_on',
    ];

    protected function casts(): array
    {
        return [
            'visit_date'   => 'date',
            'completed_on' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Encounter $encounter) {
            if ($encounter->status === 'complete' && ! $encounter->completed_on) {
                $encounter->completed_on = now();
            }
        });
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

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(Practitioner::class);
    }

    public function acupunctureEncounter(): HasOne
    {
        return $this->hasOne(AcupunctureEncounter::class);
    }
}
