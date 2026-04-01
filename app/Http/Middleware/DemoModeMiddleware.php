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

            // Allow login and logout through
            if ($request->is('admin/login') || $request->is('admin/logout') || $request->is('logout')) {
                return $next($request);
            }

            // Block create and edit pages (GET requests)
            if ($request->isMethod('GET') &&
                (str_contains($request->path(), '/create') ||
                 preg_match('/\/[\w-]+\/edit/', $request->path()) ||
                 str_contains($request->path(), '/edit'))) {
                return redirect('/admin/dashboard')->with('warning',
                    'This is a read-only demo. Sign up for a free trial to make changes.');
            }

            // Block all write methods
            if ($request->isMethod('POST') || $request->isMethod('PUT') ||
                $request->isMethod('PATCH') || $request->isMethod('DELETE')) {
                Notification::make()
                    ->warning()
                    ->title('Demo Mode')
                    ->body('This action is disabled in the public demo. Sign up for a free trial.')
                    ->send();
                return back();
            }
        }
        return $next($request);
    }
}
