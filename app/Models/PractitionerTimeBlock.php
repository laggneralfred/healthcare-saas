<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPractice;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class PractitionerTimeBlock extends Model
{
    use BelongsToPractice;
    use HasFactory;

    public const TYPE_UNAVAILABLE = 'unavailable';
    public const TYPE_VACATION = 'vacation';
    public const TYPE_SICK = 'sick';
    public const TYPE_LUNCH = 'lunch';
    public const TYPE_ADMIN = 'admin';
    public const TYPE_CUSTOM = 'custom';

    public const TYPE_OPTIONS = [
        self::TYPE_UNAVAILABLE => 'Unavailable',
        self::TYPE_VACATION => 'Vacation',
        self::TYPE_SICK => 'Sick',
        self::TYPE_LUNCH => 'Lunch',
        self::TYPE_ADMIN => 'Admin',
        self::TYPE_CUSTOM => 'Custom',
    ];

    protected $fillable = [
        'practice_id',
        'practitioner_id',
        'starts_at',
        'ends_at',
        'block_type',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $timeBlock): void {
            $timeBlock->validateScheduleBlock();
        });
    }

    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(Practitioner::class);
    }

    private function validateScheduleBlock(): void
    {
        if ($this->starts_at >= $this->ends_at) {
            throw ValidationException::withMessages([
                'ends_at' => 'End time must be after start time.',
            ]);
        }

        $practitioner = Practitioner::withoutPracticeScope()->find($this->practitioner_id);

        if (! $practitioner || (int) $practitioner->practice_id !== (int) $this->practice_id) {
            throw ValidationException::withMessages([
                'practitioner_id' => 'Choose a practitioner in the same practice.',
            ]);
        }
    }
}
