<?php

namespace App\Services;

use App\Models\Practice;

class PracticeContext
{
    private const SESSION_KEY = 'admin_selected_practice_id';

    /**
     * Return the practice ID the current user should be scoped to.
     * Regular users are locked to their own practice.
     * Super-admins (no practice_id) use the session-selected practice.
     */
    public static function currentPracticeId(): ?int
    {
        $user = auth()->user();
        if (!$user) {
            return null;
        }

        // Regular user — always locked to their own practice
        if ($user->practice_id) {
            return (int) $user->practice_id;
        }

        // Super-admin — use session selection, default to first practice
        $sessionId = session(self::SESSION_KEY);
        if ($sessionId && Practice::where('id', $sessionId)->exists()) {
            return (int) $sessionId;
        }

        $first = Practice::orderBy('id')->value('id');
        if ($first) {
            session([self::SESSION_KEY => $first]);
        }

        return $first ? (int) $first : null;
    }

    public static function setCurrentPracticeId(int $id): void
    {
        session([self::SESSION_KEY => $id]);
    }

    public static function isSuperAdmin(): bool
    {
        return auth()->check() && auth()->user()->practice_id === null;
    }
}
