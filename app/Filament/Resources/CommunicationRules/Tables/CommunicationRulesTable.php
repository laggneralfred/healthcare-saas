<?php

namespace App\Filament\Resources\CommunicationRules\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CommunicationRulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('messageTemplate.name')
                    ->label('Template')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('practitioner.user.name')
                    ->label('Practitioner')
                    ->placeholder('All practitioners')
                    ->sortable(),

                TextColumn::make('appointmentType.name')
                    ->label('Appointment Type')
                    ->placeholder('All types')
                    ->sortable(),

                TextColumn::make('timing')
                    ->label('Timing')
                    ->state(fn ($record) => $record->getTimingDescription()),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
