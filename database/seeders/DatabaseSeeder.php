<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\ConsentRecord;
use App\Models\IntakeSubmission;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
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

        $acuTypes = collect([
            'Initial Consultation',
            'Follow-up Treatment',
            'Cupping Session',
            'Herbal Consultation',
        ])->map(fn ($name) => AppointmentType::create([
            'practice_id' => $acupuncture->id,
            'name'        => $name,
            'is_active'   => true,
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

        $massageTypes = collect([
            'Swedish Massage (60 min)',
            'Deep Tissue Massage',
            'Sports Recovery Massage',
            'Hot Stone Therapy',
        ])->map(fn ($name) => AppointmentType::create([
            'practice_id' => $massage->id,
            'name'        => $name,
            'is_active'   => true,
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
}
