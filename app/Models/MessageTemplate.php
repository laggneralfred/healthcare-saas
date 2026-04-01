<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPractice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MessageTemplate extends Model
{
    use HasFactory, BelongsToPractice, SoftDeletes;

    protected $fillable = [
        'practice_id',
        'name',
        'channel',
        'trigger_event',
        'subject',
        'body',
        'is_active',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }

    public function communicationRules(): HasMany
    {
        return $this->hasMany(CommunicationRule::class);
    }

    public function messageLogs(): HasMany
    {
        return $this->hasMany(MessageLog::class);
    }

    public function renderBody(array $variables): string
    {
        return $this->replaceVariables($this->body, $variables);
    }

    public function renderSubject(array $variables): string
    {
        return $this->replaceVariables($this->subject ?? '', $variables);
    }

    private function replaceVariables(string $text, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $text = str_replace('{{' . $key . '}}', $value ?? '', $text);
        }
        return $text;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForEvent(Builder $query, string $event): Builder
    {
        return $query->where('trigger_event', $event);
    }

    public static function triggerEventLabels(): array
    {
        return [
            'appointment_booked'   => 'Appointment Booked',
            'reminder_48h'         => '48-Hour Reminder',
            'reminder_24h'         => '24-Hour Reminder',
            'reminder_2h'          => '2-Hour Reminder',
            'appointment_followup' => 'Follow-Up (24h after)',
            'missed_appointment'   => 'Missed Appointment',
            'custom'               => 'Custom',
        ];
    }
}
