<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPractice;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    use HasFactory, BelongsToPractice;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'practice_id',
        'inventory_product_id',
        'type',
        'quantity',
        'unit_price',
        'reference',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function product(): BelongsTo
    {
        return $this->belongsTo(InventoryProduct::class, 'inventory_product_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Boot ───────────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (InventoryMovement $movement) {
            if (!$movement->id) {
                $movement->id = \Illuminate\Support\Str::uuid();
            }
        });

        static::created(function (InventoryMovement $movement) {
            $product = $movement->product;
            if ($product) {
                // Update stock by adding quantity (negative for sales/adjustments out)
                $product->increment('stock_quantity', $movement->quantity);
            }
        });
    }
}
