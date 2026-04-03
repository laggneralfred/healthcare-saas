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
        $practice = Practice::updateOrCreate(
            ['slug' => 'serenity-acupuncture'],
            [
                'name'       => 'Serenity Acupuncture & Wellness',
                'timezone'   => 'America/Los_Angeles',
                'is_active'  => true,
                'is_demo'    => true,
                'discipline' => 'acupuncture',
            ]
        );

        // ── Admin user ────────────────────────────────────────────────────────
        User::updateOrCreate(
            ['email' => 'demo@practiqapp.com'],
            [
                'name'        => 'Demo Admin',
                'password'    => Hash::make('demo1234'),
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
            ['name' => 'James Patterson', 'email' => 'james.patterson@email.com', 'phone' => '(415) 555-0110', 'dob' => '1979-03-15', 'address_line_1' => '742 Presidio Ave', 'city' => 'San Francisco', 'state' => 'CA', 'postal_code' => '94115', 'emergency_contact_name' => 'Margaret Patterson'],
            ['name' => 'Lisa Cohen', 'email' => 'lisa.cohen@email.com', 'phone' => '(415) 555-0111', 'dob' => '1992-07-22', 'address_line_1' => '1456 Ocean Ave', 'city' => 'San Francisco', 'state' => 'CA', 'postal_code' => '94112', 'emergency_contact_name' => 'Rachel Cohen'],
            ['name' => 'Michael Rodriguez', 'email' => 'michael.r@email.com', 'phone' => '(415) 555-0112', 'dob' => '1968-11-08', 'address_line_1' => '987 Valencia St', 'city' => 'San Francisco', 'state' => 'CA', 'postal_code' => '94103', 'emergency_contact_name' => 'Carlos Rodriguez'],
            ['name' => 'Emma Williams', 'email' => 'emma.w@email.com', 'phone' => '(415) 555-0113', 'dob' => '1995-05-30', 'address_line_1' => '2234 Fillmore St', 'city' => 'San Francisco', 'state' => 'CA', 'postal_code' => '94115', 'emergency_contact_name' => 'Susan Williams'],
            ['name' => 'David Park', 'email' => 'dpark@email.com', 'phone' => '(415) 555-0114', 'dob' => '1975-02-14', 'address_line_1' => '5678 Irving St', 'city' => 'San Francisco', 'state' => 'CA', 'postal_code' => '94122', 'emergency_contact_name' => 'Min Park'],
            ['name' => 'Sarah Thompson', 'email' => 'sthompson@email.com', 'phone' => '(415) 555-0115', 'dob' => '1960-09-25', 'address_line_1' => '1123 Lyon St', 'city' => 'San Francisco', 'state' => 'CA', 'postal_code' => '94109', 'emergency_contact_name' => 'James Thompson'],
            ['name' => 'Robert Martinez', 'email' => 'rmartinez@email.com', 'phone' => '(415) 555-0116', 'dob' => '1972-12-03', 'address_line_1' => '3456 Mission St', 'city' => 'San Francisco', 'state' => 'CA', 'postal_code' => '94110', 'emergency_contact_name' => 'Elena Martinez'],
            ['name' => 'Jennifer Lee', 'email' => 'jlee@email.com', 'phone' => '(415) 555-0117', 'dob' => '1988-06-18', 'address_line_1' => '789 Market St Apt 401', 'city' => 'San Francisco', 'state' => 'CA', 'postal_code' => '94102', 'emergency_contact_name' => 'David Lee'],
            ['name' => 'Christopher Johnson', 'email' => 'cjohnson@email.com', 'phone' => '(415) 555-0118', 'dob' => '1982-04-10', 'address_line_1' => '4567 Geary Blvd', 'city' => 'San Francisco', 'state' => 'CA', 'postal_code' => '94118', 'emergency_contact_name' => 'Patricia Johnson'],
            ['name' => 'Maria Gonzalez', 'email' => 'mgonzalez@email.com', 'phone' => '(415) 555-0119', 'dob' => '1990-01-27', 'address_line_1' => '2890 16th St', 'city' => 'San Francisco', 'state' => 'CA', 'postal_code' => '94103', 'emergency_contact_name' => 'Jorge Gonzalez'],
            ['name' => 'Daniel Anderson', 'email' => 'danderson@email.com', 'phone' => '(415) 555-0120', 'dob' => '1956-08-12', 'address_line_1' => '5555 California St', 'city' => 'San Francisco', 'state' => 'CA', 'postal_code' => '94118', 'emergency_contact_name' => 'Thomas Anderson'],
            ['name' => 'Michelle Brown', 'email' => 'mbrown@email.com', 'phone' => '(415) 555-0121', 'dob' => '1987-10-19', 'address_line_1' => '1234 Haight St', 'city' => 'San Francisco', 'state' => 'CA', 'postal_code' => '94117', 'emergency_contact_name' => 'William Brown'],
            ['name' => 'Kevin Taylor', 'email' => 'ktaylor@email.com', 'phone' => '(415) 555-0122', 'dob' => '1970-07-06', 'address_line_1' => '6789 Castro St', 'city' => 'San Francisco', 'state' => 'CA', 'postal_code' => '94114', 'emergency_contact_name' => 'Anne Taylor'],
            ['name' => 'Patricia White', 'email' => 'pwhite@email.com', 'phone' => '(415) 555-0123', 'dob' => '1951-11-21', 'address_line_1' => '3333 Washington St', 'city' => 'San Francisco', 'state' => 'CA', 'postal_code' => '94115', 'emergency_contact_name' => 'Richard White'],
            ['name' => 'Brian Miller', 'email' => 'bmiller@email.com', 'phone' => '(415) 555-0124', 'dob' => '1998-02-09', 'address_line_1' => '7777 Lombard St', 'city' => 'San Francisco', 'state' => 'CA', 'postal_code' => '94123', 'emergency_contact_name' => 'Karen Miller'],
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

        // Five Elements mapping for TCM clinical data
        $fiveElements = [
            'Wood' => ['color' => 'Green/Blue', 'sound' => 'Shouting', 'odor' => 'Rancid', 'emotion' => 'Anger'],
            'Fire' => ['color' => 'Red', 'sound' => 'Laughing', 'odor' => 'Scorched', 'emotion' => 'Joy'],
            'Earth' => ['color' => 'Yellow', 'sound' => 'Singing', 'odor' => 'Fragrant', 'emotion' => 'Worry'],
            'Metal' => ['color' => 'White', 'sound' => 'Weeping', 'odor' => 'Rotten', 'emotion' => 'Grief'],
            'Water' => ['color' => 'Black', 'sound' => 'Groaning', 'odor' => 'Putrid', 'emotion' => 'Fear'],
        ];

        $tongueBodyVariations = ['Pale', 'Red', 'Purple', 'Slightly red sides', 'Pale with teeth marks', 'Swollen', 'Thin', 'Thick'];
        $tongueCoatingVariations = ['Thin white', 'Thick yellow', 'Thin yellow', 'No coating', 'Greasy white', 'Thin greasy', 'Yellow greasy'];
        $pulseQualityVariations = ['Wiry', 'Slippery', 'Thin', 'Deep weak', 'Rapid thin', 'Wiry slippery', 'Floating weak', 'Deep wiry'];
        $elementArray = array_keys($fiveElements);

        // TCM Part B variations for intake submissions
        $tcmResponsesToVariations = [
            0 => [ // Variation 1: Low energy, stress
                'tcm' => [
                    'energy_level'         => 'low',
                    'energy_time_pattern'  => 'afternoon',
                    'temperature_preference' => 'cold',
                    'appetite'             => 'normal',
                    'digestion_issues'     => ['bloating', 'constipation'],
                    'bowel_frequency'      => 'less_than_daily',
                    'thirst'               => 'low',
                    'beverage_preference'  => 'hot',
                    'sleep_issues'         => ['staying_asleep', 'night_sweats'],
                    'dream_frequency'      => 'sometimes',
                    'emotional_tendencies' => ['stress', 'anxiety'],
                    'emotional_impact'     => 'significantly',
                    'previous_acupuncture' => false,
                    'needle_comfort'       => 'nervous',
                ]
            ],
            1 => [ // Variation 2: Sleep and anxiety
                'tcm' => [
                    'energy_level'         => 'low',
                    'energy_time_pattern'  => 'morning',
                    'temperature_preference' => 'hot',
                    'appetite'             => 'poor',
                    'digestion_issues'     => ['none'],
                    'bowel_frequency'      => 'once_daily',
                    'thirst'               => 'high',
                    'beverage_preference'  => 'cold',
                    'sleep_issues'         => ['falling_asleep', 'vivid_dreams', 'early_waking'],
                    'dream_frequency'      => 'often',
                    'emotional_tendencies' => ['anxiety', 'worry'],
                    'emotional_impact'     => 'significantly',
                    'previous_acupuncture' => true,
                    'previous_acupuncture_experience' => 'positive',
                    'needle_comfort'       => 'comfortable',
                ]
            ],
            2 => [ // Variation 3: Migraines, good exercise
                'tcm' => [
                    'energy_level'         => 'moderate',
                    'energy_time_pattern'  => 'no_pattern',
                    'temperature_preference' => 'neutral',
                    'appetite'             => 'normal',
                    'digestion_issues'     => ['nausea'],
                    'bowel_frequency'      => 'once_daily',
                    'thirst'               => 'normal',
                    'beverage_preference'  => 'room',
                    'sleep_issues'         => ['none'],
                    'dream_frequency'      => 'sometimes',
                    'emotional_tendencies' => ['stress', 'anger'],
                    'emotional_impact'     => 'somewhat',
                    'previous_acupuncture' => false,
                    'needle_comfort'       => 'comfortable',
                ]
            ],
            3 => [ // Variation 4: Balanced with mild issues
                'tcm' => [
                    'energy_level'         => 'normal',
                    'energy_time_pattern'  => 'evening',
                    'temperature_preference' => 'warm',
                    'appetite'             => 'good',
                    'digestion_issues'     => ['occasional_bloating'],
                    'bowel_frequency'      => 'once_daily',
                    'thirst'               => 'moderate',
                    'beverage_preference'  => 'warm',
                    'sleep_issues'         => ['occasional_waking'],
                    'dream_frequency'      => 'rarely',
                    'emotional_tendencies' => ['occasional_stress'],
                    'emotional_impact'     => 'mildly',
                    'previous_acupuncture' => false,
                    'needle_comfort'       => 'comfortable',
                ]
            ],
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

            $selectedElement = $elementArray[$i % count($elementArray)];
            $elementData = $fiveElements[$selectedElement];
            $zangFuExpanded = match($tczDiagnoses[$i % count($tczDiagnoses)]) {
                'Qi deficiency in Spleen and Stomach' => 'Qi deficiency in Spleen and Stomach with Spleen Yang weakness',
                'Liver Qi stagnation' => 'Liver Qi stagnation with Heart Blood deficiency',
                'Blood deficiency with Spleen weakness' => 'Blood deficiency with Spleen weakness and poor transport',
                'Kidney Yang deficiency' => 'Kidney Yang deficiency with poor Ming Men fire',
                'Heart and Spleen disharmony' => 'Heart and Spleen disharmony with anxiety',
                'Damp-heat in Spleen and Liver' => 'Damp-heat in Spleen and Liver with Qi stagnation',
                'Yin deficiency with empty heat' => 'Yin deficiency with empty heat and insomnia',
                'Cold obstruction in the meridians' => 'Cold obstruction in the meridians with poor circulation',
                'Liver Yang rising' => 'Liver Yang rising with Liver Blood deficiency',
                'Phlegm obstruction in the chest' => 'Phlegm obstruction in the chest with Qi stagnation',
                default => 'Qi and Blood disharmony',
            };

            AcupunctureEncounter::create([
                'encounter_id'    => $encounter->id,
                'tcm_diagnosis'   => $tczDiagnoses[$i % count($tczDiagnoses)],
                'tongue_body'     => $tongueBodyVariations[$i % count($tongueBodyVariations)],
                'tongue_coating'  => $tongueCoatingVariations[$i % count($tongueCoatingVariations)],
                'pulse_quality'   => $pulseQualityVariations[$i % count($pulseQualityVariations)],
                'zang_fu_diagnosis' => $zangFuExpanded,
                'five_elements'   => json_encode([$selectedElement]),
                'csor_color'      => $elementData['color'],
                'csor_sound'      => $elementData['sound'],
                'csor_odor'       => $elementData['odor'],
                'csor_emotion'    => $elementData['emotion'],
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
                'discipline_responses' => $tcmResponsesToVariations[$i % 4],
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

                $selectedElement = $elementArray[$i % count($elementArray)];
                $elementData = $fiveElements[$selectedElement];

                AcupunctureEncounter::create([
                    'encounter_id'      => $encounter->id,
                    'tcm_diagnosis'     => $tczDiagnoses[0],
                    'tongue_body'       => $tongueBodyVariations[$i % count($tongueBodyVariations)],
                    'tongue_coating'    => $tongueCoatingVariations[$i % count($tongueCoatingVariations)],
                    'pulse_quality'     => $pulseQualityVariations[$i % count($pulseQualityVariations)],
                    'zang_fu_diagnosis' => 'Qi deficiency in Spleen and Stomach with Spleen Yang weakness',
                    'five_elements'     => json_encode([$selectedElement]),
                    'csor_color'        => $elementData['color'],
                    'csor_sound'        => $elementData['sound'],
                    'csor_odor'         => $elementData['odor'],
                    'csor_emotion'      => $elementData['emotion'],
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
                'discipline_responses' => $status !== 'scheduled' ? $tcmResponsesToVariations[$i % 4] : null,
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
                'discipline_responses' => $tcmResponsesToVariations[$i % 4],
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

        // ── Featured intake submissions with full TCM health history ─────────────

        // Patient 1 — James Patterson — chronic lower back pain
        IntakeSubmission::create([
            'practice_id'    => $practice->id,
            'patient_id'     => $patients[0]->id,
            'status'         => 'complete',
            'discipline'     => 'acupuncture',
            'submitted_on'   => $now->copy()->subDays(3),
            'chief_complaint' => 'Chronic lower back pain, worse with prolonged sitting. Some radiation into the left buttock.',
            'onset_duration' => '8 months',
            'onset_type'     => 'gradual',
            'aggravating_factors' => 'Sitting at a desk for more than 30 minutes, cold weather, stress.',
            'relieving_factors'   => 'Heat, gentle walking, lying down.',
            'pain_scale'     => 6,
            'previous_episodes' => true,
            'previous_episodes_description' => 'Had similar pain 3 years ago after moving house. Resolved on its own after 6 weeks.',
            'exercise_frequency' => 'rarely',
            'sleep_quality'  => 'fair',
            'sleep_hours'    => 6,
            'stress_level'   => 'high',
            'treatment_goals' => 'Reduce pain to a manageable level so I can work a full day without discomfort. Improve flexibility.',
            'consent_given'  => true,
            'consent_signed_by' => 'James Patterson',
            'consent_signed_at' => $now->copy()->subDays(3),
            'discipline_responses' => [
                'tcm' => [
                    'energy_level'         => 'low',
                    'energy_time_pattern'  => 'afternoon',
                    'temperature_preference' => 'cold',
                    'appetite'             => 'normal',
                    'digestion_issues'     => ['bloating', 'constipation'],
                    'bowel_frequency'      => 'less_than_daily',
                    'thirst'               => 'low',
                    'beverage_preference'  => 'hot',
                    'sleep_issues'         => ['staying_asleep', 'night_sweats'],
                    'dream_frequency'      => 'sometimes',
                    'emotional_tendencies' => ['stress', 'anxiety'],
                    'emotional_impact'     => 'significantly',
                    'previous_acupuncture' => false,
                    'needle_comfort'       => 'nervous',
                ],
            ],
        ]);

        // Patient 2 — Lisa Cohen — insomnia and anxiety
        IntakeSubmission::create([
            'practice_id'    => $practice->id,
            'patient_id'     => $patients[1]->id,
            'status'         => 'complete',
            'discipline'     => 'acupuncture',
            'submitted_on'   => $now->copy()->subDays(7),
            'chief_complaint' => 'Difficulty sleeping and persistent anxiety. Racing thoughts at night, trouble unwinding after work.',
            'onset_duration' => '4 months',
            'onset_type'     => 'gradual',
            'aggravating_factors' => 'Work deadlines, screen time before bed, caffeine.',
            'relieving_factors'   => 'Meditation (sometimes), weekends when workload is lighter.',
            'pain_scale'     => 3,
            'previous_episodes' => false,
            'exercise_frequency' => '1-2x_week',
            'sleep_quality'  => 'poor',
            'sleep_hours'    => 5,
            'stress_level'   => 'very_high',
            'diet_description' => 'Tends to skip breakfast. High coffee intake (3-4 cups/day). Light dinner.',
            'treatment_goals' => 'Fall asleep more easily and stay asleep through the night. Feel less anxious during the day.',
            'success_indicators' => 'Sleeping 7 hours without waking. Feeling calm before work presentations.',
            'consent_given'  => true,
            'consent_signed_by' => 'Lisa Cohen',
            'consent_signed_at' => $now->copy()->subDays(7),
            'discipline_responses' => [
                'tcm' => [
                    'energy_level'         => 'low',
                    'energy_time_pattern'  => 'morning',
                    'temperature_preference' => 'hot',
                    'appetite'             => 'poor',
                    'digestion_issues'     => ['none'],
                    'bowel_frequency'      => 'once_daily',
                    'thirst'               => 'high',
                    'beverage_preference'  => 'cold',
                    'sleep_issues'         => ['falling_asleep', 'vivid_dreams', 'early_waking'],
                    'dream_frequency'      => 'often',
                    'emotional_tendencies' => ['anxiety', 'worry'],
                    'emotional_impact'     => 'significantly',
                    'previous_acupuncture' => true,
                    'previous_acupuncture_experience' => 'positive',
                    'needle_comfort'       => 'comfortable',
                ],
            ],
        ]);

        // Patient 3 — Michael Rodriguez — recurring migraines
        IntakeSubmission::create([
            'practice_id'    => $practice->id,
            'patient_id'     => $patients[2]->id,
            'status'         => 'complete',
            'discipline'     => 'acupuncture',
            'submitted_on'   => $now->copy()->subDays(14),
            'chief_complaint' => 'Recurring migraines 2-3 times per month. Throbbing pain, usually right-sided. Accompanied by nausea and light sensitivity.',
            'onset_duration' => '2 years',
            'onset_type'     => 'recurring',
            'aggravating_factors' => 'Bright lights, loud noise, red wine, hormonal fluctuations, skipping meals.',
            'relieving_factors'   => 'Dark quiet room, cold compress, sleep.',
            'pain_scale'     => 8,
            'previous_episodes' => true,
            'previous_episodes_description' => 'Migraines started after a period of intense workplace stress 2 years ago. Have been cyclical since.',
            'exercise_frequency' => '3-4x_week',
            'sleep_quality'  => 'good',
            'sleep_hours'    => 7,
            'stress_level'   => 'moderate',
            'smoking_status' => 'never',
            'alcohol_use'    => 'social',
            'treatment_goals' => 'Reduce migraine frequency to once a month or less. Shorten duration when they do occur.',
            'success_indicators' => 'Going two months with fewer than 2 migraines. Being able to work through mild headaches without medication.',
            'consent_given'  => true,
            'consent_signed_by' => 'Michael Rodriguez',
            'consent_signed_at' => $now->copy()->subDays(14),
            'discipline_responses' => [
                'tcm' => [
                    'energy_level'         => 'moderate',
                    'energy_time_pattern'  => 'no_pattern',
                    'temperature_preference' => 'neutral',
                    'appetite'             => 'normal',
                    'digestion_issues'     => ['nausea'],
                    'bowel_frequency'      => 'once_daily',
                    'thirst'               => 'normal',
                    'beverage_preference'  => 'room',
                    'sleep_issues'         => ['none'],
                    'dream_frequency'      => 'sometimes',
                    'emotional_tendencies' => ['stress', 'anger'],
                    'emotional_impact'     => 'somewhat',
                    'previous_acupuncture' => false,
                    'needle_comfort'       => 'comfortable',
                ],
            ],
        ]);

        // Seed inventory products (if add-on is included)
        $this->call(InventoryProductSeeder::class);

        // Seed default communication templates and rules
        (new DefaultMessageTemplatesSeeder())->seedForPractice($practice);

        $this->command->info('Demo data seeded successfully.');
        $this->command->info('  Practice : Serenity Acupuncture & Wellness');
        $this->command->info('  Timezone : America/Los_Angeles');
        $this->command->info('  Practitioner : Dr. Sarah Chen, L.Ac.');
        $this->command->info('  Patients : ' . count($patients));
        $this->command->info('  Appointments : ' . $appointmentCount);
        $this->command->info('  Inventory Products : 15 herbal products, formulas, and supplements');
        $this->command->info('  Login : demo@serenity.test / password');
        $this->command->info('  Booking : /book/serenity-acupuncture');
    }
}
