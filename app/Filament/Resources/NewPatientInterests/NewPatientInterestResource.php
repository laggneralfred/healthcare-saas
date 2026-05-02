<?php

namespace App\Filament\Resources\NewPatientInterests;

use App\Filament\Resources\NewPatientInterests\Pages\ListNewPatientInterests;
use App\Filament\Resources\NewPatientInterests\Pages\ViewNewPatientInterest;
use App\Filament\Resources\NewPatientInterests\Tables\NewPatientInterestsTable;
use App\Filament\Traits\BelongsToPractice;
use App\Models\NewPatientInterest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class NewPatientInterestResource extends Resource
{
    use BelongsToPractice;

    protected static ?string $model = NewPatientInterest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    protected static string|\UnitEnum|null $navigationGroup = 'Patients';

    protected static ?string $navigationLabel = 'New Patient Interests';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return NewPatientInterestsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNewPatientInterests::route('/'),
            'view' => ViewNewPatientInterest::route('/{record}'),
        ];
    }
}
