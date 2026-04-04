<?php

namespace App\Filament\Resources\IntakeSubmissions\Pages;

use App\Filament\Resources\IntakeSubmissions\IntakeSubmissionResource;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewIntakeSubmission extends ViewRecord
{
    protected static string $resource = IntakeSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('Back')
                ->icon('heroicon-o-arrow-left')
                ->url(fn () => static::getResource()::getUrl('index'))
                ->color('gray'),
        ];
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
                            if ($record->is_pregnant)          $flags[] = 'Pregnant';
                            if ($record->has_pacemaker)        $flags[] = 'Pacemaker / implanted device';
                            if ($record->takes_blood_thinners) $flags[] = 'Blood thinners / anticoagulants';
                            if ($record->has_bleeding_disorder) $flags[] = 'Bleeding disorder';
                            if ($record->has_infectious_disease) $flags[] = 'Active infectious disease';
                            return implode(' · ', $flags);
                        }),
                ])
                ->heading('⚠ Red Flags — Review Before Treatment')
                ->visible(fn ($record) => $record->hasRedFlags()),

            // ── Overview ──────────────────────────────────────────────────────────
            Section::make('Overview')
                ->columns(3)
                ->schema([
                    Placeholder::make('patient_name')
                        ->label('Patient')
                        ->content(fn ($record) => $record->patient?->name ?? '—'),

                    Placeholder::make('discipline_label')
                        ->label('Discipline')
                        ->content(fn ($record) => $record->discipline_label ?? '—'),

                    Placeholder::make('status')
                        ->label('Status')
                        ->content(fn ($record) => ucfirst($record->status)),

                    Placeholder::make('pain_scale')
                        ->label('Pain Scale')
                        ->content(fn ($record) => $record->pain_scale !== null
                            ? "{$record->pain_scale}/10 — {$record->pain_scale_label}"
                            : '—'),

                    Placeholder::make('consent_status')
                        ->label('Consent')
                        ->content(fn ($record) => $record->consent_given
                            ? "Signed by {$record->consent_signed_by}" . ($record->consent_signed_at ? ' on ' . $record->consent_signed_at->format('M j, Y g:ia') : '')
                            : 'Pending'),

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
                            if (empty($meds)) return '—';
                            return implode("\n", array_map(
                                fn ($m) => trim(($m['name'] ?? '') . ' ' . ($m['dose'] ?? '') . ' ' . ($m['frequency'] ?? '')),
                                $meds
                            ));
                        }),

                    Placeholder::make('allergies_list')
                        ->label('Allergies')
                        ->content(function ($record) {
                            $items = $record->allergies;
                            if (empty($items)) return '—';
                            return implode("\n", array_map(
                                fn ($a) => trim(($a['name'] ?? '') . ($a['reaction'] ? ' (' . $a['reaction'] . ')' : '')),
                                $items
                            ));
                        }),

                    Placeholder::make('past_diagnoses_list')
                        ->label('Past Diagnoses')
                        ->content(function ($record) {
                            $items = $record->past_diagnoses;
                            if (empty($items)) return '—';
                            return implode("\n", array_map(
                                fn ($d) => trim(($d['condition'] ?? '') . ($d['year'] ? ' (' . $d['year'] . ')' : '')),
                                $items
                            ));
                        }),

                    Placeholder::make('past_surgeries_list')
                        ->label('Past Surgeries')
                        ->content(function ($record) {
                            $items = $record->past_surgeries;
                            if (empty($items)) return '—';
                            return implode("\n", array_map(
                                fn ($s) => trim(($s['procedure'] ?? '') . ($s['year'] ? ' (' . $s['year'] . ')' : '')),
                                $items
                            ));
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
                ->visible(fn ($record) => !empty($record->getDisciplineSection()['data']))
                ->schema([
                    Placeholder::make('discipline_section_content')
                        ->hiddenLabel()
                        ->columnSpanFull()
                        ->content(function ($record) {
                            try {
                                $section = $record->getDisciplineSection();
                                $data    = $section['data'];
                                $key     = $section['key'];

                                if (empty($data)) {
                                    return '—';
                                }

                                return match ($key) {
                                    'tcm'     => static::formatTcmSection($data),
                                    'massage' => static::formatMassageSection($data),
                                    'chiro'   => static::formatChiroSection($data),
                                    'physio'  => static::formatPhysioSection($data),
                                    default   => implode("\n", array_map(
                                        fn ($k, $v) => ucwords(str_replace('_', ' ', $k)) . ': ' . static::flattenValue($v),
                                        array_keys($data), $data
                                    )),
                                };
                            } catch (\Throwable $e) {
                                return '(Unable to display discipline data)';
                            }
                        }),
                ]),

        ]);
    }

    // ── Discipline section formatters ──────────────────────────────────────────

    private static function formatTcmSection(array $data): string
    {
        $lines = [];

        $labels = [
            'energy_level'          => 'Energy Level',
            'energy_time_pattern'   => 'Energy Lowest',
            'temperature_preference' => 'Temperature Preference',
            'appetite'              => 'Appetite',
            'digestion_issues'      => 'Digestive Issues',
            'bowel_frequency'       => 'Bowel Movements',
            'thirst'                => 'Thirst',
            'beverage_preference'   => 'Beverage Preference',
            'sleep_issues'          => 'Sleep Concerns',
            'dream_frequency'       => 'Dream Frequency',
            'emotional_tendencies'  => 'Emotional Tendencies',
            'emotional_impact'      => 'Emotional Impact on Health',
            'menstrual_applicable'  => 'Menstrual Questions Apply',
            'cycle_length'          => 'Cycle Length',
            'period_duration'       => 'Period Duration',
            'flow'                  => 'Flow',
            'period_pain'           => 'Period Pain',
            'clots'                 => 'Blood Clots',
            'pms_symptoms'          => 'PMS Symptoms',
            'previous_acupuncture'  => 'Previous Acupuncture',
            'previous_acupuncture_experience' => 'Acupuncture Experience',
            'needle_comfort'        => 'Needle Comfort',
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
            'focus_areas'              => 'Focus Areas',
            'areas_to_avoid'           => 'Areas to Avoid',
            'pressure_preference'      => 'Pressure Preference',
            'previous_massage'         => 'Previous Massage',
            'massage_types'            => 'Massage Types Experienced',
            'previous_massage_reaction' => 'Reaction to Previous Massage',
            'skin_conditions'          => 'Skin Conditions',
            'recent_injuries'          => 'Recent Injuries',
            'varicose_veins'           => 'Varicose Veins',
            'osteoporosis'             => 'Osteoporosis',
            'draping_comfort'          => 'Draping Preference',
            'session_goals'            => 'Session Goals',
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
            'pain_locations'         => 'Pain Locations',
            'pain_character'         => 'Pain Character',
            'pain_radiation'         => 'Pain Radiates',
            'radiation_description'  => 'Radiation Location',
            'onset_mechanism'        => 'Onset Mechanism',
            'accident_date'          => 'Date of Accident',
            'workers_comp'           => 'Workers Compensation',
            'mva_claim'              => 'MVA Claim',
            'neurological_symptoms'  => 'Neurological Symptoms',
            'symptom_location'       => 'Symptom Location',
            'previous_imaging'       => 'Previous Imaging',
            'imaging_findings'       => 'Imaging Findings',
            'previous_chiropractic'  => 'Previous Chiropractic Care',
            'previous_chiro_outcome' => 'Previous Care Outcome',
            'adjustment_consent'     => 'Adjustment Comfort',
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
        if (is_bool($v)) return $v ? 'Yes' : 'No';
        if (is_null($v)) return '—';
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
            'functional_limitations'    => 'Functional Limitations',
            'work_status'               => 'Work Status',
            'work_demands'              => 'Work Demands',
            'recreational_impact'       => 'Recreational Impact',
            'morning_stiffness'         => 'Morning Stiffness',
            'morning_stiffness_duration' => 'Stiffness Duration',
            'activity_effect'           => 'Effect of Activity',
            'rest_effect'               => 'Effect of Rest',
            'previous_physio'           => 'Previous Physiotherapy',
            'previous_physio_outcome'   => 'Previous Physio Outcome',
            'physician_referral'        => 'Physician Referral',
            'referring_physician'       => 'Referring Physician',
            'functional_goals'          => 'Functional Goals',
            'timeline_expectation'      => 'Recovery Timeline',
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
