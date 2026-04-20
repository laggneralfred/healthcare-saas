<?php

namespace App\Filament\Resources\Appointments\Schemas;

use App\Models\AppointmentType;
use App\Models\States\Appointment\Checkout;
use App\Models\States\Appointment\Closed;
use App\Models\States\Appointment\Completed;
use App\Models\States\Appointment\InProgress;
use App\Models\States\Appointment\Scheduled;
use Carbon\Carbon;
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
                    ->label('Appointment Type')
                    ->relationship('appointmentType', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        if (! $state) {
                            return;
                        }
                        $type = AppointmentType::find($state);
                        if (! $type || ! $type->duration_minutes) {
                            return;
                        }
                        $set('duration_minutes', $type->duration_minutes);
                        $start = $get('start_datetime');
                        if ($start) {
                            $set('end_datetime', Carbon::parse($start)->addMinutes($type->duration_minutes)->format('Y-m-d H:i:00'));
                        }
                    })
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
                    ->label('Start Time')
                    ->required()
                    ->default(function () {
                        $now = now();
                        $floored = (int) floor($now->minute / 15) * 15;
                        return $now->setMinute($floored)->setSecond(0);
                    })
                    ->seconds(false)
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $duration = $get('duration_minutes');
                        if ($state && $duration) {
                            $set('end_datetime', Carbon::parse($state)->addMinutes((int) $duration)->format('Y-m-d H:i:00'));
                        }
                    })
                    ->disabledOn('view'),

                Select::make('duration_minutes')
                    ->label('Duration')
                    ->options([
                        15  => '15 min',
                        30  => '30 min',
                        45  => '45 min',
                        60  => '1 hour',
                        75  => '1 hr 15 min',
                        90  => '1.5 hours',
                        120 => '2 hours',
                    ])
                    ->default(60)
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $start = $get('start_datetime');
                        if ($start && $state) {
                            $set('end_datetime', Carbon::parse($start)->addMinutes((int) $state)->format('Y-m-d H:i:00'));
                        }
                    })
                    ->disabledOn('view'),

                DateTimePicker::make('end_datetime')
                    ->label('End Time')
                    ->required()
                    ->after('start_datetime')
                    ->seconds(false)
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
