<?php

namespace App\Models;

use App\Models\States\Appointment\AppointmentState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\ModelStates\HasStates;

class Appointment extends Model
{
    use HasStates;

    protected $fillable = [
        'practice_id',
        'practitioner_id',
        'appointment_type_id',
        'status',
        'start_datetime',
        'end_datetime',
        'needs_follow_up',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status'          => AppointmentState::class,
            'start_datetime'  => 'datetime',
            'end_datetime'    => 'datetime',
            'needs_follow_up' => 'boolean',
        ];
    }

    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(Practitioner::class);
    }

    public function appointmentType(): BelongsTo
    {
        return $this->belongsTo(AppointmentType::class);
    }
}
