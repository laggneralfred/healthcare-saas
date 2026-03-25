<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcupunctureEncounter extends Model
{
    use HasFactory;

    protected $fillable = [
        'encounter_id',
        'tcm_diagnosis',
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
        ];
    }

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }
}
