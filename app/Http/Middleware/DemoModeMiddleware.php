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
            if ($request->is('admin/login') || $request->is('admin/logout')) {
                return $next($request);
            }

            $isWriteMethod = $request->isMethod('POST') || $request->isMethod('PUT') ||
                             $request->isMethod('PATCH') || $request->isMethod('DELETE');

            $isWritePage = $request->isMethod('GET') &&
                           (str_contains($request->path(), '/create') ||
                            str_contains($request->path(), '/edit'));

            if ($isWriteMethod || $isWritePage) {
                Notification::make()
                    ->warning()
                    ->title('Demo Mode')
                    ->body('This is a read-only demo. Sign up for a free trial to make changes.')
                    ->send();

                return redirect()->route('filament.admin.pages.dashboard');
            }
        }

        return $next($request);
    }
}
