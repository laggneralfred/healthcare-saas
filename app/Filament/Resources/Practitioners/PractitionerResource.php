<?php

namespace App\Filament\Resources\Practitioners;

use App\Filament\Resources\Practitioners\Pages\CreatePractitioner;
use App\Filament\Resources\Practitioners\Pages\EditPractitioner;
use App\Filament\Resources\Practitioners\Pages\ListPractitioners;
use App\Filament\Resources\Practitioners\Schemas\PractitionerForm;
use App\Filament\Resources\Practitioners\Tables\PractitionersTable;
use App\Filament\Traits\BelongsToPractice;
use App\Models\Practitioner;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PractitionerResource extends Resource
{
    use BelongsToPractice;
    protected static ?string $model = Practitioner::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|\UnitEnum|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return PractitionerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PractitionersTable::configure($table);
    }

    public static function getWidgets(): array
    {
        return [
            Widgets\PractitionerStats::class,
        ];
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPractitioners::route('/'),
            'create' => CreatePractitioner::route('/create'),
            'view' => Pages\ViewPractitioner::route('/{record}'),
            'edit' => EditPractitioner::route('/{record}/edit'),
        ];
    }
}
