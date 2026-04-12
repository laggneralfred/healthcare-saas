<?php

namespace App\Filament\Resources\CheckoutSessions;

use App\Filament\Resources\CheckoutSessions\Pages\CreateCheckoutSession;
use App\Filament\Resources\CheckoutSessions\Pages\EditCheckoutSession;
use App\Filament\Resources\CheckoutSessions\Pages\ListCheckoutSessions;
use App\Filament\Resources\CheckoutSessions\Pages\ViewCheckoutSession;
use App\Filament\Resources\CheckoutSessions\Schemas\CheckoutSessionForm;
use App\Filament\Resources\CheckoutSessions\Tables\CheckoutSessionsTable;
use App\Filament\Traits\BelongsToPractice;
use App\Models\CheckoutSession;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CheckoutSessionResource extends Resource
{
    use BelongsToPractice;
    protected static ?string $model = CheckoutSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static string|\UnitEnum|null $navigationGroup = 'Billing';

    protected static ?int $navigationGroupSort = 40;

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return CheckoutSessionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CheckoutSessionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListCheckoutSessions::route('/'),
            'create' => CreateCheckoutSession::route('/create'),
            'view'   => ViewCheckoutSession::route('/{record}'),
            'edit'   => EditCheckoutSession::route('/{record}/edit'),
        ];
    }
}
