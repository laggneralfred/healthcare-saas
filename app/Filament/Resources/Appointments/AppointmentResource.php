<?php

namespace App\Filament\Resources\Appointments;

use App\Filament\Resources\Appointments\Pages\CreateAppointment;
use App\Filament\Resources\Appointments\Pages\EditAppointment;
use App\Filament\Resources\Appointments\Pages\ListAppointments;
use App\Filament\Resources\Appointments\Pages\ViewAppointment;
use App\Filament\Resources\Appointments\Schemas\AppointmentForm;
use App\Filament\Resources\Appointments\Tables\AppointmentsTable;
use App\Filament\Traits\BelongsToPractice;
use App\Models\Appointment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AppointmentResource extends Resource
{
    use BelongsToPractice {
        getEloquentQuery as getPracticeScopedEloquentQuery;
    }
    protected static ?string $model = Appointment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Calendar';

    protected static ?int $navigationGroupSort = 10;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'id';

    public static function getGloballySearchableAttributes(): array
    {
        return ['patient.first_name', 'patient.last_name', 'patient.name'];
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        $date = $record->start_datetime?->format('M j, Y g:i A') ?? '—';
        $name = $record->patient?->name ?? 'Appointment';

        return "{$name} — {$date}";
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return array_filter([
            'Practitioner' => $record->practitioner?->user?->name,
            'Status'       => is_object($record->status) ? class_basename($record->status) : $record->status,
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return AppointmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AppointmentsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = static::getPracticeScopedEloquentQuery();
        $user = auth()->user();

        if ($user?->isPractitioner() && ! $user->canManageOperations()) {
            $practitionerId = $user->practitioner()->value('id');

            return $practitionerId
                ? $query->where('practitioner_id', $practitionerId)
                : $query->whereRaw('1 = 0');
        }

        return $query;
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
            'index' => ListAppointments::route('/'),
            'create' => CreateAppointment::route('/create'),
            'view' => ViewAppointment::route('/{record}'),
            'edit' => EditAppointment::route('/{record}/edit'),
        ];
    }
}
