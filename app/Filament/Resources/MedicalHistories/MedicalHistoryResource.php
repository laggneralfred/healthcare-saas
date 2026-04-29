<?php

namespace App\Filament\Resources\MedicalHistories;

use App\Filament\Resources\MedicalHistories\Pages\CreateMedicalHistory;
use App\Filament\Resources\MedicalHistories\Pages\EditMedicalHistory;
use App\Filament\Resources\MedicalHistories\Pages\ListMedicalHistories;
use App\Filament\Resources\MedicalHistories\Pages\ViewMedicalHistory;
use App\Filament\Resources\MedicalHistories\Schemas\MedicalHistoryForm;
use App\Filament\Resources\MedicalHistories\Tables\MedicalHistoriesTable;
use App\Filament\Traits\BelongsToPractice;
use App\Models\MedicalHistory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MedicalHistoryResource extends Resource
{
    use BelongsToPractice {
        getEloquentQuery as getPracticeScopedEloquentQuery;
    }
    protected static ?string $model = MedicalHistory::class;

    protected static ?string $navigationLabel = 'Medical History';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Patients';

    protected static ?int $navigationGroupSort = 3;

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return MedicalHistoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MedicalHistoriesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = static::getPracticeScopedEloquentQuery();
        $user = auth()->user();

        if ($user?->isPractitioner() && ! $user->canManageOperations()) {
            $practitionerId = $user->practitioner()->value('id');

            return $practitionerId
                ? $query->where(function (Builder $query) use ($practitionerId): void {
                    $query->where('practitioner_id', $practitionerId)
                        ->orWhere(function (Builder $query) use ($practitionerId): void {
                            $query->whereNull('practitioner_id')
                                ->whereHas('appointment', fn (Builder $query) => $query->where('practitioner_id', $practitionerId));
                        });
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
            'index'  => ListMedicalHistories::route('/'),
            'create' => CreateMedicalHistory::route('/create'),
            'view'   => ViewMedicalHistory::route('/{record}'),
            'edit'   => EditMedicalHistory::route('/{record}/edit'),
        ];
    }
}
