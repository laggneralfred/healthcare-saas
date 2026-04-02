<?php

namespace App\Filament\Resources\Encounters\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class EncounterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('practice_id')
                ->default(fn () => auth()->user()->practice_id),

            Section::make('Encounter Details')
                ->schema([
                    Select::make('patient_id')
                        ->relationship('patient', 'name')
                        ->required()
                        ->searchable()
                        ->preload(),
                    Select::make('practitioner_id')
                        ->relationship('practitioner', 'id')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? "Practitioner #{$record->id}")
                        ->required()
                        ->searchable()
                        ->preload(),
                    DatePicker::make('date')
                        ->required()
                        ->default(now()),
                    Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'final' => 'Final',
                        ])
                        ->default('draft')
                        ->required(),
                ])->columns(2),

            Tabs::make('Clinical Documentation')->tabs([
                Tab::make('Core Notes')->schema([
                    Textarea::make('chief_complaint')
                        ->rows(3)
                        ->required(),
                    Textarea::make('subjective')
                        ->label('Subjective (S)')
                        ->rows(5),
                    Textarea::make('objective')
                        ->label('Objective (O)')
                        ->rows(5),
                    Textarea::make('assessment')
                        ->label('Assessment (A)')
                        ->rows(5),
                    Textarea::make('plan')
                        ->label('Plan (P)')
                        ->rows(5)
                        ->columnSpanFull(),
                ]),

                Tab::make('Acupuncture')->schema([
                    Section::make('Traditional Chinese Medicine (TCM)')
                        ->schema([
                            TextInput::make('acupunctureEncounter.tcm_diagnosis')
                                ->label('TCM Diagnosis')
                                ->maxLength(255),
                            TextInput::make('acupunctureEncounter.tongue_body')
                                ->label('Tongue Body'),
                            TextInput::make('acupunctureEncounter.tongue_coating')
                                ->label('Tongue Coating'),
                            TextInput::make('acupunctureEncounter.pulse_quality')
                                ->label('Pulse Quality'),
                            TextInput::make('acupunctureEncounter.zang_fu_diagnosis')
                                ->label('Zang-Fu Diagnosis'),
                        ])->columns(2),

                    Section::make('Worsley Five Element')
                        ->schema([
                            Select::make('acupunctureEncounter.five_elements')
                                ->label('Five Elements')
                                ->options([
                                    'Wood'  => 'Wood',
                                    'Fire'  => 'Fire',
                                    'Earth' => 'Earth',
                                    'Metal' => 'Metal',
                                    'Water' => 'Water',
                                ])
                                ->multiple(),

                            Grid::make(4)
                                ->schema([
                                    TextInput::make('acupunctureEncounter.csor_color')
                                        ->label('Color (C)'),
                                    TextInput::make('acupunctureEncounter.csor_sound')
                                        ->label('Sound (S)'),
                                    TextInput::make('acupunctureEncounter.csor_odor')
                                        ->label('Odor (O)'),
                                    TextInput::make('acupunctureEncounter.csor_emotion')
                                        ->label('Emotion (R)'),
                                ]),
                        ])->columns(1),

                    TextInput::make('acupunctureEncounter.needle_count')
                        ->numeric()
                        ->default(0),
                    Textarea::make('acupunctureEncounter.points_used')
                        ->rows(3),
                    Textarea::make('acupunctureEncounter.treatment_protocol')
                        ->rows(3),
                ])->visible(fn ($record) => true), // Logic simplified for brevity

                Tab::make('Massage')->schema([
                    TextInput::make('massageEncounter.technique_used'),
                    TextInput::make('massageEncounter.pressure_level'),
                    Textarea::make('massageEncounter.areas_focused'),
                ])->visible(fn ($record) => true),

                Tab::make('Chiropractic')->schema([
                    TextInput::make('chiropracticEncounter.adjustment_level'),
                    TextInput::make('chiropracticEncounter.technique'),
                ])->visible(fn ($record) => true),

                Tab::make('Physiotherapy')->schema([
                    TextInput::make('physiotherapyEncounter.exercise_program'),
                    TextInput::make('physiotherapyEncounter.equipment_used'),
                ])->visible(fn ($record) => true),

            ])->columnSpanFull(),
        ]);
    }
}
