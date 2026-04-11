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

        // Always allow certain routes to avoid redirect loops
        if ($request->routeIs(
            'filament.admin.pages.billing',
            'filament.admin.auth.login',
            'filament.admin.auth.logout',
            'register',
            'register.store',
            'subscribe',
            'export.request',
            'export.download',
            'filament.admin.pages.export-data',
        )) {
            return $next($request);
        }

        $practice = $user->practice;

        if (! $practice) {
            return redirect('/subscribe');
        }

        // Allow active trial (no Stripe subscription yet)
        if ($practice->trial_ends_at && $practice->trial_ends_at->isFuture()) {
            return $next($request);
        }

        // Allow active Stripe subscription
        if ($practice->subscribed('default')) {
            return $next($request);
        }

        // Grace period: allow read-only access for 30 days after trial expiry
        if ($practice->trial_ends_at) {
            $daysSinceExpiry = (int) now()->diffInDays($practice->trial_ends_at);

            if ($daysSinceExpiry <= 30) {
                session(['trial_grace' => true]);
                return $next($request);
            }

            return redirect('/subscribe')->with(
                'error',
                'Your trial ended 30+ days ago. Subscribe to access your account.'
            );
        }

        // No trial at all — no subscription
        return redirect('/subscribe');
    }
}
