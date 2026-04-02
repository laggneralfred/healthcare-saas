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

        ]);
    }
}
