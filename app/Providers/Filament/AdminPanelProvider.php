<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EnforceGracePeriodReadOnly;
use App\Http\Middleware\RequiresActiveSubscription;
use App\Models\Practice;
use App\Services\PracticeContext;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Filament\Actions\Action as FilamentAction;
use Filament\Actions\BulkActionGroup;
use Filament\Widgets\AccountWidget;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function register(): void
    {
        parent::register();

        FilamentView::registerRenderHook(
            'panels::topbar.end',
            function (): View {
                $isSuperAdmin = PracticeContext::isSuperAdmin();
                $selectedId   = PracticeContext::currentPracticeId();
                $practices    = $isSuperAdmin ? Practice::orderBy('name')->get(['id', 'name']) : collect();

                return view('filament.hooks.practice-switcher', compact('isSuperAdmin', 'selectedId', 'practices'));
            },
        );
    }

    public function boot(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::CONTENT_START,
            function (): string {
                $user = auth()->user();
                if (!$user || !$user->practice_id) {
                    return '';
                }

                // No trial banner for demo users
                if ($user->isDemo()) {
                    return '';
                }

                $practice = $user->practice;
                if (!$practice) {
                    return '';
                }

                // Hide for active subscribers
                if ($practice->subscribed('default')) {
                    return '';
                }

                // Show for any non-subscriber (on trial or trial_ends_at not set)
                $daysRemaining = ($practice->trial_ends_at && $practice->trial_ends_at->isFuture())
                    ? (int) now()->diffInDays($practice->trial_ends_at, false)
                    : 0;

                return view('filament.hooks.trial-banner', compact('daysRemaining'))->render();
            },
        );

        // Grace period banner — shown when trial has expired but within 30-day grace window
        FilamentView::registerRenderHook(
            PanelsRenderHook::CONTENT_START,
            function (): string {
                if (! session('trial_grace')) {
                    return '';
                }
                $billingUrl = route('filament.admin.pages.billing');
                return <<<HTML
<div x-data="{ dismissed: sessionStorage.getItem('practiq_grace_dismissed') === '1' }"
     x-show="!dismissed"
     x-transition
     style="background-color:#d97706;color:white;padding:12px 24px;display:flex;justify-content:space-between;align-items:center;font-size:14px;">
    <span>
        <strong>⚠ Your free trial has expired.</strong>
        Your data is safe — subscribe to continue using Practiq.
        <a href="{$billingUrl}" style="color:white;text-decoration:underline;font-weight:600;margin-left:8px;">
            Subscribe Now &rarr;
        </a>
    </span>
    <button @click="dismissed=true;sessionStorage.setItem('practiq_grace_dismissed','1')"
            style="background:none;border:none;color:white;cursor:pointer;font-size:18px;line-height:1;padding:0;margin:0;"
            type="button" aria-label="Dismiss">&times;</button>
</div>
HTML;
            },
        );

        $isDemo = fn () => auth()->check() && auth()->user()->isDemo();

        FilamentAction::configureUsing(function (FilamentAction $action) use ($isDemo): void {
            if (in_array($action->getName(), ['create', 'edit', 'delete'])) {
                $action->hidden(fn () => $isDemo());
            }
        });

        BulkActionGroup::configureUsing(function (BulkActionGroup $action) use ($isDemo): void {
            $action->hidden(fn () => $isDemo());
        });
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('Practiq')
            ->login()
            ->colors([
                'primary' => Color::Teal,
            ])
            ->homeUrl('/admin/dashboard')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                'demo.mode',
                'grace.readonly',
            ])
            ->authMiddleware([
                Authenticate::class,
                RequiresActiveSubscription::class,
            ]);
    }
}
