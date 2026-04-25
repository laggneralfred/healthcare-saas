<?php

namespace App\Filament\Resources\Encounters\Schemas;

use App\Models\Patient;
use App\Models\Practice;
use App\Services\PracticeContext;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Illuminate\Support\HtmlString;

class EncounterForm
{
    private const AI_FIELD_ACTIONS = [
        'visit_notes' => [
            'improve' => 'improveVisitNotesField',
            'accept' => 'acceptVisitNotesFieldSuggestion',
            'dismiss' => 'dismissVisitNotesFieldSuggestion',
        ],
        'subjective' => [
            'improve' => 'improveSubjectiveField',
            'accept' => 'acceptSubjectiveFieldSuggestion',
            'dismiss' => 'dismissSubjectiveFieldSuggestion',
        ],
        'objective' => [
            'improve' => 'improveObjectiveField',
            'accept' => 'acceptObjectiveFieldSuggestion',
            'dismiss' => 'dismissObjectiveFieldSuggestion',
        ],
        'assessment' => [
            'improve' => 'improveAssessmentField',
            'accept' => 'acceptAssessmentFieldSuggestion',
            'dismiss' => 'dismissAssessmentFieldSuggestion',
        ],
        'plan' => [
            'improve' => 'improvePlanField',
            'accept' => 'acceptPlanFieldSuggestion',
            'dismiss' => 'dismissPlanFieldSuggestion',
        ],
    ];

    private static function insuranceBillingEnabled(): bool
    {
        $practiceId = PracticeContext::currentPracticeId();

        if (! $practiceId) {
            return false;
        }

        return (bool) Practice::query()
            ->whereKey($practiceId)
            ->value('insurance_billing_enabled');
    }

    private static function simpleVisitNoteMode(): bool
    {
        return ! self::insuranceBillingEnabled();
    }

    private static function aiFieldAssist(string $field): array
    {
        $actions = self::AI_FIELD_ACTIONS[$field];

        return [
            Actions::make([
                Action::make($actions['improve'])
                    ->label('Improve with AI')
                    ->color(fn (Get $get): string => $get('active_ai_field') === $field && filled($get('active_ai_suggestion')) ? 'gray' : 'success')
                    ->size(Size::Small)
                    ->action($actions['improve']),
            ])
                ->hiddenOn('view')
                ->columnSpanFull(),
            Section::make('AI suggestion')
                ->description('Review before accepting. This will only update this field.')
                ->hidden(fn (Get $get): bool => $get('active_ai_field') !== $field || blank($get('active_ai_suggestion')))
                ->hiddenOn('view')
                ->schema([
                    Html::make(fn (Get $get): HtmlString => self::renderAISuggestionCard((string) $get('active_ai_suggestion'))),
                    Actions::make([
                        Action::make($actions['accept'])
                            ->label('Accept')
                            ->color('success')
                            ->size(Size::Small)
                            ->action($actions['accept']),
                        Action::make($actions['dismiss'])
                            ->label('Dismiss')
                            ->color('gray')
                            ->size(Size::Small)
                            ->action($actions['dismiss']),
                    ]),
                ])
                ->columnSpanFull(),
        ];
    }

    private static function renderAISuggestionCard(string $suggestion): HtmlString
    {
        $suggestion = nl2br(e($suggestion), false);

        return new HtmlString(<<<HTML
<div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm leading-6 text-gray-950 shadow-sm dark:border-amber-700 dark:bg-amber-950/30 dark:text-gray-100">
    <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">AI suggestion</div>
    <div class="whitespace-pre-wrap">{$suggestion}</div>
</div>
HTML);
    }

    private static function aiAssistedLabel(string $label, string $field): callable
    {
        return fn (Get $get): string => $label . ((bool) $get("ai_assisted_fields.{$field}") ? ' · AI-assisted' : '');
    }

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

    private static function formatPatientContext($record, ?int $patientId = null): HtmlString
    {
        $patient = $record?->patient;

        if (! $patient && $patientId) {
            $patient = Patient::find($patientId);
        }

        if (! $patient) {
            return new HtmlString('<div class="text-sm text-gray-500 dark:text-gray-400">Select a patient to show context.</div>');
        }

        $lines = [];
        $name = trim((string) ($patient->name ?: $patient->full_name));

        if ($name !== '') {
            $lines[] = '<div><span class="font-medium">Patient:</span> ' . e($name) . '</div>';
        }

        if ($patient->dob) {
            $lines[] = '<div><span class="font-medium">DOB:</span> ' . e($patient->dob->format('M j, Y')) . ' (' . $patient->dob->age . ')</div>';
        }

        if ($patient->phone) {
            $lines[] = '<div><span class="font-medium">Phone:</span> ' . e($patient->phone) . '</div>';
        }

        if ($patient->email) {
            $lines[] = '<div><span class="font-medium">Email:</span> ' . e($patient->email) . '</div>';
        }

        $lastVisit = $patient->encounters()
            ->when($record?->id, fn ($query) => $query->where('id', '!=', $record->id))
            ->latest('visit_date')
            ->first();

        if ($lastVisit?->visit_date) {
            $summary = $lastVisit->chief_complaint ? ' - ' . e(str($lastVisit->chief_complaint)->limit(40)) : '';
            $lines[] = '<div><span class="font-medium">Last visit:</span> ' . e($lastVisit->visit_date->format('M j, Y')) . $summary . '</div>';
        }

        $content = implode("\n", $lines);

        return new HtmlString(<<<HTML
<div class="space-y-1 text-sm text-gray-700 dark:text-gray-200">
    {$content}
</div>
HTML);
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('practice_id')
                ->default(fn () => PracticeContext::currentPracticeId()),

            Hidden::make('ai_suggestion_id')
                ->dehydrated(false),
            Hidden::make('active_ai_field')
                ->dehydrated(false),
            Hidden::make('active_ai_field_label')
                ->dehydrated(false),
            Hidden::make('active_ai_suggestion')
                ->dehydrated(false),
            Hidden::make('active_ai_suggestion_id')
                ->dehydrated(false),

            Grid::make(3)->columnSpanFull()->schema([
                Grid::make(1)
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Patient Context')
                            ->schema([
                                Html::make(fn ($record, Get $get): HtmlString => self::formatPatientContext(
                                    $record,
                                    $get('patient_id') ? (int) $get('patient_id') : null,
                                )),
                            ]),
                        Section::make('Encounter Details')
                            ->schema([
                                Select::make('patient_id')
                                    ->relationship('patient', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
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
                            ]),
                    ]),

                Tabs::make('Visit Documentation')
                    ->columnSpan(2)
                    ->tabs([
                Tab::make('Core Notes')->schema([
                    Section::make('Simple Visit Note')
                        ->visible(fn (): bool => self::simpleVisitNoteMode())
                        ->schema([
                                Textarea::make('chief_complaint')
                                    ->label('Chief Complaint')
                                    ->rows(2)
                                    ->required()
                                    ->disabledOn('view'),
                                Textarea::make('visit_notes')
                                    ->label(self::aiAssistedLabel('Visit Note / General Note', 'visit_notes'))
                                    ->rows(9)
                                    ->columnSpanFull()
                                    ->disabledOn('view'),
                                ...self::aiFieldAssist('visit_notes'),
                                Textarea::make('plan')
                                    ->label(self::aiAssistedLabel('Plan / Follow-up', 'plan'))
                                    ->rows(4)
                                    ->columnSpanFull()
                                    ->disabledOn('view'),
                                ...self::aiFieldAssist('plan'),
                        ])
                        ->columnSpanFull(),

                    Section::make('Insurance SOAP Note')
                        ->visible(fn (): bool => self::insuranceBillingEnabled())
                        ->schema([
                                Actions::make([
                                    Action::make('checkMissingDocumentation')
                                        ->label('Check Missing Documentation')
                                        ->color('gray')
                                        ->size(Size::Small)
                                        ->action('checkMissingDocumentation')
                                        ->visible(fn (): bool => self::insuranceBillingEnabled()),
                                ])
                                    ->visible(fn (): bool => self::insuranceBillingEnabled())
                                    ->hiddenOn('view')
                                    ->columnSpanFull(),
                                Textarea::make('chief_complaint')
                                    ->label('Chief Complaint')
                                    ->rows(2)
                                    ->required()
                                    ->disabledOn('view'),
                                Textarea::make('subjective')
                                    ->label(self::aiAssistedLabel('Subjective', 'subjective'))
                                    ->rows(4)
                                    ->disabledOn('view'),
                                ...self::aiFieldAssist('subjective'),
                                Textarea::make('objective')
                                    ->label(self::aiAssistedLabel('Objective', 'objective'))
                                    ->rows(4)
                                    ->disabledOn('view'),
                                ...self::aiFieldAssist('objective'),
                                Textarea::make('assessment')
                                    ->label(self::aiAssistedLabel('Assessment', 'assessment'))
                                    ->rows(4)
                                    ->disabledOn('view'),
                                ...self::aiFieldAssist('assessment'),
                                Textarea::make('plan')
                                    ->label(self::aiAssistedLabel('Plan', 'plan'))
                                    ->rows(4)
                                    ->columnSpanFull()
                                    ->disabledOn('view'),
                                ...self::aiFieldAssist('plan'),
                                Textarea::make('visit_notes')
                                    ->label(self::aiAssistedLabel('General Visit Note (Optional)', 'visit_notes'))
                                    ->rows(3)
                                    ->columnSpanFull()
                                    ->disabledOn('view'),
                                ...self::aiFieldAssist('visit_notes'),
                                Textarea::make('documentation_check_result')
                                    ->label('AI Documentation Check')
                                    ->helperText('Completeness review only. This does not modify the encounter note.')
                                    ->rows(5)
                                    ->live()
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->columnSpanFull()
                                    ->visible(fn (): bool => self::insuranceBillingEnabled())
                                    ->hiddenOn('view'),
                        ])
                        ->columnSpanFull(),
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
        ]);
    }
}
