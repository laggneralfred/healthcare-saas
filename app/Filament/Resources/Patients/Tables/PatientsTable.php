<?php

namespace App\Filament\Resources\Patients\Tables;

use App\Models\Patient;
use App\Services\PatientCareStatusService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PatientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('practice.name')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('care_status')
                    ->label('Care Status')
                    ->state(fn (Patient $record): string => self::careStatus($record)['label'])
                    ->badge()
                    ->color(fn (Patient $record): string => self::careStatus($record)['color'])
                    ->tooltip(fn (Patient $record): string => self::careStatus($record)['helper']),
                TextColumn::make('preferred_language_label')
                    ->label('Language')
                    ->badge()
                    ->toggleable(),
                IconColumn::make('is_patient')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No patients yet')
            ->emptyStateDescription('Add your first patient to get started.')
            ->emptyStateActions([
                CreateAction::make()->label('Add patient'),
            ]);
    }

    private static function careStatus(Patient $patient): array
    {
        static $statuses = [];

        return $statuses[$patient->getKey()] ??= app(PatientCareStatusService::class)->forPatient($patient);
    }
}
