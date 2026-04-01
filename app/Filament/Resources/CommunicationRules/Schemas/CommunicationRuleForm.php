<?php

namespace App\Filament\Resources\CommunicationRules\Schemas;

use App\Models\AppointmentType;
use App\Models\MessageTemplate;
use App\Models\Practitioner;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CommunicationRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Template & Scope')
                    ->schema([
                        Select::make('message_template_id')
                            ->label('Message Template')
                            ->options(fn () => MessageTemplate::active()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),

                        Select::make('practitioner_id')
                            ->label('Practitioner')
                            ->options(fn () => Practitioner::with('user')
                                ->get()
                                ->pluck('user.name', 'id')
                                ->filter())
                            ->placeholder('All practitioners')
                            ->searchable(),

                        Select::make('appointment_type_id')
                            ->label('Appointment Type')
                            ->options(fn () => AppointmentType::pluck('name', 'id'))
                            ->placeholder('All types')
                            ->searchable(),
                    ]),

                Section::make('Timing')
                    ->schema([
                        Select::make('timing_direction')
                            ->label('When to send')
                            ->options([
                                'at_booking' => 'At booking (immediately)',
                                'before'     => 'Before appointment',
                                'after'      => 'After appointment',
                            ])
                            ->default('before')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state === 'at_booking') {
                                    $set('send_at_offset_minutes', 0);
                                }
                            }),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('timing_amount')
                                    ->label('How many')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(24)
                                    ->live()
                                    ->afterStateUpdated(fn ($state, $set, $get) => self::updateOffset($set, $get)),

                                Select::make('timing_unit')
                                    ->label('Unit')
                                    ->options([
                                        'minutes' => 'Minutes',
                                        'hours'   => 'Hours',
                                        'days'    => 'Days',
                                        'weeks'   => 'Weeks',
                                    ])
                                    ->default('hours')
                                    ->live()
                                    ->afterStateUpdated(fn ($state, $set, $get) => self::updateOffset($set, $get)),
                            ])
                            ->visible(fn ($get) => $get('timing_direction') !== 'at_booking'),

                        Placeholder::make('timing_preview')
                            ->label('Computed timing')
                            ->content(function ($get) {
                                $direction = $get('timing_direction');
                                $amount    = (int) ($get('timing_amount') ?? 0);
                                $unit      = $get('timing_unit') ?? 'hours';

                                if ($direction === 'at_booking') {
                                    return 'This message will be sent immediately when the appointment is booked.';
                                }

                                if ($amount <= 0) {
                                    return 'Enter an amount above.';
                                }

                                $label = $amount === 1 ? rtrim($unit, 's') : $unit;
                                $when  = $direction === 'before' ? 'before' : 'after';
                                return "This reminder will be sent {$amount} {$label} {$when} the appointment.";
                            }),

                        Hidden::make('send_at_offset_minutes'),
                    ]),

                Section::make('Settings')
                    ->schema([
                        Toggle::make('is_active')
                            ->default(true)
                            ->label('Active'),
                    ]),
            ]);
    }

    private static function updateOffset($set, $get): void
    {
        $direction = $get('timing_direction');
        $amount    = (int) ($get('timing_amount') ?? 0);
        $unit      = $get('timing_unit') ?? 'hours';

        $multipliers = ['minutes' => 1, 'hours' => 60, 'days' => 1440, 'weeks' => 10080];
        $multiplier  = $multipliers[$unit] ?? 60;
        $minutes     = $amount * $multiplier;

        $offset = match ($direction) {
            'before'     => -$minutes,
            'after'      => $minutes,
            default      => 0,
        };

        $set('send_at_offset_minutes', $offset);
    }
}
