<?php

namespace App\Filament\Resources\ConsentRecords\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ConsentRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('patient.name')->searchable()->sortable(),
                TextColumn::make('practice.name')->searchable()->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => $state === 'complete' ? 'success' : 'warning'),
                TextColumn::make('consent_given_by')->searchable()->placeholder('—'),
                TextColumn::make('signed_on')->dateTime()->sortable()->placeholder('—'),
                TextColumn::make('appointment.start_datetime')
                    ->label('Appointment')
                    ->dateTime()
                    ->placeholder('No appointment')
                    ->toggleable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
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
            ->emptyStateHeading('No consent records yet')
            ->emptyStateDescription('Signed consents will appear here after patients complete their forms.');
    }
}
