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
use App\Models\InventoryMovement;
use App\Models\InventoryProduct;
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
                [
                    'slug'          => 'eureka-health',
                    'is_active'     => true,
                    'is_demo'       => true,
                    'trial_ends_at' => now()->addYears(10),
                ]
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

            // 4. Seed Clinical Records (Intake, Encounters, Acupuncture)
            $this->seedClinicalRecords($practice, $patients, $practitioners);

            // 5. Seed Inventory
            $this->seedInventory($practice);

            // 6. Seed Infrastructure (Plans)
            $this->seedSubscriptionPlans();

            // 7. Default communication templates and rules
            (new DefaultMessageTemplatesSeeder())->seedForPractice($practice);

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

    private function seedClinicalRecords($practice, $patients, $practitioners): void
    {
        $acupunctureDoc = $practitioners['Acupuncture'] ?? $practitioners[array_key_first($practitioners)];
        $aptTypes = AppointmentType::where('practice_id', $practice->id)->get();

        foreach ($patients as $patient) {
            // 3-5 intake submissions per patient
            $intakeCount = rand(3, 5);
            for ($i = 0; $i < $intakeCount; $i++) {
                if (rand(0, 1)) {
                    // Complete intake
                    IntakeSubmission::factory()->complete()->create([
                        'practice_id' => $practice->id,
                        'patient_id' => $patient->id,
                    ]);
                } else {
                    // Missing intake
                    IntakeSubmission::factory()->missing()->create([
                        'practice_id' => $practice->id,
                        'patient_id' => $patient->id,
                    ]);
                }
            }

            // 5-10 encounters per patient
            $encounterCount = rand(5, 10);
            for ($i = 0; $i < $encounterCount; $i++) {
                $practitioner = $practitioners[array_rand($practitioners)];
                $aptType = $aptTypes->random();

                // Create an appointment for this encounter
                $appointment = Appointment::create([
                    'practice_id' => $practice->id,
                    'patient_id' => $patient->id,
                    'practitioner_id' => $practitioner->id,
                    'appointment_type_id' => $aptType->id,
                    'status' => Closed::class,
                    'start_datetime' => now()->subDays(rand(1, 60)),
                    'end_datetime' => now()->subDays(rand(1, 60))->addHour(),
                ]);

                // Randomly create complete or draft encounters
                if (rand(0, 2) > 0) {
                    $encounter = Encounter::factory()->complete()->create([
                        'practice_id' => $practice->id,
                        'patient_id' => $patient->id,
                        'appointment_id' => $appointment->id,
                        'practitioner_id' => $practitioner->id,
                    ]);
                } else {
                    $encounter = Encounter::factory()->draft()->create([
                        'practice_id' => $practice->id,
                        'patient_id' => $patient->id,
                        'appointment_id' => $appointment->id,
                        'practitioner_id' => $practitioner->id,
                    ]);
                }

                // For acupuncture practitioners, add acupuncture encounter extension
                if ($practitioner->id === $acupunctureDoc->id && $encounter->status === 'complete' && rand(0, 1)) {
                    AcupunctureEncounter::factory()
                        ->withClinicalData()
                        ->create([
                            'encounter_id' => $encounter->id,
                        ]);
                }
            }
        }
    }

    private function seedInventory($practice): void
    {
        // Create 20-30 inventory products
        $productCount = rand(20, 30);
        $products = [];

        for ($i = 0; $i < $productCount; $i++) {
            $product = InventoryProduct::factory()->create([
                'practice_id' => $practice->id,
            ]);
            $products[] = $product;
        }

        // Create movements for each product (restock, sales, adjustments)
        foreach ($products as $product) {
            // 3-8 movements per product
            $movementCount = rand(3, 8);
            for ($i = 0; $i < $movementCount; $i++) {
                InventoryMovement::factory()->create([
                    'practice_id' => $practice->id,
                    'inventory_product_id' => $product->id,
                ]);
            }
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
