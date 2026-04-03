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
                        ->preload()
                        ->disabledOn('view'),
                    Select::make('practitioner_id')
                        ->relationship('practitioner', 'id')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? "Practitioner #{$record->id}")
                        ->required()
                        ->searchable()
                        ->preload()
                        ->disabledOn('view'),
                    DatePicker::make('date')
                        ->required()
                        ->default(now())
                        ->disabledOn('view'),
                    Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'final' => 'Final',
                        ])
                        ->default('draft')
                        ->required()
                        ->disabledOn('view'),
                ])->columns(2),

            Tabs::make('Clinical Documentation')->tabs([
                Tab::make('Core Notes')->schema([
                    Textarea::make('chief_complaint')
                        ->rows(3)
                        ->required()
                        ->disabledOn('view'),
                    Textarea::make('subjective')
                        ->label('Subjective (S)')
                        ->rows(5)
                        ->disabledOn('view'),
                    Textarea::make('objective')
                        ->label('Objective (O)')
                        ->rows(5)
                        ->disabledOn('view'),
                    Textarea::make('assessment')
                        ->label('Assessment (A)')
                        ->rows(5)
                        ->disabledOn('view'),
                    Textarea::make('plan')
                        ->label('Plan (P)')
                        ->rows(5)
                        ->columnSpanFull()
                        ->disabledOn('view'),
                ]),

                Tab::make('Acupuncture')->schema([
                    Section::make('Traditional Chinese Medicine (TCM)')
                        ->schema([
                            TextInput::make('acupunctureEncounter.tcm_diagnosis')
                                ->label('TCM Diagnosis')
                                ->maxLength(255)
                                ->disabledOn('view'),
                            TextInput::make('acupunctureEncounter.tongue_body')
                                ->label('Tongue Body')
                                ->disabledOn('view'),
                            TextInput::make('acupunctureEncounter.tongue_coating')
                                ->label('Tongue Coating')
                                ->disabledOn('view'),
                            TextInput::make('acupunctureEncounter.pulse_quality')
                                ->label('Pulse Quality')
                                ->disabledOn('view'),
                            TextInput::make('acupunctureEncounter.zang_fu_diagnosis')
                                ->label('Zang-Fu Diagnosis')
                                ->disabledOn('view'),
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
                                ->multiple()
                                ->disabledOn('view'),

                            Grid::make(4)
                                ->schema([
                                    TextInput::make('acupunctureEncounter.csor_color')
                                        ->label('Color (C)')
                                        ->disabledOn('view'),
                                    TextInput::make('acupunctureEncounter.csor_sound')
                                        ->label('Sound (S)')
                                        ->disabledOn('view'),
                                    TextInput::make('acupunctureEncounter.csor_odor')
                                        ->label('Odor (O)')
                                        ->disabledOn('view'),
                                    TextInput::make('acupunctureEncounter.csor_emotion')
                                        ->label('Emotion (R)')
                                        ->disabledOn('view'),
                                ]),
                        ])->columns(1),

                    TextInput::make('acupunctureEncounter.needle_count')
                        ->numeric()
                        ->default(0)
                        ->disabledOn('view'),
                    Textarea::make('acupunctureEncounter.points_used')
                        ->rows(3)
                        ->disabledOn('view'),
                    Textarea::make('acupunctureEncounter.treatment_protocol')
                        ->rows(3)
                        ->disabledOn('view'),
                ])->visible(fn ($record) => true), // Logic simplified for brevity

                Tab::make('Massage')->schema([
                    TextInput::make('massageEncounter.technique_used')
                        ->disabledOn('view'),
                    TextInput::make('massageEncounter.pressure_level')
                        ->disabledOn('view'),
                    Textarea::make('massageEncounter.areas_focused')
                        ->disabledOn('view'),
                ])->visible(fn ($record) => true),

                Tab::make('Chiropractic')->schema([
                    TextInput::make('chiropracticEncounter.adjustment_level')
                        ->disabledOn('view'),
                    TextInput::make('chiropracticEncounter.technique')
                        ->disabledOn('view'),
                ])->visible(fn ($record) => true),

                Tab::make('Physiotherapy')->schema([
                    TextInput::make('physiotherapyEncounter.exercise_program')
                        ->disabledOn('view'),
                    TextInput::make('physiotherapyEncounter.equipment_used')
                        ->disabledOn('view'),
                ])->visible(fn ($record) => true),

            ])->columnSpanFull(),
        ]);
    }
}
