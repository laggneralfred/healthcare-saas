<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPractice;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckoutLine extends Model
{
    use HasFactory, BelongsToPractice;

    public const TYPE_CUSTOM = 'custom';

    public const TYPE_SERVICE = 'service';

    public const TYPE_INVENTORY = 'inventory';

    public const TYPES = [
        self::TYPE_SERVICE => 'Service',
        self::TYPE_INVENTORY => 'Inventory',
        self::TYPE_CUSTOM => 'Custom',
    ];

    protected $fillable = [
        'checkout_session_id',
        'practice_id',
        'sequence',
        'line_type',
        'service_fee_id',
        'description',
        'amount',
        'unit_price',
        'inventory_product_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'amount'   => 'decimal:2',
            'unit_price' => 'decimal:2',
            'sequence' => 'integer',
            'quantity' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        $sync = fn (CheckoutLine $line) => $line->checkoutSession->syncTotalFromLines();

        static::saving(function (CheckoutLine $line): void {
            $line->normalizeAndValidate();
        });

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

    public function inventoryProduct(): BelongsTo
    {
        return $this->belongsTo(InventoryProduct::class);
    }

    public function serviceFee(): BelongsTo
    {
        return $this->belongsTo(ServiceFee::class);
    }

    public function isService(): bool
    {
        return $this->line_type === self::TYPE_SERVICE;
    }

    public function isInventory(): bool
    {
        return $this->line_type === self::TYPE_INVENTORY;
    }

    public function isCustom(): bool
    {
        return $this->line_type === self::TYPE_CUSTOM;
    }

    private function normalizeAndValidate(): void
    {
        $this->line_type = $this->line_type ?: $this->inferLineType();

        if (! array_key_exists($this->line_type, self::TYPES)) {
            throw new \InvalidArgumentException('Choose a valid checkout line type.');
        }

        match ($this->line_type) {
            self::TYPE_SERVICE => $this->normalizeServiceLine(),
            self::TYPE_INVENTORY => $this->normalizeInventoryLine(),
            self::TYPE_CUSTOM => $this->normalizeCustomLine(),
        };
    }

    private function inferLineType(): string
    {
        if ($this->inventory_product_id) {
            return self::TYPE_INVENTORY;
        }

        if ($this->service_fee_id) {
            return self::TYPE_SERVICE;
        }

        return self::TYPE_CUSTOM;
    }

    private function normalizeServiceLine(): void
    {
        if (! $this->service_fee_id) {
            throw new \InvalidArgumentException('Choose a service fee for service checkout lines.');
        }

        $serviceFee = ServiceFee::withoutPracticeScope()
            ->where('practice_id', $this->practice_id)
            ->find($this->service_fee_id);

        if (! $serviceFee || (! $serviceFee->is_active && (! $this->exists || $this->isDirty('service_fee_id')))) {
            throw new \InvalidArgumentException('Choose an active service fee for the current practice.');
        }

        $this->inventory_product_id = null;
        $this->quantity = max(1, (int) ($this->quantity ?: 1));
        $this->description = $this->description ?: $serviceFee->name;
        $this->unit_price = $this->unit_price ?? $serviceFee->default_price;
        $this->amount = $this->amount ?? $serviceFee->default_price;
    }

    private function normalizeInventoryLine(): void
    {
        if (! $this->inventory_product_id) {
            throw new \InvalidArgumentException('Choose an inventory product for inventory checkout lines.');
        }

        $product = InventoryProduct::withoutPracticeScope()
            ->where('practice_id', $this->practice_id)
            ->find($this->inventory_product_id);

        if (! $product || (! $product->is_active && (! $this->exists || $this->isDirty('inventory_product_id')))) {
            throw new \InvalidArgumentException('Choose an active inventory product for the current practice.');
        }

        $quantity = max(1, (int) ($this->quantity ?: 1));

        $this->service_fee_id = null;
        $this->quantity = $quantity;
        $this->description = $this->description ?: "{$product->name} (x{$quantity})";
        $this->unit_price = $this->unit_price ?? $product->selling_price;
        $this->amount = $this->amount ?? ((float) $this->unit_price * $quantity);
    }

    private function normalizeCustomLine(): void
    {
        $this->service_fee_id = null;
        $this->inventory_product_id = null;
        $this->quantity = null;
        $this->unit_price = $this->unit_price ?: null;
    }
}
