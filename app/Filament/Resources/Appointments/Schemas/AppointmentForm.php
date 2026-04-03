<?php

namespace App\Filament\Resources\Appointments\Schemas;

use App\Models\States\Appointment\Checkout;
use App\Models\States\Appointment\Closed;
use App\Models\States\Appointment\Completed;
use App\Models\States\Appointment\InProgress;
use App\Models\States\Appointment\Scheduled;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('practice_id')
                    ->default(fn () => auth()->user()->practice_id),

                Select::make('patient_id')
                    ->relationship('patient', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabledOn('view'),

                Select::make('practitioner_id')
                    ->relationship('practitioner', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? "Practitioner #{$record->id}")
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabledOn('view'),

                Select::make('appointment_type_id')
                    ->relationship('appointmentType', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabledOn('view'),

                Select::make('status')
                    ->options([
                        Scheduled::$name  => 'Scheduled',
                        InProgress::$name => 'In Progress',
                        Completed::$name  => 'Completed',
                        Closed::$name     => 'Closed',
                        Checkout::$name   => 'Checkout',
                    ])
                    ->default(Scheduled::$name)
                    ->required()
                    ->disabledOn('view'),

                DateTimePicker::make('start_datetime')
                    ->required()
                    ->disabledOn('view'),

                DateTimePicker::make('end_datetime')
                    ->required()
                    ->after('start_datetime')
                    ->disabledOn('view'),

                Toggle::make('needs_follow_up')
                    ->default(false)
                    ->disabledOn('view'),

                Textarea::make('notes')
                    ->rows(3)
                    ->nullable()
                    ->disabledOn('view'),
            ]);
    }
}
