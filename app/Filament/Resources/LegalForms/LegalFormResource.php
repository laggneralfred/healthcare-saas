<?php

namespace App\Filament\Resources\LegalForms;

use App\Filament\Resources\LegalForms\Pages\CreateLegalForm;
use App\Filament\Resources\LegalForms\Pages\EditLegalForm;
use App\Filament\Resources\LegalForms\Pages\ListLegalForms;
use App\Filament\Resources\LegalForms\Schemas\LegalFormForm;
use App\Filament\Resources\LegalForms\Tables\LegalFormsTable;
use App\Filament\Traits\BelongsToPractice;
use App\Models\LegalForm;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LegalFormResource extends Resource
{
    use BelongsToPractice;

    protected static ?string $model = LegalForm::class;

    protected static ?string $navigationLabel = 'Legal Forms';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationGroupSort = 100;

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return LegalFormForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LegalFormsTable::configure($table);
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
            'index' => ListLegalForms::route('/'),
            'create' => CreateLegalForm::route('/create'),
            'edit' => EditLegalForm::route('/{record}/edit'),
        ];
    }
}
