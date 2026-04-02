<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPractice;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientCommunicationPreference extends Model
{
    use HasFactory, BelongsToPractice;

    protected $fillable = [
        'practice_id',
        'patient_id',
        'email_opt_in',
        'sms_opt_in',
        'preferred_channel',
        'opted_out_at',
    ];

    protected function casts(): array
    {
        return [
            'email_opt_in' => 'boolean',
            'sms_opt_in'   => 'boolean',
            'opted_out_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PatientCommunicationPreference $pref) {
            if (empty($pref->practice_id) && $pref->patient_id) {
                $patient = \App\Models\Patient::withoutPracticeScope()->find($pref->patient_id);
                if ($patient) {
                    $pref->practice_id = $patient->practice_id;
                }
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

    public function canReceiveEmail(): bool
    {
        return $this->email_opt_in && $this->opted_out_at === null;
    }

    public function canReceiveSms(): bool
    {
        return $this->sms_opt_in && $this->opted_out_at === null;
    }

    public function hasOptedOut(): bool
    {
        return $this->opted_out_at !== null;
    }

    public static function findOrCreateForPatient(Patient $patient): self
    {
        return self::withoutPracticeScope()->firstOrCreate(
            [
                'practice_id' => $patient->practice_id,
                'patient_id'  => $patient->id,
            ],
            [
                'email_opt_in'      => true,
                'sms_opt_in'        => true,
                'preferred_channel' => 'email',
            ]
        );
    }
}
