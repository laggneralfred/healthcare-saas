<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPractice;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppointmentType extends Model
{
    use HasFactory, BelongsToPractice;
    protected $fillable = ['practice_id', 'name', 'duration_minutes', 'is_active', 'default_service_fee_id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function defaultServiceFee(): BelongsTo
    {
        return $this->belongsTo(ServiceFee::class, 'default_service_fee_id');
    }
}
