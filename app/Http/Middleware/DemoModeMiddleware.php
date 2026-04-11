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

            // Check if this is a write page (GET /create, /edit, /import, or /export)
            $isWritePage = $request->isMethod('GET') &&
                (str_contains($request->path(), '/create') ||
                 str_contains($request->path(), '/edit') ||
                 str_contains($request->path(), '/import') ||
                 str_contains($request->path(), '/export'));

            // Check if this is a state-changing HTTP method or import/export action
            $isWriteMethod = $request->isMethod('POST') || $request->isMethod('PUT') ||
                $request->isMethod('PATCH') || $request->isMethod('DELETE');

            // Block import/export routes explicitly
            $isImportExport = str_contains($request->path(), '/import') ||
                str_contains($request->path(), '/export');

            // Block both write pages and write methods, plus import/export
            if ($isWritePage || $isWriteMethod || $isImportExport) {
                Notification::make()
                    ->warning()
                    ->title('Demo mode — import and export are disabled')
                    ->body('This is a read-only preview. Sign up for a free trial to import or export data.')
                    ->send();
                return redirect('/admin/dashboard');
            }
        }
        return $next($request);
    }
}
