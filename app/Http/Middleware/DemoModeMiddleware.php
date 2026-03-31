<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Filament\Notifications\Notification;

class DemoModeMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->practice && $user->practice->is_demo) {
            if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('PATCH') || $request->isMethod('DELETE')) {
                
                if ($request->is('admin/login') || $request->is('admin/logout')) {
                    return $next($request);
                }

                Notification::make()
                    ->warning()
                    ->title('Demo Mode')
                    ->body('This action is disabled in the public demo. Sign up for a free trial to test all features.')
                    ->send();

                return back();
            }
        }

        return $next($request);
    }
}
