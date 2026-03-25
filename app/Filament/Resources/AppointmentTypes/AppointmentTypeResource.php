<?php

namespace App\Filament\Resources\AppointmentTypes;

use App\Filament\Resources\AppointmentTypes\Pages\CreateAppointmentType;
use App\Filament\Resources\AppointmentTypes\Pages\EditAppointmentType;
use App\Filament\Resources\AppointmentTypes\Pages\ListAppointmentTypes;
use App\Filament\Resources\AppointmentTypes\Schemas\AppointmentTypeForm;
use App\Filament\Resources\AppointmentTypes\Tables\AppointmentTypesTable;
use App\Models\AppointmentType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AppointmentTypeResource extends Resource
{
    protected static ?string $model = AppointmentType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return AppointmentTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AppointmentTypesTable::configure($table);
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
            'index' => ListAppointmentTypes::route('/'),
            'create' => CreateAppointmentType::route('/create'),
            'edit' => EditAppointmentType::route('/{record}/edit'),
        ];
    }
}
