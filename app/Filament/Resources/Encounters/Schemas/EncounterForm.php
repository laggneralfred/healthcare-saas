<?php

namespace App\Filament\Resources\Encounters\Schemas;

use App\Models\Encounter;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Services\EncounterDisciplineTemplate;
use App\Services\EncounterNoteDocument;
use App\Services\PracticeContext;
use App\Support\ClinicalStyle;
use App\Support\PracticeType;
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

    private static function insuranceBillingEnabled(?Encounter $record = null): bool
    {
        if ($record?->exists) {
            return (bool) Practice::query()
                ->whereKey($record->practice_id)
                ->value('insurance_billing_enabled');
        }

        $practiceId = PracticeContext::currentPracticeId();

        if (! $practiceId) {
            return false;
        }

        return (bool) Practice::query()
            ->whereKey($practiceId)
            ->value('insurance_billing_enabled');
    }

    private static function simpleVisitNoteMode(?Encounter $record = null): bool
    {
        return ! self::insuranceBillingEnabled($record);
    }

    private static function currentPracticeType(?Encounter $record = null, ?int $practitionerId = null): string
    {
        if ($record?->exists) {
            $record->loadMissing(['practice', 'practitioner']);

            return ClinicalStyle::fromEncounter($record);
        }

        if ($practitionerId) {
            $practitioner = Practitioner::query()
                ->with('practice:id,practice_type,discipline')
                ->select(['id', 'practice_id', 'clinical_style'])
                ->whereKey($practitionerId)
                ->first();

            if ($practitioner) {
                return ClinicalStyle::fromPractitioner($practitioner, $practitioner->practice);
            }
        }

        $practiceId = PracticeContext::currentPracticeId();

        if (! $practiceId) {
            return PracticeType::GENERAL_WELLNESS;
        }

        $practice = Practice::query()
            ->select(['practice_type', 'discipline'])
            ->whereKey($practiceId)
            ->first();

        return PracticeType::fromPractice($practice);
    }

    private static function isFiveElementStyle(?Encounter $record = null, ?int $practitionerId = null): bool
    {
        return self::currentPracticeType($record, $practitionerId) === PracticeType::FIVE_ELEMENT_ACUPUNCTURE;
    }

    private static function applyDisciplineTemplate(callable $set, Get $get, ?string $discipline, ?Encounter $record = null): void
    {
        $document = (string) $get('visit_note_document');

        if (EncounterDisciplineTemplate::isBlankOrTemplate($document)) {
            $practitionerId = $get('practitioner_id') ? (int) $get('practitioner_id') : null;

            $set('visit_note_document', EncounterNoteDocument::template(self::currentPracticeType($record, $practitionerId)));
        }
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

    private static function renderAISuggestionCard(string $suggestion, string $label = 'AI Suggestion'): HtmlString
    {
        $suggestion = nl2br(e($suggestion), false);
        $label = e($label);

        return new HtmlString(<<<HTML
<div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm leading-6 text-gray-950 shadow-sm dark:border-amber-700 dark:bg-amber-950/30 dark:text-gray-100">
    <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">{$label}</div>
    <div class="whitespace-pre-wrap">{$suggestion}</div>
</div>
HTML);
    }

    private static function renderModeIndicator(bool $insuranceBillingEnabled): HtmlString
    {
        $label = $insuranceBillingEnabled ? 'SOAP / Insurance Mode' : 'Simple Visit Note Mode';
        $description = $insuranceBillingEnabled
            ? 'This practice has insurance billing enabled, so visit notes use structured SOAP fields.'
            : 'This practice has insurance billing disabled, so visit notes use one unified writing surface.';

        return new HtmlString(<<<HTML
<div style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; font-size: 0.8125rem; color: #6b7280;">
    <span style="display: inline-flex; align-items: center; border: 1px solid #d1d5db; background-color: #f9fafb; border-radius: 0.375rem; padding: 0.2rem 0.45rem; font-weight: 600; color: #374151;">{$label}</span>
    <span style="line-height: 1.35;">{$description}</span>
</div>
HTML);
    }

    private static function renderMobileVisitNoteStyles(): HtmlString
    {
        return new HtmlString(<<<HTML
<style>
    textarea.practiq-visit-note-field,
    textarea.practiq-soap-note-field {
        font-size: 1rem;
        line-height: 1.7;
    }

    @media (max-width: 768px) {
        .practiq-encounter-main {
            order: -1;
        }

        .practiq-encounter-sidebar {
            order: 1;
        }

        textarea.practiq-visit-note-field {
            min-height: 62vh;
            padding: 1rem;
        }

        textarea.practiq-soap-note-field {
            min-height: 9rem;
            padding: 0.875rem;
        }
    }
</style>
HTML);
    }

    private static function renderDictationTip(): HtmlString
    {
        return new HtmlString(<<<HTML
<div class="rounded-lg border border-sky-100 bg-sky-50 px-3 py-2 text-sm leading-6 text-sky-900 dark:border-sky-900/60 dark:bg-sky-950/30 dark:text-sky-100">
    Tip: On your phone, tap the microphone on your keyboard to dictate your note.
</div>
HTML);
    }

    private static function renderFiveElementPulseReference(): HtmlString
    {
        return new HtmlString(<<<HTML
<div class="rounded-lg border border-amber-100 bg-amber-50 px-3 py-3 text-sm leading-6 text-amber-950 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-100">
    <div class="font-semibold">Five Element pulse shorthand</div>
    <div class="mt-1">+++ very strong / excess; ++ strong; + slightly strong; = even / balanced; - slightly weak; -- weak; --- very weak / depleted; 0 absent or barely perceptible.</div>
    <div class="mt-1">Officials: L, LI, St, Sp, Ht, SI, B, K, PC, TB, GB, Lv. Optional numeric style: 0 absent, 1 very weak, 2 weak, 3 moderate, 4 strong, 5 very strong.</div>
</div>
HTML);
    }

    private static function simpleNoteAssist(): array
    {
        return [
            Actions::make([
                Action::make('resetVisitNoteTemplate')
                    ->label('Reset Template')
                    ->color('gray')
                    ->size(Size::Small)
                    ->requiresConfirmation(fn (Get $get): bool => self::resetTemplateNeedsConfirmation($get))
                    ->modalHeading('Reset Visit Note template?')
                    ->modalDescription('This replaces the current editor text with the current Clinical Style template. Click Save Note to keep the change.')
                    ->modalSubmitActionLabel('Reset Template')
                    ->action('resetVisitNoteTemplate'),
            ])
                ->hiddenOn('view')
                ->columnSpanFull(),
            Actions::make([
                Action::make('improveNote')
                    ->label('AI Assist / Improve with AI')
                    ->color('gray')
                    ->size(Size::Small)
                    ->action('improveNote'),
            ])
                ->hiddenOn('view')
                ->columnSpanFull(),
            Section::make()
                ->hidden(fn (Get $get): bool => blank($get('ai_suggestion')))
                ->hiddenOn('view')
                ->schema([
                    Html::make(fn (Get $get): HtmlString => self::renderAISuggestionCard(
                        (string) $get('ai_suggestion'),
                        filled($get('ai_suggestion')) ? 'AI Draft' : '',
                    )),
                    Actions::make([
                        Action::make('replaceNoteWithAIDraft')
                            ->label('Replace Note')
                            ->color('success')
                            ->size(Size::Small)
                            ->action('replaceNoteWithAIDraft'),
                        Action::make('insertAIDraftBelowNote')
                            ->label('Insert Below')
                            ->color('gray')
                            ->size(Size::Small)
                            ->action('insertAIDraftBelowNote'),
                        Action::make('dismissAIDraft')
                            ->label('Dismiss')
                            ->color('gray')
                            ->size(Size::Small)
                            ->action('dismissAIDraft'),
                    ]),
                ])
                ->columnSpanFull(),
        ];
    }

    private static function resetTemplateNeedsConfirmation(Get $get): bool
    {
        return ! EncounterDisciplineTemplate::isBlankOrTemplate((string) $get('visit_note_document'));
    }

    private static function aiAssistedLabel(string $label, string $field): callable
    {
        return fn (Get $get): string => $label.((bool) $get("ai_assisted_fields.{$field}") ? ' · AI-assisted' : '');
    }

    private static function formatLastVisits($record): string
    {
        if (! $record || ! $record->patient) {
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
            $lines[] = '<div class="pb-1"><span class="block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Patient</span><span class="text-base font-semibold leading-6 text-gray-950 dark:text-gray-50">'.e($name).'</span></div>';
        }

        if ($patient->dob) {
            $lines[] = '<div><span class="text-xs font-medium text-gray-500 dark:text-gray-400">DOB / Age</span><span class="block text-sm text-gray-700 dark:text-gray-200">'.e($patient->dob->format('M j, Y')).' ('.$patient->dob->age.')</span></div>';
        }

        if ($patient->phone) {
            $lines[] = '<div><span class="text-xs font-medium text-gray-500 dark:text-gray-400">Phone</span><span class="block text-sm text-gray-700 dark:text-gray-200">'.e($patient->phone).'</span></div>';
        }

        if ($patient->email) {
            $lines[] = '<div><span class="text-xs font-medium text-gray-500 dark:text-gray-400">Email</span><span class="block break-words text-sm text-gray-700 dark:text-gray-200">'.e($patient->email).'</span></div>';
        }

        $lastVisit = $patient->encounters()
            ->when($record?->id, fn ($query) => $query->where('id', '!=', $record->id))
            ->latest('visit_date')
            ->first();

        if ($lastVisit?->visit_date) {
            $summary = $lastVisit->chief_complaint ? ' - '.e(str($lastVisit->chief_complaint)->limit(40)) : '';
            $lines[] = '<div><span class="text-xs font-medium text-gray-500 dark:text-gray-400">Last visit</span><span class="block text-sm text-gray-700 dark:text-gray-200">'.e($lastVisit->visit_date->format('M j, Y')).$summary.'</span></div>';
        }

        $content = implode("\n", $lines);

        return new HtmlString(<<<HTML
<div class="space-y-2">
    {$content}
</div>
HTML);
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Html::make(fn (): HtmlString => self::renderMobileVisitNoteStyles())
                ->columnSpanFull(),
            Hidden::make('practice_id')
                ->default(fn () => PracticeContext::currentPracticeId()),
            Hidden::make('appointment_id'),

            Hidden::make('ai_suggestion')
                ->dehydrated(false),
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

            Grid::make([
                'default' => 1,
                'lg' => 4,
            ])->columnSpanFull()->schema([
                Grid::make(1)
                    ->extraAttributes(['class' => 'practiq-encounter-sidebar'])
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 1,
                    ])
                    ->schema([
                        Section::make('Patient / Visit Context')
                            ->compact()
                            ->collapsible()
                            ->collapsed()
                            ->schema([
                                Html::make(fn ($record, Get $get): HtmlString => self::formatPatientContext(
                                    $record,
                                    $get('patient_id') ? (int) $get('patient_id') : null,
                                )),
                            ]),
                        Section::make('Visit Details')
                            ->compact()
                            ->collapsible()
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
                                    ->afterStateUpdated(function (callable $set, Get $get, $state, ?Encounter $record = null) {
                                        if ($state) {
                                            $practitioner = Practitioner::find($state);
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
                                                    self::applyDisciplineTemplate($set, $get, $discipline, $record);
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
                                        'general' => 'Custom / General',
                                    ])
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (callable $set, Get $get, ?string $state, ?Encounter $record = null) => self::applyDisciplineTemplate($set, $get, $state, $record))
                                    ->disabledOn('view'),
                                DatePicker::make('visit_date')
                                    ->required()
                                    ->default(now())
                                    ->disabledOn('view'),
                                Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'complete' => 'Complete',
                                    ])
                                    ->default('draft')
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                    ]),

                Tabs::make('Visit Documentation')
                    ->extraAttributes(['class' => 'practiq-encounter-main'])
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 3,
                    ])
                    ->tabs([
                        Tab::make('Core Notes')->schema([
                            Section::make('Your Note')
                                ->description('Simple Visit Note')
                                ->visible(fn (?Encounter $record = null): bool => self::simpleVisitNoteMode($record))
                                ->schema([
                                    Html::make(fn (): HtmlString => self::renderModeIndicator(false)),
                                    Html::make(fn (): HtmlString => self::renderDictationTip())
                                        ->hiddenOn('view')
                                        ->columnSpanFull(),
                                    Textarea::make('visit_note_document')
                                        ->label('Visit Note')
                                        ->helperText('Write naturally first. You can organize or improve the note later. Changes are saved when you click Save Note.')
                                        ->default(fn (Get $get, ?Encounter $record = null): string => EncounterNoteDocument::template(self::currentPracticeType(
                                            $record,
                                            $get('practitioner_id') ? (int) $get('practitioner_id') : null,
                                        )))
                                        ->rows(24)
                                        ->extraInputAttributes([
                                            'class' => 'practiq-visit-note-field text-base leading-7 px-5 py-4 bg-white dark:bg-gray-950 font-normal',
                                            'autocomplete' => 'off',
                                            'autocapitalize' => 'sentences',
                                            'spellcheck' => 'true',
                                        ])
                                        ->columnSpanFull()
                                        ->disabledOn('view'),
                                    ...self::simpleNoteAssist(),
                                ])
                                ->columnSpanFull(),

                            Section::make('Your Note')
                                ->description('Insurance SOAP Note')
                                ->visible(fn (?Encounter $record = null): bool => self::insuranceBillingEnabled($record))
                                ->schema([
                                    Html::make(fn (): HtmlString => self::renderModeIndicator(true)),
                                    Actions::make([
                                        Action::make('checkMissingDocumentation')
                                            ->label('Check Missing Documentation')
                                            ->color('gray')
                                            ->size(Size::Small)
                                            ->action('checkMissingDocumentation')
                                            ->visible(fn (?Encounter $record = null): bool => self::insuranceBillingEnabled($record)),
                                    ])
                                        ->visible(fn (?Encounter $record = null): bool => self::insuranceBillingEnabled($record))
                                        ->hiddenOn('view')
                                        ->columnSpanFull(),
                                    Textarea::make('chief_complaint')
                                        ->label('Chief Complaint')
                                        ->rows(2)
                                        ->extraInputAttributes(['class' => 'practiq-soap-note-field'])
                                        ->required()
                                        ->disabledOn('view'),
                                    Textarea::make('subjective')
                                        ->label(self::aiAssistedLabel('Subjective', 'subjective'))
                                        ->rows(6)
                                        ->extraInputAttributes(['class' => 'practiq-soap-note-field'])
                                        ->disabledOn('view'),
                                    ...self::aiFieldAssist('subjective'),
                                    Textarea::make('objective')
                                        ->label(self::aiAssistedLabel('Objective', 'objective'))
                                        ->rows(6)
                                        ->extraInputAttributes(['class' => 'practiq-soap-note-field'])
                                        ->disabledOn('view'),
                                    ...self::aiFieldAssist('objective'),
                                    Textarea::make('assessment')
                                        ->label(self::aiAssistedLabel('Assessment', 'assessment'))
                                        ->rows(6)
                                        ->extraInputAttributes(['class' => 'practiq-soap-note-field'])
                                        ->disabledOn('view'),
                                    ...self::aiFieldAssist('assessment'),
                                    Textarea::make('plan')
                                        ->label(self::aiAssistedLabel('Plan', 'plan'))
                                        ->rows(6)
                                        ->extraInputAttributes(['class' => 'practiq-soap-note-field'])
                                        ->columnSpanFull()
                                        ->disabledOn('view'),
                                    ...self::aiFieldAssist('plan'),
                                    Textarea::make('visit_notes')
                                        ->label(self::aiAssistedLabel('General Visit Note (Optional)', 'visit_notes'))
                                        ->rows(5)
                                        ->extraInputAttributes(['class' => 'practiq-soap-note-field'])
                                        ->columnSpanFull()
                                        ->disabledOn('view'),
                                    ...self::aiFieldAssist('visit_notes'),
                                    Textarea::make('documentation_check_result')
                                        ->label('AI Documentation Check')
                                        ->helperText('Completeness review only. This does not modify the visit note.')
                                        ->rows(5)
                                        ->live()
                                        ->readOnly()
                                        ->dehydrated(false)
                                        ->columnSpanFull()
                                        ->visible(fn (?Encounter $record = null): bool => self::insuranceBillingEnabled($record))
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
                                ])->columns([
                                    'default' => 1,
                                    'md' => 2,
                                ]),

                            Section::make('Five Element Pulse Documentation')
                                ->description('Record the comparative pulse picture before and after treatment.')
                                ->visible(fn (Get $get, ?Encounter $record = null): bool => self::isFiveElementStyle(
                                    $record,
                                    $get('practitioner_id') ? (int) $get('practitioner_id') : null,
                                ))
                                ->schema([
                                    Html::make(fn (): HtmlString => self::renderFiveElementPulseReference())
                                        ->hiddenOn('view')
                                        ->columnSpanFull(),

                                    Placeholder::make('pulse_before_treatment')
                                        ->label('Pulses before treatment')
                                        ->content(fn ($record) => $record?->acupunctureEncounter?->pulse_before_treatment ?? '—')
                                        ->visibleOn('view')
                                        ->columnSpanFull(),

                                    Textarea::make('acupunctureEncounter.pulse_before_treatment')
                                        ->label('Pulses before treatment')
                                        ->helperText('Record the relative strength or weakness of the officials before treatment. Example: K --, Sp --, Ht -, PC -; St ++, GB ++.')
                                        ->rows(3)
                                        ->visibleOn('edit')
                                        ->columnSpanFull(),

                                    Placeholder::make('pulse_after_treatment')
                                        ->label('Pulses after treatment')
                                        ->content(fn ($record) => $record?->acupunctureEncounter?->pulse_after_treatment ?? '—')
                                        ->visibleOn('view')
                                        ->columnSpanFull(),

                                    Textarea::make('acupunctureEncounter.pulse_after_treatment')
                                        ->label('Pulses after treatment')
                                        ->helperText('Record what changed after treatment. Example: K +, Sp =, Ht =, PC =; St +, GB +. Overall more even.')
                                        ->rows(3)
                                        ->visibleOn('edit')
                                        ->columnSpanFull(),

                                    Placeholder::make('pulse_change_interpretation')
                                        ->label('Pulse change / interpretation')
                                        ->content(fn ($record) => $record?->acupunctureEncounter?->pulse_change_interpretation ?? '—')
                                        ->visibleOn('view')
                                        ->columnSpanFull(),

                                    Textarea::make('acupunctureEncounter.pulse_change_interpretation')
                                        ->label('Pulse change / interpretation')
                                        ->helperText('Note whether the pulses became more even, which officials changed, and which remained unchanged.')
                                        ->rows(3)
                                        ->visibleOn('edit')
                                        ->columnSpanFull(),
                                ]),

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
                                ])->columns([
                                    'default' => 1,
                                    'md' => 2,
                                ]),
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
