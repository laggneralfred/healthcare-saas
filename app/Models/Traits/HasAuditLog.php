<?php

namespace App\Models\Traits;

use App\Services\AuditLogger;

/**
 * Automatically writes audit log entries for Eloquent lifecycle events.
 * Apply to any model that should be audited.
 */
trait HasAuditLog
{
    public static function bootHasAuditLog(): void
    {
        static::created(fn ($model) => AuditLogger::created($model));

        static::updated(fn ($model) => AuditLogger::updated($model));

        static::deleted(fn ($model) => AuditLogger::deleted($model));
    }
}
