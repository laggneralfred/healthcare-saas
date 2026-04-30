<?php

namespace App\Filament\Resources\PracticePaymentMethods;

use App\Filament\Resources\PracticePaymentMethods\Pages\EditPracticePaymentMethod;
use App\Filament\Resources\PracticePaymentMethods\Pages\ListPracticePaymentMethods;
use App\Filament\Resources\PracticePaymentMethods\Schemas\PracticePaymentMethodForm;
use App\Filament\Resources\PracticePaymentMethods\Tables\PracticePaymentMethodsTable;
use App\Filament\Traits\BelongsToPractice;
use App\Models\PracticePaymentMethod;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PracticePaymentMethodResource extends Resource
{
    use BelongsToPractice;

    protected static ?string $model = PracticePaymentMethod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|\UnitEnum|null $navigationGroup = 'Checkout';

    protected static ?int $navigationGroupSort = 60;

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Payment Methods';

    public static function form(Schema $schema): Schema
    {
        return PracticePaymentMethodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PracticePaymentMethodsTable::configure($table);
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
            'index' => ListPracticePaymentMethods::route('/'),
            'edit' => EditPracticePaymentMethod::route('/{record}/edit'),
        ];
    }
}
