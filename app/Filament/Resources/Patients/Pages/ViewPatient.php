<?php

namespace App\Filament\Resources\Patients\Pages;

use App\Filament\Resources\Patients\PatientResource;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewPatient extends ViewRecord
{
    protected static string $resource = PatientResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Summary')
                    ->columns(2)
                    ->schema([
                        Placeholder::make('appointments_count')
                            ->label('Total Appointments')
                            ->content(fn ($record) => $record->appointments()->count()),
                        Placeholder::make('practitioner.user.name')
                            ->label('Primary Practitioner')
                            ->content(fn ($record) => $record->appointments()->first()?->practitioner?->user?->name ?? 'None'),
                    ]),
            ]);
    }
}
