<?php

namespace App\Filament\Resources\Appointments\Tables;

use App\Models\States\Appointment\Checkout;
use App\Models\States\Appointment\Closed;
use App\Models\States\Appointment\Completed;
use App\Models\States\Appointment\InProgress;
use App\Models\States\Appointment\Scheduled;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AppointmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('practice.name')->sortable()->searchable()->toggleable(),
                TextColumn::make('patient.name')->label('Patient')->sortable()->searchable(),
                TextColumn::make('practitioner.user.name')->label('Practitioner')->sortable()->searchable(),
                TextColumn::make('appointmentType.name')->label('Type')->sortable()->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Scheduled::$name  => 'info',
                        InProgress::$name => 'warning',
                        Completed::$name  => 'success',
                        Closed::$name     => 'gray',
                        Checkout::$name   => 'primary',
                        default           => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Scheduled::$name  => 'Scheduled',
                        InProgress::$name => 'In Progress',
                        Completed::$name  => 'Completed',
                        Closed::$name     => 'Closed',
                        Checkout::$name   => 'Checkout',
                        default           => $state,
                    }),
                TextColumn::make('start_datetime')->dateTime()->sortable(),
                TextColumn::make('end_datetime')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('needs_follow_up')->boolean()->label('Follow-up'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
