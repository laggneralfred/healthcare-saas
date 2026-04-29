<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPractice;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckoutPayment extends Model
{
    use HasFactory, BelongsToPractice;

    public const METHOD_CASH = 'cash';
    public const METHOD_CHECK = 'check';
    public const METHOD_CARD_EXTERNAL = 'card_external';
    public const METHOD_OTHER = 'other';
    public const METHOD_COMPED = 'comped';

    public const METHODS = [
        self::METHOD_CASH => 'Cash',
        self::METHOD_CHECK => 'Check',
        self::METHOD_CARD_EXTERNAL => 'Card (external)',
        self::METHOD_OTHER => 'Other',
        self::METHOD_COMPED => 'Comped / no charge',
    ];

    protected $fillable = [
        'practice_id',
        'checkout_session_id',
        'amount',
        'payment_method',
        'paid_at',
        'reference',
        'notes',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        $sync = fn (CheckoutPayment $payment) => $payment->checkoutSession?->syncPayments();

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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
