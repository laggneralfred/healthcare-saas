<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequiresActiveSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        // Bypass entirely in local development
        if (app()->environment('local')) {
            return $next($request);
        }

        $user = $request->user();

        // Unauthenticated requests pass through (login/logout pages)
        if (! $user) {
            return $next($request);
        }

        // Super-admin users (no practice_id) bypass subscription checks
        if (! $user->practice_id) {
            return $next($request);
        }

        // Always allow billing, login, and logout routes to avoid redirect loops
        if ($request->routeIs(
            'filament.admin.pages.billing',
            'filament.admin.auth.login',
            'filament.admin.auth.logout',
        )) {
            return $next($request);
        }

        $practice = $user->practice;

        if (! $practice || ! $practice->subscribed('default')) {
            return redirect()->route('filament.admin.pages.billing')
                ->with('subscription_required', 'An active subscription is required to access the admin panel.');
        }

        return $next($request);
    }
}
