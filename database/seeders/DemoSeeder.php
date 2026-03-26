<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\ConsentRecord;
use App\Models\IntakeSubmission;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\ServiceFee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // ── Practice ──────────────────────────────────────────────────────────
        $practice = Practice::firstOrCreate(
            ['slug' => 'demo-acupuncture-clinic'],
            [
                'name'      => 'Demo Acupuncture Clinic',
                'timezone'  => 'America/Los_Angeles',
                'is_active' => true,
            ]
        );

        // Set the PostgreSQL tenant context so RLS policies allow inserts for
        // this practice.  The seeder runs as the healthcare DB user which is
        // subject to FORCE ROW LEVEL SECURITY on all practice-scoped tables.
        DB::statement(
            "SELECT set_config('app.practice_id', ?, false)",
            [(string) $practice->id]
        );

        // ── Admin user ────────────────────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name'        => 'Demo Admin',
                'password'    => Hash::make('password'),
                'practice_id' => $practice->id,
            ]
        );

        // ── Service fees ──────────────────────────────────────────────────────
        $feeData = [
            ['name' => 'Initial Consultation',  'default_price' => 150.00, 'short_description' => 'Comprehensive new patient assessment'],
            ['name' => 'Follow-up Treatment',   'default_price' => 95.00,  'short_description' => 'Standard acupuncture follow-up session'],
            ['name' => 'Cupping Session',       'default_price' => 80.00,  'short_description' => 'Cupping therapy session'],
            ['name' => 'Herbal Consultation',   'default_price' => 75.00,  'short_description' => 'TCM herbal formula consultation'],
        ];

        $fees = [];
        foreach ($feeData as $data) {
            $fees[$data['name']] = ServiceFee::firstOrCreate(
                ['practice_id' => $practice->id, 'name' => $data['name']],
                array_merge($data, ['practice_id' => $practice->id, 'is_active' => true])
            );
        }

        // ── Appointment types ─────────────────────────────────────────────────
        $typeData = [
            ['name' => 'Initial Consultation', 'fee' => 'Initial Consultation'],
            ['name' => 'Follow-up Treatment',  'fee' => 'Follow-up Treatment'],
            ['name' => 'Cupping Session',      'fee' => 'Cupping Session'],
            ['name' => 'Herbal Consultation',  'fee' => 'Herbal Consultation'],
        ];

        $types = [];
        foreach ($typeData as $data) {
            $types[$data['name']] = AppointmentType::firstOrCreate(
                ['practice_id' => $practice->id, 'name' => $data['name']],
                [
                    'practice_id'            => $practice->id,
                    'is_active'              => true,
                    'default_service_fee_id' => $fees[$data['fee']]->id,
                ]
            );
        }

        // ── Practitioner ──────────────────────────────────────────────────────
        $practUser = User::firstOrCreate(
            ['email' => 'practitioner@demo-clinic.test'],
            [
                'name'        => 'Dr. Sarah Chen',
                'password'    => Hash::make('password'),
                'practice_id' => $practice->id,
            ]
        );

        $practitioner = Practitioner::firstOrCreate(
            ['practice_id' => $practice->id, 'user_id' => $practUser->id],
            [
                'license_number' => 'AC-99001',
                'specialty'      => 'TCM Acupuncture',
                'is_active'      => true,
            ]
        );

        // ── 5 patients with appointments in various statuses ──────────────────
        $patients = [
            ['name' => 'Alice Thornton', 'email' => 'alice@demo.test',   'phone' => '(415) 555-0101'],
            ['name' => 'Bob Nguyen',     'email' => 'bob@demo.test',     'phone' => '(415) 555-0102'],
            ['name' => 'Carol Martinez', 'email' => 'carol@demo.test',   'phone' => '(415) 555-0103'],
            ['name' => 'David Kim',      'email' => 'david@demo.test',   'phone' => '(415) 555-0104'],
            ['name' => 'Eva Okonkwo',   'email' => 'eva@demo.test',     'phone' => '(415) 555-0105'],
        ];

        // One appointment per status so the dashboard shows variety
        $statuses = ['scheduled', 'in_progress', 'completed', 'closed', 'checkout'];

        $typeList = array_values($types);

        foreach ($patients as $index => $patientData) {
            $patient = Patient::firstOrCreate(
                ['practice_id' => $practice->id, 'email' => $patientData['email']],
                array_merge($patientData, ['practice_id' => $practice->id, 'is_patient' => true])
            );

            $status    = $statuses[$index];
            $startTime = Carbon::now('America/Los_Angeles')
                ->addDays($index - 2)   // mix of past and upcoming
                ->setTime(9 + $index * 1, 0, 0)
                ->utc();

            $appointment = Appointment::firstOrCreate(
                [
                    'practice_id'    => $practice->id,
                    'patient_id'     => $patient->id,
                    'start_datetime' => $startTime,
                ],
                [
                    'practitioner_id'     => $practitioner->id,
                    'appointment_type_id' => $typeList[$index % count($typeList)]->id,
                    'status'              => $status,
                    'start_datetime'      => $startTime,
                    'end_datetime'        => $startTime->copy()->addHour(),
                    'notes'               => match ($status) {
                        'scheduled'   => 'Patient confirmed via phone.',
                        'in_progress' => 'Visit in progress.',
                        'completed'   => 'Treatment completed successfully.',
                        'closed'      => 'Visit closed. Follow-up in 2 weeks.',
                        'checkout'    => 'Proceeding to payment.',
                        default       => null,
                    },
                ]
            );

            // Intake and consent for each patient
            IntakeSubmission::firstOrCreate(
                ['practice_id' => $practice->id, 'patient_id' => $patient->id, 'appointment_id' => $appointment->id],
                [
                    'practice_id'    => $practice->id,
                    'patient_id'     => $patient->id,
                    'appointment_id' => $appointment->id,
                    'status'         => in_array($status, ['completed', 'closed', 'checkout']) ? 'complete' : 'pending',
                    'submitted_on'   => in_array($status, ['completed', 'closed', 'checkout']) ? $startTime->copy()->subDay() : null,
                ]
            );

            ConsentRecord::firstOrCreate(
                ['practice_id' => $practice->id, 'patient_id' => $patient->id, 'appointment_id' => $appointment->id],
                [
                    'practice_id'     => $practice->id,
                    'patient_id'      => $patient->id,
                    'appointment_id'  => $appointment->id,
                    'status'          => in_array($status, ['completed', 'closed', 'checkout']) ? 'complete' : 'pending',
                    'signed_on'       => in_array($status, ['completed', 'closed', 'checkout']) ? $startTime->copy()->subDay() : null,
                    'consent_given_by'=> in_array($status, ['completed', 'closed', 'checkout']) ? $patient->name : null,
                ]
            );
        }

        $this->command->info('Demo data seeded successfully.');
        $this->command->info('  Practice : Demo Acupuncture Clinic');
        $this->command->info('  Login    : demo@example.com / password');
        $this->command->info('  Booking  : /book/demo-acupuncture-clinic');
    }
}
