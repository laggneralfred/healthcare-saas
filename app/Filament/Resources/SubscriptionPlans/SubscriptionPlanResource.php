<?php

namespace App\Filament\Resources\SubscriptionPlans;

use App\Filament\Resources\SubscriptionPlans\Pages\EditSubscriptionPlan;
use App\Filament\Resources\SubscriptionPlans\Pages\ListSubscriptionPlans;
use App\Filament\Resources\SubscriptionPlans\Schemas\SubscriptionPlanForm;
use App\Filament\Resources\SubscriptionPlans\Tables\SubscriptionPlansTable;
use App\Models\SubscriptionPlan;
use App\Services\PracticeContext;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SubscriptionPlanResource extends Resource
{
    protected static ?string $model = SubscriptionPlan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 7;

    protected static ?string $navigationLabel = 'Subscription Plans';

    protected static ?string $modelLabel = 'Subscription Plan';

    protected static ?string $pluralModelLabel = 'Subscription Plans';

    public static function canAccess(): bool
    {
        return PracticeContext::isSuperAdmin();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return SubscriptionPlanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubscriptionPlansTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubscriptionPlans::route('/'),
            'edit' => EditSubscriptionPlan::route('/{record}/edit'),
        ];
    }
}
