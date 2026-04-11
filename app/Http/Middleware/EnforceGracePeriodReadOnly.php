<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceGracePeriodReadOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session('trial_grace')) {
            return $next($request);
        }

        $isWritePage = $request->isMethod('GET') &&
            (str_contains($request->path(), '/create') ||
             str_contains($request->path(), '/edit'));

        $isWriteMethod = $request->isMethod('POST') || $request->isMethod('PUT') ||
            $request->isMethod('PATCH') || $request->isMethod('DELETE');

        if ($isWritePage || $isWriteMethod) {
            Notification::make()
                ->warning()
                ->title('Your trial has expired — upgrade to make changes.')
                ->body('<a href="/admin/billing" style="font-weight:600;text-decoration:underline;">Subscribe Now &rarr;</a>')
                ->send();

            return redirect('/admin/dashboard');
        }

        return $next($request);
    }
}
