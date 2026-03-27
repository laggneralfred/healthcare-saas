<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Thin façade over ActivityLog::record().
 * All public methods are static for easy call-site ergonomics.
 */
class AuditLogger
{
    /**
     * Fields that must never appear in old_values / new_values.
     * Covers passwords, Stripe tokens, and card details.
     */
    private const SENSITIVE = [
        'password',
        'remember_token',
        'stripe_id',
        'stripe_price',
        'pm_type',
        'pm_last_four',
        'card_brand',
        'card_last_four',
        'trial_ends_at',
    ];

    // ── Named constructors ─────────────────────────────────────────────────────

    public static function viewed(Model $model): ActivityLog
    {
        return ActivityLog::record('viewed', $model);
    }

    public static function created(Model $model): ActivityLog
    {
        return ActivityLog::record('created', $model, [
            'new_values' => static::filter($model->getAttributes()),
        ]);
    }

    public static function updated(Model $model): ActivityLog
    {
        $dirty = $model->getDirty();

        return ActivityLog::record('updated', $model, [
            'old_values' => static::filter(
                array_intersect_key($model->getRawOriginal(), $dirty)
            ),
            'new_values' => static::filter($dirty),
        ]);
    }

    public static function deleted(Model $model): ActivityLog
    {
        return ActivityLog::record('deleted', $model, [
            'old_values' => static::filter($model->getAttributes()),
        ]);
    }

    public static function stateChanged(
        Model  $model,
        string $fromState,
        string $toState,
        array  $metadata = []
    ): ActivityLog {
        return ActivityLog::record('state_changed', $model, [
            'old_values' => ['state' => $fromState],
            'new_values' => ['state' => $toState],
            'metadata'   => $metadata ?: null,
        ]);
    }

    public static function signed(Model $model, array $metadata = []): ActivityLog
    {
        return ActivityLog::record('signed', $model, [
            'metadata' => $metadata ?: null,
        ]);
    }

    public static function exported(Model $model, array $metadata = []): ActivityLog
    {
        return ActivityLog::record('exported', $model, [
            'metadata' => $metadata ?: null,
        ]);
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    /**
     * Remove sensitive keys from an attribute array before persisting.
     */
    private static function filter(array $attributes): array
    {
        return array_diff_key($attributes, array_flip(self::SENSITIVE));
    }
}
