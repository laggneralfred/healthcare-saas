<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPractice;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommunicationRule extends Model
{
    use HasFactory, BelongsToPractice, SoftDeletes;

    protected $fillable = [
        'practice_id',
        'practitioner_id',
        'appointment_type_id',
        'message_template_id',
        'is_active',
        'send_at_offset_minutes',
    ];

    protected function casts(): array
    {
        return [
            'is_active'               => 'boolean',
            'send_at_offset_minutes'  => 'integer',
        ];
    }

    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }

    public function messageTemplate(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class);
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(Practitioner::class);
    }

    public function appointmentType(): BelongsTo
    {
        return $this->belongsTo(AppointmentType::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getSendAtDateTime(Carbon $appointmentTime): Carbon
    {
        return $appointmentTime->copy()->addMinutes($this->send_at_offset_minutes);
    }

    public function getTimingDescription(): string
    {
        $offset = $this->send_at_offset_minutes;

        if ($offset === 0) {
            return 'At booking';
        }

        $abs       = abs($offset);
        $direction = $offset < 0 ? 'before' : 'after';

        if ($abs % 10080 === 0) {
            $n = $abs / 10080;
            return $n === 1 ? "1 week {$direction}" : "{$n} weeks {$direction}";
        }

        if ($abs % 1440 === 0) {
            $n = $abs / 1440;
            return $n === 1 ? "1 day {$direction}" : "{$n} days {$direction}";
        }

        if ($abs % 60 === 0) {
            $n = $abs / 60;
            return $n === 1 ? "1 hour {$direction}" : "{$n} hours {$direction}";
        }

        return "{$abs} minutes {$direction}";
    }
}
