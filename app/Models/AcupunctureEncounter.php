<?php

namespace App\Models;

use App\Models\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcupunctureEncounter extends Model
{
    use HasFactory, HasAuditLog;

    protected $fillable = [
        'encounter_id',
        'tcm_diagnosis',
        'tongue_body',
        'tongue_coating',
        'pulse_quality',
        'zang_fu_diagnosis',
        'five_elements',
        'csor_color',
        'csor_sound',
        'csor_odor',
        'csor_emotion',
        'points_used',
        'meridians',
        'treatment_protocol',
        'needle_count',
        'session_notes',
    ];

    protected function casts(): array
    {
        return [
            'needle_count' => 'integer',
            'five_elements' => 'array',
        ];
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }
}
