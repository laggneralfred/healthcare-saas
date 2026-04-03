<?php

namespace App\Filament\Resources\Patients\RelationManagers;

use App\Models\States\Appointment\Cancelled;
use App\Models\States\Appointment\Checkout;
use App\Models\States\Appointment\Closed;
use App\Models\States\Appointment\Completed;
use App\Models\States\Appointment\InProgress;
use App\Models\States\Appointment\Scheduled;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AppointmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'appointments';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('practitioner_id')
                    ->relationship('practitioner', 'user.name')
                    ->required(),
                Select::make('appointment_type_id')
                    ->relationship('appointmentType', 'name')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default(Scheduled::$name),
                DateTimePicker::make('start_datetime')
                    ->required(),
                DateTimePicker::make('end_datetime')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('status')
            ->columns([
                TextColumn::make('practitioner.user.name')
                    ->label('Practitioner'),
                TextColumn::make('appointmentType.name')
                    ->label('Type'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Cancelled::$name  => 'danger',
                        'missed'          => 'warning',
                        Completed::$name  => 'success',
                        Scheduled::$name  => 'primary',
                        InProgress::$name => 'warning',
                        Closed::$name     => 'gray',
                        Checkout::$name   => 'info',
                        default           => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Scheduled::$name  => 'Scheduled',
                        InProgress::$name => 'In Progress',
                        Completed::$name  => 'Completed',
                        Closed::$name     => 'Closed',
                        Checkout::$name   => 'Checkout',
                        Cancelled::$name  => 'Cancelled',
                        default           => ucfirst($state),
                    })
                    ->searchable(),
                TextColumn::make('start_datetime')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
                AssociateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DissociateAction::make()
                    ->hidden(fn () => auth()->user()?->isDemo()),
                DeleteAction::make()
                    ->hidden(fn () => auth()->user()?->isDemo()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make()
                        ->hidden(fn () => auth()->user()?->isDemo()),
                    DeleteBulkAction::make()
                        ->hidden(fn () => auth()->user()?->isDemo()),
                ]),
            ]);
    }
}
