<?php

namespace App\Filament\Pages;

use App\Services\Billing\SubscriptionPlanCatalog;
use App\Services\PracticeContext;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class BillingReadinessPage extends Page
{
    protected static ?string $slug = 'billing-readiness';

    protected static ?string $title = 'Billing Readiness';

    protected static ?string $navigationLabel = 'Billing Readiness';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 8;

    protected string $view = 'filament.pages.billing-readiness';

    public static function canAccess(): bool
    {
        return PracticeContext::isSuperAdmin();
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    protected function getViewData(): array
    {
        $catalog = app(SubscriptionPlanCatalog::class);

        return [
            'readiness' => $catalog->readiness(),
            'catalog' => $catalog,
        ];
    }
}
