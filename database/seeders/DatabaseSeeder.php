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
use App\Models\States\Appointment\Closed;
use App\Models\States\Appointment\Checkout;
use App\Models\States\Appointment\Cancelled;
use App\Models\States\CheckoutSession\Paid;
use App\Models\States\CheckoutSession\Open;
use App\Models\States\CheckoutSession\PaymentDue;
use App\Models\States\CheckoutSession\Voided;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        try {
            // 1. Setup the Infrastructure
            $practice = Practice::updateOrCreate(
                ['name' => 'Eureka Integrated Health'],
                ['slug' => 'eureka-health', 'is_active' => true]
            );

            $admin = User::where('email', 'admin@healthcare.test')->first() 
                     ?? User::factory()->create([
                         'name' => 'Alfred Laggner',
                         'email' => 'admin@healthcare.test', 
                         'password' => Hash::make('password'),
                         'practice_id' => $practice->id
                     ]);

            // Create some Appointment Types
            $aptTypes = [
                AppointmentType::create(['practice_id' => $practice->id, 'name' => 'Initial Consultation', 'duration_minutes' => 60]),
                AppointmentType::create(['practice_id' => $practice->id, 'name' => 'Follow-up Visit', 'duration_minutes' => 30]),
                AppointmentType::create(['practice_id' => $practice->id, 'name' => 'Emergency Session', 'duration_minutes' => 45]),
            ];

            // 2. Define the Team
            $team = [
                'Acupuncture' => ['name' => 'Acu Anna', 'specialty' => 'Acupuncture'],
                'Chiro'       => ['name' => 'Dr. Bone', 'specialty' => 'Chiropractic'],
                'Massage'     => ['name' => 'Sarah Massage', 'specialty' => 'Massage Therapy'],
                'PT'          => ['name' => 'PT Paul', 'specialty' => 'Physical Therapy'],
            ];

            $practitioners = [];
            foreach ($team as $key => $data) {
                // Practitioner needs a User
                $user = User::factory()->create([
                    'name' => $data['name'],
                    'email' => strtolower(str_replace(' ', '.', $data['name'])) . '@healthcare.test',
                    'practice_id' => $practice->id,
                ]);

                $practitioners[$key] = Practitioner::updateOrCreate(
                    ['user_id' => $user->id, 'practice_id' => $practice->id],
                    ['specialty' => $data['specialty'], 'is_active' => true]
                );
            }

            // 3. The "State of the Clinic" Generator
            $patients = Patient::factory()->count(50)->create(['practice_id' => $practice->id]);

            foreach ($practitioners as $key => $doc) {
                // BILLED & PAID (The Green State)
                $this->createAppointment($practice, $doc, $patients->random(), $aptTypes[array_rand($aptTypes)], Closed::class, Paid::class);

                // UNPAID / OPEN (The Yellow State)
                $this->createAppointment($practice, $doc, $patients->random(), $aptTypes[array_rand($aptTypes)], Checkout::class, Open::class);

                // OVERDUE (The Red State)
                $this->createAppointment($practice, $doc, $patients->random(), $aptTypes[array_rand($aptTypes)], Checkout::class, PaymentDue::class, now()->subDays(45));

                // CANCELLED (The Grey State)
                $this->createAppointment($practice, $doc, $patients->random(), $aptTypes[array_rand($aptTypes)], Cancelled::class, Voided::class);
            }

            // 4. Seed Infrastructure (Plans)
            $this->seedSubscriptionPlans();

            $this->command->info('Clinic seeded: 5 Practitioners (including Admin), 50 Patients, and a mess of Financial States.');

        } catch (\Throwable $e) {
            $this->command->error('DatabaseSeeder failed: ' . $e->getMessage());
            $this->command->error('  at ' . $e->getFile() . ':' . $e->getLine());
            throw $e;
        }
    }

    private function createAppointment($practice, $doc, $patient, $type, $status, $paymentState, $date = null)
    {
        $appt = Appointment::create([
            'practice_id' => $practice->id,
            'practitioner_id' => $doc->id,
            'patient_id' => $patient->id,
            'appointment_type_id' => $type->id,
            'status' => $status,
            'start_datetime' => $date ?? now()->subHours(rand(1, 72)),
            'end_datetime' => ($date ?? now())->addHour(),
        ]);

        if ($status !== Cancelled::class) {
            CheckoutSession::create([
                'practice_id' => $practice->id,
                'appointment_id' => $appt->id,
                'patient_id' => $patient->id,
                'practitioner_id' => $doc->id,
                'state' => $paymentState,
                'charge_label' => $type->name,
                'amount_total' => rand(85, 150),
                'amount_paid' => $paymentState === Paid::class ? 100 : 0,
            ]);
        }
    }

    private function seedSubscriptionPlans(): void
    {
        $plans = [
            [
                'key' => 'solo', 
                'name' => 'Solo Plan', 
                'price_monthly' => 4900, 
                'max_practitioners' => 1,
                'features' => ['Core clinical tools', '1 Practitioner', 'Basic reporting']
            ],
            [
                'key' => 'clinic', 
                'name' => 'Clinic Plan', 
                'price_monthly' => 9900, 
                'max_practitioners' => 5,
                'features' => ['Up to 5 Practitioners', 'Advanced reporting', 'Inventory management']
            ],
            [
                'key' => 'enterprise', 
                'name' => 'Enterprise Plan', 
                'price_monthly' => 19900, 
                'max_practitioners' => -1,
                'features' => ['Unlimited Practitioners', 'Custom reporting', 'Priority support']
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(['key' => $plan['key']], $plan);
        }
    }
}
