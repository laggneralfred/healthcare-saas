<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPractice;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class PracticePaymentMethod extends Model
{
    use HasFactory, BelongsToPractice;

    protected $fillable = [
        'practice_id',
        'method_key',
        'display_name',
        'enabled',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }

    public static function ensureDefaultsForPractice(Practice|int $practice): void
    {
        if (! Schema::hasTable('practice_payment_methods')) {
            return;
        }

        $practiceId = $practice instanceof Practice ? $practice->id : $practice;
        $existing = static::withoutPracticeScope()
            ->where('practice_id', $practiceId)
            ->pluck('method_key')
            ->all();
        $existing = array_flip($existing);

        foreach (CheckoutPayment::METHODS as $methodKey => $displayName) {
            if (isset($existing[$methodKey])) {
                continue;
            }

            static::withoutPracticeScope()->create([
                'practice_id' => $practiceId,
                'method_key' => $methodKey,
                'display_name' => $displayName,
                'enabled' => true,
                'sort_order' => static::defaultSortOrder($methodKey),
            ]);
        }
    }

    public static function enabledOptionsForPractice(?int $practiceId): array
    {
        if (! $practiceId || ! Schema::hasTable('practice_payment_methods')) {
            return CheckoutPayment::METHODS;
        }

        $configured = static::withoutPracticeScope()
            ->where('practice_id', $practiceId)
            ->whereIn('method_key', array_keys(CheckoutPayment::METHODS))
            ->orderBy('sort_order')
            ->orderBy('display_name')
            ->get();

        if ($configured->isEmpty()) {
            return CheckoutPayment::METHODS;
        }

        return $configured
            ->where('enabled', true)
            ->mapWithKeys(fn (PracticePaymentMethod $method): array => [
                $method->method_key => $method->display_name,
            ])
            ->all();
    }

    public static function isEnabledForPractice(?int $practiceId, string $methodKey): bool
    {
        if (! array_key_exists($methodKey, CheckoutPayment::METHODS)) {
            return false;
        }

        if (! $practiceId || ! Schema::hasTable('practice_payment_methods')) {
            return true;
        }

        $configured = static::withoutPracticeScope()
            ->where('practice_id', $practiceId)
            ->whereIn('method_key', array_keys(CheckoutPayment::METHODS))
            ->exists();

        if (! $configured) {
            return true;
        }

        return static::withoutPracticeScope()
            ->where('practice_id', $practiceId)
            ->where('method_key', $methodKey)
            ->where('enabled', true)
            ->exists();
    }

    public static function defaultSortOrder(string $methodKey): int
    {
        $index = array_search($methodKey, array_keys(CheckoutPayment::METHODS), true);

        return $index === false ? 999 : $index * 10;
    }
}
