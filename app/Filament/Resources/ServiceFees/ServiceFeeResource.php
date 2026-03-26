<?php

namespace App\Filament\Resources\ServiceFees;

use App\Filament\Resources\ServiceFees\Pages\CreateServiceFee;
use App\Filament\Resources\ServiceFees\Pages\EditServiceFee;
use App\Filament\Resources\ServiceFees\Pages\ListServiceFees;
use App\Filament\Resources\ServiceFees\Schemas\ServiceFeeForm;
use App\Filament\Resources\ServiceFees\Tables\ServiceFeesTable;
use App\Filament\Traits\BelongsToPractice;
use App\Models\ServiceFee;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ServiceFeeResource extends Resource
{
    use BelongsToPractice;
    protected static ?string $model = ServiceFee::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    public static function form(Schema $schema): Schema
    {
        return ServiceFeeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServiceFeesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListServiceFees::route('/'),
            'create' => CreateServiceFee::route('/create'),
            'edit'   => EditServiceFee::route('/{record}/edit'),
        ];
    }
}
