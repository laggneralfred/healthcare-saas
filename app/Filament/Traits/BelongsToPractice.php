<?php

namespace App\Filament\Traits;

use App\Services\PracticeContext;
use Illuminate\Database\Eloquent\Builder;

/**
 * Scopes Filament resource queries to the currently selected practice.
 * Regular users are locked to their own practice; super-admins see whichever
 * practice they have selected via the PracticeSwitcher widget.
 */
trait BelongsToPractice
{
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $practiceId = PracticeContext::currentPracticeId();
        if ($practiceId) {
            $query->where('practice_id', $practiceId);
        }

        return $query;
    }
}
