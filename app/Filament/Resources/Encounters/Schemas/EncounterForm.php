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
    private static function formatLastVisits($record): string
    {
        if (!$record || !$record->patient) {
            return '—';
        }

        $visits = $record->patient->encounters()
            ->where('id', '!=', $record->id)
            ->latest('visit_date')
            ->limit(3)
            ->get();

        if ($visits->isEmpty()) {
            return '—';
        }

        $lines = [];
        foreach ($visits as $visit) {
            $date = $visit->visit_date->format('M j');
            $chief = $visit->chief_complaint ? substr($visit->chief_complaint, 0, 25) : 'Visit';
            $lines[] = "$date — $chief ✓";
        }

        return implode("\n", $lines);
    }

    private static function formatIntakeSummary($record): string
    {
        if (!$record || !$record->patient) {
            return '—';
        }

        $intake = $record->patient->medicalHistories()
            ->where('status', 'complete')
            ->latest()
            ->first();

        if (!$intake) {
            return '—';
        }

        $chief = $intake->chief_complaint ?? '—';
        $painLabel = $intake->pain_scale_label ?? '—';
        $redFlags = $intake->hasRedFlags() ? '⚠ Red flags detected' : '✓ No red flags';

        return "Chief: $chief\nPain: $painLabel\n$redFlags";
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('practice_id')
                ->default(fn () => auth()->user()->practice_id),

            Grid::make(3)->columnSpanFull()->schema([
                // ── Left Panel: Patient Context ────────────────────────
                Section::make('Patient Context')
                    ->columnSpan(1)
                    ->schema([
                        Placeholder::make('last_visits')
                            ->label('Last Visits')
                            ->hiddenLabel()
                            ->content(fn ($record) => self::formatLastVisits($record)),

                        Placeholder::make('intake_summary')
                            ->label('Intake Summary')
                            ->hiddenLabel()
                            ->content(fn ($record) => self::formatIntakeSummary($record))
                            ->columnSpanFull(),
                    ]),

                // ── Right Panel: Encounter Details + Clinical Notes ────
                Section::make('')
                    ->columnSpan(2)
                    ->schema([
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
                                    ->live()
                                    ->afterStateUpdated(function (callable $set, $state) {
                                        if ($state) {
                                            $practitioner = \App\Models\Practitioner::find($state);
                                            if ($practitioner && $practitioner->specialty) {
                                                $disciplineMap = [
                                                    'Acupuncture' => 'acupuncture',
                                                    'Acupuncture & Oriental Medicine' => 'acupuncture',
                                                    'Traditional Chinese Medicine' => 'acupuncture',
                                                    'Massage Therapy' => 'massage',
                                                    'Massage' => 'massage',
                                                    'Chiropractic' => 'chiropractic',
                                                    'Chiropractic Care' => 'chiropractic',
                                                    'Physical Therapy' => 'physiotherapy',
                                                    'Physiotherapy' => 'physiotherapy',
                                                ];
                                                $discipline = $disciplineMap[$practitioner->specialty] ?? null;
                                                if ($discipline) {
                                                    $set('discipline', $discipline);
                                                }
                                            }
                                        }
                                    })
                                    ->disabledOn('view'),
                                Select::make('discipline')
                                    ->options([
                                        'acupuncture' => 'Acupuncture',
                                        'massage' => 'Massage Therapy',
                                        'chiropractic' => 'Chiropractic',
                                        'physiotherapy' => 'Physical Therapy',
                                    ])
                                    ->required()
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
                                ->visibleOn('view'),

                            TextInput::make('acupunctureEncounter.tcm_diagnosis')
                                ->label('TCM Diagnosis')
                                ->maxLength(255)
                                ->visibleOn('edit'),

                            Placeholder::make('tongue_body')
                                ->label('Tongue Body')
                                ->content(fn ($record) => $record?->acupunctureEncounter?->tongue_body ?? '—')
                                ->visibleOn('view'),

                            TextInput::make('acupunctureEncounter.tongue_body')
                                ->label('Tongue Body')
                                ->visibleOn('edit'),

                            Placeholder::make('tongue_coating')
                                ->label('Tongue Coating')
                                ->content(fn ($record) => $record?->acupunctureEncounter?->tongue_coating ?? '—')
                                ->visibleOn('view'),

                            TextInput::make('acupunctureEncounter.tongue_coating')
                                ->label('Tongue Coating')
                                ->visibleOn('edit'),

                            Placeholder::make('pulse_quality')
                                ->label('Pulse Quality')
                                ->content(fn ($record) => $record?->acupunctureEncounter?->pulse_quality ?? '—')
                                ->visibleOn('view'),

                            TextInput::make('acupunctureEncounter.pulse_quality')
                                ->label('Pulse Quality')
                                ->visibleOn('edit'),

                            Placeholder::make('zang_fu_diagnosis')
                                ->label('Zang-Fu Diagnosis')
                                ->content(fn ($record) => $record?->acupunctureEncounter?->zang_fu_diagnosis ?? '—')
                                ->visibleOn('view'),

                            TextInput::make('acupunctureEncounter.zang_fu_diagnosis')
                                ->label('Zang-Fu Diagnosis')
                                ->visibleOn('edit'),
                        ])->columns(2),

                    Section::make('Treatment Details')
                        ->schema([
                            Placeholder::make('points_used')
                                ->label('Points Used')
                                ->content(fn ($record) => $record?->acupunctureEncounter?->points_used ?? '—')
                                ->visibleOn('view')
                                ->columnSpanFull(),

                            Textarea::make('acupunctureEncounter.points_used')
                                ->rows(3)
                                ->visibleOn('edit')
                                ->columnSpanFull(),

                            Placeholder::make('needle_count')
                                ->label('Needle Count')
                                ->content(fn ($record) => ($record?->acupunctureEncounter?->needle_count ?? '—'))
                                ->visibleOn('view'),

                            TextInput::make('acupunctureEncounter.needle_count')
                                ->numeric()
                                ->default(0)
                                ->visibleOn('edit'),

                            Placeholder::make('treatment_protocol')
                                ->label('Treatment Protocol')
                                ->content(fn ($record) => $record?->acupunctureEncounter?->treatment_protocol ?? '—')
                                ->visibleOn('view')
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

            ]),
        ]),
        ]),
        ]);
    }
}
