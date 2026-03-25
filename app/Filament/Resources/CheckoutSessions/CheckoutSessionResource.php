<?php

namespace App\Filament\Resources\CheckoutSessions;

use App\Filament\Resources\CheckoutSessions\Pages\CreateCheckoutSession;
use App\Filament\Resources\CheckoutSessions\Pages\EditCheckoutSession;
use App\Filament\Resources\CheckoutSessions\Pages\ListCheckoutSessions;
use App\Filament\Resources\CheckoutSessions\Schemas\CheckoutSessionForm;
use App\Filament\Resources\CheckoutSessions\Tables\CheckoutSessionsTable;
use App\Models\CheckoutSession;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CheckoutSessionResource extends Resource
{
    protected static ?string $model = CheckoutSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

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
            'edit'   => EditCheckoutSession::route('/{record}/edit'),
        ];
    }
}
