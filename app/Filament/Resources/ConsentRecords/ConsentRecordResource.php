<?php

namespace App\Filament\Resources\ConsentRecords;

use App\Filament\Resources\ConsentRecords\Pages\CreateConsentRecord;
use App\Filament\Resources\ConsentRecords\Pages\EditConsentRecord;
use App\Filament\Resources\ConsentRecords\Pages\ListConsentRecords;
use App\Filament\Resources\ConsentRecords\Pages\ViewConsentRecord;
use App\Filament\Resources\ConsentRecords\Schemas\ConsentRecordForm;
use App\Filament\Resources\ConsentRecords\Tables\ConsentRecordsTable;
use App\Filament\Traits\BelongsToPractice;
use App\Models\ConsentRecord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ConsentRecordResource extends Resource
{
    use BelongsToPractice;
    protected static ?string $model = ConsentRecord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|\UnitEnum|null $navigationGroup = 'Patients';
    protected static ?int $navigationGroupSort = 4;
    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return ConsentRecordForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ConsentRecordsTable::configure($table);
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
            'index' => ListConsentRecords::route('/'),
            'create' => CreateConsentRecord::route('/create'),
            'view' => ViewConsentRecord::route('/{record}'),
            'edit' => EditConsentRecord::route('/{record}/edit'),
        ];
    }
}
