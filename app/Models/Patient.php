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

    public function setPhoneAttribute(?string $value): void
    {
        $this->attributes['phone'] = $this->formatPhoneToE164($value);
    }

    public function getPhoneAttribute(?string $value): ?string
    {
        return $value ? $this->formatPhoneDisplay($value) : null;
    }

    public function setEmergencyContactPhoneAttribute(?string $value): void
    {
        $this->attributes['emergency_contact_phone'] = $this->formatPhoneToE164($value);
    }

    public function getEmergencyContactPhoneAttribute(?string $value): ?string
    {
        return $value ? $this->formatPhoneDisplay($value) : null;
    }

    private function formatPhoneToE164(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone);
        if (empty($digits)) {
            return $phone;
        }

        if (strlen($digits) === 10) {
            return '+1' . $digits;
        }
        if (strlen($digits) === 11 && $digits[0] === '1') {
            return '+' . $digits;
        }
        if (strlen($digits) === 11) {
            return '+1' . substr($digits, 1);
        }

        return '+' . $digits;
    }

    private function formatPhoneDisplay(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone);
        if (empty($digits)) {
            return $phone;
        }

        if (strlen($digits) === 10) {
            return '(' . substr($digits, 0, 3) . ') ' . substr($digits, 3, 3) . '-' . substr($digits, 6);
        }
        if (strlen($digits) === 11 && $digits[0] === '1') {
            $digits = substr($digits, 1);
            return '+1 (' . substr($digits, 0, 3) . ') ' . substr($digits, 3, 3) . '-' . substr($digits, 6);
        }

        return $phone;
    }

    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function medicalHistories(): HasMany
    {
        return $this->hasMany(MedicalHistory::class);
    }

    public function medicalHistory(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(MedicalHistory::class)->latestOfMany();
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
