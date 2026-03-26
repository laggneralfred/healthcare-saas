<?php

namespace App\Http\Middleware;

use App\Services\PracticeContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Writes the current practice ID into the PostgreSQL session variable
 * `app.practice_id` so that Row Level Security policies can filter rows
 * to the correct tenant without any application-level query modification.
 *
 * Runs on every web request after the session / auth middleware have
 * resolved the authenticated user (appended to the `web` middleware group).
 *
 * Setting lifecycle
 * ─────────────────
 * The setting is written at request start and cleared in a `finally` block
 * so that long-lived worker processes (Octane, queue workers) never leak a
 * practice context from one request into the next.
 *
 * Unauthenticated requests
 * ────────────────────────
 * When no user is logged in, PracticeContext returns null and the setting is
 * written as an empty string.  The NULLIF expression in the RLS policy then
 * produces NULL, making `practice_id = NULL` false for every row — no data
 * is exposed.
 *
 * Public token routes (/intake/{token}, /consent/{token})
 * ────────────────────────────────────────────────────────
 * These routes access practice-scoped tables via a globally unique token.
 * The Livewire components call withoutPracticeScope() (Eloquent layer) AND
 * must resolve the practice from the token before querying RLS-protected
 * tables.  The component's mount() method should call
 * DB::statement("SELECT set_config('app.practice_id', ?, false)", [$id])
 * after resolving the practice from the token.
 */
class SetPostgresTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $practiceId = PracticeContext::currentPracticeId();

        DB::statement(
            "SELECT set_config('app.practice_id', ?, false)",
            [$practiceId !== null ? (string) $practiceId : '']
        );

        try {
            return $next($request);
        } finally {
            // Reset on the way out to prevent context leaking across requests
            // in long-lived processes (Octane, persistent FPM workers, etc.).
            DB::statement("SELECT set_config('app.practice_id', '', false)");
        }
    }
}
