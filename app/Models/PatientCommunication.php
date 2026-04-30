<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPractice;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientCommunication extends Model
{
    use HasFactory, BelongsToPractice;

    public const TYPE_INVITE_BACK = 'invite_back';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PREVIEWED = 'previewed';
    public const STATUS_MARKED_SENT = 'marked_sent';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    public const CHANNEL_PREVIEW = 'preview';
    public const CHANNEL_MANUAL = 'manual';
    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_SMS = 'sms';

    protected $fillable = [
        'practice_id',
        'patient_id',
        'appointment_id',
        'encounter_id',
        'type',
        'channel',
        'language',
        'subject',
        'body',
        'status',
        'created_by',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
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

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_INVITE_BACK => 'Invite Back',
            default => str($this->type)->replace('_', ' ')->title()->toString(),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return str($this->status)->replace('_', ' ')->title()->toString();
    }
}
