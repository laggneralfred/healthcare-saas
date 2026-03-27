<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only audit log entry.  Never update or soft-delete rows.
 */
class ActivityLog extends Model
{
    // No updated_at — immutable
    const UPDATED_AT = null;

    protected $fillable = [
        'practice_id',
        'user_id',
        'user_email',
        'action',
        'auditable_type',
        'auditable_id',
        'auditable_label',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata'   => 'array',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function practice(): BelongsTo
    {
        return $this->belongsTo(Practice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Factory ───────────────────────────────────────────────────────────────

    /**
     * Create one audit log entry.
     *
     * @param  string  $action   viewed|created|updated|deleted|state_changed|signed|exported
     * @param  Model   $model    The subject of the action
     * @param  array   $extra    Overrides / additional fields (old_values, new_values, metadata…)
     */
    public static function record(string $action, Model $model, array $extra = []): self
    {
        $user = auth()->user();

        // Resolve practice_id: direct column or via Encounter parent (AcupunctureEncounter)
        $practiceId = $model->practice_id
            ?? ($model->encounter?->practice_id ?? null);

        return static::create(array_merge([
            'practice_id'     => $practiceId,
            'user_id'         => $user?->id,
            'user_email'      => $user?->email,
            'action'          => $action,
            'auditable_type'  => get_class($model),
            'auditable_id'    => $model->getKey(),
            'auditable_label' => static::resolveLabel($model),
            'ip_address'      => request()?->ip(),
            'user_agent'      => request()?->userAgent(),
        ], $extra));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private static function resolveLabel(Model $model): string
    {
        return match (true) {
            isset($model->name)        => $model->name,
            isset($model->patient)     => $model->patient?->name ?? class_basename($model).'#'.$model->getKey(),
            default                    => class_basename($model).'#'.$model->getKey(),
        };
    }
}
