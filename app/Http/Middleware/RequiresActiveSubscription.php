<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequiresActiveSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $practice = $user->practice;

        // Admin users with no practice (global admin) pass through
        if (! $practice) {
            return $next($request);
        }

        // Allow access to the billing page itself to avoid redirect loops
        if ($request->routeIs('filament.admin.pages.billing')) {
            return $next($request);
        }

        if (! $practice->subscribed('default')) {
            return redirect()->route('filament.admin.pages.billing')
                ->with('subscription_required', 'An active subscription is required to access the admin panel.');
        }

        return $next($request);
    }
}
