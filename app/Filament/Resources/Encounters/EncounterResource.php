<?php

namespace App\Filament\Resources\Encounters;

use App\Filament\Resources\Encounters\Pages\CreateEncounter;
use App\Filament\Resources\Encounters\Pages\EditEncounter;
use App\Filament\Resources\Encounters\Pages\ListEncounters;
use App\Filament\Resources\Encounters\Pages\ViewEncounter;
use App\Filament\Resources\Encounters\Schemas\EncounterForm;
use App\Filament\Resources\Encounters\Tables\EncountersTable;
use App\Filament\Traits\BelongsToPractice;
use App\Models\Encounter;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EncounterResource extends Resource
{
    use BelongsToPractice;
    protected static ?string $model = Encounter::class;

    protected static ?string $navigationLabel = 'Visits';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocument;

    protected static string|\UnitEnum|null $navigationGroup = 'Patients';

    protected static ?int $navigationGroupSort = 2;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'id';

    public static function getGloballySearchableAttributes(): array
    {
        return ['patient.first_name', 'patient.last_name', 'patient.name', 'chief_complaint'];
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        $date = $record->visit_date?->format('M j, Y') ?? '—';
        $name = $record->patient?->name ?? 'Visit';

        return "{$name} — {$date}";
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return array_filter([
            'Practitioner'    => $record->practitioner?->user?->name,
            'Chief Complaint' => $record->chief_complaint ? \Illuminate\Support\Str::limit($record->chief_complaint, 60) : null,
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return EncounterForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EncountersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListEncounters::route('/'),
            'create' => CreateEncounter::route('/create'),
            'view'   => ViewEncounter::route('/{record}'),
            'edit'   => EditEncounter::route('/{record}/edit'),
        ];
    }
}
