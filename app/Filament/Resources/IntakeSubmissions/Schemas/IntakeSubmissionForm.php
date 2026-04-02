<?php

namespace App\Filament\Resources\IntakeSubmissions\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

class IntakeSubmissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('practice_id')
                ->default(fn () => auth()->user()->practice_id),

            Grid::make(2)->schema([
                Select::make('patient_id')
                    ->relationship('patient', 'name')
                    ->searchable()->preload()->required(),

                Select::make('discipline')
                    ->options([
                        'acupuncture'   => 'Acupuncture',
                        'massage'       => 'Massage Therapy',
                        'chiropractic'  => 'Chiropractic',
                        'physiotherapy' => 'Physiotherapy',
                        'general'       => 'General',
                    ])
                    ->nullable(),

                Select::make('appointment_id')
                    ->relationship('appointment', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($r) => "#{$r?->id} — {$r?->start_datetime?->format('M j, Y g:ia')}")
                    ->searchable()->nullable(),

                Select::make('status')
                    ->options(['missing' => 'Missing', 'complete' => 'Complete'])
                    ->default('missing')
                    ->required(),
            ]),

            Wizard::make([

                // ── Step 1: Why are you here today? ───────────────────────────────

                Step::make('Why are you here today?')
                    ->schema([
                        Textarea::make('chief_complaint')
                            ->label('Chief Complaint')
                            ->helperText('Describe your main reason for seeking treatment.')
                            ->rows(3)
                            ->columnSpanFull(),

                        Grid::make(2)->schema([
                            TextInput::make('onset_duration')
                                ->label('How long has this been going on?')
                                ->placeholder('e.g. 3 weeks, 6 months'),

                            Select::make('onset_type')
                                ->label('How did it start?')
                                ->options([
                                    'sudden'    => 'Sudden / Acute',
                                    'gradual'   => 'Gradual / Chronic',
                                    'recurring' => 'Recurring',
                                ])
                                ->nullable(),
                        ]),

                        Grid::make(2)->schema([
                            Textarea::make('aggravating_factors')
                                ->label('What makes it worse?')
                                ->rows(2),

                            Textarea::make('relieving_factors')
                                ->label('What makes it better?')
                                ->rows(2),
                        ]),

                        Grid::make(2)->schema([
                            Select::make('pain_scale')
                                ->label('Pain Level (0–10)')
                                ->options(array_combine(range(0, 10), range(0, 10)))
                                ->nullable(),

                            Toggle::make('previous_episodes')
                                ->label('Has this happened before?')
                                ->default(false)
                                ->live(),
                        ]),

                        Textarea::make('previous_episodes_description')
                            ->label('Describe previous episodes')
                            ->rows(2)
                            ->visible(fn ($get) => (bool) $get('previous_episodes'))
                            ->columnSpanFull(),
                    ]),

                // ── Step 2: Medical History ────────────────────────────────────────

                Step::make('Medical History')
                    ->schema([
                        Repeater::make('current_medications')
                            ->label('Current Medications')
                            ->schema([
                                TextInput::make('name')->label('Medication')->required(),
                                TextInput::make('dose')->label('Dose'),
                                TextInput::make('frequency')->label('Frequency'),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel('Add Medication')
                            ->collapsible()
                            ->columnSpanFull(),

                        Repeater::make('allergies')
                            ->label('Allergies')
                            ->schema([
                                TextInput::make('name')->label('Allergen')->required(),
                                TextInput::make('reaction')->label('Reaction'),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->addActionLabel('Add Allergy')
                            ->collapsible()
                            ->columnSpanFull(),

                        Repeater::make('past_diagnoses')
                            ->label('Past Diagnoses / Medical Conditions')
                            ->schema([
                                TextInput::make('condition')->label('Condition')->required(),
                                TextInput::make('year')->label('Year'),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->addActionLabel('Add Diagnosis')
                            ->collapsible()
                            ->columnSpanFull(),

                        Repeater::make('past_surgeries')
                            ->label('Past Surgeries / Hospitalizations')
                            ->schema([
                                TextInput::make('procedure')->label('Procedure')->required(),
                                TextInput::make('year')->label('Year'),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->addActionLabel('Add Surgery')
                            ->collapsible()
                            ->columnSpanFull(),

                        Section::make('Health Flags')
                            ->description('Please indicate if any of the following apply.')
                            ->schema([
                                Grid::make(2)->schema([
                                    Toggle::make('is_pregnant')
                                        ->label('Currently pregnant'),
                                    Toggle::make('has_pacemaker')
                                        ->label('Has a pacemaker / implanted device'),
                                    Toggle::make('takes_blood_thinners')
                                        ->label('Takes blood thinners / anticoagulants'),
                                    Toggle::make('has_bleeding_disorder')
                                        ->label('Has a bleeding disorder'),
                                    Toggle::make('has_infectious_disease')
                                        ->label('Has an active infectious disease'),
                                ]),
                            ]),
                    ]),

                // ── Step 3: Lifestyle & Wellness ──────────────────────────────────

                Step::make('Lifestyle & Wellness')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('exercise_frequency')
                                ->label('Exercise Frequency')
                                ->options([
                                    'never'         => 'Never',
                                    'rarely'        => 'Rarely (< once/week)',
                                    '1-2x_week'     => '1–2× per week',
                                    '3-4x_week'     => '3–4× per week',
                                    '5+x_week'      => '5+ × per week',
                                ])
                                ->nullable(),

                            Select::make('sleep_quality')
                                ->label('Sleep Quality')
                                ->options([
                                    'poor'      => 'Poor',
                                    'fair'      => 'Fair',
                                    'good'      => 'Good',
                                    'excellent' => 'Excellent',
                                ])
                                ->nullable(),

                            Select::make('sleep_hours')
                                ->label('Average Sleep (hours)')
                                ->options(array_combine(range(2, 12), array_map(fn ($h) => "{$h} hrs", range(2, 12))))
                                ->nullable(),

                            Select::make('stress_level')
                                ->label('Stress Level')
                                ->options([
                                    'low'      => 'Low',
                                    'moderate' => 'Moderate',
                                    'high'     => 'High',
                                    'very_high' => 'Very High',
                                ])
                                ->nullable(),
                        ]),

                        Textarea::make('diet_description')
                            ->label('Diet / Nutrition Notes')
                            ->rows(2)
                            ->columnSpanFull(),

                        Grid::make(2)->schema([
                            Select::make('smoking_status')
                                ->label('Smoking Status')
                                ->options([
                                    'never'   => 'Never',
                                    'former'  => 'Former smoker',
                                    'current' => 'Current smoker',
                                ])
                                ->live()
                                ->nullable(),

                            TextInput::make('smoking_amount')
                                ->label('How much / how long?')
                                ->visible(fn ($get) => $get('smoking_status') === 'current' || $get('smoking_status') === 'former'),

                            Select::make('alcohol_use')
                                ->label('Alcohol Use')
                                ->options([
                                    'none'     => 'None',
                                    'social'   => 'Social / occasional',
                                    'moderate' => 'Moderate (1–2 drinks/day)',
                                    'heavy'    => 'Heavy (3+ drinks/day)',
                                ])
                                ->nullable(),
                        ]),
                    ]),

                // ── Step 4: Previous Treatment ────────────────────────────────────

                Step::make('Previous Treatment')
                    ->schema([
                        Toggle::make('had_previous_treatment')
                            ->label('Have you received this type of treatment before?')
                            ->default(false)
                            ->live(),

                        Repeater::make('previous_treatments_tried')
                            ->label('Treatments Tried')
                            ->schema([
                                TextInput::make('treatment')->label('Treatment')->required(),
                                TextInput::make('provider')->label('Provider / Clinic'),
                                TextInput::make('duration')->label('Duration'),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel('Add Treatment')
                            ->collapsible()
                            ->visible(fn ($get) => (bool) $get('had_previous_treatment'))
                            ->columnSpanFull(),

                        Textarea::make('previous_treatment_results')
                            ->label('Results / Outcomes')
                            ->rows(3)
                            ->visible(fn ($get) => (bool) $get('had_previous_treatment'))
                            ->columnSpanFull(),

                        Toggle::make('other_practitioner')
                            ->label('Are you currently seeing another practitioner for this condition?')
                            ->default(false)
                            ->live(),

                        TextInput::make('other_practitioner_name')
                            ->label('Practitioner Name / Specialty')
                            ->visible(fn ($get) => (bool) $get('other_practitioner')),
                    ]),

                // ── Step 5: Your Goals ────────────────────────────────────────────

                Step::make('Your Goals')
                    ->schema([
                        Textarea::make('treatment_goals')
                            ->label('What are your treatment goals?')
                            ->helperText('What do you hope to achieve through this treatment?')
                            ->rows(4)
                            ->columnSpanFull(),

                        Textarea::make('success_indicators')
                            ->label('How will you know when you have reached your goals?')
                            ->helperText('What does success look like for you?')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                // ── Step 6: Informed Consent ──────────────────────────────────────

                Step::make('Informed Consent')
                    ->schema([
                        Section::make('Consent Agreement')
                            ->description(
                                'By signing below, I consent to the treatment and confirm that the information ' .
                                'provided in this form is accurate to the best of my knowledge. I understand ' .
                                'that treatment involves inherent risks and that I may ask questions at any time.'
                            )
                            ->schema([
                                Toggle::make('consent_given')
                                    ->label('I agree to the above and consent to treatment')
                                    ->required()
                                    ->live(),

                                TextInput::make('consent_signed_by')
                                    ->label('Full name (signature)')
                                    ->visible(fn ($get) => (bool) $get('consent_given'))
                                    ->required(fn ($get) => (bool) $get('consent_given')),
                            ]),
                    ]),

            ])
            ->columnSpanFull()
            ->skippable(),
        ]);
    }
}
