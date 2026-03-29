<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\AcupunctureEncounter;
use App\Models\CheckoutLine;
use App\Models\CheckoutSession;
use App\Models\ConsentRecord;
use App\Models\Encounter;
use App\Models\IntakeSubmission;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\ServiceFee;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        try {
            // ── Subscription plan catalog ─────────────────────────────────────────
            $this->seedSubscriptionPlans();

            // ── Practice 1: Green Valley Acupuncture ──────────────────────────────
            $acupuncture = Practice::firstOrCreate(
                ['slug' => 'green-valley-acupuncture'],
                [
                    'name'      => 'Green Valley Acupuncture',
                    'timezone'  => 'America/Los_Angeles',
                    'is_active' => true,
                ]
            );

            // ── Admin user (for Filament panel login) ─────────────────────────────
            User::firstOrCreate(
                ['email' => 'admin@healthcare.test'],
                [
                    'name'              => 'Admin',
                    'password'          => Hash::make('password'),
                    'practice_id'       => $acupuncture->id,
                    'email_verified_at' => now(),
                ]
            );

            // Service fees for acupuncture
            $acuFees = collect([
                ['name' => 'Initial Consultation',   'price' => 150.00, 'desc' => 'Comprehensive new patient assessment'],
                ['name' => 'Follow-up Treatment',     'price' => 95.00,  'desc' => 'Standard follow-up acupuncture session'],
                ['name' => 'Cupping Session',         'price' => 80.00,  'desc' => 'Cupping therapy (standalone or add-on)'],
                ['name' => 'Herbal Consultation',     'price' => 75.00,  'desc' => 'TCM herbal formula consultation'],
                ['name' => 'Late Cancellation Fee',   'price' => 50.00,  'desc' => 'Fee for cancellation under 24 hours'],
            ])->map(fn ($data) => ServiceFee::firstOrCreate(
                ['practice_id' => $acupuncture->id, 'name' => $data['name']],
                [
                    'short_description' => $data['desc'],
                    'default_price'     => $data['price'],
                    'is_active'         => true,
                ]
            ));

            $acuFeeByName = $acuFees->keyBy('name');

            $acuTypes = collect([
                ['name' => 'Initial Consultation',   'fee' => 'Initial Consultation'],
                ['name' => 'Follow-up Treatment',    'fee' => 'Follow-up Treatment'],
                ['name' => 'Cupping Session',        'fee' => 'Cupping Session'],
                ['name' => 'Herbal Consultation',    'fee' => 'Herbal Consultation'],
            ])->map(fn ($data) => AppointmentType::firstOrCreate(
                ['practice_id' => $acupuncture->id, 'name' => $data['name']],
                [
                    'is_active'              => true,
                    'default_service_fee_id' => $acuFeeByName[$data['fee']]->id,
                ]
            ));

            $acuPractitioners = collect([
                ['name' => 'Dr. Li Wei',    'specialty' => 'TCM Acupuncture',  'license' => 'AC-10234'],
                ['name' => 'Dr. Maya Patel','specialty' => 'Cupping Therapy',  'license' => 'AC-10235'],
                ['name' => 'Dr. James Park','specialty' => 'Herbal Medicine',  'license' => 'AC-10236'],
            ])->map(function ($data) use ($acupuncture) {
                $user = User::firstOrCreate(
                    ['email' => strtolower(str_replace([' ', '.'], ['_', ''], $data['name'])) . '@greenvalley.test'],
                    [
                        'name'              => $data['name'],
                        'password'          => Hash::make('password'),
                        'practice_id'       => $acupuncture->id,
                        'email_verified_at' => now(),
                    ]
                );
                return Practitioner::firstOrCreate(
                    ['practice_id' => $acupuncture->id, 'user_id' => $user->id],
                    [
                        'license_number' => $data['license'],
                        'specialty'      => $data['specialty'],
                        'is_active'      => true,
                    ]
                );
            });

            $acuPatients = collect([
                ['name' => 'Alice Thornton',  'email' => 'alice.thornton@email.test',  'phone' => '(415) 555-0101'],
                ['name' => 'Bob Nguyen',      'email' => 'bob.nguyen@email.test',       'phone' => '(415) 555-0102'],
                ['name' => 'Carol Martinez',  'email' => 'carol.m@email.test',          'phone' => '(415) 555-0103'],
                ['name' => 'David Kim',       'email' => 'david.kim@email.test',        'phone' => '(415) 555-0104'],
                ['name' => 'Eva Okonkwo',     'email' => 'eva.o@email.test',            'phone' => '(415) 555-0105'],
                ['name' => 'Frank Chen',      'email' => 'frank.chen@email.test',       'phone' => '(415) 555-0106'],
                ['name' => 'Grace Liu',       'email' => 'grace.liu@email.test',        'phone' => '(415) 555-0107'],
                ['name' => 'Henry Walsh',     'email' => 'henry.walsh@email.test',      'phone' => '(415) 555-0108'],
                ['name' => 'Irene Santos',    'email' => 'irene.santos@email.test',     'phone' => '(415) 555-0109'],
                ['name' => 'Jason Burke',     'email' => 'jason.burke@email.test',      'phone' => '(415) 555-0110'],
            ])->map(fn ($data) => Patient::firstOrCreate(
                ['practice_id' => $acupuncture->id, 'email' => $data['email']],
                [
                    'name'       => $data['name'],
                    'phone'      => $data['phone'],
                    'is_patient' => true,
                ]
            ));

            // ── Practice 2: Serenity Massage Therapy ──────────────────────────────
            $massage = Practice::firstOrCreate(
                ['slug' => 'serenity-massage-therapy'],
                [
                    'name'      => 'Serenity Massage Therapy',
                    'timezone'  => 'America/New_York',
                    'is_active' => true,
                ]
            );

            // Service fees for massage
            $massageFees = collect([
                ['name' => 'Swedish Massage (60 min)', 'price' => 90.00,  'desc' => 'Relaxation massage — 60 minutes'],
                ['name' => 'Deep Tissue Massage',       'price' => 110.00, 'desc' => 'Deep tissue therapeutic massage'],
                ['name' => 'Sports Recovery Massage',   'price' => 100.00, 'desc' => 'Sports recovery and injury prevention'],
                ['name' => 'Hot Stone Therapy',          'price' => 120.00, 'desc' => 'Hot stone massage therapy'],
                ['name' => 'Late Cancellation Fee',      'price' => 50.00,  'desc' => 'Fee for cancellation under 24 hours'],
            ])->map(fn ($data) => ServiceFee::firstOrCreate(
                ['practice_id' => $massage->id, 'name' => $data['name']],
                [
                    'short_description' => $data['desc'],
                    'default_price'     => $data['price'],
                    'is_active'         => true,
                ]
            ));

            $massageFeeByName = $massageFees->keyBy('name');

            $massageTypes = collect([
                ['name' => 'Swedish Massage (60 min)', 'fee' => 'Swedish Massage (60 min)'],
                ['name' => 'Deep Tissue Massage',       'fee' => 'Deep Tissue Massage'],
                ['name' => 'Sports Recovery Massage',   'fee' => 'Sports Recovery Massage'],
                ['name' => 'Hot Stone Therapy',          'fee' => 'Hot Stone Therapy'],
            ])->map(fn ($data) => AppointmentType::firstOrCreate(
                ['practice_id' => $massage->id, 'name' => $data['name']],
                [
                    'is_active'              => true,
                    'default_service_fee_id' => $massageFeeByName[$data['fee']]->id,
                ]
            ));

            $massagePractitioners = collect([
                ['name' => 'Sarah Connelly', 'specialty' => 'Swedish Massage',  'license' => 'MT-55001'],
                ['name' => 'Marcus Brown',   'specialty' => 'Deep Tissue',      'license' => 'MT-55002'],
                ['name' => 'Tina Yamamoto',  'specialty' => 'Sports Recovery',  'license' => 'MT-55003'],
            ])->map(function ($data) use ($massage) {
                $user = User::firstOrCreate(
                    ['email' => strtolower(str_replace(' ', '.', $data['name'])) . '@serenity.test'],
                    [
                        'name'              => $data['name'],
                        'password'          => Hash::make('password'),
                        'practice_id'       => $massage->id,
                        'email_verified_at' => now(),
                    ]
                );
                return Practitioner::firstOrCreate(
                    ['practice_id' => $massage->id, 'user_id' => $user->id],
                    [
                        'license_number' => $data['license'],
                        'specialty'      => $data['specialty'],
                        'is_active'      => true,
                    ]
                );
            });

            $massagePatients = collect([
                ['name' => 'Laura Prescott',  'email' => 'laura.p@email.test',        'phone' => '(212) 555-0201'],
                ['name' => 'Mark Delgado',    'email' => 'mark.d@email.test',          'phone' => '(212) 555-0202'],
                ['name' => 'Nancy Ford',      'email' => 'nancy.ford@email.test',      'phone' => '(212) 555-0203'],
                ['name' => 'Oliver Grant',    'email' => 'oliver.grant@email.test',    'phone' => '(212) 555-0204'],
                ['name' => 'Paula Hernandez', 'email' => 'paula.h@email.test',         'phone' => '(212) 555-0205'],
                ['name' => 'Quinn Murphy',    'email' => 'quinn.m@email.test',         'phone' => '(212) 555-0206'],
                ['name' => 'Rachel Stone',    'email' => 'rachel.stone@email.test',    'phone' => '(212) 555-0207'],
                ['name' => 'Samuel Torres',   'email' => 'samuel.torres@email.test',   'phone' => '(212) 555-0208'],
                ['name' => 'Theresa Nguyen',  'email' => 'theresa.n@email.test',       'phone' => '(212) 555-0209'],
                ['name' => 'Ulrich Becker',   'email' => 'ulrich.b@email.test',        'phone' => '(212) 555-0210'],
            ])->map(fn ($data) => Patient::firstOrCreate(
                ['practice_id' => $massage->id, 'email' => $data['email']],
                [
                    'name'       => $data['name'],
                    'phone'      => $data['phone'],
                    'is_patient' => true,
                ]
            ));

            // ── Intake & Consent for acupuncture patients ─────────────────────────
            $this->seedIntakeAndConsent($acupuncture, $acuPatients);

            // ── Intake & Consent for massage patients ─────────────────────────────
            $this->seedIntakeAndConsent($massage, $massagePatients);

            // ── Appointments: 20 total, 4 per status, split across both practices ─
            $statuses = ['scheduled', 'in_progress', 'completed', 'closed', 'checkout'];

            // 10 appointments for Green Valley Acupuncture (2 per status)
            $this->seedAppointments(
                practice: $acupuncture,
                patients: $acuPatients,
                practitioners: $acuPractitioners,
                types: $acuTypes,
                statuses: $statuses,
                count: 2,
            );

            // 10 appointments for Serenity Massage Therapy (2 per status)
            $this->seedAppointments(
                practice: $massage,
                patients: $massagePatients,
                practitioners: $massagePractitioners,
                types: $massageTypes,
                statuses: $statuses,
                count: 2,
            );

            // ── Encounters: 5 per practice with acupuncture extension ─────────────
            $this->seedEncounters($acupuncture, $acuPatients, $acuPractitioners);
            $this->seedEncounters($massage, $massagePatients, $massagePractitioners);

            // ── Checkout sessions: 1 per checkout-status appointment ──────────────
            $this->seedCheckoutSessions($acupuncture, $acuFees);
            $this->seedCheckoutSessions($massage, $massageFees);

        } catch (\Throwable $e) {
            $this->command->error('DatabaseSeeder failed: ' . $e->getMessage());
            $this->command->error('  at ' . $e->getFile() . ':' . $e->getLine());
            throw $e;
        }
    }

    private function seedIntakeAndConsent(
        Practice $practice,
        \Illuminate\Support\Collection $patients,
    ): void {
        $reasons = [
            'Persistent lower back pain radiating to left leg.',
            'Chronic migraines, 3–4 times per week for the past month.',
            'Shoulder tension and reduced range of motion.',
            'Fatigue and difficulty sleeping for the past three weeks.',
            'Digestive issues, bloating after meals.',
            'Anxiety and stress management.',
            'Neck stiffness after long hours at computer.',
        ];

        $concerns = [
            'Pain is worse in the morning and improves through the day.',
            'Headaches begin behind the eyes and spread to the temples.',
            'Shoulder pain worsens when reaching overhead.',
            'Difficulty falling asleep, waking at 3–4 am.',
            'Bloating most noticeable after dinner.',
            'Tension in chest and shallow breathing.',
        ];

        $histories = [
            'No prior surgeries. Family history of hypertension.',
            'Herniated disc (L4-L5) diagnosed 2019, managed conservatively.',
            'Rotator cuff strain 2022, resolved with PT.',
            'No significant medical history.',
            'IBS diagnosis 2018. Managed with diet.',
            'Anxiety disorder, currently on no medication.',
        ];

        $medications = [
            'Ibuprofen 400mg as needed.',
            'None.',
            'Magnesium glycinate 400mg nightly.',
            'Vitamin D3 2000IU daily. Fish oil 1000mg.',
            'Metformin 500mg twice daily.',
            'Sertraline 50mg daily.',
        ];

        $consentSummaries = [
            'I understand the treatment plan and agree to proceed.',
            'Consent given on behalf of myself. No known allergies to treatment.',
            'I have reviewed the informed consent document and agree to the terms.',
            'Agreeing to treatment. Please note I have a latex sensitivity.',
            'Consent given. I may need shorter sessions due to a low pain threshold.',
        ];

        if (IntakeSubmission::where('practice_id', $practice->id)->exists()) {
            return;
        }

        // First 7 patients → complete; last 3 → missing (pending)
        $patients->each(function (Patient $patient, int $index) use (
            $practice, $reasons, $concerns, $histories, $medications, $consentSummaries
        ) {
            $isComplete  = $index < 7;
            $submittedOn = $isComplete ? now()->subDays(14 - $index) : null;

            IntakeSubmission::create([
                'practice_id'      => $practice->id,
                'patient_id'       => $patient->id,
                'status'           => $isComplete ? 'complete' : 'missing',
                'submitted_on'     => $submittedOn,
                'reason_for_visit' => $isComplete ? $reasons[$index % count($reasons)] : null,
                'current_concerns' => $isComplete ? $concerns[$index % count($concerns)] : null,
                'relevant_history' => $isComplete ? $histories[$index % count($histories)] : null,
                'medications'      => $isComplete ? $medications[$index % count($medications)] : null,
            ]);

            ConsentRecord::create([
                'practice_id'      => $practice->id,
                'patient_id'       => $patient->id,
                'status'           => $isComplete ? 'complete' : 'missing',
                'signed_on'        => $submittedOn,
                'consent_given_by' => $isComplete ? $patient->name : null,
                'consent_summary'  => $isComplete ? $consentSummaries[$index % count($consentSummaries)] : null,
            ]);
        });
    }

    private function seedEncounters(
        Practice $practice,
        \Illuminate\Support\Collection $patients,
        \Illuminate\Support\Collection $practitioners,
    ): void {
        // (no practice context needed — BelongsToPractice scope is a no-op without auth)

        $visitNotes = [
            'Patient reports significant improvement in lower back pain. Responded well to treatment. Plan to continue weekly sessions.',
            'Chief concern: chronic headaches. Treatment focused on GB and LI meridians. Patient tolerated well.',
            'Follow-up visit for insomnia protocol. Patient sleeping 6-7 hours vs previous 4. Continuing treatment plan.',
            'New patient intake visit. Comprehensive assessment completed. Initial treatment administered with positive response.',
            'Maintenance treatment. Patient reports feeling balanced. Reduced appointment frequency to bi-weekly.',
        ];

        $tcmDiagnoses = [
            'Liver Qi Stagnation',
            'Kidney Yin Deficiency',
            'Spleen Qi Deficiency',
            'Heart Blood Deficiency',
            'Lung Qi Deficiency',
        ];

        $pointSets = [
            'LR3, SP6, ST36, PC6, HT7',
            'GB20, LI4, ST44, BL62, KD3',
            'CV4, CV6, SP6, ST36, KD7',
            'BL15, BL20, HT7, SP10, PC6',
            'LU7, LI4, SP6, ST36, CV17',
        ];

        $meridianSets = [
            'Liver, Spleen, Stomach',
            'Kidney, Bladder',
            'Heart, Pericardium',
            'Lung, Large Intestine',
            'Gallbladder, Liver',
        ];

        $protocols = [
            'Tonify Liver Qi, regulate menstruation, calm Shen',
            'Nourish Kidney Yin, clear deficiency heat',
            'Tonify Spleen Qi, resolve dampness, improve digestion',
            'Nourish Heart Blood, calm the mind, improve sleep',
            'Tonify Lung Qi, regulate Wei Qi, stop sweating',
        ];

        $sessionNotes = [
            'Patient tolerated all needles well. De-qi achieved at most points.',
            'Retained needles for 25 minutes. Added moxa on CV4 for warmth.',
            'Patient reported immediate relaxation. Mild soreness at LR3 expected.',
            'Electroacupuncture applied at BL25-BL40 pair at 2Hz for 20 min.',
            'Patient fell asleep during treatment — strong Shen response.',
        ];

        $needleCounts = [8, 10, 12, 14, 16];

        if (Encounter::where('practice_id', $practice->id)->exists()) {
            return;
        }

        // Pick 5 appointments for this practice that don't already have an encounter
        $appointments = Appointment::where('practice_id', $practice->id)
            ->whereNotIn('status', ['scheduled'])
            ->inRandomOrder()
            ->limit(5)
            ->get();

        $appointments->each(function (Appointment $appointment, int $index) use (
            $practice, $visitNotes, $tcmDiagnoses, $pointSets, $meridianSets, $protocols, $sessionNotes, $needleCounts
        ) {
            $isComplete = $index < 4; // 4 complete, 1 draft

            $encounter = Encounter::create([
                'practice_id'     => $practice->id,
                'patient_id'      => $appointment->patient_id,
                'appointment_id'  => $appointment->id,
                'practitioner_id' => $appointment->practitioner_id,
                'visit_date'      => $appointment->start_datetime->format('Y-m-d'),
                'status'          => $isComplete ? 'complete' : 'draft',
                'visit_notes'     => $isComplete ? $visitNotes[$index % count($visitNotes)] : null,
                'completed_on'    => $isComplete ? $appointment->start_datetime->copy()->addHour() : null,
            ]);

            // Attach acupuncture extension to every encounter
            AcupunctureEncounter::create([
                'encounter_id'       => $encounter->id,
                'tcm_diagnosis'      => $isComplete ? $tcmDiagnoses[$index % count($tcmDiagnoses)] : null,
                'points_used'        => $isComplete ? $pointSets[$index % count($pointSets)] : null,
                'meridians'          => $isComplete ? $meridianSets[$index % count($meridianSets)] : null,
                'treatment_protocol' => $isComplete ? $protocols[$index % count($protocols)] : null,
                'needle_count'       => $isComplete ? $needleCounts[$index % count($needleCounts)] : null,
                'session_notes'      => $isComplete ? $sessionNotes[$index % count($sessionNotes)] : null,
            ]);
        });
    }

    private function seedAppointments(
        Practice $practice,
        \Illuminate\Support\Collection $patients,
        \Illuminate\Support\Collection $practitioners,
        \Illuminate\Support\Collection $types,
        array $statuses,
        int $count,
    ): void {
        // (no practice context needed — BelongsToPractice scope is a no-op without auth)

        if (Appointment::where('practice_id', $practice->id)->exists()) {
            return;
        }

        $baseDate = now()->subDays(30);

        foreach ($statuses as $statusIndex => $status) {
            for ($i = 0; $i < $count; $i++) {
                $offset    = $statusIndex * 10 + $i;
                $start     = $baseDate->copy()->addDays($offset)->setTime(9 + ($i * 2), 0);
                $end       = $start->copy()->addMinutes(60);

                Appointment::create([
                    'practice_id'         => $practice->id,
                    'patient_id'          => $patients->random()->id,
                    'practitioner_id'     => $practitioners->random()->id,
                    'appointment_type_id' => $types->random()->id,
                    'status'              => $status,
                    'start_datetime'      => $start,
                    'end_datetime'        => $end,
                    'needs_follow_up'     => in_array($status, ['closed', 'checkout']) && ($i % 2 === 0),
                    'notes'               => match ($status) {
                        'scheduled'   => 'Patient confirmed via phone.',
                        'in_progress' => 'Visit started. Patient reported mild tension.',
                        'completed'   => 'Treatment completed successfully.',
                        'closed'      => 'Visit closed. Follow-up recommended in 2 weeks.',
                        'checkout'    => 'Proceeding to payment.',
                        default       => null,
                    },
                ]);
            }
        }
    }

    private function seedCheckoutSessions(
        Practice $practice,
        \Illuminate\Support\Collection $serviceFees,
    ): void {
        // (no practice context needed — BelongsToPractice scope is a no-op without auth)

        if (CheckoutSession::where('practice_id', $practice->id)->exists()) {
            return;
        }

        // Get all checkout-status appointments for this practice that don't yet have a session
        $appointments = Appointment::where('practice_id', $practice->id)
            ->where('status', 'checkout')
            ->get();

        // State distribution: paid (card), paid (cash), payment_due, open
        $statePattern = ['paid_card', 'paid_cash', 'payment_due', 'open'];

        $appointments->each(function (Appointment $appointment, int $index) use ($practice, $serviceFees, $statePattern) {
            $stateKey = $statePattern[$index % count($statePattern)];

            // Pick a service fee to use as the primary line item
            $fee = $serviceFees->random();

            // Create session base data
            $sessionData = [
                'practice_id'     => $practice->id,
                'appointment_id'  => $appointment->id,
                'patient_id'      => $appointment->patient_id,
                'practitioner_id' => $appointment->practitioner_id,
                'charge_label'    => $fee->name,
                'amount_total'    => 0, // will be synced from lines
                'amount_paid'     => 0,
                'started_on'      => $appointment->start_datetime,
            ];

            // Set state-specific fields
            $sessionData['state'] = match ($stateKey) {
                'paid_card'   => 'paid',
                'paid_cash'   => 'paid',
                'payment_due' => 'payment_due',
                'open'        => 'open',
            };

            if (in_array($stateKey, ['paid_card', 'paid_cash'])) {
                $sessionData['tender_type'] = $stateKey === 'paid_card' ? 'card' : 'cash';
                $sessionData['paid_on']     = $appointment->end_datetime;
            }

            $session = CheckoutSession::create($sessionData);

            // Create line items (disable model events temporarily to avoid partial syncs)
            $lines = [];

            // Primary line from the service fee
            $lines[] = [
                'checkout_session_id' => $session->id,
                'practice_id'         => $practice->id,
                'sequence'            => 0,
                'description'         => $fee->name,
                'amount'              => $fee->default_price,
            ];

            // 30% chance of an additional supply/materials line
            if ($index % 3 === 0) {
                $lines[] = [
                    'checkout_session_id' => $session->id,
                    'practice_id'         => $practice->id,
                    'sequence'            => 1,
                    'description'         => 'Supplies & Materials',
                    'amount'              => 15.00,
                ];
            }

            // Insert lines directly to avoid double-firing events during seed
            CheckoutLine::insert($lines);

            // Manually sync total (model events don't fire on ::insert)
            $total = collect($lines)->sum('amount');
            $paidAmount = in_array($stateKey, ['paid_card', 'paid_cash']) ? $total : 0;
            $session->updateQuietly([
                'amount_total' => $total,
                'amount_paid'  => $paidAmount,
            ]);
        });
    }

    private function seedSubscriptionPlans(): void
    {
        $stripePrices = config('services.stripe.subscription_prices', []);

        $plans = [
            [
                'key'                => 'solo',
                'name'               => 'Solo Plan',
                'price_monthly'      => 4900,   // $49.00
                'stripe_price_id'    => $stripePrices['solo'] ?? null,
                'max_practitioners'  => 1,
                'features'           => [
                    '1 practitioner',
                    'Unlimited patients',
                    'Appointment scheduling',
                    'Intake & consent forms',
                    'Encounter notes',
                    'Checkout & payment tracking',
                ],
            ],
            [
                'key'                => 'clinic',
                'name'               => 'Clinic Plan',
                'price_monthly'      => 9900,   // $99.00
                'stripe_price_id'    => $stripePrices['clinic'] ?? null,
                'max_practitioners'  => 5,
                'features'           => [
                    'Up to 5 practitioners',
                    'Unlimited patients',
                    'All Solo features',
                    'Multi-practitioner scheduling',
                    'Team reporting',
                ],
            ],
            [
                'key'                => 'enterprise',
                'name'               => 'Enterprise Plan',
                'price_monthly'      => 19900,  // $199.00
                'stripe_price_id'    => $stripePrices['enterprise'] ?? null,
                'max_practitioners'  => -1,
                'features'           => [
                    'Unlimited practitioners',
                    'Unlimited patients',
                    'All Clinic features',
                    'Priority support',
                    'Custom integrations',
                ],
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(['key' => $plan['key']], $plan);
        }
    }

}
