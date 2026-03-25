<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    protected $fillable = ['practice_id', 'name', 'email', 'phone', 'notes', 'is_patient'];

    protected function casts(): array
    {
        return ['is_patient' => 'boolean'];
    }

    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function intakeSubmissions(): HasMany
    {
        return $this->hasMany(IntakeSubmission::class);
    }

    public function consentRecords(): HasMany
    {
        return $this->hasMany(ConsentRecord::class);
    }
}
