<?php

namespace App\Filament\Resources\Appointments\Schemas;

use App\Models\AppointmentType;
use Carbon\Carbon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\View;

class AppointmentForm
{
    private static function computeDefaultStart(): \Carbon\Carbon
    {
        $now = now();
        $remainder = $now->minute % 30;
        if ($remainder > 0) {
            $now->addMinutes(30 - $remainder);
        }
        $now->setSecond(0);

        if ($now->hour < 8) {
            $now->setHour(8)->setMinute(0);
        }

        if ($now->hour >= 18) {
            $now->addDay()->setHour(9)->setMinute(0)->setSecond(0);
            while ($now->isWeekend()) {
                $now->addDay();
            }
        }

        return $now;
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament.resources.appointments.pages.create-request-context')
                    ->viewData(fn ($livewire): array => [
                        'appointmentRequest' => property_exists($livewire, 'appointmentRequest') ? $livewire->appointmentRequest : null,
                    ])
                    ->visible(fn ($livewire): bool => property_exists($livewire, 'appointmentRequest') && filled($livewire->appointmentRequest)),

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
                    ->live()
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

                DateTimePicker::make('start_datetime')
                    ->label('Start Time')
                    ->required()
                    ->default(fn () => self::computeDefaultStart())
                    ->minutesStep(15)
                    ->seconds(false)
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $duration = $get('duration_minutes');
                        if ($state && $duration) {
                            $set('end_datetime', Carbon::parse($state)->addMinutes((int) $duration)->format('Y-m-d H:i:00'));
                        }
                    })
                    ->disabledOn('view'),

                View::make('filament.resources.appointments.pages.create-schedule-context')
                    ->viewData(fn ($livewire): array => [
                        'scheduleContext' => method_exists($livewire, 'scheduleContext') ? $livewire->scheduleContext() : null,
                    ])
                    ->visible(fn ($livewire): bool => method_exists($livewire, 'scheduleContext') && filled($livewire->scheduleContext())),

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
                    ->minutesStep(15)
                    ->seconds(false)
                    ->default(function () {
                        $start    = self::computeDefaultStart();
                        $duration = auth()->user()->practice?->default_appointment_duration ?? 60;
                        return $start->addMinutes((int) $duration);
                    })
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
