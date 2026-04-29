<?php

namespace App\Filament\Resources\MedicalHistories\Schemas;

use App\Models\Practice;
use App\Models\Practitioner;
use App\Services\PracticeContext;
use App\Support\ClinicalStyle;
use App\Support\PracticeType;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
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

class MedicalHistoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('practice_id')
                ->default(fn () => auth()->user()->practice_id),

            Hidden::make('discipline')
                ->default(fn () => PracticeType::disciplineFallback(
                    PracticeType::fromPractice(auth()->user()?->practice),
                )),

            Grid::make(2)->schema([
                Select::make('patient_id')
                    ->relationship('patient', 'name')
                    ->searchable()->preload()->required(),

                Select::make('appointment_id')
                    ->relationship('appointment', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($r) => "#{$r?->id} — {$r?->start_datetime?->format('M j, Y g:ia')}")
                    ->searchable()->nullable(),

                Select::make('practitioner_id')
                    ->label('Assigned Practitioner')
                    ->helperText('Used to choose the practitioner’s clinical style for intake labels and AI summaries. Leave blank to use the practice default.')
                    ->options(fn () => self::practitionerOptions())
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->live(),

                Select::make('status')
                    ->options(['missing' => 'Missing', 'complete' => 'Complete', 'pending' => 'Pending'])
                    ->default('missing')
                    ->required(),
            ]),

            Wizard::make([

                // ── Step 1: Why are you here today? ──────────────────────────────

                Step::make('Why are you here today?')
                    ->schema([
                        Placeholder::make('practice_type_context')
                            ->label('Practice Type')
                            ->content(fn ($record, $get) => self::practiceTypeLabel($record, $get('practitioner_id')))
                            ->helperText('This comes from the assigned practitioner when set, otherwise Practice Settings.')
                            ->columnSpanFull(),

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
                                    'sudden' => 'Sudden / Acute',
                                    'gradual' => 'Gradual / Chronic',
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

                // ── Step 2: Medical History ───────────────────────────────────────

                Step::make('Medical History')
                    ->schema([
                        Repeater::make('current_medications')
                            ->label('Current Medications')
                            ->schema([
                                TextInput::make('name')->label('Medication')->required(),
                                TextInput::make('dose')->label('Dose'),
                                TextInput::make('frequency')->label('Frequency'),
                            ])
                            ->columns(3)->defaultItems(0)->addActionLabel('Add Medication')
                            ->collapsible()->columnSpanFull(),

                        Repeater::make('allergies')
                            ->label('Allergies')
                            ->schema([
                                TextInput::make('name')->label('Allergen')->required(),
                                TextInput::make('reaction')->label('Reaction'),
                            ])
                            ->columns(2)->defaultItems(0)->addActionLabel('Add Allergy')
                            ->collapsible()->columnSpanFull(),

                        Repeater::make('past_diagnoses')
                            ->label('Past Diagnoses / Medical Conditions')
                            ->schema([
                                TextInput::make('condition')->label('Condition')->required(),
                                TextInput::make('year')->label('Year'),
                            ])
                            ->columns(2)->defaultItems(0)->addActionLabel('Add Diagnosis')
                            ->collapsible()->columnSpanFull(),

                        Repeater::make('past_surgeries')
                            ->label('Past Surgeries / Hospitalizations')
                            ->schema([
                                TextInput::make('procedure')->label('Procedure')->required(),
                                TextInput::make('year')->label('Year'),
                            ])
                            ->columns(2)->defaultItems(0)->addActionLabel('Add Surgery')
                            ->collapsible()->columnSpanFull(),

                        Section::make('Health Flags')
                            ->description('Please indicate if any of the following apply.')
                            ->schema([
                                Grid::make(2)->schema([
                                    Toggle::make('is_pregnant')->label('Currently pregnant'),
                                    Toggle::make('has_pacemaker')->label('Has a pacemaker / implanted device'),
                                    Toggle::make('takes_blood_thinners')->label('Takes blood thinners / anticoagulants'),
                                    Toggle::make('has_bleeding_disorder')->label('Has a bleeding disorder'),
                                    Toggle::make('has_infectious_disease')->label('Has an active infectious disease'),
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
                                    'never' => 'Never',
                                    'rarely' => 'Rarely (< once/week)',
                                    '1-2x_week' => '1–2× per week',
                                    '3-4x_week' => '3–4× per week',
                                    '5+x_week' => '5+ × per week',
                                ])->nullable(),

                            Select::make('sleep_quality')
                                ->label('Sleep Quality')
                                ->options([
                                    'poor' => 'Poor',
                                    'fair' => 'Fair',
                                    'good' => 'Good',
                                    'excellent' => 'Excellent',
                                ])->nullable(),

                            Select::make('sleep_hours')
                                ->label('Average Sleep (hours)')
                                ->options(array_combine(range(2, 12), array_map(fn ($h) => "{$h} hrs", range(2, 12))))
                                ->nullable(),

                            Select::make('stress_level')
                                ->label('Stress Level')
                                ->options([
                                    'low' => 'Low',
                                    'moderate' => 'Moderate',
                                    'high' => 'High',
                                    'very_high' => 'Very High',
                                ])->nullable(),
                        ]),

                        Textarea::make('diet_description')
                            ->label('Diet / Nutrition Notes')
                            ->rows(2)->columnSpanFull(),

                        Grid::make(2)->schema([
                            Select::make('smoking_status')
                                ->label('Smoking Status')
                                ->options([
                                    'never' => 'Never',
                                    'former' => 'Former smoker',
                                    'current' => 'Current smoker',
                                ])->live()->nullable(),

                            TextInput::make('smoking_amount')
                                ->label('How much / how long?')
                                ->visible(fn ($get) => in_array($get('smoking_status'), ['current', 'former'])),

                            Select::make('alcohol_use')
                                ->label('Alcohol Use')
                                ->options([
                                    'none' => 'None',
                                    'social' => 'Social / occasional',
                                    'moderate' => 'Moderate (1–2 drinks/day)',
                                    'heavy' => 'Heavy (3+ drinks/day)',
                                ])->nullable(),
                        ]),
                    ]),

                // ── Step 4: Previous Treatment ────────────────────────────────────

                Step::make('Previous Treatment')
                    ->schema([
                        Toggle::make('had_previous_treatment')
                            ->label('Have you received this type of treatment before?')
                            ->default(false)->live(),

                        Repeater::make('previous_treatments_tried')
                            ->label('Treatments Tried')
                            ->schema([
                                TextInput::make('treatment')->label('Treatment')->required(),
                                TextInput::make('provider')->label('Provider / Clinic'),
                                TextInput::make('duration')->label('Duration'),
                            ])
                            ->columns(3)->defaultItems(0)->addActionLabel('Add Treatment')
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
                            ->default(false)->live(),

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
                            ->rows(4)->columnSpanFull(),

                        Textarea::make('success_indicators')
                            ->label('How will you know when you have reached your goals?')
                            ->helperText('What does success look like for you?')
                            ->rows(4)->columnSpanFull(),
                    ]),

                // ── Step 6: Treatment-Specific Information ────────────────────────

                Step::make('Treatment-Specific Information')
                    ->icon('heroicon-o-beaker')
                    ->schema([

                        // ── ACUPUNCTURE ───────────────────────────────────────────

                        Section::make(fn ($record, $get) => self::acupunctureIntakeHeading($record, $get('practitioner_id')))
                            ->visible(fn ($get) => $get('discipline') === 'acupuncture')
                            ->statePath('discipline_responses.tcm')
                            ->schema([

                                Section::make('Energy & Constitution')->columns(2)->schema([
                                    Select::make('energy_level')
                                        ->label('Overall energy level')
                                        ->options([
                                            'low' => 'Low — often fatigued',
                                            'moderate' => 'Moderate — varies through day',
                                            'high' => 'High — generally energetic',
                                        ])->nullable(),

                                    Select::make('energy_time_pattern')
                                        ->label('When is your energy lowest?')
                                        ->options([
                                            'morning' => 'Morning',
                                            'afternoon' => 'Afternoon',
                                            'evening' => 'Evening',
                                            'no_pattern' => 'No consistent pattern',
                                        ])->nullable(),

                                    Select::make('temperature_preference')
                                        ->label('Do you tend to run hot or cold?')
                                        ->options([
                                            'hot' => 'Run hot — prefer cool environments',
                                            'cold' => 'Run cold — prefer warm environments',
                                            'neutral' => 'Neutral',
                                        ])->nullable(),
                                ]),

                                Section::make('Digestion & Appetite')->columns(2)->schema([
                                    Select::make('appetite')
                                        ->label('Appetite')
                                        ->options([
                                            'poor' => 'Poor — often not hungry',
                                            'normal' => 'Normal',
                                            'excessive' => 'Excessive — frequently hungry',
                                        ])->nullable(),

                                    CheckboxList::make('digestion_issues')
                                        ->label('Any digestive issues? (check all that apply)')
                                        ->options([
                                            'bloating' => 'Bloating',
                                            'gas' => 'Gas',
                                            'constipation' => 'Constipation',
                                            'diarrhea' => 'Diarrhea',
                                            'acid_reflux' => 'Acid reflux',
                                            'nausea' => 'Nausea',
                                            'none' => 'None',
                                        ])->columns(3),

                                    Select::make('bowel_frequency')
                                        ->label('Bowel movements')
                                        ->options([
                                            'less_than_daily' => 'Less than daily',
                                            'once_daily' => 'Once daily',
                                            'twice_daily' => 'Twice daily',
                                            'more' => 'More than twice daily',
                                        ])->nullable(),

                                    Select::make('thirst')
                                        ->label('Thirst level')
                                        ->options([
                                            'low' => 'Low — rarely thirsty',
                                            'normal' => 'Normal',
                                            'high' => 'High — frequently thirsty',
                                        ])->nullable(),

                                    Select::make('beverage_preference')
                                        ->label('Preference for beverages')
                                        ->options([
                                            'hot' => 'Prefer hot drinks',
                                            'cold' => 'Prefer cold drinks',
                                            'room' => 'Room temperature',
                                        ])->nullable(),
                                ]),

                                Section::make('Sleep')->columns(2)->schema([
                                    CheckboxList::make('sleep_issues')
                                        ->label('Sleep concerns (check all that apply)')
                                        ->options([
                                            'falling_asleep' => 'Difficulty falling asleep',
                                            'staying_asleep' => 'Difficulty staying asleep',
                                            'early_waking' => 'Wake too early',
                                            'vivid_dreams' => 'Vivid or disturbing dreams',
                                            'night_sweats' => 'Night sweats',
                                            'none' => 'No issues',
                                        ])->columns(2),

                                    Select::make('dream_frequency')
                                        ->label('How often do you dream?')
                                        ->options([
                                            'rarely' => 'Rarely',
                                            'sometimes' => 'Sometimes',
                                            'often' => 'Often — most nights',
                                        ])->nullable(),
                                ]),

                                Section::make('Emotional Health')->columns(2)->schema([
                                    CheckboxList::make('emotional_tendencies')
                                        ->label('Which emotions are most prominent for you?')
                                        ->options([
                                            'stress' => 'Stress',
                                            'anxiety' => 'Anxiety',
                                            'depression' => 'Low mood or depression',
                                            'anger' => 'Irritability or anger',
                                            'grief' => 'Grief or sadness',
                                            'worry' => 'Overthinking or worry',
                                            'balanced' => 'Generally balanced',
                                        ])->columns(2),

                                    Select::make('emotional_impact')
                                        ->label('How much do emotions affect your physical health?')
                                        ->options([
                                            'not_much' => 'Not much',
                                            'somewhat' => 'Somewhat',
                                            'significantly' => 'Significantly',
                                        ])->nullable(),
                                ]),

                                Section::make("Women's Health — Optional")->schema([
                                    Toggle::make('menstrual_applicable')
                                        ->label('Menstrual cycle questions apply to me')
                                        ->default(false)->live(),

                                    Grid::make(2)->schema([
                                        TextInput::make('cycle_length')
                                            ->label('Cycle length (days)')
                                            ->visible(fn ($get) => (bool) $get('menstrual_applicable')),

                                        TextInput::make('period_duration')
                                            ->label('Period duration (days)')
                                            ->visible(fn ($get) => (bool) $get('menstrual_applicable')),

                                        Select::make('flow')
                                            ->label('Flow')
                                            ->options([
                                                'light' => 'Light',
                                                'moderate' => 'Moderate',
                                                'heavy' => 'Heavy',
                                            ])
                                            ->nullable()
                                            ->visible(fn ($get) => (bool) $get('menstrual_applicable')),

                                        Select::make('period_pain')
                                            ->label('Period pain')
                                            ->options([
                                                'none' => 'None',
                                                'mild' => 'Mild',
                                                'moderate' => 'Moderate',
                                                'severe' => 'Severe',
                                            ])
                                            ->nullable()
                                            ->visible(fn ($get) => (bool) $get('menstrual_applicable')),

                                        Toggle::make('clots')
                                            ->label('Blood clots present')
                                            ->visible(fn ($get) => (bool) $get('menstrual_applicable')),

                                        CheckboxList::make('pms_symptoms')
                                            ->label('PMS symptoms')
                                            ->options([
                                                'mood' => 'Mood changes',
                                                'bloating' => 'Bloating',
                                                'breast_tenderness' => 'Breast tenderness',
                                                'fatigue' => 'Fatigue',
                                                'cramps' => 'Cramps',
                                                'none' => 'None',
                                            ])
                                            ->columns(3)
                                            ->visible(fn ($get) => (bool) $get('menstrual_applicable')),
                                    ]),
                                ]),

                                Section::make('Previous Acupuncture')->columns(2)->schema([
                                    Toggle::make('previous_acupuncture')
                                        ->label('Have you received acupuncture before?')
                                        ->default(false)->live(),

                                    Select::make('previous_acupuncture_experience')
                                        ->label('Overall experience')
                                        ->options([
                                            'positive' => 'Positive',
                                            'neutral' => 'Neutral',
                                            'negative' => 'Negative',
                                            'mixed' => 'Mixed',
                                        ])
                                        ->nullable()
                                        ->visible(fn ($get) => (bool) $get('previous_acupuncture')),

                                    Select::make('needle_comfort')
                                        ->label('Comfort level with needles')
                                        ->options([
                                            'comfortable' => 'Comfortable',
                                            'nervous' => 'A little nervous',
                                            'very_nervous' => 'Very nervous',
                                            'phobia' => 'Needle phobia',
                                        ])->nullable(),
                                ]),

                            ]),

                        // ── MASSAGE THERAPY ───────────────────────────────────────

                        Section::make('Massage Therapy Intake')
                            ->visible(fn ($get) => $get('discipline') === 'massage')
                            ->statePath('discipline_responses.massage')
                            ->schema([

                                Section::make('Treatment Focus')->columns(1)->schema([
                                    CheckboxList::make('focus_areas')
                                        ->label('Areas to focus on (check all that apply)')
                                        ->options([
                                            'neck' => 'Neck',
                                            'shoulders' => 'Shoulders',
                                            'upper_back' => 'Upper back',
                                            'lower_back' => 'Lower back',
                                            'hips' => 'Hips and glutes',
                                            'arms' => 'Arms and hands',
                                            'legs' => 'Legs',
                                            'feet' => 'Feet',
                                            'full_body' => 'Full body',
                                        ])->columns(3),

                                    Textarea::make('areas_to_avoid')
                                        ->label('Any areas to avoid?')
                                        ->placeholder('e.g. left knee, recent scar on abdomen')
                                        ->rows(2),

                                    Select::make('pressure_preference')
                                        ->label('Pressure preference')
                                        ->options([
                                            'light' => 'Light',
                                            'medium' => 'Medium',
                                            'firm' => 'Firm',
                                            'deep' => 'Deep tissue',
                                            'unsure' => "Not sure — therapist's discretion",
                                        ])->nullable(),
                                ]),

                                Section::make('Previous Massage')->columns(2)->schema([
                                    Toggle::make('previous_massage')
                                        ->label('Have you received massage therapy before?')
                                        ->default(false)->live(),

                                    CheckboxList::make('massage_types')
                                        ->label('Types experienced')
                                        ->options([
                                            'swedish' => 'Swedish',
                                            'deep_tissue' => 'Deep tissue',
                                            'sports' => 'Sports massage',
                                            'thai' => 'Thai massage',
                                            'hot_stone' => 'Hot stone',
                                            'prenatal' => 'Prenatal',
                                            'other' => 'Other',
                                        ])
                                        ->columns(3)
                                        ->visible(fn ($get) => (bool) $get('previous_massage')),

                                    Select::make('previous_massage_reaction')
                                        ->label('How did you typically feel after?')
                                        ->options([
                                            'great' => 'Great — very beneficial',
                                            'good' => 'Good — generally positive',
                                            'sore' => 'Sore afterward',
                                            'no_effect' => 'No noticeable effect',
                                            'negative' => 'Negative reaction',
                                        ])
                                        ->nullable()
                                        ->visible(fn ($get) => (bool) $get('previous_massage')),
                                ]),

                                Section::make('Health Considerations')->columns(2)->schema([
                                    CheckboxList::make('skin_conditions')
                                        ->label('Skin conditions (check all that apply)')
                                        ->options([
                                            'eczema' => 'Eczema',
                                            'psoriasis' => 'Psoriasis',
                                            'rashes' => 'Rashes',
                                            'open_wounds' => 'Open wounds or sores',
                                            'sunburn' => 'Sunburn',
                                            'none' => 'None',
                                        ])->columns(3),

                                    Textarea::make('recent_injuries')
                                        ->label('Recent injuries, surgeries, or acute conditions')
                                        ->rows(2),

                                    Toggle::make('varicose_veins')
                                        ->label('Varicose veins present'),

                                    Toggle::make('osteoporosis')
                                        ->label('Osteoporosis or fragile bones'),
                                ]),

                                Section::make('Comfort & Preferences')->columns(1)->schema([
                                    Select::make('draping_comfort')
                                        ->label('Draping preference')
                                        ->options([
                                            'standard' => 'Standard draping',
                                            'extra_coverage' => 'Extra coverage preferred',
                                            'discuss' => 'Prefer to discuss with therapist',
                                        ])->nullable(),

                                    CheckboxList::make('session_goals')
                                        ->label("Goals for today's session")
                                        ->options([
                                            'relaxation' => 'Relaxation',
                                            'pain_relief' => 'Pain relief',
                                            'injury_recovery' => 'Injury recovery',
                                            'stress' => 'Stress reduction',
                                            'range_of_motion' => 'Improve range of motion',
                                            'sports' => 'Sports performance',
                                        ])->columns(3),
                                ]),

                            ]),

                        // ── CHIROPRACTIC ──────────────────────────────────────────

                        Section::make('Chiropractic Intake')
                            ->visible(fn ($get) => $get('discipline') === 'chiropractic')
                            ->statePath('discipline_responses.chiro')
                            ->schema([

                                Section::make('Pain Assessment')->columns(2)->schema([
                                    CheckboxList::make('pain_locations')
                                        ->label('Location of pain/discomfort')
                                        ->options([
                                            'neck' => 'Neck',
                                            'upper_back' => 'Upper back',
                                            'mid_back' => 'Mid back',
                                            'lower_back' => 'Lower back',
                                            'shoulder' => 'Shoulder',
                                            'elbow' => 'Elbow',
                                            'wrist' => 'Wrist or hand',
                                            'hip' => 'Hip',
                                            'knee' => 'Knee',
                                            'ankle' => 'Ankle or foot',
                                            'headache' => 'Headache',
                                        ])->columns(3),

                                    CheckboxList::make('pain_character')
                                        ->label('Character of pain (check all that apply)')
                                        ->options([
                                            'sharp' => 'Sharp',
                                            'dull' => 'Dull aching',
                                            'burning' => 'Burning',
                                            'shooting' => 'Shooting',
                                            'throbbing' => 'Throbbing',
                                            'stiffness' => 'Stiffness',
                                            'pressure' => 'Pressure or heaviness',
                                        ])->columns(3),

                                    Toggle::make('pain_radiation')
                                        ->label('Does pain radiate or travel to another area?')
                                        ->live(),

                                    TextInput::make('radiation_description')
                                        ->label('Where does it travel?')
                                        ->visible(fn ($get) => (bool) $get('pain_radiation')),
                                ]),

                                Section::make('Onset & Mechanism')->columns(2)->schema([
                                    Select::make('onset_mechanism')
                                        ->label('How did this condition start?')
                                        ->options([
                                            'accident' => 'Motor vehicle accident',
                                            'work_injury' => 'Work-related injury',
                                            'sports' => 'Sports or exercise injury',
                                            'lifting' => 'Lifting or bending',
                                            'repetitive' => 'Repetitive strain',
                                            'gradual' => 'Gradual onset — no specific event',
                                            'unknown' => 'Unknown',
                                        ])
                                        ->nullable()->live(),

                                    DatePicker::make('accident_date')
                                        ->label('Date of accident or injury')
                                        ->visible(fn ($get) => in_array($get('onset_mechanism'), ['accident', 'work_injury', 'sports', 'lifting'])),

                                    Toggle::make('workers_comp')
                                        ->label('Is this a workers compensation claim?'),

                                    Toggle::make('mva_claim')
                                        ->label('Is this a motor vehicle accident claim?'),
                                ]),

                                Section::make('Neurological Symptoms')->columns(2)->schema([
                                    CheckboxList::make('neurological_symptoms')
                                        ->label('Neurological symptoms (check all that apply)')
                                        ->options([
                                            'numbness' => 'Numbness',
                                            'tingling' => 'Tingling or pins and needles',
                                            'weakness' => 'Muscle weakness',
                                            'coordination' => 'Balance or coordination issues',
                                            'bowel_bladder' => 'Bowel or bladder changes',
                                            'none' => 'None of the above',
                                        ])->columns(2)->live(),

                                    TextInput::make('symptom_location')
                                        ->label('Where are these symptoms located?')
                                        ->visible(fn ($get) => ! empty($get('neurological_symptoms'))
                                            && $get('neurological_symptoms') !== ['none']),
                                ]),

                                Section::make('Previous Imaging & Care')->columns(2)->schema([
                                    Toggle::make('previous_imaging')
                                        ->label('Have you had X-rays, MRI, or CT scans?')
                                        ->live(),

                                    Textarea::make('imaging_findings')
                                        ->label('Findings (if known)')
                                        ->rows(2)
                                        ->visible(fn ($get) => (bool) $get('previous_imaging')),

                                    Toggle::make('previous_chiropractic')
                                        ->label('Have you received chiropractic care before?')
                                        ->live(),

                                    Select::make('previous_chiro_outcome')
                                        ->label('Outcome of previous care')
                                        ->options([
                                            'very_helpful' => 'Very helpful',
                                            'somewhat' => 'Somewhat helpful',
                                            'no_effect' => 'No effect',
                                            'made_worse' => 'Made symptoms worse',
                                        ])
                                        ->nullable()
                                        ->visible(fn ($get) => (bool) $get('previous_chiropractic')),

                                    Select::make('adjustment_consent')
                                        ->label('Comfort level with spinal adjustments')
                                        ->options([
                                            'comfortable' => 'Comfortable and informed',
                                            'questions' => 'Have some questions first',
                                            'prefer_gentle' => 'Prefer gentle techniques only',
                                        ])->nullable(),
                                ]),

                            ]),

                        // ── PHYSIOTHERAPY ─────────────────────────────────────────

                        Section::make('Physiotherapy Intake')
                            ->visible(fn ($get) => $get('discipline') === 'physiotherapy')
                            ->statePath('discipline_responses.physio')
                            ->schema([

                                Section::make('Functional Impact')->columns(2)->schema([
                                    Textarea::make('functional_limitations')
                                        ->label('What activities are you unable to do or find difficult because of this condition?')
                                        ->rows(3)
                                        ->required()
                                        ->columnSpanFull(),

                                    Select::make('work_status')
                                        ->label('Current work status')
                                        ->options([
                                            'normal' => 'Working normally',
                                            'modified' => 'On modified or light duties',
                                            'off_work' => 'Off work due to this condition',
                                            'not_working' => 'Not currently employed',
                                            'retired' => 'Retired',
                                        ])->nullable(),

                                    Select::make('work_demands')
                                        ->label('Physical demands of your work')
                                        ->options([
                                            'sedentary' => 'Mostly sedentary (desk work)',
                                            'light' => 'Light physical activity',
                                            'moderate' => 'Moderate physical activity',
                                            'heavy' => 'Heavy physical activity',
                                            'na' => 'Not applicable',
                                        ])->nullable(),

                                    Textarea::make('recreational_impact')
                                        ->label('Recreational activities or sports affected')
                                        ->rows(2)
                                        ->columnSpanFull(),
                                ]),

                                Section::make('Pain Behavior')->columns(2)->schema([
                                    Toggle::make('morning_stiffness')
                                        ->label('Morning stiffness present')
                                        ->live(),

                                    Select::make('morning_stiffness_duration')
                                        ->label('How long does morning stiffness last?')
                                        ->options([
                                            'under_30' => 'Under 30 minutes',
                                            '30_to_60' => '30-60 minutes',
                                            'over_60' => 'Over 60 minutes',
                                        ])
                                        ->nullable()
                                        ->visible(fn ($get) => (bool) $get('morning_stiffness')),

                                    Select::make('activity_effect')
                                        ->label('Effect of activity on symptoms')
                                        ->options([
                                            'better' => 'Symptoms improve with activity',
                                            'worse' => 'Symptoms worsen with activity',
                                            'no_change' => 'No change with activity',
                                            'mixed' => 'Mixed — depends on the activity',
                                        ])->nullable(),

                                    Select::make('rest_effect')
                                        ->label('Effect of rest on symptoms')
                                        ->options([
                                            'better' => 'Rest helps',
                                            'worse' => 'Rest makes it worse',
                                            'no_change' => 'No change with rest',
                                        ])->nullable(),
                                ]),

                                Section::make('Previous Physiotherapy')->columns(2)->schema([
                                    Toggle::make('previous_physio')
                                        ->label('Have you received physiotherapy before?')
                                        ->live(),

                                    Select::make('previous_physio_outcome')
                                        ->label('Outcome of previous physiotherapy')
                                        ->options([
                                            'very_helpful' => 'Very helpful',
                                            'somewhat' => 'Somewhat helpful',
                                            'no_effect' => 'No effect',
                                            'incomplete' => 'Did not complete treatment',
                                        ])
                                        ->nullable()
                                        ->visible(fn ($get) => (bool) $get('previous_physio')),

                                    Toggle::make('physician_referral')
                                        ->label('Referred by a physician for this visit?')
                                        ->live(),

                                    TextInput::make('referring_physician')
                                        ->label('Referring physician name')
                                        ->visible(fn ($get) => (bool) $get('physician_referral')),
                                ]),

                                Section::make('Goals & Expectations')->columns(1)->schema([
                                    CheckboxList::make('functional_goals')
                                        ->label('Primary goals (check all that apply)')
                                        ->options([
                                            'return_work' => 'Return to full work duties',
                                            'return_sport' => 'Return to sport or exercise',
                                            'daily_activities' => 'Manage daily activities independently',
                                            'pain_reduction' => 'Reduce pain levels',
                                            'posture' => 'Improve posture',
                                            'education' => 'Understand my condition better',
                                        ])->columns(2),

                                    Select::make('timeline_expectation')
                                        ->label('Expected recovery timeline')
                                        ->options([
                                            'weeks' => 'A few weeks',
                                            'months' => 'Several months',
                                            'ongoing' => 'Ongoing management',
                                            'unsure' => 'Not sure',
                                        ])->nullable(),
                                ]),

                            ]),

                    ]),

                // ── Step 7: Informed Consent ──────────────────────────────────────

                Step::make('Informed Consent')
                    ->schema([
                        Section::make('Consent Agreement')
                            ->description(
                                'By signing below, I consent to the treatment and confirm that the information '.
                                'provided in this form is accurate to the best of my knowledge. I understand '.
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

    private static function practiceTypeLabel($record = null, $practitionerId = null): string
    {
        return PracticeType::label(self::practiceType($record, $practitionerId));
    }

    private static function acupunctureIntakeHeading($record = null, $practitionerId = null): string
    {
        return match (self::practiceType($record, $practitionerId)) {
            PracticeType::TCM_ACUPUNCTURE => 'TCM Acupuncture Intake',
            PracticeType::FIVE_ELEMENT_ACUPUNCTURE => 'Five Element Acupuncture Intake',
            default => 'Acupuncture Intake',
        };
    }

    private static function practiceType($record = null, $practitionerId = null): string
    {
        if (blank($practitionerId) && $record) {
            return ClinicalStyle::fromMedicalHistory($record);
        }

        $practitioner = $practitionerId
            ? Practitioner::query()->whereKey($practitionerId)->first()
            : null;

        $practice = PracticeContext::currentPracticeId()
            ? Practice::query()->find(PracticeContext::currentPracticeId())
            : auth()->user()?->practice;

        return ClinicalStyle::fromPractitioner($practitioner, $practice);
    }

    private static function practitionerOptions(): array
    {
        $practiceId = PracticeContext::currentPracticeId();

        if (! $practiceId) {
            return [];
        }

        return Practitioner::query()
            ->with('user:id,name')
            ->where('practice_id', $practiceId)
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (Practitioner $practitioner) => [
                $practitioner->id => $practitioner->user?->name ?? "Practitioner #{$practitioner->id}",
            ])
            ->all();
    }
}
