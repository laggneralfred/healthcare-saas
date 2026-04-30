<?php

namespace App\Filament\Resources\Patients;

use App\Filament\Resources\Patients\Pages\CreatePatient;
use App\Filament\Resources\Patients\Pages\EditPatient;
use App\Filament\Resources\Patients\Pages\ListPatients;
use App\Filament\Resources\Patients\RelationManagers;
use App\Filament\Resources\Patients\Schemas\PatientForm;
use App\Filament\Resources\Patients\Tables\PatientsTable;
use App\Filament\Traits\BelongsToPractice;
use App\Models\Patient;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PatientResource extends Resource
{
    use BelongsToPractice {
        getEloquentQuery as getPracticeScopedEloquentQuery;
    }
    protected static ?string $model = Patient::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Patients';

    protected static ?int $navigationGroupSort = 1;

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Patients';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'name', 'email', 'phone'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return array_filter([
            'Email' => $record->email,
            'Phone' => $record->phone,
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return PatientForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PatientsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = static::getPracticeScopedEloquentQuery()
            ->with([
                'appointments',
                'encounters',
            ]);
        $user = auth()->user();

        if ($user?->isPractitioner() && ! $user->canManageOperations()) {
            $practitionerId = $user->practitioner()->value('id');

            return $practitionerId
                ? $query->where(function ($query) use ($practitionerId): void {
                    $query->whereHas('appointments', fn ($query) => $query->where('practitioner_id', $practitionerId))
                        ->orWhereHas('encounters', fn ($query) => $query->where('practitioner_id', $practitionerId));
                })
                : $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPatients::route('/'),
            'create' => CreatePatient::route('/create'),
            'view' => Pages\ViewPatient::route('/{record}'),
            'edit' => EditPatient::route('/{record}/edit'),
        ];
    }
}
