<?php

namespace App\Filament\Resources\Encounters\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class EncounterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()->tabs([

                Tab::make('Visit')->schema([
                    Select::make('practice_id')
                        ->relationship('practice', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('patient_id')
                        ->relationship('patient', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('practitioner_id')
                        ->relationship('practitioner', 'id')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? "Practitioner #{$record->id}")
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('appointment_id')
                        ->relationship('appointment', 'id')
                        ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->patient?->name} — {$record->start_datetime?->format('M d, Y H:i')}")
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('status')
                        ->options([
                            'draft'    => 'Draft',
                            'complete' => 'Complete',
                        ])
                        ->default('draft')
                        ->required(),

                    DatePicker::make('visit_date')
                        ->required(),

                    Textarea::make('visit_notes')
                        ->rows(5)
                        ->columnSpanFull(),
                ]),

                Tab::make('Acupuncture')->schema([
                    TextInput::make('acupunctureEncounter.tcm_diagnosis')
                        ->label('TCM Diagnosis')
                        ->maxLength(255),

                    TextInput::make('acupunctureEncounter.needle_count')
                        ->label('Needle Count')
                        ->numeric()
                        ->minValue(0),

                    Textarea::make('acupunctureEncounter.points_used')
                        ->label('Points Used')
                        ->rows(3),

                    Textarea::make('acupunctureEncounter.meridians')
                        ->label('Meridians')
                        ->rows(2),

                    Textarea::make('acupunctureEncounter.treatment_protocol')
                        ->label('Treatment Protocol')
                        ->rows(3),

                    Textarea::make('acupunctureEncounter.session_notes')
                        ->label('Session Notes')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),

            ])->columnSpanFull(),
        ]);
    }
}
