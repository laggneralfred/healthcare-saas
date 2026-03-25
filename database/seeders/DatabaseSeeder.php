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
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Admin user (for Filament panel login) ─────────────────────────────
        User::factory()->create([
            'name'     => 'Admin',
            'email'    => 'admin@healthcare.test',
            'password' => Hash::make('password'),
        ]);

        // ── Practice 1: Green Valley Acupuncture ──────────────────────────────
        $acupuncture = Practice::create([
            'name'     => 'Green Valley Acupuncture',
            'slug'     => 'green-valley-acupuncture',
            'timezone' => 'America/Los_Angeles',
            'is_active' => true,
        ]);

        // Service fees for acupuncture
        $acuFees = collect([
            ['name' => 'Initial Consultation',   'price' => 150.00, 'desc' => 'Comprehensive new patient assessment'],
            ['name' => 'Follow-up Treatment',     'price' => 95.00,  'desc' => 'Standard follow-up acupuncture session'],
            ['name' => 'Cupping Session',         'price' => 80.00,  'desc' => 'Cupping therapy (standalone or add-on)'],
            ['name' => 'Herbal Consultation',     'price' => 75.00,  'desc' => 'TCM herbal formula consultation'],
            ['name' => 'Late Cancellation Fee',   'price' => 50.00,  'desc' => 'Fee for cancellation under 24 hours'],
        ])->map(fn ($data) => ServiceFee::create([
            'practice_id'       => $acupuncture->id,
            'name'              => $data['name'],
            'short_description' => $data['desc'],
            'default_price'     => $data['price'],
            'is_active'         => true,
        ]));

        $acuFeeByName = $acuFees->keyBy('name');

        $acuTypes = collect([
            ['name' => 'Initial Consultation',   'fee' => 'Initial Consultation'],
            ['name' => 'Follow-up Treatment',    'fee' => 'Follow-up Treatment'],
            ['name' => 'Cupping Session',        'fee' => 'Cupping Session'],
            ['name' => 'Herbal Consultation',    'fee' => 'Herbal Consultation'],
        ])->map(fn ($data) => AppointmentType::create([
            'practice_id'            => $acupuncture->id,
            'name'                   => $data['name'],
            'is_active'              => true,
            'default_service_fee_id' => $acuFeeByName[$data['fee']]->id,
        ]));

        $acuPractitioners = collect([
            ['name' => 'Dr. Li Wei',    'specialty' => 'TCM Acupuncture',  'license' => 'AC-10234'],
            ['name' => 'Dr. Maya Patel','specialty' => 'Cupping Therapy',  'license' => 'AC-10235'],
            ['name' => 'Dr. James Park','specialty' => 'Herbal Medicine',  'license' => 'AC-10236'],
        ])->map(function ($data) use ($acupuncture) {
            $user = User::factory()->create([
                'name'        => $data['name'],
                'email'       => strtolower(str_replace([' ', '.'], ['_', ''], $data['name'])) . '@greenvalley.test',
                'practice_id' => $acupuncture->id,
            ]);
            return Practitioner::create([
                'practice_id'    => $acupuncture->id,
                'user_id'        => $user->id,
                'license_number' => $data['license'],
                'specialty'      => $data['specialty'],
                'is_active'      => true,
            ]);
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
        ])->map(fn ($data) => Patient::create([
            'practice_id' => $acupuncture->id,
            'name'        => $data['name'],
            'email'       => $data['email'],
            'phone'       => $data['phone'],
            'is_patient'  => true,
        ]));

        // ── Practice 2: Serenity Massage Therapy ──────────────────────────────
        $massage = Practice::create([
            'name'     => 'Serenity Massage Therapy',
            'slug'     => 'serenity-massage-therapy',
            'timezone' => 'America/New_York',
            'is_active' => true,
        ]);

        // Service fees for massage
        $massageFees = collect([
            ['name' => 'Swedish Massage (60 min)', 'price' => 90.00,  'desc' => 'Relaxation massage — 60 minutes'],
            ['name' => 'Deep Tissue Massage',       'price' => 110.00, 'desc' => 'Deep tissue therapeutic massage'],
            ['name' => 'Sports Recovery Massage',   'price' => 100.00, 'desc' => 'Sports recovery and injury prevention'],
            ['name' => 'Hot Stone Therapy',          'price' => 120.00, 'desc' => 'Hot stone massage therapy'],
            ['name' => 'Late Cancellation Fee',      'price' => 50.00,  'desc' => 'Fee for cancellation under 24 hours'],
        ])->map(fn ($data) => ServiceFee::create([
            'practice_id'       => $massage->id,
            'name'              => $data['name'],
            'short_description' => $data['desc'],
            'default_price'     => $data['price'],
            'is_active'         => true,
        ]));

        $massageFeeByName = $massageFees->keyBy('name');

        $massageTypes = collect([
            ['name' => 'Swedish Massage (60 min)', 'fee' => 'Swedish Massage (60 min)'],
            ['name' => 'Deep Tissue Massage',       'fee' => 'Deep Tissue Massage'],
            ['name' => 'Sports Recovery Massage',   'fee' => 'Sports Recovery Massage'],
            ['name' => 'Hot Stone Therapy',          'fee' => 'Hot Stone Therapy'],
        ])->map(fn ($data) => AppointmentType::create([
            'practice_id'            => $massage->id,
            'name'                   => $data['name'],
            'is_active'              => true,
            'default_service_fee_id' => $massageFeeByName[$data['fee']]->id,
        ]));

        $massagePractitioners = collect([
            ['name' => 'Sarah Connelly', 'specialty' => 'Swedish Massage',  'license' => 'MT-55001'],
            ['name' => 'Marcus Brown',   'specialty' => 'Deep Tissue',      'license' => 'MT-55002'],
            ['name' => 'Tina Yamamoto', 'specialty' => 'Sports Recovery',  'license' => 'MT-55003'],
        ])->map(function ($data) use ($massage) {
            $user = User::factory()->create([
                'name'        => $data['name'],
                'email'       => strtolower(str_replace(' ', '.', $data['name'])) . '@serenity.test',
                'practice_id' => $massage->id,
            ]);
            return Practitioner::create([
                'practice_id'    => $massage->id,
                'user_id'        => $user->id,
                'license_number' => $data['license'],
                'specialty'      => $data['specialty'],
                'is_active'      => true,
            ]);
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
        ])->map(fn ($data) => Patient::create([
            'practice_id' => $massage->id,
            'name'        => $data['name'],
            'email'       => $data['email'],
            'phone'       => $data['phone'],
            'is_patient'  => true,
        ]));

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
    }

    private function seedIntakeAndConsent(
        Practice $practice,
        \Illuminate\Support\Collection $patients,
    ): void {
        // First 7 patients → complete; last 3 → missing (pending)
        $patients->each(function (Patient $patient, int $index) use ($practice) {
            $isComplete = $index < 7;

            $intakeFactory = IntakeSubmission::factory()->state([
                'practice_id' => $practice->id,
                'patient_id'  => $patient->id,
            ]);

            $consentFactory = ConsentRecord::factory()->state([
                'practice_id' => $practice->id,
                'patient_id'  => $patient->id,
            ]);

            $isComplete
                ? $intakeFactory->complete()->create()
                : $intakeFactory->missing()->create();

            $isComplete
                ? $consentFactory->complete()->create()
                : $consentFactory->missing()->create();
        });
    }

    private function seedEncounters(
        Practice $practice,
        \Illuminate\Support\Collection $patients,
        \Illuminate\Support\Collection $practitioners,
    ): void {
        // Pick 5 appointments for this practice that don't already have an encounter
        $appointments = Appointment::where('practice_id', $practice->id)
            ->whereNotIn('status', ['scheduled'])
            ->inRandomOrder()
            ->limit(5)
            ->get();

        $appointments->each(function (Appointment $appointment, int $index) use ($practice, $practitioners) {
            $isComplete = $index < 4; // 4 complete, 1 draft

            $encounter = Encounter::factory()
                ->state([
                    'practice_id'     => $practice->id,
                    'patient_id'      => $appointment->patient_id,
                    'appointment_id'  => $appointment->id,
                    'practitioner_id' => $appointment->practitioner_id,
                    'visit_date'      => $appointment->start_datetime->format('Y-m-d'),
                ])
                ->when($isComplete, fn ($f) => $f->complete(), fn ($f) => $f->draft())
                ->create();

            // Attach acupuncture extension to every encounter
            AcupunctureEncounter::factory()
                ->state(['encounter_id' => $encounter->id])
                ->when($isComplete, fn ($f) => $f->withClinicalData())
                ->create();
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
}
