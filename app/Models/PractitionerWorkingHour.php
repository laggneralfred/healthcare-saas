<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPractice;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class PractitionerWorkingHour extends Model
{
    use BelongsToPractice;
    use HasFactory;

    public const DAYS = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    protected $fillable = [
        'practice_id',
        'practitioner_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $workingHour): void {
            $workingHour->validateScheduleRule();
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

    private function validateScheduleRule(): void
    {
        if (! array_key_exists((int) $this->day_of_week, self::DAYS)) {
            throw ValidationException::withMessages([
                'day_of_week' => 'Choose a valid day of the week.',
            ]);
        }

        if ($this->start_time >= $this->end_time) {
            throw ValidationException::withMessages([
                'end_time' => 'End time must be after start time.',
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
