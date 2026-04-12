<?php

namespace App\Filament\Resources\IntakeSubmissions;

use App\Filament\Resources\IntakeSubmissions\Pages\CreateIntakeSubmission;
use App\Filament\Resources\IntakeSubmissions\Pages\EditIntakeSubmission;
use App\Filament\Resources\IntakeSubmissions\Pages\ListIntakeSubmissions;
use App\Filament\Resources\IntakeSubmissions\Pages\ViewIntakeSubmission;
use App\Filament\Resources\IntakeSubmissions\Schemas\IntakeSubmissionForm;
use App\Filament\Resources\IntakeSubmissions\Tables\IntakeSubmissionsTable;
use App\Filament\Traits\BelongsToPractice;
use App\Models\IntakeSubmission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class IntakeSubmissionResource extends Resource
{
    use BelongsToPractice;
    protected static ?string $model = IntakeSubmission::class;

    protected static ?string $navigationLabel = 'Medical History';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Patients';

    protected static ?int $navigationGroupSort = 3;

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return IntakeSubmissionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IntakeSubmissionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListIntakeSubmissions::route('/'),
            'create' => CreateIntakeSubmission::route('/create'),
            'view'   => ViewIntakeSubmission::route('/{record}'),
            'edit'   => EditIntakeSubmission::route('/{record}/edit'),
        ];
    }
}
