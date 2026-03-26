<?php

namespace App\Models\Concerns;

use App\Services\PracticeContext;
use Illuminate\Database\Eloquent\Builder;

/**
 * Automatically scopes all Eloquent queries to the currently active practice.
 *
 * For regular users:  scopes to their own practice_id.
 * For super-admins:   scopes to the session-selected practice (or the first
 *                     practice in the database as a safe fallback).
 * For unauthenticated / console context: scope is a no-op (PracticeContext
 *                     returns null when there is no authenticated user).
 *
 * When you need to query across practices (reports, API endpoints that receive
 * the practice via route model binding, seeders), bypass the scope with:
 *
 *   Model::withoutPracticeScope()->where(...)->get();
 */
trait BelongsToPractice
{
    public static function bootBelongsToPractice(): void
    {
        static::addGlobalScope('practice', function (Builder $builder) {
            $practiceId = PracticeContext::currentPracticeId();

            if ($practiceId !== null) {
                $builder->where(
                    $builder->getModel()->getTable() . '.practice_id',
                    $practiceId
                );
            }
        });
    }

    /**
     * Return a query builder with the practice global scope removed.
     *
     * Use this when the calling code already constrains the query to a specific
     * practice (e.g. route-bound $practice->id) and double-scoping would either
     * be redundant or, in the case of a super-admin whose session practice
     * differs from the target, would incorrectly return zero rows.
     */
    public static function withoutPracticeScope(): Builder
    {
        return static::withoutGlobalScope('practice');
    }
}
