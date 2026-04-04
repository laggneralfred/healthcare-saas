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

            // Check if this is a write page (GET /create or /edit)
            $isWritePage = $request->isMethod('GET') &&
                (str_contains($request->path(), '/create') ||
                 str_contains($request->path(), '/edit'));

            // Check if this is a state-changing HTTP method
            $isWriteMethod = $request->isMethod('POST') || $request->isMethod('PUT') ||
                $request->isMethod('PATCH') || $request->isMethod('DELETE');

            // Block both write pages and write methods
            if ($isWritePage || $isWriteMethod) {
                Notification::make()
                    ->warning()
                    ->title('Demo mode — changes are disabled')
                    ->body('This is a read-only preview. Sign up for a free trial to make changes.')
                    ->send();
                return redirect('/admin/dashboard');
            }
        }
        return $next($request);
    }
}
