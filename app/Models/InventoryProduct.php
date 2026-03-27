<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPractice;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryProduct extends Model
{
    use HasFactory, BelongsToPractice, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'practice_id',
        'name',
        'sku',
        'description',
        'category',
        'unit',
        'selling_price',
        'cost_price',
        'stock_quantity',
        'low_stock_threshold',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'selling_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    // ── Accessors ──────────────────────────────────────────────────────────────

    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->low_stock_threshold;
    }

    // ── Boot ───────────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (InventoryProduct $product) {
            if (!$product->id) {
                $product->id = \Illuminate\Support\Str::uuid();
            }
        });
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock($query)
    {
        return $query->whereRaw('stock_quantity <= low_stock_threshold');
    }
}
