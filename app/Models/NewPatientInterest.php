<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPractice;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewPatientInterest extends Model
{
    use BelongsToPractice, HasFactory;

    public const STATUS_NEW = 'new';
    public const STATUS_REVIEWING = 'reviewing';
    public const STATUS_FORMS_SENT = 'forms_sent';
    public const STATUS_CONVERTED = 'converted';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_CLOSED = 'closed';

    public const STATUS_OPTIONS = [
        self::STATUS_NEW => 'New',
        self::STATUS_REVIEWING => 'Reviewing',
        self::STATUS_FORMS_SENT => 'Forms Sent',
        self::STATUS_CONVERTED => 'Converted',
        self::STATUS_DECLINED => 'Declined',
        self::STATUS_CLOSED => 'Closed',
    ];

    protected $fillable = [
        'practice_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'preferred_service',
        'preferred_practitioner_id',
        'preferred_days_times',
        'message',
        'status',
        'responded_at',
        'responded_by_user_id',
        'converted_patient_id',
    ];

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
        ];
    }

    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }

    public function preferredPractitioner(): BelongsTo
    {
        return $this->belongsTo(Practitioner::class, 'preferred_practitioner_id');
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by_user_id');
    }

    public function convertedPatient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'converted_patient_id');
    }

    public function formSubmissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
