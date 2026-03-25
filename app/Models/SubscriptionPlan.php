<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'key',
        'name',
        'price_monthly',
        'stripe_price_id',
        'max_practitioners',
        'features',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'features'         => 'array',
            'is_active'        => 'boolean',
            'price_monthly'    => 'integer',
            'max_practitioners' => 'integer',
        ];
    }

    /** Price in dollars for display. */
    public function monthlyDollars(): string
    {
        return '$' . number_format($this->price_monthly / 100, 0);
    }

    /** Human label for practitioner limit. */
    public function practitionerLimit(): string
    {
        return $this->max_practitioners === -1 ? 'Unlimited' : "Up to {$this->max_practitioners}";
    }

    public static function solo(): ?self
    {
        return static::where('key', 'solo')->first();
    }

    public static function clinic(): ?self
    {
        return static::where('key', 'clinic')->first();
    }

    public static function enterprise(): ?self
    {
        return static::where('key', 'enterprise')->first();
    }
}
