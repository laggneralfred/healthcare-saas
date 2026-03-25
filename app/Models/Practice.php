<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Cashier\Billable;

class Practice extends Model
{
    use Billable, HasFactory;
    protected $fillable = [
        'name', 'slug', 'timezone', 'is_active',
        'stripe_id', 'pm_type', 'pm_last_four', 'trial_ends_at',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function practitioners(): HasMany
    {
        return $this->hasMany(Practitioner::class);
    }
}
