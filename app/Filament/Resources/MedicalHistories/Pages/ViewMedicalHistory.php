<?php

namespace App\Filament\Resources\MedicalHistories\Pages;

use App\Filament\Resources\MedicalHistories\MedicalHistoryResource;
use App\Models\AISuggestion;
use App\Models\AIUsageLog;
use App\Services\AI\AIService;
use App\Support\ClinicalStyle;
use App\Support\PracticeType;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Throwable;

class ViewMedicalHistory extends ViewRecord
{
    protected static string $resource = MedicalHistoryResource::class;

    public ?string $aiIntakeSummary = null;

    public ?int $aiIntakeSummarySuggestionId = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateIntakeSummary')
                ->label('Generate Intake Summary')
                ->icon('heroicon-o-sparkles')
                ->color('gray')
                ->action(fn (AIService $ai): null => $this->generateIntakeSummary($ai)),
            Action::make('back')
                ->label('Back')
                ->icon('heroicon-o-arrow-left')
                ->url(fn () => static::getResource()::getUrl('index'))
                ->color('gray'),
        ];
    }

    public function generateIntakeSummary(AIService $ai): void
    {
        $record = $this->record->loadMissing(['practice', 'patient', 'practitioner.user']);
        $practiceId = $record->practice_id;
        $context = $this->buildIntakeSummaryContext();

        if (! auth()->user() || auth()->user()->cannot('view', $record)) {
            $this->aiIntakeSummary = null;
            $this->aiIntakeSummarySuggestionId = null;

            Notification::make()
                ->title('You are not authorized to use AI for this intake.')
                ->danger()
                ->send();

            return;
        }

        if (! $this->hasSubstantiveIntakeContent($context)) {
            $this->aiIntakeSummary = null;
            $this->aiIntakeSummarySuggestionId = null;

            Notification::make()
                ->title('There is not enough intake information to summarize yet.')
                ->warning()
                ->send();

            return;
        }

        $originalText = json_encode($context, JSON_PRETTY_PRINT);

        $suggestion = AISuggestion::create([
            'practice_id' => $practiceId,
            'user_id' => auth()->id(),
            'patient_id' => $record->patient_id,
            'appointment_id' => $record->appointment_id,
            'feature' => 'intake_summary',
            'context_json' => [
                'medical_history_id' => $record->id,
                'practice_type' => $context['practice_type'] ?? null,
            ],
            'original_text' => $originalText,
            'status' => 'pending',
        ]);

        try {
            $summary = $ai->summarizeIntake($context);

            $suggestion->update([
                'suggested_text' => $summary,
                'status' => 'pending',
            ]);

            AIUsageLog::create([
                'practice_id' => $practiceId,
                'user_id' => auth()->id(),
                'feature' => 'intake_summary',
                'status' => 'success',
            ]);

            $this->aiIntakeSummary = $summary;
            $this->aiIntakeSummarySuggestionId = $suggestion->id;

            Notification::make()
                ->title('AI Intake Summary ready.')
                ->success()
                ->send();
        } catch (Throwable $exception) {
            $suggestion->update(['status' => 'failed']);

            AIUsageLog::create([
                'practice_id' => $practiceId,
                'user_id' => auth()->id(),
                'feature' => 'intake_summary',
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            Notification::make()
                ->title('Intake Summary is unavailable.')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([

            // ── Red flag banner (only shown when flags exist) ─────────────────────
            Section::make('Clinical Alerts')
                ->extraAttributes(['style' => 'background-color:#fef2f2;border:2px solid #ef4444;border-radius:0.5rem;'])
                ->schema([
                    Placeholder::make('red_flags_detail')
                        ->hiddenLabel()
                        ->content(function ($record) {
                            $flags = [];
                            if ($record->is_pregnant) {
                                $flags[] = 'Pregnant';
                            }
                            if ($record->has_pacemaker) {
                                $flags[] = 'Pacemaker / implanted device';
                            }
                            if ($record->takes_blood_thinners) {
                                $flags[] = 'Blood thinners / anticoagulants';
                            }
                            if ($record->has_bleeding_disorder) {
                                $flags[] = 'Bleeding disorder';
                            }
                            if ($record->has_infectious_disease) {
                                $flags[] = 'Active infectious disease';
                            }

                            return implode(' · ', $flags);
                        }),
                ])
                ->heading('⚠ Red Flags — Review Before Treatment')
                ->visible(fn ($record) => $record->hasRedFlags()),

            Section::make('AI Intake Summary')
                ->description('Patient-reported summary for practitioner review. This does not modify the intake or visit note.')
                ->visible(fn (ViewMedicalHistory $livewire): bool => filled($livewire->aiIntakeSummary))
                ->schema([
                    Html::make(fn (ViewMedicalHistory $livewire): HtmlString => $this->renderIntakeSummaryDraft(
                        (string) $livewire->aiIntakeSummary,
                    )),
                ]),

            // ── Overview ──────────────────────────────────────────────────────────
            Section::make('Overview')
                ->columns(3)
                ->schema([
                    Placeholder::make('patient_name')
                        ->label('Patient')
                        ->content(fn ($record) => $record->patient?->name ?? '—')
                        ->extraAttributes(['style' => 'background-color: #fef3c7; padding: 0.75rem; border-radius: 0.375rem;']),

                    Placeholder::make('practice_type_label')
                        ->label('Clinical Style')
                        ->content(fn ($record) => $this->clinicalStyleSourceLabel($record))
                        ->extraAttributes(['style' => 'background-color: #fef3c7; padding: 0.75rem; border-radius: 0.375rem;']),

                    Placeholder::make('assigned_practitioner')
                        ->label('Assigned Practitioner')
                        ->content(fn ($record) => $record->practitioner?->user?->name ?? '—'),

                    Placeholder::make('status')
                        ->label('Status')
                        ->content(fn ($record) => ucfirst($record->status))
                        ->extraAttributes(['style' => 'background-color: #fef3c7; padding: 0.75rem; border-radius: 0.375rem;']),

                    Placeholder::make('pain_scale')
                        ->label('Pain Scale')
                        ->content(fn ($record) => $record->pain_scale !== null
                            ? "{$record->pain_scale}/10 — {$record->pain_scale_label}"
                            : '—'),

                    Placeholder::make('consent_status')
                        ->label('Consent')
                        ->content(fn ($record) => $record->consent_given
                            ? "Signed by {$record->consent_signed_by}".($record->consent_signed_at ? ' on '.$record->consent_signed_at->format('M j, Y g:ia') : '')
                            : 'Pending')
                        ->extraAttributes(['style' => 'background-color: #fef3c7; padding: 0.75rem; border-radius: 0.375rem;']),

                    Placeholder::make('submitted_on')
                        ->label('Submitted On')
                        ->content(fn ($record) => $record->submitted_on?->format('M j, Y g:ia') ?? '—'),
                ]),

            // ── Chief Complaint ───────────────────────────────────────────────────
            Section::make('Chief Complaint & Onset')
                ->columns(2)
                ->schema([
                    Placeholder::make('chief_complaint')
                        ->label('Chief Complaint')
                        ->columnSpan(2)
                        ->content(fn ($record) => $record->chief_complaint ?: '—'),

                    Placeholder::make('onset_type_label')
                        ->label('Onset Type')
                        ->content(fn ($record) => $record->onset_type_label ?? '—'),

                    Placeholder::make('onset_duration')
                        ->label('Duration')
                        ->content(fn ($record) => $record->onset_duration ?: '—'),

                    Placeholder::make('aggravating_factors')
                        ->label('Aggravating Factors')
                        ->content(fn ($record) => $record->aggravating_factors ?: '—'),

                    Placeholder::make('relieving_factors')
                        ->label('Relieving Factors')
                        ->content(fn ($record) => $record->relieving_factors ?: '—'),

                    Placeholder::make('previous_episodes_description')
                        ->label('Previous Episodes')
                        ->columnSpan(2)
                        ->content(fn ($record) => $record->previous_episodes
                            ? ($record->previous_episodes_description ?: 'Yes — no details provided')
                            : 'No'),
                ]),

            // ── Medical History ───────────────────────────────────────────────────
            Section::make('Medical History')
                ->columns(2)
                ->schema([
                    Placeholder::make('current_medications_list')
                        ->label('Current Medications')
                        ->content(function ($record) {
                            $meds = $record->current_medications;
                            if (empty($meds)) {
                                return '—';
                            }

                            // Handle if it's a string instead of array
                            if (is_string($meds)) {
                                return $meds;
                            }

                            // Handle array of medications
                            if (is_array($meds)) {
                                return implode("\n", array_map(
                                    fn ($m) => is_array($m)
                                        ? trim(($m['name'] ?? '').' '.($m['dose'] ?? '').' '.($m['frequency'] ?? ''))
                                        : (string) $m,
                                    $meds
                                ));
                            }

                            return '—';
                        }),

                    Placeholder::make('allergies_list')
                        ->label('Allergies')
                        ->content(function ($record) {
                            $items = $record->allergies;
                            if (empty($items)) {
                                return '—';
                            }

                            // Handle if it's a string instead of array
                            if (is_string($items)) {
                                return $items;
                            }

                            // Handle array of allergies
                            if (is_array($items)) {
                                return implode("\n", array_map(
                                    fn ($a) => is_array($a)
                                        ? trim(($a['name'] ?? '').($a['reaction'] ? ' ('.$a['reaction'].')' : ''))
                                        : (string) $a,
                                    $items
                                ));
                            }

                            return '—';
                        }),

                    Placeholder::make('past_diagnoses_list')
                        ->label('Past Diagnoses')
                        ->content(function ($record) {
                            $items = $record->past_diagnoses;
                            if (empty($items)) {
                                return '—';
                            }

                            // Handle if it's a string instead of array
                            if (is_string($items)) {
                                return $items;
                            }

                            // Handle array of diagnoses
                            if (is_array($items)) {
                                return implode("\n", array_map(
                                    fn ($d) => is_array($d)
                                        ? trim(($d['condition'] ?? '').($d['year'] ? ' ('.$d['year'].')' : ''))
                                        : (string) $d,
                                    $items
                                ));
                            }

                            return '—';
                        }),

                    Placeholder::make('past_surgeries_list')
                        ->label('Past Surgeries')
                        ->content(function ($record) {
                            $items = $record->past_surgeries;
                            if (empty($items)) {
                                return '—';
                            }

                            // Handle if it's a string instead of array
                            if (is_string($items)) {
                                return $items;
                            }

                            // Handle array of surgeries
                            if (is_array($items)) {
                                return implode("\n", array_map(
                                    fn ($s) => is_array($s)
                                        ? trim(($s['procedure'] ?? '').($s['year'] ? ' ('.$s['year'].')' : ''))
                                        : (string) $s,
                                    $items
                                ));
                            }

                            return '—';
                        }),
                ]),

            // ── Health Flags ──────────────────────────────────────────────────────
            Section::make('Health Flags')
                ->columns(3)
                ->schema([
                    Placeholder::make('is_pregnant')
                        ->label('Pregnant')
                        ->content(fn ($record) => $record->is_pregnant ? 'Yes' : 'No'),
                    Placeholder::make('has_pacemaker')
                        ->label('Pacemaker')
                        ->content(fn ($record) => $record->has_pacemaker ? 'Yes' : 'No'),
                    Placeholder::make('takes_blood_thinners')
                        ->label('Blood Thinners')
                        ->content(fn ($record) => $record->takes_blood_thinners ? 'Yes' : 'No'),
                    Placeholder::make('has_bleeding_disorder')
                        ->label('Bleeding Disorder')
                        ->content(fn ($record) => $record->has_bleeding_disorder ? 'Yes' : 'No'),
                    Placeholder::make('has_infectious_disease')
                        ->label('Infectious Disease')
                        ->content(fn ($record) => $record->has_infectious_disease ? 'Yes' : 'No'),
                ]),

            // ── Lifestyle ─────────────────────────────────────────────────────────
            Section::make('Lifestyle & Wellness')
                ->columns(3)
                ->schema([
                    Placeholder::make('exercise_frequency')
                        ->label('Exercise')
                        ->content(fn ($record) => $record->exercise_frequency ?: '—'),
                    Placeholder::make('sleep_quality')
                        ->label('Sleep Quality')
                        ->content(fn ($record) => ucfirst($record->sleep_quality ?? '') ?: '—'),
                    Placeholder::make('sleep_hours')
                        ->label('Sleep (hrs)')
                        ->content(fn ($record) => $record->sleep_hours ? "{$record->sleep_hours} hrs" : '—'),
                    Placeholder::make('stress_level')
                        ->label('Stress Level')
                        ->content(fn ($record) => ucfirst($record->stress_level ?? '') ?: '—'),
                    Placeholder::make('smoking_status')
                        ->label('Smoking')
                        ->content(fn ($record) => ucfirst($record->smoking_status ?? '') ?: '—'),
                    Placeholder::make('alcohol_use')
                        ->label('Alcohol')
                        ->content(fn ($record) => ucfirst($record->alcohol_use ?? '') ?: '—'),
                    Placeholder::make('diet_description')
                        ->label('Diet Notes')
                        ->columnSpan(3)
                        ->content(fn ($record) => $record->diet_description ?: '—'),
                ]),

            // ── Previous Treatment ────────────────────────────────────────────────
            Section::make('Previous Treatment')
                ->columns(2)
                ->schema([
                    Placeholder::make('had_previous_treatment')
                        ->label('Previous Treatment')
                        ->content(fn ($record) => $record->had_previous_treatment ? 'Yes' : 'No'),
                    Placeholder::make('other_practitioner_name')
                        ->label('Other Practitioner')
                        ->content(fn ($record) => $record->other_practitioner
                            ? ($record->other_practitioner_name ?: 'Yes — name not provided')
                            : 'No'),
                    Placeholder::make('previous_treatment_results')
                        ->label('Results / Outcomes')
                        ->columnSpan(2)
                        ->content(fn ($record) => $record->previous_treatment_results ?: '—'),
                ]),

            // ── Goals ─────────────────────────────────────────────────────────────
            Section::make('Treatment Goals')
                ->columns(2)
                ->schema([
                    Placeholder::make('treatment_goals')
                        ->label('Goals')
                        ->content(fn ($record) => $record->treatment_goals ?: '—'),
                    Placeholder::make('success_indicators')
                        ->label('Success Indicators')
                        ->content(fn ($record) => $record->success_indicators ?: '—'),
                ]),

            // ── Discipline-specific responses ─────────────────────────────────────
            Section::make(fn ($record) => $record->getDisciplineSection()['label'])
                ->visible(fn ($record) => ! empty($record->getDisciplineSection()['data']))
                ->schema([
                    Placeholder::make('discipline_section_content')
                        ->hiddenLabel()
                        ->columnSpanFull()
                        ->content(function ($record) {
                            try {
                                $section = $record->getDisciplineSection();
                                $data = $section['data'];
                                $key = $section['key'];

                                if (empty($data)) {
                                    return '—';
                                }

                                return match ($key) {
                                    'tcm' => static::formatTcmSection($data),
                                    'massage' => static::formatMassageSection($data),
                                    'chiro' => static::formatChiroSection($data),
                                    'physio' => static::formatPhysioSection($data),
                                    default => implode("\n", array_map(
                                        fn ($k, $v) => ucwords(str_replace('_', ' ', $k)).': '.static::flattenValue($v),
                                        array_keys($data), $data
                                    )),
                                };
                            } catch (Throwable $e) {
                                return '(Unable to display discipline data)';
                            }
                        }),
                ]),

        ]);
    }

    private function buildIntakeSummaryContext(): array
    {
        $record = $this->record->loadMissing(['practice', 'patient', 'practitioner.user']);
        $disciplineSection = $record->getDisciplineSection();

        return array_filter([
            'practice_type' => ClinicalStyle::fromMedicalHistory($record),
            'discipline' => $record->discipline,
            'patient_reported' => array_filter([
                'reason_for_visit' => $record->reason_for_visit,
                'chief_complaint' => $record->chief_complaint,
                'current_concerns' => $record->current_concerns,
                'relevant_history' => $record->relevant_history,
                'onset_duration' => $record->onset_duration,
                'onset_type' => $record->onset_type,
                'aggravating_factors' => $record->aggravating_factors,
                'relieving_factors' => $record->relieving_factors,
                'pain_scale' => $record->pain_scale,
                'previous_episodes' => $record->previous_episodes,
                'previous_episodes_description' => $record->previous_episodes_description,
                'treatment_goals' => $record->treatment_goals,
                'success_indicators' => $record->success_indicators,
                'previous_treatment_results' => $record->previous_treatment_results,
                'notes' => $record->notes,
            ], fn ($value) => filled($value)),
            'medications_allergies_history' => array_filter([
                'current_medications' => $this->formatContextValue($record->current_medications),
                'allergies' => $this->formatContextValue($record->allergies),
                'past_diagnoses' => $this->formatContextValue($record->past_diagnoses),
                'past_surgeries' => $this->formatContextValue($record->past_surgeries),
            ], fn ($value) => filled($value)),
            'patient_reported_health_flags' => array_filter([
                'is_pregnant' => $record->is_pregnant ? 'Yes' : null,
                'has_pacemaker_or_implanted_device' => $record->has_pacemaker ? 'Yes' : null,
                'takes_blood_thinners' => $record->takes_blood_thinners ? 'Yes' : null,
                'has_bleeding_disorder' => $record->has_bleeding_disorder ? 'Yes' : null,
                'has_active_infectious_disease' => $record->has_infectious_disease ? 'Yes' : null,
            ], fn ($value) => filled($value)),
            'lifestyle_patient_reported' => array_filter([
                'exercise_frequency' => $record->exercise_frequency,
                'sleep_quality' => $record->sleep_quality,
                'sleep_hours' => $record->sleep_hours,
                'stress_level' => $record->stress_level,
                'diet_description' => $record->diet_description,
                'smoking_status' => $record->smoking_status,
                'alcohol_use' => $record->alcohol_use,
            ], fn ($value) => filled($value)),
            'practice_type_specific_patient_responses' => array_filter([
                'section' => $disciplineSection['label'] ?? null,
                'responses' => $this->formatContextValue($disciplineSection['data'] ?? []),
            ], fn ($value) => filled($value)),
        ], fn ($value) => filled($value));
    }

    private function hasSubstantiveIntakeContent(array $context): bool
    {
        foreach ([
            'patient_reported',
            'medications_allergies_history',
            'patient_reported_health_flags',
            'lifestyle_patient_reported',
        ] as $section) {
            if ($this->containsSubstantiveIntakeValue($context[$section] ?? null)) {
                return true;
            }
        }

        return $this->containsSubstantiveIntakeValue(
            data_get($context, 'practice_type_specific_patient_responses.responses'),
        );
    }

    private function containsSubstantiveIntakeValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if ($this->containsSubstantiveIntakeValue($item)) {
                    return true;
                }
            }

            return false;
        }

        return filled($value);
    }

    private function formatContextValue(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if (! is_array($value)) {
            return (string) $value;
        }

        return static::flattenValue($value);
    }

    private function renderIntakeSummaryDraft(string $summary): HtmlString
    {
        $summary = nl2br(e($summary), false);

        return new HtmlString(<<<HTML
<div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm leading-6 text-gray-950 shadow-sm dark:border-amber-700 dark:bg-amber-950/30 dark:text-gray-100">
    <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">AI Intake Summary</div>
    <div class="whitespace-pre-wrap">{$summary}</div>
</div>
HTML);
    }

    private function clinicalStyleSourceLabel($record): string
    {
        $styleLabel = PracticeType::label(ClinicalStyle::fromMedicalHistory($record));

        if ($record->practitioner?->clinical_style) {
            $name = $record->practitioner->user?->name ?? "Practitioner #{$record->practitioner->id}";

            return "{$styleLabel} via {$name}";
        }

        return "Practice default — {$styleLabel}";
    }

    // ── Discipline section formatters ──────────────────────────────────────────

    private static function formatTcmSection(array $data): string
    {
        $lines = [];

        $labels = [
            'energy_level' => 'Energy Level',
            'energy_time_pattern' => 'Energy Lowest',
            'temperature_preference' => 'Temperature Preference',
            'appetite' => 'Appetite',
            'digestion_issues' => 'Digestive Issues',
            'bowel_frequency' => 'Bowel Movements',
            'thirst' => 'Thirst',
            'beverage_preference' => 'Beverage Preference',
            'sleep_issues' => 'Sleep Concerns',
            'dream_frequency' => 'Dream Frequency',
            'emotional_tendencies' => 'Emotional Tendencies',
            'emotional_impact' => 'Emotional Impact on Health',
            'menstrual_applicable' => 'Menstrual Questions Apply',
            'cycle_length' => 'Cycle Length',
            'period_duration' => 'Period Duration',
            'flow' => 'Flow',
            'period_pain' => 'Period Pain',
            'clots' => 'Blood Clots',
            'pms_symptoms' => 'PMS Symptoms',
            'previous_acupuncture' => 'Previous Acupuncture',
            'previous_acupuncture_experience' => 'Acupuncture Experience',
            'needle_comfort' => 'Needle Comfort',
        ];

        foreach ($labels as $fieldKey => $label) {
            if (! isset($data[$fieldKey])) {
                continue;
            }
            $value = $data[$fieldKey];
            if (is_array($value)) {
                $value = implode(', ', $value);
            } elseif (is_bool($value)) {
                $value = $value ? 'Yes' : 'No';
            }
            if (filled($value)) {
                $lines[] = "{$label}: {$value}";
            }
        }

        return implode("\n", $lines) ?: '—';
    }

    private static function formatMassageSection(array $data): string
    {
        $lines = [];

        $labels = [
            'focus_areas' => 'Focus Areas',
            'areas_to_avoid' => 'Areas to Avoid',
            'pressure_preference' => 'Pressure Preference',
            'previous_massage' => 'Previous Massage',
            'massage_types' => 'Massage Types Experienced',
            'previous_massage_reaction' => 'Reaction to Previous Massage',
            'skin_conditions' => 'Skin Conditions',
            'recent_injuries' => 'Recent Injuries',
            'varicose_veins' => 'Varicose Veins',
            'osteoporosis' => 'Osteoporosis',
            'draping_comfort' => 'Draping Preference',
            'session_goals' => 'Session Goals',
        ];

        foreach ($labels as $fieldKey => $label) {
            if (! isset($data[$fieldKey])) {
                continue;
            }
            $value = $data[$fieldKey];
            if (is_array($value)) {
                $value = implode(', ', $value);
            } elseif (is_bool($value)) {
                $value = $value ? 'Yes' : 'No';
            }
            if (filled($value)) {
                $lines[] = "{$label}: {$value}";
            }
        }

        return implode("\n", $lines) ?: '—';
    }

    private static function formatChiroSection(array $data): string
    {
        $lines = [];

        $labels = [
            'pain_locations' => 'Pain Locations',
            'pain_character' => 'Pain Character',
            'pain_radiation' => 'Pain Radiates',
            'radiation_description' => 'Radiation Location',
            'onset_mechanism' => 'Onset Mechanism',
            'accident_date' => 'Date of Accident',
            'workers_comp' => 'Workers Compensation',
            'mva_claim' => 'MVA Claim',
            'neurological_symptoms' => 'Neurological Symptoms',
            'symptom_location' => 'Symptom Location',
            'previous_imaging' => 'Previous Imaging',
            'imaging_findings' => 'Imaging Findings',
            'previous_chiropractic' => 'Previous Chiropractic Care',
            'previous_chiro_outcome' => 'Previous Care Outcome',
            'adjustment_consent' => 'Adjustment Comfort',
        ];

        foreach ($labels as $fieldKey => $label) {
            if (! isset($data[$fieldKey])) {
                continue;
            }
            $value = $data[$fieldKey];
            if (is_array($value)) {
                $value = implode(', ', $value);
            } elseif (is_bool($value)) {
                $value = $value ? 'Yes' : 'No';
            }
            if (filled($value)) {
                $lines[] = "{$label}: {$value}";
            }
        }

        return implode("\n", $lines) ?: '—';
    }

    private static function flattenValue(mixed $v): string
    {
        if (is_bool($v)) {
            return $v ? 'Yes' : 'No';
        }
        if (is_null($v)) {
            return '—';
        }
        if (is_array($v)) {
            return implode(', ', array_map(
                fn ($i) => is_array($i) ? json_encode($i) : (string) $i,
                $v
            ));
        }

        return (string) $v;
    }

    private static function formatPhysioSection(array $data): string
    {
        $lines = [];

        $labels = [
            'functional_limitations' => 'Functional Limitations',
            'work_status' => 'Work Status',
            'work_demands' => 'Work Demands',
            'recreational_impact' => 'Recreational Impact',
            'morning_stiffness' => 'Morning Stiffness',
            'morning_stiffness_duration' => 'Stiffness Duration',
            'activity_effect' => 'Effect of Activity',
            'rest_effect' => 'Effect of Rest',
            'previous_physio' => 'Previous Physiotherapy',
            'previous_physio_outcome' => 'Previous Physio Outcome',
            'physician_referral' => 'Physician Referral',
            'referring_physician' => 'Referring Physician',
            'functional_goals' => 'Functional Goals',
            'timeline_expectation' => 'Recovery Timeline',
        ];

        foreach ($labels as $fieldKey => $label) {
            if (! isset($data[$fieldKey])) {
                continue;
            }
            $value = $data[$fieldKey];
            if (is_array($value)) {
                $value = implode(', ', $value);
            } elseif (is_bool($value)) {
                $value = $value ? 'Yes' : 'No';
            }
            if (filled($value)) {
                $lines[] = "{$label}: {$value}";
            }
        }

        return implode("\n", $lines) ?: '—';
    }
}
