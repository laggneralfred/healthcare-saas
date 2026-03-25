<?php

namespace App\Filament\Resources\Encounters\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EncountersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('practice.name')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('patient.name')
                    ->label('Patient')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('practitioner.user.name')
                    ->label('Practitioner')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'complete' => 'success',
                        'draft'    => 'warning',
                        default    => 'gray',
                    }),

                TextColumn::make('visit_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('acupunctureEncounter.tcm_diagnosis')
                    ->label('TCM Diagnosis')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('acupunctureEncounter.needle_count')
                    ->label('Needles')
                    ->alignCenter()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft'    => 'Draft',
                        'complete' => 'Complete',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('visit_date', 'desc');
    }
}
