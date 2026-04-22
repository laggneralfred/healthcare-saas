@php
    $disciplineLabel = fn($d) => match($d) {
        'acupuncture'  => 'Acupuncture',
        'massage'      => 'Massage Therapy',
        'chiropractic' => 'Chiropractic',
        'physiotherapy'=> 'Physical Therapy',
        default        => ucfirst($d ?? ''),
    };

    $disciplineBadgeStyle = match($discipline ?? '') {
        'acupuncture'  => 'background-color:#e0f2fe;color:#0c4a6e;',
        'massage'      => 'background-color:#fce7f3;color:#831843;',
        'chiropractic' => 'background-color:#ede9fe;color:#4c1d95;',
        'physiotherapy'=> 'background-color:#dcfce7;color:#14532d;',
        default        => 'background-color:#f3f4f6;color:#374151;',
    };

    $statusBadgeStyle = match($status) {
        'active'   => 'background-color:#d1fae5;color:#065f46;',
        'inactive' => 'background-color:#fee2e2;color:#991b1b;',
        default    => 'background-color:#e0f2fe;color:#0c4a6e;',
    };
    $statusLabel = match($status) {
        'active'   => 'Active',
        'inactive' => 'Inactive',
        default    => 'New Patient',
    };

    $encounterViewUrl   = fn($enc)  => \App\Filament\Resources\Encounters\EncounterResource::getUrl('view', ['record' => $enc->id]);
    $appointmentViewUrl = fn($appt) => \App\Filament\Resources\Appointments\AppointmentResource::getUrl('view', ['record' => $appt->id]);
    $checkoutViewUrl    = fn($co)   => \App\Filament\Resources\CheckoutSessions\CheckoutSessionResource::getUrl('view', ['record' => $co->id]);
    $intakeCreateUrl    = \App\Filament\Resources\MedicalHistories\MedicalHistoryResource::getUrl('create', ['patient_id' => $patient->id]);
    $newVisitUrl        = \App\Filament\Resources\Encounters\EncounterResource::getUrl('create', ['patient_id' => $patient->id]);
    $encounterCreateUrl = fn($appt, $pat) => \App\Filament\Resources\Encounters\EncounterResource::getUrl('create') . '?appointment_id=' . $appt->id . '&patient_id=' . $pat->id;
    $encounterBtn       = fn($appt, $pat) => $appt->encounter
        ? '<a href="' . $encounterViewUrl($appt->encounter) . '" style="color:#0d9488;text-decoration:none;font-weight:500;font-size:0.85rem;">View Visit</a>'
        : '<a href="' . $encounterCreateUrl($appt, $pat) . '" style="color:#1e40af;text-decoration:none;font-weight:500;font-size:0.85rem;">Start Visit</a>';
@endphp

<style>[x-cloak] { display: none !important; }</style>

{{-- ── HEADER BAR ─────────────────────────────────────────────────────────── --}}
<div style="background-color:#f9fafb;border:1px solid #e5e7eb;border-radius:0.5rem;padding:1.5rem 1.75rem;margin-bottom:1.5rem;">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;">

        {{-- Left: Name + status badges + labeled demographics --}}
        <div>
            <div style="display:flex;align-items:center;gap:0.625rem;flex-wrap:wrap;margin-bottom:0.5rem;">
                <h2 style="font-size:1.375rem;font-weight:700;color:#111827;margin:0;">
                    {{ $patient->name }}
                </h2>
                <span style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.2rem 0.6rem;border-radius:9999px;font-size:0.75rem;font-weight:600;{{ $statusBadgeStyle }}">
                    <span style="width:0.45rem;height:0.45rem;border-radius:50%;background-color:currentColor;display:inline-block;"></span>
                    {{ $statusLabel }}
                </span>
                @if(!empty($discipline))
                <span style="padding:0.2rem 0.6rem;border-radius:9999px;font-size:0.75rem;font-weight:600;{{ $disciplineBadgeStyle }}">
                    {{ $disciplineLabel($discipline) }}
                </span>
                @endif
            </div>
            <div style="display:flex;gap:1.5rem;flex-wrap:wrap;font-size:0.875rem;color:#6b7280;">
                @if($patient->dob)
                    <span><strong style="color:#374151;font-weight:600;">DOB:</strong> {{ $patient->dob->format('M j, Y') }} ({{ $patient->dob->age }}y)</span>
                @endif
                @if($patient->gender)
                    <span><strong style="color:#374151;font-weight:600;">Gender:</strong> {{ ucfirst($patient->gender) }}{{ $patient->pronouns ? ' · ' . $patient->pronouns : '' }}</span>
                @endif
                @if($patient->phone)
                    <span><strong style="color:#374151;font-weight:600;">Phone:</strong> {{ $patient->phone }}</span>
                @endif
                @if($patient->email)
                    <span><strong style="color:#374151;font-weight:600;">Email:</strong> {{ $patient->email }}</span>
                @endif
            </div>
        </div>

        {{-- Right: Primary concern --}}
        @if($latestIntake?->chief_complaint)
        <div style="text-align:right;max-width:320px;">
            <div style="font-size:0.75rem;color:#9ca3af;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.2rem;">Primary Concern</div>
            <div style="font-size:0.9rem;color:#374151;font-style:italic;">
                "{{ Str::limit($latestIntake->chief_complaint, 80) }}"
            </div>
        </div>
        @endif

    </div>
</div>

{{-- ── TWO-COLUMN LAYOUT ─────────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:1fr 2fr;gap:1.5rem;align-items:start;">

    {{-- ── LEFT: QUICK SUMMARY ──────────────────────────────────────────── --}}
    <div>
        <div style="background-color:#ffffff;border:1px solid #e5e7eb;border-radius:0.5rem;padding:1.25rem;">
            <h3 style="font-size:0.8rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:#6b7280;margin:0 0 1rem 0;">Quick Summary</h3>

            {{-- Active Alerts — only rendered when at least one issue exists --}}
            @if(!$hasCompletedIntake || !$hasSignedConsent || $hasOutstandingPayment)
            <div style="margin-bottom:1rem;display:flex;flex-direction:column;gap:0.375rem;">
                @if(!$hasCompletedIntake)
                <div style="background-color:#fee2e2;color:#991b1b;border-radius:0.375rem;padding:0.375rem 0.625rem;font-size:0.8rem;font-weight:500;">
                    ⚠ Intake form missing
                </div>
                @endif
                @if(!$hasSignedConsent)
                <div style="background-color:#fef3c7;color:#92400e;border-radius:0.375rem;padding:0.375rem 0.625rem;font-size:0.8rem;font-weight:500;">
                    ⚠ No signed consent
                </div>
                @endif
                @if($hasOutstandingPayment)
                <div style="background-color:#fef3c7;color:#92400e;border-radius:0.375rem;padding:0.375rem 0.625rem;font-size:0.8rem;font-weight:500;">
                    $ Outstanding: ${{ number_format($outstandingBalance, 2) }}
                </div>
                @endif
            </div>
            @endif

            {{-- Chief concern --}}
            <div style="margin-bottom:0.875rem;">
                <div style="font-size:0.75rem;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.2rem;">Chief Concern</div>
                <div style="font-size:0.9rem;color:#1f2937;">
                    {{ $latestIntake?->chief_complaint ?? '—' }}
                </div>
                @if($latestIntake?->onset_duration)
                <div style="font-size:0.8rem;color:#6b7280;">Since: {{ $latestIntake->onset_duration }}</div>
                @endif
            </div>

            {{-- Pain scale --}}
            @if($latestIntake?->pain_scale)
            <div style="margin-bottom:0.875rem;">
                <div style="font-size:0.75rem;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.2rem;">Pain Scale</div>
                @php
                    $pain      = $latestIntake->pain_scale;
                    $painColor = $pain <= 3 ? '#065f46' : ($pain <= 6 ? '#92400e' : '#991b1b');
                    $painBg    = $pain <= 3 ? '#d1fae5' : ($pain <= 6 ? '#fef3c7' : '#fee2e2');
                @endphp
                <span style="background-color:{{ $painBg }};color:{{ $painColor }};border-radius:9999px;padding:0.15rem 0.6rem;font-size:0.8rem;font-weight:600;">
                    {{ $pain }}/10 — {{ $latestIntake->pain_scale_label }}
                </span>
            </div>
            @endif

            {{-- Last Visit --}}
            <div style="margin-bottom:0.875rem;">
                <div style="font-size:0.75rem;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.2rem;">Last Visit</div>
                @if($lastEncounter)
                <div style="font-size:0.9rem;color:#1f2937;">{{ $lastEncounter->visit_date->format('M j, Y') }}</div>
                @if($lastEncounter->practitioner?->user?->name)
                <div style="font-size:0.8rem;color:#6b7280;">{{ $lastEncounter->practitioner->user->name }}</div>
                @endif
                @if($lastEncounter->chief_complaint)
                <div style="font-size:0.8rem;color:#9ca3af;font-style:italic;">{{ Str::limit($lastEncounter->chief_complaint, 40) }}</div>
                @endif
                @else
                <div style="font-size:0.9rem;color:#9ca3af;">No visits yet</div>
                @endif
            </div>

            {{-- Next Appointment --}}
            <div style="margin-bottom:0.875rem;">
                <div style="font-size:0.75rem;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.2rem;">Next Appointment</div>
                @if($nextAppointment)
                <div style="font-size:0.9rem;color:#1f2937;">{{ $nextAppointment->start_datetime->format('M j, Y g:i A') }}</div>
                <div style="font-size:0.8rem;color:#6b7280;">{{ $nextAppointment->practitioner?->user?->name ?? '—' }}</div>
                @else
                <div style="font-size:0.9rem;color:#9ca3af;">None scheduled</div>
                @endif
            </div>

            {{-- Visit count --}}
            <div style="border-top:1px solid #f3f4f6;padding-top:0.75rem;margin-top:0.75rem;">
                <div style="display:flex;justify-content:space-between;font-size:0.85rem;">
                    <span style="color:#6b7280;">Total Visits</span>
                    <span style="font-weight:600;color:#1f2937;">{{ $encounters->count() }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── RIGHT: TABS ───────────────────────────────────────────────────── --}}
    <div x-data="{ tab: 'visits' }">

        {{-- Tab bar --}}
        <div style="display:flex;border-bottom:2px solid #e5e7eb;overflow-x:auto;margin-bottom:0;background-color:#f9fafb;border-radius:0.5rem 0.5rem 0 0;border:1px solid #e5e7eb;border-bottom:none;">
            @foreach([
                ['visits',        'Visit History'],
                ['intake',        'Intake & History'],
                ['appointments',  'Appointments'],
                ['demographics',  'Demographics'],
                ['billing',       'Billing'],
            ] as [$key, $label])
            <button
                x-on:click="tab = '{{ $key }}'"
                :style="tab === '{{ $key }}'
                    ? 'color:#0d9488;border-bottom:2px solid #0d9488;font-weight:600;background-color:#ffffff;'
                    : 'color:#6b7280;border-bottom:2px solid transparent;font-weight:400;background-color:transparent;'"
                style="padding:0.75rem 1.125rem;font-size:0.875rem;border:none;border-bottom:2px solid transparent;cursor:pointer;white-space:nowrap;margin-bottom:-1px;outline:none;flex-shrink:0;transition:color 0.15s,border-color 0.15s,background-color 0.15s;">
                {{ $label }}
            </button>
            @endforeach
        </div>

        {{-- Tab content container --}}
        <div style="border:1px solid #e5e7eb;border-top:none;border-radius:0 0 0.5rem 0.5rem;background-color:#ffffff;padding:1.25rem;">

            {{-- ── TAB: VISIT HISTORY ─────────────────────────────────────── --}}
            <div x-show="tab === 'visits'" x-cloak>
                @if($encounters->isEmpty())
                    <div style="text-align:center;padding:3rem 1.5rem;">
                        <div style="font-size:2rem;margin-bottom:0.75rem;">📋</div>
                        <p style="font-size:0.9375rem;font-weight:600;color:#374151;margin:0 0 0.375rem 0;">No visits recorded yet.</p>
                        <p style="font-size:0.875rem;color:#6b7280;margin:0 0 1.25rem 0;">Start a new visit to begin tracking clinical notes for this patient.</p>
                        <a href="{{ $newVisitUrl }}"
                           style="display:inline-block;padding:0.5rem 1.25rem;background-color:#0d9488;color:#ffffff;font-size:0.875rem;font-weight:600;border-radius:0.5rem;text-decoration:none;">
                            Start New Visit →
                        </a>
                    </div>
                @else
                    <div style="overflow-x:auto;">
                        <table style="width:100%;border-collapse:collapse;font-size:0.875rem;">
                            <thead>
                                <tr style="background-color:#f9fafb;border-bottom:2px solid #e5e7eb;">
                                    <th style="text-align:left;padding:0.625rem 0.875rem;color:#6b7280;font-weight:600;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;">Date</th>
                                    <th style="text-align:left;padding:0.625rem 0.875rem;color:#6b7280;font-weight:600;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;">Practitioner</th>
                                    <th style="text-align:left;padding:0.625rem 0.875rem;color:#6b7280;font-weight:600;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;">Chief Complaint</th>
                                    <th style="text-align:left;padding:0.625rem 0.875rem;color:#6b7280;font-weight:600;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($encounters->take(10) as $encounter)
                                @php $rowBg = $loop->even ? '#f9fafb' : '#ffffff'; @endphp
                                <tr style="border-bottom:1px solid #f3f4f6;background-color:{{ $rowBg }};"
                                    onmouseover="this.style.backgroundColor='#f0fdfa'"
                                    onmouseout="this.style.backgroundColor='{{ $rowBg }}'">
                                    <td style="padding:0.625rem 0.875rem;color:#1f2937;white-space:nowrap;">
                                        <a href="{{ $encounterViewUrl($encounter) }}" style="color:#0d9488;text-decoration:none;font-weight:500;">
                                            {{ $encounter->visit_date?->format('M j, Y') ?? '—' }}
                                        </a>
                                    </td>
                                    <td style="padding:0.625rem 0.875rem;color:#374151;">{{ $encounter->practitioner?->user?->name ?? '—' }}</td>
                                    <td style="padding:0.625rem 0.875rem;color:#374151;max-width:200px;">{{ Str::limit($encounter->chief_complaint ?? '—', 45) }}</td>
                                    <td style="padding:0.625rem 0.875rem;">
                                        @php
                                            $encBg    = $encounter->status === 'complete' ? '#d1fae5' : '#f3f4f6';
                                            $encColor = $encounter->status === 'complete' ? '#065f46' : '#374151';
                                        @endphp
                                        <span style="background-color:{{ $encBg }};color:{{ $encColor }};border-radius:9999px;padding:0.15rem 0.55rem;font-size:0.75rem;font-weight:600;">
                                            {{ ucfirst($encounter->status) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- ── TAB: INTAKE & HISTORY ──────────────────────────────────── --}}
            <div x-show="tab === 'intake'" x-cloak>
                @if(!$latestIntake)
                    <div style="text-align:center;padding:2.5rem 1.5rem;background:#fffbeb;border:1px dashed #fcd34d;border-radius:0.5rem;">
                        <div style="font-size:1.5rem;margin-bottom:0.75rem;">📋</div>
                        <p style="font-size:0.9375rem;font-weight:600;color:#374151;margin:0 0 0.375rem 0;">No intake form on file.</p>
                        <p style="font-size:0.875rem;color:#6b7280;margin:0 0 1.25rem 0;">Collect the patient's medical history before their first visit.</p>
                        <a href="{{ $intakeCreateUrl }}"
                           style="display:inline-block;padding:0.5rem 1.25rem;background:#d97706;color:#ffffff;font-size:0.875rem;font-weight:600;border-radius:0.5rem;text-decoration:none;">
                            Start intake →
                        </a>
                    </div>
                @else
                    @php $i = $latestIntake; @endphp

                    {{-- Core complaint --}}
                    <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:0.5rem;padding:1rem;margin-bottom:0.75rem;">
                        <div style="font-size:0.75rem;font-weight:600;color:#9ca3af;text-transform:uppercase;margin-bottom:0.5rem;">Presenting Complaint</div>
                        <div style="font-size:0.9rem;color:#1f2937;margin-bottom:0.5rem;">{{ $i->chief_complaint ?? '—' }}</div>
                        <div style="display:flex;gap:1rem;flex-wrap:wrap;font-size:0.8rem;color:#6b7280;">
                            @if($i->onset_duration) <span>Onset: {{ $i->onset_duration }}</span> @endif
                            @if($i->onset_type) <span>Type: {{ $i->onset_type_label }}</span> @endif
                            @if($i->pain_scale)
                                @php
                                    $p     = $i->pain_scale;
                                    $pBg   = $p <= 3 ? '#d1fae5' : ($p <= 6 ? '#fef3c7' : '#fee2e2');
                                    $pColor = $p <= 3 ? '#065f46' : ($p <= 6 ? '#92400e' : '#991b1b');
                                @endphp
                                <span style="background-color:{{ $pBg }};color:{{ $pColor }};border-radius:9999px;padding:0.1rem 0.5rem;font-weight:600;">
                                    Pain {{ $p }}/10 — {{ $i->pain_scale_label }}
                                </span>
                            @endif
                        </div>
                        @if($i->aggravating_factors || $i->relieving_factors)
                        <div style="margin-top:0.5rem;font-size:0.8rem;color:#374151;">
                            @if($i->aggravating_factors) <div>↑ Worse: {{ $i->aggravating_factors }}</div> @endif
                            @if($i->relieving_factors) <div>↓ Better: {{ $i->relieving_factors }}</div> @endif
                        </div>
                        @endif
                    </div>

                    {{-- Health Flags --}}
                    @php
                        $flags = array_filter([
                            $i->is_pregnant          ? 'Pregnant' : null,
                            $i->has_pacemaker        ? 'Pacemaker' : null,
                            $i->takes_blood_thinners ? 'Blood Thinners' : null,
                            $i->has_bleeding_disorder ? 'Bleeding Disorder' : null,
                            $i->has_infectious_disease ? 'Infectious Disease' : null,
                        ]);
                    @endphp
                    @if(count($flags) > 0)
                    <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:0.5rem;padding:0.75rem 1rem;margin-bottom:0.75rem;">
                        <div style="font-size:0.75rem;font-weight:600;color:#991b1b;text-transform:uppercase;margin-bottom:0.375rem;">⚠ Health Flags</div>
                        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                            @foreach($flags as $flag)
                            <span style="background:#fca5a5;color:#7f1d1d;border-radius:9999px;padding:0.15rem 0.6rem;font-size:0.8rem;font-weight:600;">{{ $flag }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Medical history 2-col --}}
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:0.75rem;">

                        <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:0.5rem;padding:0.875rem;">
                            <div style="font-size:0.75rem;font-weight:600;color:#9ca3af;text-transform:uppercase;margin-bottom:0.4rem;">Current Medications</div>
                            @php
                                $meds     = $i->current_medications;
                                $medsText = is_array($meds)
                                    ? implode(', ', array_map(fn($m) => is_array($m) ? ($m['name'] ?? '') : (string)$m, $meds))
                                    : ($meds ?: null);
                            @endphp
                            <div style="font-size:0.85rem;color:#374151;">{{ $medsText ?: '—' }}</div>
                        </div>

                        <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:0.5rem;padding:0.875rem;">
                            <div style="font-size:0.75rem;font-weight:600;color:#9ca3af;text-transform:uppercase;margin-bottom:0.4rem;">Allergies</div>
                            @php
                                $allerg  = $i->allergies;
                                $allText = is_array($allerg)
                                    ? implode(', ', array_map(fn($a) => is_array($a) ? ($a['name'] ?? '') : (string)$a, $allerg))
                                    : ($allerg ?: null);
                            @endphp
                            <div style="font-size:0.85rem;color:#374151;">{{ $allText ?: 'None reported' }}</div>
                        </div>

                        <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:0.5rem;padding:0.875rem;">
                            <div style="font-size:0.75rem;font-weight:600;color:#9ca3af;text-transform:uppercase;margin-bottom:0.4rem;">Past Diagnoses</div>
                            @php
                                $diag     = $i->past_diagnoses;
                                $diagText = is_array($diag)
                                    ? implode(', ', array_map(fn($d) => is_array($d) ? ($d['condition'] ?? '') : (string)$d, $diag))
                                    : ($diag ?: null);
                            @endphp
                            <div style="font-size:0.85rem;color:#374151;">{{ $diagText ?: '—' }}</div>
                        </div>

                        <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:0.5rem;padding:0.875rem;">
                            <div style="font-size:0.75rem;font-weight:600;color:#9ca3af;text-transform:uppercase;margin-bottom:0.4rem;">Past Surgeries</div>
                            @php
                                $surg     = $i->past_surgeries;
                                $surgText = is_array($surg)
                                    ? implode(', ', array_map(fn($s) => is_array($s) ? ($s['procedure'] ?? '') : (string)$s, $surg))
                                    : ($surg ?: null);
                            @endphp
                            <div style="font-size:0.85rem;color:#374151;">{{ $surgText ?: '—' }}</div>
                        </div>

                    </div>

                    {{-- Lifestyle --}}
                    <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:0.5rem;padding:0.875rem;margin-bottom:0.75rem;">
                        <div style="font-size:0.75rem;font-weight:600;color:#9ca3af;text-transform:uppercase;margin-bottom:0.5rem;">Lifestyle</div>
                        <div style="display:flex;gap:1.25rem;flex-wrap:wrap;font-size:0.85rem;color:#374151;">
                            @if($i->exercise_frequency) <span>Exercise: {{ ucfirst(str_replace('_', ' ', $i->exercise_frequency)) }}</span> @endif
                            @if($i->sleep_quality) <span>Sleep: {{ ucfirst($i->sleep_quality) }}{{ $i->sleep_hours ? ' (' . $i->sleep_hours . 'h)' : '' }}</span> @endif
                            @if($i->stress_level) <span>Stress: {{ ucfirst(str_replace('_', ' ', $i->stress_level)) }}</span> @endif
                            @if($i->smoking_status && $i->smoking_status !== 'never') <span>Smoking: {{ ucfirst($i->smoking_status) }}</span> @endif
                            @if($i->alcohol_use && $i->alcohol_use !== 'none') <span>Alcohol: {{ ucfirst($i->alcohol_use) }}</span> @endif
                        </div>
                    </div>

                    {{-- Goals --}}
                    @if($i->treatment_goals)
                    <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:0.5rem;padding:0.875rem;">
                        <div style="font-size:0.75rem;font-weight:600;color:#9ca3af;text-transform:uppercase;margin-bottom:0.4rem;">Treatment Goals</div>
                        <div style="font-size:0.85rem;color:#374151;">{{ $i->treatment_goals }}</div>
                    </div>
                    @endif
                @endif
            </div>

            {{-- ── TAB: APPOINTMENTS ──────────────────────────────────────── --}}
            <div x-show="tab === 'appointments'" x-cloak>

                {{-- Upcoming --}}
                <div style="margin-bottom:1.5rem;">
                    <div style="font-size:0.8rem;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.625rem;padding-bottom:0.375rem;border-bottom:1px solid #f3f4f6;">Upcoming</div>
                    @if($upcomingAppointments->isEmpty())
                        <div style="color:#9ca3af;font-size:0.875rem;padding:0.5rem 0;">None scheduled.</div>
                    @else
                        @foreach($upcomingAppointments as $appt)
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;padding:0.75rem 0;border-bottom:1px solid #f3f4f6;gap:0.75rem;">
                            <div style="flex:1;">
                                <a href="{{ $appointmentViewUrl($appt) }}" style="color:#0d9488;text-decoration:none;font-weight:500;font-size:0.9rem;">
                                    {{ $appt->start_datetime->format('M j, Y · g:i A') }}
                                </a>
                                <div style="font-size:0.8rem;color:#6b7280;margin-top:0.125rem;">
                                    {{ $appt->practitioner?->user?->name ?? '—' }}
                                    @if($appt->appointmentType) · {{ $appt->appointmentType->name }} @endif
                                </div>
                                <div style="margin-top:0.25rem;">
                                    {!! $encounterBtn($appt, $patient) !!}
                                </div>
                            </div>
                            @php $s = (string)$appt->status; @endphp
                            <span style="background-color:#dbeafe;color:#1e40af;border-radius:9999px;padding:0.15rem 0.55rem;font-size:0.75rem;font-weight:600;white-space:nowrap;">
                                {{ $s }}
                            </span>
                        </div>
                        @endforeach
                    @endif
                </div>

                {{-- Past --}}
                <div>
                    <div style="font-size:0.8rem;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.625rem;padding-bottom:0.375rem;border-bottom:1px solid #f3f4f6;">Past (last 10)</div>
                    @if($pastAppointments->isEmpty())
                        <div style="color:#9ca3af;font-size:0.875rem;padding:0.5rem 0;">No past appointments.</div>
                    @else
                        @foreach($pastAppointments as $appt)
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;padding:0.75rem 0;border-bottom:1px solid #f3f4f6;gap:0.75rem;">
                            <div style="flex:1;">
                                <a href="{{ $appointmentViewUrl($appt) }}" style="color:#0d9488;text-decoration:none;font-weight:500;font-size:0.9rem;">
                                    {{ $appt->start_datetime->format('M j, Y · g:i A') }}
                                </a>
                                <div style="font-size:0.8rem;color:#6b7280;margin-top:0.125rem;">
                                    {{ $appt->practitioner?->user?->name ?? '—' }}
                                    @if($appt->appointmentType) · {{ $appt->appointmentType->name }} @endif
                                </div>
                                <div style="margin-top:0.25rem;">
                                    {!! $encounterBtn($appt, $patient) !!}
                                </div>
                            </div>
                            @php
                                $s       = (string)$appt->status;
                                $apptBg  = str_contains(strtolower($s), 'complet') ? '#d1fae5'
                                         : (str_contains(strtolower($s), 'cancel') ? '#fee2e2' : '#f3f4f6');
                                $apptColor = str_contains(strtolower($s), 'complet') ? '#065f46'
                                           : (str_contains(strtolower($s), 'cancel') ? '#991b1b' : '#374151');
                            @endphp
                            <span style="background-color:{{ $apptBg }};color:{{ $apptColor }};border-radius:9999px;padding:0.15rem 0.55rem;font-size:0.75rem;font-weight:600;white-space:nowrap;">
                                {{ $s }}
                            </span>
                        </div>
                        @endforeach
                    @endif
                </div>
            </div>

            {{-- ── TAB: DEMOGRAPHICS ──────────────────────────────────────── --}}
            <div x-show="tab === 'demographics'" x-cloak>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">

                    {{-- Personal Info --}}
                    <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:0.5rem;padding:1rem;">
                        <div style="font-size:0.75rem;font-weight:600;color:#9ca3af;text-transform:uppercase;margin-bottom:0.75rem;">Personal</div>
                        @foreach([
                            ['First Name',   $patient->first_name],
                            ['Last Name',    $patient->last_name],
                            ['Preferred',    $patient->preferred_name],
                            ['DOB',          $patient->dob?->format('M j, Y')],
                            ['Age',          $patient->dob ? $patient->dob->age . ' years' : null],
                            ['Gender',       $patient->gender ? ucfirst($patient->gender) : null],
                            ['Pronouns',     $patient->pronouns],
                            ['Occupation',   $patient->occupation],
                            ['Referred By',  $patient->referred_by],
                        ] as [$label, $value])
                        @if($value)
                        <div style="display:flex;justify-content:space-between;padding:0.3rem 0;border-bottom:1px solid #f3f4f6;font-size:0.85rem;">
                            <span style="color:#6b7280;">{{ $label }}</span>
                            <span style="color:#1f2937;font-weight:500;">{{ $value }}</span>
                        </div>
                        @endif
                        @endforeach
                    </div>

                    {{-- Contact Info --}}
                    <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:0.5rem;padding:1rem;">
                        <div style="font-size:0.75rem;font-weight:600;color:#9ca3af;text-transform:uppercase;margin-bottom:0.75rem;">Contact</div>
                        @foreach([
                            ['Email',   $patient->email],
                            ['Phone',   $patient->phone],
                            ['Address', trim(($patient->address_line_1 ?? '') . ' ' . ($patient->address_line_2 ?? ''))],
                            ['City',    $patient->city],
                            ['State',   $patient->state],
                            ['Postal',  $patient->postal_code],
                            ['Country', $patient->country],
                        ] as [$label, $value])
                        @if($value)
                        <div style="display:flex;justify-content:space-between;padding:0.3rem 0;border-bottom:1px solid #f3f4f6;font-size:0.85rem;">
                            <span style="color:#6b7280;">{{ $label }}</span>
                            <span style="color:#1f2937;font-weight:500;max-width:200px;text-align:right;">{{ $value }}</span>
                        </div>
                        @endif
                        @endforeach
                    </div>

                    {{-- Emergency Contact --}}
                    @if($patient->emergency_contact_name)
                    <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:0.5rem;padding:1rem;">
                        <div style="font-size:0.75rem;font-weight:600;color:#9ca3af;text-transform:uppercase;margin-bottom:0.75rem;">Emergency Contact</div>
                        @foreach([
                            ['Name',         $patient->emergency_contact_name],
                            ['Relationship', $patient->emergency_contact_relationship],
                            ['Phone',        $patient->emergency_contact_phone],
                        ] as [$label, $value])
                        @if($value)
                        <div style="display:flex;justify-content:space-between;padding:0.3rem 0;border-bottom:1px solid #f3f4f6;font-size:0.85rem;">
                            <span style="color:#6b7280;">{{ $label }}</span>
                            <span style="color:#1f2937;font-weight:500;">{{ $value }}</span>
                        </div>
                        @endif
                        @endforeach
                    </div>
                    @endif

                    {{-- Notes --}}
                    @if($patient->notes)
                    <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:0.5rem;padding:1rem;">
                        <div style="font-size:0.75rem;font-weight:600;color:#9ca3af;text-transform:uppercase;margin-bottom:0.5rem;">Notes</div>
                        <div style="font-size:0.85rem;color:#374151;">{{ $patient->notes }}</div>
                    </div>
                    @endif

                </div>
            </div>

            {{-- ── TAB: BILLING ───────────────────────────────────────────── --}}
            <div x-show="tab === 'billing'" x-cloak>
                @if($checkoutSessions->isEmpty())
                    <div style="text-align:center;padding:2rem;color:#9ca3af;">No billing records found.</div>
                @else
                    @if($outstandingBalance > 0)
                    <div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:0.5rem;padding:0.75rem 1rem;margin-bottom:1rem;font-size:0.9rem;color:#92400e;">
                        <strong>Outstanding Balance: ${{ number_format($outstandingBalance, 2) }}</strong>
                    </div>
                    @endif

                    <div style="overflow-x:auto;">
                        <table style="width:100%;border-collapse:collapse;font-size:0.875rem;">
                            <thead>
                                <tr style="background-color:#f9fafb;border-bottom:2px solid #e5e7eb;">
                                    <th style="text-align:left;padding:0.625rem 0.875rem;color:#6b7280;font-size:0.75rem;text-transform:uppercase;font-weight:600;letter-spacing:0.05em;">Date</th>
                                    <th style="text-align:left;padding:0.625rem 0.875rem;color:#6b7280;font-size:0.75rem;text-transform:uppercase;font-weight:600;letter-spacing:0.05em;">Description</th>
                                    <th style="text-align:right;padding:0.625rem 0.875rem;color:#6b7280;font-size:0.75rem;text-transform:uppercase;font-weight:600;letter-spacing:0.05em;">Total</th>
                                    <th style="text-align:right;padding:0.625rem 0.875rem;color:#6b7280;font-size:0.75rem;text-transform:uppercase;font-weight:600;letter-spacing:0.05em;">Paid</th>
                                    <th style="text-align:left;padding:0.625rem 0.875rem;color:#6b7280;font-size:0.75rem;text-transform:uppercase;font-weight:600;letter-spacing:0.05em;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($checkoutSessions as $session)
                                @php
                                    $isPaid    = $session->state instanceof \App\Models\States\CheckoutSession\Paid;
                                    $sessBg    = $isPaid ? '#d1fae5' : '#fef3c7';
                                    $sessColor = $isPaid ? '#065f46' : '#92400e';
                                    $stateStr  = (string)$session->state;
                                    $billRowBg = $loop->even ? '#f9fafb' : '#ffffff';
                                @endphp
                                <tr style="border-bottom:1px solid #f3f4f6;background-color:{{ $billRowBg }};"
                                    onmouseover="this.style.backgroundColor='#f0fdfa'"
                                    onmouseout="this.style.backgroundColor='{{ $billRowBg }}'">
                                    <td style="padding:0.625rem 0.875rem;color:#1f2937;white-space:nowrap;">
                                        <a href="{{ $checkoutViewUrl($session) }}" style="color:#0d9488;text-decoration:none;font-weight:500;">
                                            {{ $session->started_on?->format('M j, Y') ?? $session->created_at->format('M j, Y') }}
                                        </a>
                                    </td>
                                    <td style="padding:0.625rem 0.875rem;color:#374151;">{{ $session->charge_label ?? '—' }}</td>
                                    <td style="padding:0.625rem 0.875rem;color:#1f2937;text-align:right;font-weight:500;">${{ number_format($session->amount_total, 2) }}</td>
                                    <td style="padding:0.625rem 0.875rem;color:#6b7280;text-align:right;">${{ number_format($session->amount_paid, 2) }}</td>
                                    <td style="padding:0.625rem 0.875rem;">
                                        <span style="background-color:{{ $sessBg }};color:{{ $sessColor }};border-radius:9999px;padding:0.15rem 0.55rem;font-size:0.75rem;font-weight:600;">
                                            {{ $stateStr }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>{{-- end tab content container --}}
    </div>{{-- end x-data --}}
</div>{{-- end two-column --}}
