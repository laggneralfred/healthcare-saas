<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPractice;
use App\Models\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    use HasFactory, BelongsToPractice, HasAuditLog;
    protected $fillable = [
        'practice_id',
        'name',
        'first_name',
        'last_name',
        'middle_name',
        'preferred_name',
        'email',
        'phone',
        'dob',
        'gender',
        'pronouns',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'occupation',
        'referred_by',
        'notes',
        'is_patient',
    ];

    protected function casts(): array
    {
        return [
            'is_patient' => 'boolean',
            'dob'        => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Patient $patient) {
            if ($patient->first_name || $patient->last_name) {
                $patient->name = trim("{$patient->first_name} {$patient->last_name}");
            }
        });
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function intakeSubmissions(): HasMany
    {
        return $this->hasMany(IntakeSubmission::class);
    }

    public function encounters(): HasMany
    {
        return $this->hasMany(Encounter::class);
    }

    public function checkoutSessions(): HasMany
    {
        return $this->hasMany(CheckoutSession::class);
    }

    public function consentRecords(): HasMany
    {
        return $this->hasMany(ConsentRecord::class);
    }

    public function messageLogs(): HasMany
    {
        return $this->hasMany(MessageLog::class);
    }

    public function communicationPreference(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PatientCommunicationPreference::class);
    }
}
