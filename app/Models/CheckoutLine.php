<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPractice;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckoutLine extends Model
{
    use HasFactory, BelongsToPractice;

    protected $fillable = [
        'checkout_session_id',
        'practice_id',
        'sequence',
        'description',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount'   => 'decimal:2',
            'sequence' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        $sync = fn (CheckoutLine $line) => $line->checkoutSession->syncTotalFromLines();

        static::created($sync);
        static::updated($sync);
        static::deleted($sync);
    }

    public function checkoutSession(): BelongsTo
    {
        return $this->belongsTo(CheckoutSession::class);
    }

    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }
}
