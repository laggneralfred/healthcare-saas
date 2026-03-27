<?php

namespace Database\Seeders;

use App\Models\AcupunctureEncounter;
use App\Models\Appointment;
use App\Models\AppointmentType;
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
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // ── Practice ──────────────────────────────────────────────────────────
        $practice = Practice::firstOrCreate(
            ['slug' => 'serenity-acupuncture'],
            [
                'name'      => 'Serenity Acupuncture & Wellness',
                'timezone'  => 'America/Los_Angeles',
                'is_active' => true,
            ]
        );

        // ── Admin user ────────────────────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'demo@serenity.test'],
            [
                'name'        => 'Demo Admin',
                'password'    => Hash::make('password'),
                'practice_id' => $practice->id,
            ]
        );

        // ── Service fees ──────────────────────────────────────────────────────
        $feeData = [
            ['name' => 'Initial Consultation',  'default_price' => 150.00],
            ['name' => 'Follow-up Treatment',   'default_price' => 95.00],
            ['name' => 'Stress & Anxiety Protocol', 'default_price' => 110.00],
            ['name' => 'Community Acupuncture', 'default_price' => 45.00],
        ];

        $fees = [];
        foreach ($feeData as $data) {
            $fees[$data['name']] = ServiceFee::firstOrCreate(
                ['practice_id' => $practice->id, 'name' => $data['name']],
                array_merge($data, ['practice_id' => $practice->id, 'is_active' => true])
            );
        }

        // ── Appointment types ─────────────────────────────────────────────────
        $types = [];
        $typeConfigs = [
            ['name' => 'Initial Consultation', 'fee' => 'Initial Consultation', 'duration' => 90],
            ['name' => 'Follow-up Treatment', 'fee' => 'Follow-up Treatment', 'duration' => 60],
            ['name' => 'Stress & Anxiety Protocol', 'fee' => 'Stress & Anxiety Protocol', 'duration' => 75],
            ['name' => 'Community Acupuncture', 'fee' => 'Community Acupuncture', 'duration' => 45],
        ];

        foreach ($typeConfigs as $config) {
            $types[$config['name']] = AppointmentType::firstOrCreate(
                ['practice_id' => $practice->id, 'name' => $config['name']],
                [
                    'practice_id'            => $practice->id,
                    'name'                   => $config['name'],
                    'duration_minutes'       => $config['duration'],
                    'is_active'              => true,
                    'default_service_fee_id' => $fees[$config['fee']]->id,
                ]
            );
        }

        // ── Practitioner ──────────────────────────────────────────────────────
        $practUser = User::firstOrCreate(
            ['email' => 'sarah@serenity.test'],
            [
                'name'        => 'Dr. Sarah Chen',
                'password'    => Hash::make('password'),
                'practice_id' => $practice->id,
            ]
        );

        $practitioner = Practitioner::firstOrCreate(
            ['practice_id' => $practice->id, 'user_id' => $practUser->id],
            [
                'user_id'       => $practUser->id,
                'license_number' => 'L.Ac. CA-12847',
                'specialty'     => 'Acupuncture & Oriental Medicine',
                'is_active'     => true,
            ]
        );

        // ── 15 Patients (realistic demographics) ──────────────────────────────
        $patientData = [
            ['name' => 'James Patterson', 'email' => 'james.patterson@email.com', 'phone' => '(415) 555-0110'],
            ['name' => 'Lisa Cohen', 'email' => 'lisa.cohen@email.com', 'phone' => '(415) 555-0111'],
            ['name' => 'Michael Rodriguez', 'email' => 'michael.r@email.com', 'phone' => '(415) 555-0112'],
            ['name' => 'Emma Williams', 'email' => 'emma.w@email.com', 'phone' => '(415) 555-0113'],
            ['name' => 'David Park', 'email' => 'dpark@email.com', 'phone' => '(415) 555-0114'],
            ['name' => 'Sarah Thompson', 'email' => 'sthompson@email.com', 'phone' => '(415) 555-0115'],
            ['name' => 'Robert Martinez', 'email' => 'rmartinez@email.com', 'phone' => '(415) 555-0116'],
            ['name' => 'Jennifer Lee', 'email' => 'jlee@email.com', 'phone' => '(415) 555-0117'],
            ['name' => 'Christopher Johnson', 'email' => 'cjohnson@email.com', 'phone' => '(415) 555-0118'],
            ['name' => 'Maria Gonzalez', 'email' => 'mgonzalez@email.com', 'phone' => '(415) 555-0119'],
            ['name' => 'Daniel Anderson', 'email' => 'danderson@email.com', 'phone' => '(415) 555-0120'],
            ['name' => 'Michelle Brown', 'email' => 'mbrown@email.com', 'phone' => '(415) 555-0121'],
            ['name' => 'Kevin Taylor', 'email' => 'ktaylor@email.com', 'phone' => '(415) 555-0122'],
            ['name' => 'Patricia White', 'email' => 'pwhite@email.com', 'phone' => '(415) 555-0123'],
            ['name' => 'Brian Miller', 'email' => 'bmiller@email.com', 'phone' => '(415) 555-0124'],
        ];

        $patients = [];
        foreach ($patientData as $data) {
            $patients[] = Patient::firstOrCreate(
                ['practice_id' => $practice->id, 'email' => $data['email']],
                array_merge($data, [
                    'practice_id' => $practice->id,
                    'is_patient'  => true,
                ])
            );
        }

        // ── ACUPUNCTURE DATA ────────────────────────────────────────────────────
        $acupuncturePoints = [
            'LI4' => 'Large Intestine 4 (Hegu)',
            'ST36' => 'Stomach 36 (Zusanli)',
            'SP6' => 'Spleen 6 (Sanyinjiao)',
            'LV3' => 'Liver 3 (Taichong)',
            'PC6' => 'Pericardium 6 (Neiguan)',
            'GV20' => 'Governing Vessel 20 (Baihui)',
            'UB60' => 'Bladder 60 (Kunlun)',
            'KI3' => 'Kidney 3 (Taixi)',
            'HT7' => 'Heart 7 (Shenmen)',
            'GV14' => 'Governing Vessel 14 (Dazhui)',
        ];

        $tczDiagnoses = [
            'Qi deficiency in Spleen and Stomach',
            'Liver Qi stagnation',
            'Blood deficiency with Spleen weakness',
            'Kidney Yang deficiency',
            'Heart and Spleen disharmony',
            'Damp-heat in Spleen and Liver',
            'Yin deficiency with empty heat',
            'Cold obstruction in the meridians',
            'Liver Yang rising',
            'Phlegm obstruction in the chest',
        ];

        $chiefComplaints = [
            'Chronic neck and shoulder pain',
            'Lower back pain and stiffness',
            'Tension headaches',
            'Anxiety and insomnia',
            'Digestive issues and bloating',
            'Fatigue and lack of energy',
            'Menstrual irregularity',
            'Migraines',
            'Joint pain and inflammation',
            'Stress-related tension',
        ];

        // ── CREATE APPOINTMENTS ──────────────────────────────────────────────────
        // 30 historical completed appointments (spread across past 6 months)
        // 5 today (mix of statuses)
        // 5 upcoming this week

        $now = Carbon::now('America/Los_Angeles');
        $appointmentCount = 0;
        $typeArray = array_values($types);

        // Historical completed appointments (last 6 months)
        for ($i = 0; $i < 30; $i++) {
            $daysAgo = rand(5, 180);
            $appointmentDate = $now->copy()->subDays($daysAgo)->startOfDay();
            $hour = rand(9, 15);
            $appointmentDate->setHour($hour)->setMinutes(0);

            $patient = $patients[$i % count($patients)];
            $appointmentType = $typeArray[$i % count($typeArray)];
            $appointmentStart = $appointmentDate->copy()->utc();
            $appointmentEnd = $appointmentStart->copy()->addMinutes($appointmentType->duration_minutes);

            $appointment = Appointment::create([
                'practice_id'        => $practice->id,
                'patient_id'         => $patient->id,
                'practitioner_id'    => $practitioner->id,
                'appointment_type_id' => $appointmentType->id,
                'status'             => 'completed',
                'start_datetime'     => $appointmentStart,
                'end_datetime'       => $appointmentEnd,
                'notes'              => 'Completed acupuncture session.',
            ]);

            // Create Encounter
            $encounter = Encounter::create([
                'practice_id'    => $practice->id,
                'patient_id'     => $patient->id,
                'appointment_id' => $appointment->id,
                'practitioner_id' => $practitioner->id,
                'status'         => 'complete',
                'visit_date'     => $appointmentStart->toDateString(),
                'completed_on'   => $appointmentStart->copy()->addHour(),
                'visit_notes'    => implode("\n\n", [
                    'SUBJECTIVE: ' . $patient->name . ' presented with ' . $chiefComplaints[$i % count($chiefComplaints)],
                    'OBJECTIVE: Examination showed appropriate pulse quality and tongue appearance.',
                    'ASSESSMENT: ' . $tczDiagnoses[$i % count($tczDiagnoses)],
                    'PLAN: Continue acupuncture treatments with herbal support.',
                ]),
            ]);

            // Create AcupunctureEncounter
            $selectedPoints = array_rand($acupuncturePoints, rand(3, 5));
            $selectedPoints = is_array($selectedPoints) ? $selectedPoints : [$selectedPoints];
            $pointsList = implode(', ', array_map(fn($key) => $acupuncturePoints[$key], $selectedPoints));

            AcupunctureEncounter::create([
                'encounter_id'    => $encounter->id,
                'tcm_diagnosis'   => $tczDiagnoses[$i % count($tczDiagnoses)],
                'points_used'     => $pointsList,
                'meridians'       => 'Multiple',
                'treatment_protocol' => 'Standard bilateral acupuncture protocol',
                'needle_count'    => rand(8, 14),
                'session_notes'   => 'Patient responded well to treatment. No adverse reactions.',
            ]);

            // Create Intake & Consent
            IntakeSubmission::create([
                'practice_id'    => $practice->id,
                'patient_id'     => $patient->id,
                'appointment_id' => $appointment->id,
                'status'         => 'complete',
                'submitted_on'   => $appointmentStart->copy()->subDay(),
            ]);

            ConsentRecord::create([
                'practice_id'      => $practice->id,
                'patient_id'       => $patient->id,
                'appointment_id'   => $appointment->id,
                'status'           => 'complete',
                'signed_on'        => $appointmentStart->copy()->subDay(),
                'consent_given_by' => $patient->name,
            ]);

            // Create CheckoutSession (paid)
            $fee = $appointmentType->defaultServiceFee;
            $checkoutSession = CheckoutSession::create([
                'practice_id'      => $practice->id,
                'appointment_id'   => $appointment->id,
                'patient_id'       => $patient->id,
                'practitioner_id'  => $practitioner->id,
                'state'            => 'paid',
                'charge_label'     => $appointmentType->name,
                'amount_total'     => $fee->default_price,
                'amount_paid'      => $fee->default_price,
                'tender_type'      => rand(0, 1) ? 'card' : 'cash',
                'started_on'       => $appointmentStart,
                'paid_on'          => $appointmentStart->copy()->addHour(),
                'payment_note'     => 'Payment received.',
            ]);

            // Create CheckoutLine
            CheckoutLine::create([
                'checkout_session_id' => $checkoutSession->id,
                'practice_id'         => $practice->id,
                'sequence'            => 1,
                'description'         => $appointmentType->name,
                'amount'              => $fee->default_price,
            ]);

            $appointmentCount++;
        }

        // ── TODAY'S APPOINTMENTS (5 total - mix of statuses) ──────────────────
        $todayStatuses = ['scheduled', 'in_progress', 'completed', 'in_progress', 'scheduled'];
        for ($i = 0; $i < 5; $i++) {
            $appointmentType = $typeArray[$i % count($typeArray)];
            $hour = 9 + ($i * 2);
            $appointmentStart = $now->copy()->setHour($hour)->setMinutes(0)->utc();
            $appointmentEnd = $appointmentStart->copy()->addMinutes($appointmentType->duration_minutes);

            $patient = $patients[$i];
            $status = $todayStatuses[$i];

            $appointment = Appointment::create([
                'practice_id'        => $practice->id,
                'patient_id'         => $patient->id,
                'practitioner_id'    => $practitioner->id,
                'appointment_type_id' => $appointmentType->id,
                'status'             => $status,
                'start_datetime'     => $appointmentStart,
                'end_datetime'       => $appointmentEnd,
                'notes'              => match($status) {
                    'scheduled' => 'Appointment scheduled.',
                    'in_progress' => 'Treatment in progress.',
                    'completed' => 'Treatment completed today.',
                    default => ''
                },
            ]);

            // Only create encounter/checkout for completed today appointments
            if ($status === 'completed') {
                $encounter = Encounter::create([
                    'practice_id'    => $practice->id,
                    'patient_id'     => $patient->id,
                    'appointment_id' => $appointment->id,
                    'practitioner_id' => $practitioner->id,
                    'status'         => 'complete',
                    'visit_date'     => $appointmentStart->toDateString(),
                    'completed_on'   => $appointmentStart->copy()->addHour(),
                    'visit_notes'    => implode("\n\n", [
                        'SUBJECTIVE: ' . $patient->name . ' reported good progress.',
                        'OBJECTIVE: Pulse and tongue appear healthy.',
                        'ASSESSMENT: ' . $tczDiagnoses[0],
                        'PLAN: Continue current treatment protocol.',
                    ]),
                ]);

                AcupunctureEncounter::create([
                    'encounter_id'      => $encounter->id,
                    'tcm_diagnosis'     => $tczDiagnoses[0],
                    'points_used'       => implode(', ', ['LI4', 'ST36', 'SP6']),
                    'meridians'         => 'Multiple',
                    'treatment_protocol' => 'Standard bilateral acupuncture protocol',
                    'needle_count'      => 10,
                    'session_notes'     => 'Today\'s session went well.',
                ]);

                $fee = $appointmentType->defaultServiceFee;
                $checkoutSession = CheckoutSession::create([
                    'practice_id'      => $practice->id,
                    'appointment_id'   => $appointment->id,
                    'patient_id'       => $patient->id,
                    'practitioner_id'  => $practitioner->id,
                    'state'            => 'paid',
                    'charge_label'     => $appointmentType->name,
                    'amount_total'     => $fee->default_price,
                    'amount_paid'      => $fee->default_price,
                    'tender_type'      => 'card',
                    'started_on'       => $appointmentStart,
                    'paid_on'          => $appointmentStart->copy()->addMinutes(30),
                ]);

                CheckoutLine::create([
                    'checkout_session_id' => $checkoutSession->id,
                    'practice_id'         => $practice->id,
                    'sequence'            => 1,
                    'description'         => $appointmentType->name,
                    'amount'              => $fee->default_price,
                ]);
            }

            // Create intake/consent for all
            IntakeSubmission::create([
                'practice_id'    => $practice->id,
                'patient_id'     => $patient->id,
                'appointment_id' => $appointment->id,
                'status'         => $status === 'scheduled' ? 'pending' : 'complete',
                'submitted_on'   => $status === 'scheduled' ? null : $appointmentStart->copy()->subHours(2),
            ]);

            ConsentRecord::create([
                'practice_id'      => $practice->id,
                'patient_id'       => $patient->id,
                'appointment_id'   => $appointment->id,
                'status'           => $status === 'scheduled' ? 'pending' : 'complete',
                'signed_on'        => $status === 'scheduled' ? null : $appointmentStart->copy()->subHours(2),
                'consent_given_by' => $status === 'scheduled' ? null : $patient->name,
            ]);

            $appointmentCount++;
        }

        // ── THIS WEEK'S UPCOMING (5 appointments in next 3-6 days) ────────────
        for ($i = 0; $i < 5; $i++) {
            $appointmentType = $typeArray[($i + 1) % count($typeArray)];
            $daysFromNow = 1 + $i;
            $hour = 10 + ($i % 3) * 2;
            $appointmentStart = $now->copy()->addDays($daysFromNow)->setHour($hour)->setMinutes(0)->utc();
            $appointmentEnd = $appointmentStart->copy()->addMinutes($appointmentType->duration_minutes);

            $patient = $patients[(5 + $i) % count($patients)];

            $appointment = Appointment::create([
                'practice_id'        => $practice->id,
                'patient_id'         => $patient->id,
                'practitioner_id'    => $practitioner->id,
                'appointment_type_id' => $appointmentType->id,
                'status'             => 'scheduled',
                'start_datetime'     => $appointmentStart,
                'end_datetime'       => $appointmentEnd,
                'notes'              => 'Upcoming appointment.',
            ]);

            // Create intake/consent as pending
            IntakeSubmission::create([
                'practice_id'    => $practice->id,
                'patient_id'     => $patient->id,
                'appointment_id' => $appointment->id,
                'status'         => 'pending',
                'submitted_on'   => null,
            ]);

            ConsentRecord::create([
                'practice_id'      => $practice->id,
                'patient_id'       => $patient->id,
                'appointment_id'   => $appointment->id,
                'status'           => 'pending',
                'signed_on'        => null,
                'consent_given_by' => null,
            ]);

            $appointmentCount++;
        }

        $this->command->info('Demo data seeded successfully.');
        $this->command->info('  Practice : Serenity Acupuncture & Wellness');
        $this->command->info('  Timezone : America/Los_Angeles');
        $this->command->info('  Practitioner : Dr. Sarah Chen, L.Ac.');
        $this->command->info('  Patients : ' . count($patients));
        $this->command->info('  Appointments : ' . $appointmentCount);
        $this->command->info('  Login : demo@serenity.test / password');
        $this->command->info('  Booking : /book/serenity-acupuncture');
    }
}
