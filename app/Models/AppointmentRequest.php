<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPractice;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AppointmentRequest extends Model
{
    use HasFactory, BelongsToPractice;

    public const STATUS_LINK_SENT = 'link_sent';
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONTACTED = 'contacted';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_DISMISSED = 'dismissed';
    public const STATUS_SUBMITTED = self::STATUS_PENDING;
    public const STATUS_FAILED = 'failed';
    public const STATUS_REVIEWED = 'reviewed';

    protected $fillable = [
        'practice_id',
        'patient_id',
        'patient_communication_id',
        'token_hash',
        'status',
        'requested_service',
        'preferred_times',
        'note',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    public static function createLinkFor(Patient $patient, ?PatientCommunication $communication = null): array
    {
        do {
            $token = Str::random(64);
            $hash = hash('sha256', $token);
        } while (self::withoutPracticeScope()->where('token_hash', $hash)->exists());

        $request = self::withoutPracticeScope()->create([
            'practice_id' => $patient->practice_id,
            'patient_id' => $patient->id,
            'patient_communication_id' => $communication?->id,
            'token_hash' => $hash,
            'status' => self::STATUS_LINK_SENT,
        ]);

        return [$request, $token];
    }

    public static function findByToken(string $token): ?self
    {
        return self::withoutPracticeScope()
            ->with(['practice', 'patient'])
            ->where('token_hash', hash('sha256', $token))
            ->first();
    }

    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function patientCommunication(): BelongsTo
    {
        return $this->belongsTo(PatientCommunication::class);
    }

    public function publicUrl(string $token): string
    {
        return route('appointment-request.show', $token);
    }
}
