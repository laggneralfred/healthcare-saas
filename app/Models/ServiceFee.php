<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPractice;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceFee extends Model
{
    use HasFactory, BelongsToPractice;

    protected $fillable = [
        'practice_id',
        'name',
        'short_description',
        'default_price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_price' => 'decimal:2',
            'is_active'     => 'boolean',
        ];
    }

    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }

    public function appointmentTypes(): HasMany
    {
        return $this->hasMany(AppointmentType::class, 'default_service_fee_id');
    }
}
