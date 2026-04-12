<?php

namespace App\Filament\Resources\Encounters\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
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
                    DatePicker::make('visit_date')
                        ->required()
                        ->default(now())
                        ->disabledOn('view'),
                    Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'complete' => 'Complete',
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
                            Placeholder::make('tcm_diagnosis')
                                ->label('TCM Diagnosis')
                                ->content(fn ($record) => $record?->acupunctureEncounter?->tcm_diagnosis ?? '—')
                                ->visible(fn () => auth()->user()?->id), // Show in view mode

                            TextInput::make('acupunctureEncounter.tcm_diagnosis')
                                ->label('TCM Diagnosis')
                                ->maxLength(255)
                                ->visibleOn('edit')
                                ->hidden(),

                            Placeholder::make('tongue_body')
                                ->label('Tongue Body')
                                ->content(fn ($record) => $record?->acupunctureEncounter?->tongue_body ?? '—')
                                ->visible(fn () => auth()->user()?->id),

                            TextInput::make('acupunctureEncounter.tongue_body')
                                ->label('Tongue Body')
                                ->visibleOn('edit'),

                            Placeholder::make('tongue_coating')
                                ->label('Tongue Coating')
                                ->content(fn ($record) => $record?->acupunctureEncounter?->tongue_coating ?? '—')
                                ->visible(fn () => auth()->user()?->id),

                            TextInput::make('acupunctureEncounter.tongue_coating')
                                ->label('Tongue Coating')
                                ->visibleOn('edit'),

                            Placeholder::make('pulse_quality')
                                ->label('Pulse Quality')
                                ->content(fn ($record) => $record?->acupunctureEncounter?->pulse_quality ?? '—')
                                ->visible(fn () => auth()->user()?->id),

                            TextInput::make('acupunctureEncounter.pulse_quality')
                                ->label('Pulse Quality')
                                ->visibleOn('edit'),

                            Placeholder::make('zang_fu_diagnosis')
                                ->label('Zang-Fu Diagnosis')
                                ->content(fn ($record) => $record?->acupunctureEncounter?->zang_fu_diagnosis ?? '—')
                                ->visible(fn () => auth()->user()?->id),

                            TextInput::make('acupunctureEncounter.zang_fu_diagnosis')
                                ->label('Zang-Fu Diagnosis')
                                ->visibleOn('edit'),
                        ])->columns(2),

                    Section::make('Treatment Details')
                        ->schema([
                            Placeholder::make('points_used')
                                ->label('Points Used')
                                ->content(fn ($record) => $record?->acupunctureEncounter?->points_used ?? '—')
                                ->visible(fn () => auth()->user()?->id)
                                ->columnSpanFull(),

                            Textarea::make('acupunctureEncounter.points_used')
                                ->rows(3)
                                ->visibleOn('edit')
                                ->columnSpanFull(),

                            Placeholder::make('needle_count')
                                ->label('Needle Count')
                                ->content(fn ($record) => ($record?->acupunctureEncounter?->needle_count ?? '—'))
                                ->visible(fn () => auth()->user()?->id),

                            TextInput::make('acupunctureEncounter.needle_count')
                                ->numeric()
                                ->default(0)
                                ->visibleOn('edit'),

                            Placeholder::make('treatment_protocol')
                                ->label('Treatment Protocol')
                                ->content(fn ($record) => $record?->acupunctureEncounter?->treatment_protocol ?? '—')
                                ->visible(fn () => auth()->user()?->id)
                                ->columnSpanFull(),

                            Textarea::make('acupunctureEncounter.treatment_protocol')
                                ->rows(3)
                                ->visibleOn('edit')
                                ->columnSpanFull(),
                        ])->columns(2),
                ])->visible(fn ($record) => $record?->discipline === 'acupuncture'),

                Tab::make('Massage')->schema([
                    TextInput::make('massageEncounter.technique_used')
                        ->disabledOn('view'),
                    TextInput::make('massageEncounter.pressure_level')
                        ->disabledOn('view'),
                    Textarea::make('massageEncounter.areas_focused')
                        ->disabledOn('view'),
                ])->visible(fn ($record) => $record?->discipline === 'massage'),

                Tab::make('Chiropractic')->schema([
                    TextInput::make('chiropracticEncounter.adjustment_level')
                        ->disabledOn('view'),
                    TextInput::make('chiropracticEncounter.technique')
                        ->disabledOn('view'),
                ])->visible(fn ($record) => $record?->discipline === 'chiropractic'),

                Tab::make('Physiotherapy')->schema([
                    TextInput::make('physiotherapyEncounter.exercise_program')
                        ->disabledOn('view'),
                    TextInput::make('physiotherapyEncounter.equipment_used')
                        ->disabledOn('view'),
                ])->visible(fn ($record) => $record?->discipline === 'physiotherapy'),

            ])->columnSpanFull(),
        ]);
    }
}
