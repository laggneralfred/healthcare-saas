<?php

namespace Database\Seeders;

use App\Models\AcupunctureEncounter;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\CheckoutLine;
use App\Models\CheckoutSession;
use App\Models\ConsentRecord;
use App\Models\Encounter;
use App\Models\InventoryMovement;
use App\Models\InventoryProduct;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\ServiceFee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PracticeTwoDemoSeeder extends Seeder
{
    private const PRACTICE_ID = 2;
    private const PRACTICE_TIMEZONE = 'America/Los_Angeles';

    private Practice $practice;
    private array $users = [];
    private array $practitioners = [];
    private array $serviceFees = [];
    private array $appointmentTypes = [];
    private array $inventoryProducts = [];
    private array $patients = [];

    public function run(): void
    {
        DB::transaction(function () {
            $this->practice = $this->seedPractice();
            $this->resetPracticeData(self::PRACTICE_ID);

            $this->users = $this->seedUsers();
            $this->practitioners = $this->seedPractitioners();
            $this->serviceFees = $this->seedServiceFees();
            $this->appointmentTypes = $this->seedAppointmentTypes();
            $this->inventoryProducts = $this->seedInventory();
            $this->patients = $this->seedPatients();

            $this->seedClinicalData();
        });
    }

    private function seedPractice(): Practice
    {
        return Practice::query()->updateOrCreate(
            ['id' => self::PRACTICE_ID],
            [
                'name'                      => 'Harbor Integrative Clinic',
                'slug'                      => 'harbor-integrative-clinic',
                'timezone'                  => self::PRACTICE_TIMEZONE,
                'is_active'                 => true,
                'is_demo'                   => true,
                'discipline'                => 'integrative',
                'referral_source'           => 'Google',
                'trial_ends_at'             => now()->addYears(5),
                'default_appointment_duration' => 60,
                'default_reminder_hours'     => 24,
            ]
        );
    }

    private function resetPracticeData(int $practiceId): void
    {
        $encounterIds = Encounter::withoutPracticeScope()
            ->where('practice_id', $practiceId)
            ->pluck('id');

        $checkoutIds = CheckoutSession::withoutPracticeScope()
            ->where('practice_id', $practiceId)
            ->pluck('id');

        AcupunctureEncounter::withoutPracticeScope()
            ->whereIn('encounter_id', $encounterIds)
            ->delete();

        CheckoutLine::withoutPracticeScope()
            ->whereIn('checkout_session_id', $checkoutIds)
            ->delete();

        CheckoutSession::withoutPracticeScope()
            ->where('practice_id', $practiceId)
            ->delete();

        Encounter::withoutPracticeScope()
            ->where('practice_id', $practiceId)
            ->delete();

        MedicalHistory::withoutPracticeScope()
            ->where('practice_id', $practiceId)
            ->delete();

        ConsentRecord::withoutPracticeScope()
            ->where('practice_id', $practiceId)
            ->delete();

        Appointment::withoutPracticeScope()
            ->where('practice_id', $practiceId)
            ->delete();

        InventoryMovement::withoutPracticeScope()
            ->where('practice_id', $practiceId)
            ->delete();

        InventoryProduct::withoutPracticeScope()
            ->where('practice_id', $practiceId)
            ->delete();

        AppointmentType::withoutPracticeScope()
            ->where('practice_id', $practiceId)
            ->delete();

        ServiceFee::withoutPracticeScope()
            ->where('practice_id', $practiceId)
            ->delete();

        Practitioner::withoutPracticeScope()
            ->where('practice_id', $practiceId)
            ->delete();

        Patient::withoutPracticeScope()
            ->where('practice_id', $practiceId)
            ->delete();

        User::query()
            ->where('practice_id', $practiceId)
            ->delete();
    }

    private function seedUsers(): array
    {
        $users = [
            'owner' => [
                'email' => 'owner@harborintegrative.test',
                'name' => 'Mia Thornton',
            ],
            'acu' => [
                'email' => 'julia@harborintegrative.test',
                'name' => 'Dr. Julia Nguyen, L.Ac.',
            ],
            'massage' => [
                'email' => 'sam@harborintegrative.test',
                'name' => 'Sam Carter, LMT',
            ],
        ];

        foreach ($users as $key => $data) {
            $users[$key] = User::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'practice_id' => self::PRACTICE_ID,
                    'name'        => $data['name'],
                    'password'    => Hash::make('password'),
                ]
            );
        }

        return $users;
    }

    private function seedPractitioners(): array
    {
        return [
            'acu' => Practitioner::withoutPracticeScope()->updateOrCreate(
                ['practice_id' => self::PRACTICE_ID, 'user_id' => $this->users['acu']->id],
                [
                    'practice_id'    => self::PRACTICE_ID,
                    'user_id'        => $this->users['acu']->id,
                    'license_number' => 'L.Ac. CA-48219',
                    'specialty'      => 'Acupuncture and East Asian Medicine',
                    'is_active'      => true,
                ]
            ),
            'massage' => Practitioner::withoutPracticeScope()->updateOrCreate(
                ['practice_id' => self::PRACTICE_ID, 'user_id' => $this->users['massage']->id],
                [
                    'practice_id'    => self::PRACTICE_ID,
                    'user_id'        => $this->users['massage']->id,
                    'license_number' => 'LMT CA-77104',
                    'specialty'      => 'Massage Therapy and Recovery Work',
                    'is_active'      => true,
                ]
            ),
        ];
    }

    private function seedServiceFees(): array
    {
        $fees = [
            'acu_initial' => [
                'name' => 'Acupuncture Initial Consultation',
                'short_description' => 'First visit with assessment and treatment plan',
                'default_price' => 145.00,
            ],
            'acu_follow' => [
                'name' => 'Acupuncture Follow-up',
                'short_description' => 'Standard follow-up acupuncture visit',
                'default_price' => 105.00,
            ],
            'acu_short' => [
                'name' => 'Focused Acupuncture Follow-up',
                'short_description' => 'Shorter follow-up for acute symptom relief',
                'default_price' => 85.00,
            ],
            'massage_intake' => [
                'name' => 'Massage Intake Session',
                'short_description' => 'Initial massage assessment with longer intake',
                'default_price' => 120.00,
            ],
            'massage_60' => [
                'name' => 'Therapeutic Massage 60',
                'short_description' => 'Standard therapeutic massage',
                'default_price' => 110.00,
            ],
            'massage_90' => [
                'name' => 'Deep Tissue Massage 90',
                'short_description' => 'Longer deep tissue session',
                'default_price' => 145.00,
            ],
            'massage_sports' => [
                'name' => 'Sports Recovery Massage',
                'short_description' => 'Sports and recovery-focused bodywork',
                'default_price' => 130.00,
            ],
            'no_show' => [
                'name' => 'No-Show Fee',
                'short_description' => 'Fee charged when a visit is missed',
                'default_price' => 50.00,
            ],
            'late_cancel' => [
                'name' => 'Late Cancellation Fee',
                'short_description' => 'Fee for late cancellation within 24 hours',
                'default_price' => 35.00,
            ],
        ];

        $models = [];

        foreach ($fees as $key => $data) {
            $models[$key] = ServiceFee::withoutPracticeScope()->updateOrCreate(
                ['practice_id' => self::PRACTICE_ID, 'name' => $data['name']],
                [
                    'practice_id'       => self::PRACTICE_ID,
                    'name'              => $data['name'],
                    'short_description' => $data['short_description'],
                    'default_price'     => $data['default_price'],
                    'is_active'         => true,
                ]
            );
        }

        return $models;
    }

    private function seedAppointmentTypes(): array
    {
        $types = [
            'acu_initial' => ['name' => 'Acupuncture Initial Consult', 'duration' => 90, 'fee' => 'acu_initial'],
            'acu_follow' => ['name' => 'Acupuncture Follow-up', 'duration' => 60, 'fee' => 'acu_follow'],
            'acu_short' => ['name' => 'Focused Acupuncture Follow-up', 'duration' => 45, 'fee' => 'acu_short'],
            'massage_intake' => ['name' => 'Massage Intake', 'duration' => 75, 'fee' => 'massage_intake'],
            'massage_60' => ['name' => 'Therapeutic Massage', 'duration' => 60, 'fee' => 'massage_60'],
            'massage_90' => ['name' => 'Deep Tissue Massage', 'duration' => 90, 'fee' => 'massage_90'],
            'massage_sports' => ['name' => 'Sports Recovery Massage', 'duration' => 75, 'fee' => 'massage_sports'],
        ];

        $models = [];

        foreach ($types as $key => $data) {
            $models[$key] = AppointmentType::withoutPracticeScope()->updateOrCreate(
                ['practice_id' => self::PRACTICE_ID, 'name' => $data['name']],
                [
                    'practice_id'            => self::PRACTICE_ID,
                    'name'                   => $data['name'],
                    'duration_minutes'       => $data['duration'],
                    'is_active'              => true,
                    'default_service_fee_id' => $this->serviceFees[$data['fee']]->id,
                ]
            );
        }

        return $models;
    }

    private function seedInventory(): array
    {
        $products = [
            'needles' => ['name' => 'Sterile Acupuncture Needles', 'sku' => 'HAR-NEEDLES-100', 'category' => 'Other', 'unit' => 'box', 'selling_price' => 28.00, 'stock_quantity' => 0, 'restock' => 40],
            'moxa' => ['name' => 'Moxa Stick Pack', 'sku' => 'HAR-MOXA-10', 'category' => 'Herbal Formula', 'unit' => 'packet', 'selling_price' => 18.00, 'stock_quantity' => 0, 'restock' => 24],
            'cupping' => ['name' => 'Cupping Set', 'sku' => 'HAR-CUP-SET', 'category' => 'Other', 'unit' => 'set', 'selling_price' => 42.00, 'stock_quantity' => 0, 'restock' => 12],
            'oil' => ['name' => 'Massage Oil 1L', 'sku' => 'HAR-OIL-1L', 'category' => 'Other', 'unit' => 'bottle', 'selling_price' => 32.00, 'stock_quantity' => 0, 'restock' => 20],
            'arnica' => ['name' => 'Arnica Balm', 'sku' => 'HAR-ARNICA', 'category' => 'Supplement', 'unit' => 'tube', 'selling_price' => 24.00, 'stock_quantity' => 0, 'restock' => 18],
            'gua_sha' => ['name' => 'Gua Sha Tool', 'sku' => 'HAR-GS-01', 'category' => 'Other', 'unit' => 'count', 'selling_price' => 16.00, 'stock_quantity' => 0, 'restock' => 14],
            'heat_pack' => ['name' => 'Reusable Heat Pack', 'sku' => 'HAR-HEAT', 'category' => 'Other', 'unit' => 'count', 'selling_price' => 26.00, 'stock_quantity' => 0, 'restock' => 10],
            'tea' => ['name' => 'Herbal Tea Pack', 'sku' => 'HAR-TEA', 'category' => 'Herbal Formula', 'unit' => 'packet', 'selling_price' => 14.00, 'stock_quantity' => 0, 'restock' => 16],
        ];

        $models = [];

        foreach ($products as $key => $data) {
            $models[$key] = InventoryProduct::withoutPracticeScope()->create([
                'practice_id'       => self::PRACTICE_ID,
                'name'              => $data['name'],
                'sku'               => $data['sku'],
                'description'       => $data['name'] . ' for clinic retail and treatment support.',
                'category'          => $data['category'],
                'unit'              => $data['unit'],
                'selling_price'     => $data['selling_price'],
                'cost_price'        => round($data['selling_price'] * 0.5, 2),
                'stock_quantity'    => $data['stock_quantity'],
                'low_stock_threshold' => 8,
                'is_active'         => true,
            ]);

            InventoryMovement::withoutPracticeScope()->create([
                'practice_id'          => self::PRACTICE_ID,
                'inventory_product_id'  => $models[$key]->id,
                'type'                  => 'restock',
                'quantity'              => $data['restock'],
                'unit_price'            => $data['selling_price'] * 0.5,
                'reference'             => 'practice-2-demo-restock',
                'notes'                 => 'Opening stock for demo clinic',
                'created_by'            => $this->users['owner']->id,
            ]);
        }

        return $models;
    }

    private function seedPatients(): array
    {
        $patients = [];

        foreach ($this->patientBlueprints() as $key => $data) {
            $patients[$key] = Patient::withoutPracticeScope()->create(array_merge(
                [
                    'practice_id' => self::PRACTICE_ID,
                    'is_patient'  => true,
                ],
                $data['fields']
            ));
        }

        return $patients;
    }

    private function seedClinicalData(): void
    {
        foreach ($this->patientBlueprints() as $patientKey => $blueprint) {
            $patient = $this->patients[$patientKey];
            $appointments = [];

            foreach ($blueprint['appointments'] as $index => $spec) {
                $appointments[] = $this->createAppointment($patient, $patientKey, $index, $spec);
            }

            if (($blueprint['forms']['intake'] ?? 'missing') === 'complete') {
                $firstAppointment = $appointments[0] ?? null;
                $this->createMedicalHistory($patient, $firstAppointment);
            } elseif (($blueprint['forms']['intake'] ?? 'missing') === 'partial') {
                $this->createMedicalHistory($patient, null, false);
            }

            if (($blueprint['forms']['consent'] ?? 'missing') === 'complete') {
                $firstAppointment = $appointments[0] ?? null;
                $this->createConsentRecord($patient, $firstAppointment);
            } elseif (($blueprint['forms']['consent'] ?? 'missing') === 'partial') {
                $this->createConsentRecord($patient, null, false);
            }
        }
    }

    private function createAppointment(Patient $patient, string $patientKey, int $index, array $spec): Appointment
    {
        $type = $this->appointmentTypes[$spec['type']];
        $start = $this->appointmentStart($spec['days'], $spec['time']);
        $end = $start->copy()->addMinutes($type->duration_minutes);

        $appointment = Appointment::withoutPracticeScope()->create([
            'practice_id'         => self::PRACTICE_ID,
            'patient_id'          => $patient->id,
            'practitioner_id'     => $this->practitionerForType($spec['type'])->id,
            'appointment_type_id'  => $type->id,
            'status'              => $spec['status'],
            'start_datetime'      => $start,
            'end_datetime'        => $end,
            'needs_follow_up'     => (bool) ($spec['needs_follow_up'] ?? false),
            'notes'               => $spec['notes'] ?? $this->appointmentNote($patient, $spec['type'], $spec['status'], $spec['days']),
        ]);

        if (in_array($spec['status'], ['completed', 'closed', 'in_progress'], true)) {
            $this->createEncounter($appointment, $spec, $end);
        }

        $this->createCheckoutIfNeeded($appointment, $spec, $type, $patientKey, $index);

        return $appointment;
    }

    private function createEncounter(Appointment $appointment, array $spec, Carbon $end): void
    {
        $type = $this->appointmentTypes[$spec['type']];
        $isAcupuncture = Str::startsWith($spec['type'], 'acu_');
        $isComplete = in_array($spec['status'], ['completed', 'closed'], true);

        $encounter = Encounter::withoutPracticeScope()->create([
            'practice_id'     => self::PRACTICE_ID,
            'patient_id'      => $appointment->patient_id,
            'appointment_id'  => $appointment->id,
            'practitioner_id' => $appointment->practitioner_id,
            'status'          => $isComplete ? 'complete' : 'draft',
            'visit_date'      => $appointment->start_datetime->copy()->toDateString(),
            'discipline'      => $isAcupuncture ? 'acupuncture' : 'massage',
            'chief_complaint' => $spec['chief_complaint'] ?? $appointment->notes,
            'subjective'      => $spec['subjective'] ?? $this->encounterSubjective($appointment, $spec),
            'objective'       => $spec['objective'] ?? $this->encounterObjective($appointment, $spec),
            'assessment'      => $spec['assessment'] ?? $this->encounterAssessment($appointment, $spec),
            'plan'            => $spec['plan'] ?? $this->encounterPlan($appointment, $spec),
            'visit_notes'     => $spec['visit_notes'] ?? $this->encounterVisitNotes($appointment, $spec),
            'completed_on'    => $isComplete ? $end : null,
        ]);

        if ($isAcupuncture && $isComplete) {
            AcupunctureEncounter::withoutPracticeScope()->create([
                'encounter_id'       => $encounter->id,
                'tcm_diagnosis'      => $spec['tcm_diagnosis'] ?? 'Qi stagnation with channel imbalance',
                'tongue_body'        => $spec['tongue_body'] ?? 'Pale with mild scalloping',
                'tongue_coating'     => $spec['tongue_coating'] ?? 'Thin white coat',
                'pulse_quality'      => $spec['pulse_quality'] ?? 'Wiry and slightly deficient',
                'zang_fu_diagnosis'  => $spec['zang_fu_diagnosis'] ?? 'Liver Qi stagnation with underlying deficiency',
                'five_elements'      => $spec['five_elements'] ?? ['Wood'],
                'csor_color'         => $spec['csor_color'] ?? 'Green',
                'csor_sound'         => $spec['csor_sound'] ?? 'Shouting',
                'csor_odor'          => $spec['csor_odor'] ?? 'Rancid',
                'csor_emotion'       => $spec['csor_emotion'] ?? 'Frustration',
                'points_used'        => $spec['points_used'] ?? 'LI4, LV3, GB20, ST36, SP6',
                'meridians'          => $spec['meridians'] ?? 'Liver, Gallbladder, Stomach, Spleen',
                'treatment_protocol' => $spec['treatment_protocol'] ?? 'Move Qi, calm the nervous system, reduce pain, and support recovery.',
                'needle_count'       => $spec['needle_count'] ?? 12,
                'session_notes'      => $spec['session_notes'] ?? 'Good tolerance, mild soreness expected.',
            ]);
        }
    }

    private function createCheckoutIfNeeded(Appointment $appointment, array $spec, AppointmentType $type, string $patientKey, int $index): void
    {
        $status = $spec['status'];
        $billing = $spec['billing'] ?? match ($status) {
            'completed', 'closed' => 'paid',
            'in_progress'         => 'open',
            'no_show'             => 'payment_due',
            'cancelled'           => 'voided',
            default               => null,
        };

        if (! $billing) {
            return;
        }

        $session = CheckoutSession::withoutPracticeScope()->create([
            'practice_id'   => self::PRACTICE_ID,
            'appointment_id'=> $appointment->id,
            'patient_id'    => $appointment->patient_id,
            'practitioner_id'=> $appointment->practitioner_id,
            'state'         => 'open',
            'charge_label'  => $spec['charge_label'] ?? 'Treatment Charges',
            'amount_total'  => 0,
            'amount_paid'   => 0,
            'tender_type'   => null,
            'started_on'    => $appointment->end_datetime,
            'paid_on'       => null,
            'payment_note'  => $spec['payment_note'] ?? null,
            'notes'         => $spec['checkout_notes'] ?? null,
        ]);

        $sequence = 1;

        $baseFee = $this->serviceFees[$this->appointmentFeeKeyForType($type->name)];
        CheckoutLine::withoutPracticeScope()->create([
            'checkout_session_id' => $session->id,
            'practice_id'         => self::PRACTICE_ID,
            'sequence'            => $sequence++,
            'description'         => $type->name,
            'amount'              => $baseFee->default_price,
            'inventory_product_id' => null,
            'quantity'            => null,
        ]);

        if (($spec['billing'] ?? null) === 'discounted') {
            CheckoutLine::withoutPracticeScope()->create([
                'checkout_session_id' => $session->id,
                'practice_id'         => self::PRACTICE_ID,
                'sequence'            => $sequence++,
                'description'         => 'Courtesy discount',
                'amount'              => -20.00,
                'inventory_product_id' => null,
                'quantity'            => null,
            ]);
        }

        if (($spec['billing'] ?? null) === 'package_credit') {
            CheckoutLine::withoutPracticeScope()->create([
                'checkout_session_id' => $session->id,
                'practice_id'         => self::PRACTICE_ID,
                'sequence'            => $sequence++,
                'description'         => 'Package credit applied',
                'amount'              => -75.00,
                'inventory_product_id' => null,
                'quantity'            => null,
            ]);
        }

        foreach (($spec['products'] ?? []) as $productKey => $quantity) {
            $product = $this->inventoryProducts[$productKey];
            CheckoutLine::withoutPracticeScope()->create([
                'checkout_session_id'  => $session->id,
                'practice_id'          => self::PRACTICE_ID,
                'sequence'             => $sequence++,
                'description'          => $product->name . ' x ' . $quantity,
                'amount'               => $product->selling_price * $quantity,
                'inventory_product_id' => $product->id,
                'quantity'             => $quantity,
            ]);
        }

        if ($billing === 'open') {
            return;
        }

        if ($billing === 'paid') {
            $session->markPaid($spec['tender_type'] ?? 'card');
            return;
        }

        if ($billing === 'payment_due') {
            $session->markPaymentDue();
            return;
        }

        if ($billing === 'voided') {
            $session->voidSession();
        }
    }

    private function appointmentFeeKeyForType(string $typeName): string
    {
        return match ($typeName) {
            'Acupuncture Initial Consult' => 'acu_initial',
            'Acupuncture Follow-up' => 'acu_follow',
            'Focused Acupuncture Follow-up' => 'acu_short',
            'Massage Intake' => 'massage_intake',
            'Therapeutic Massage' => 'massage_60',
            'Deep Tissue Massage' => 'massage_90',
            'Sports Recovery Massage' => 'massage_sports',
            default => 'acu_follow',
        };
    }

    private function practitionerForType(string $typeKey): Practitioner
    {
        return Str::startsWith($typeKey, 'acu_')
            ? $this->practitioners['acu']
            : $this->practitioners['massage'];
    }

    private function appointmentStart(int $daysOffset, string $time): Carbon
    {
        return Carbon::now(self::PRACTICE_TIMEZONE)
            ->startOfDay()
            ->addDays($daysOffset)
            ->setTimeFromTimeString($time)
            ->setTimezone('UTC');
    }

    private function appointmentNote(Patient $patient, string $typeKey, string $status, int $daysOffset): string
    {
        $statusLabel = match ($status) {
            'completed' => 'completed',
            'closed' => 'closed after checkout',
            'in_progress' => 'checked in and chart in progress',
            'scheduled' => $daysOffset < 0 ? 'historical visit' : 'scheduled follow-up',
            'cancelled' => 'cancelled',
            'no_show' => 'no-show',
            default => $status,
        };

        return sprintf(
            '%s for %s. %s.',
            Str::headline(str_replace('_', ' ', $typeKey)),
            $patient->full_name,
            Str::of($statusLabel)->replace('_', ' ')->ucfirst()
        );
    }

    private function encounterSubjective(Appointment $appointment, array $spec): string
    {
        return match (true) {
            Str::startsWith($spec['type'], 'acu_') => 'Reports gradual improvement with residual tension and stress-related flare-ups.',
            Str::startsWith($spec['type'], 'massage_') => 'Reports reduced tightness and better range of motion after prior sessions.',
            default => 'Follow-up note entered for demo clinic charting.',
        };
    }

    private function encounterObjective(Appointment $appointment, array $spec): string
    {
        return match (true) {
            Str::startsWith($spec['type'], 'acu_') => 'Tone improved since last visit. Muscular tension present at the primary complaint area.',
            Str::startsWith($spec['type'], 'massage_') => 'Soft tissue restrictions remain but are responding well to treatment.',
            default => 'Objective assessment completed for the visit.',
        };
    }

    private function encounterAssessment(Appointment $appointment, array $spec): string
    {
        return match (true) {
            Str::startsWith($spec['type'], 'acu_') => 'Pattern consistent with Qi stagnation and local channel tension.',
            Str::startsWith($spec['type'], 'massage_') => 'Muscular tension with postural strain and recovery needs.',
            default => 'Routine maintenance visit.',
        };
    }

    private function encounterPlan(Appointment $appointment, array $spec): string
    {
        return match (true) {
            Str::startsWith($spec['type'], 'acu_') => 'Continue weekly or biweekly treatment with home care and hydration.',
            Str::startsWith($spec['type'], 'massage_') => 'Continue bodywork series, posture breaks, and home stretching.',
            default => 'Monitor progress and schedule the next session as needed.',
        };
    }

    private function encounterVisitNotes(Appointment $appointment, array $spec): string
    {
        return sprintf(
            '%s visit in the Harbor Integrative Clinic demo schedule.',
            Str::headline(str_replace('_', ' ', $spec['type']))
        );
    }

    private function createMedicalHistory(Patient $patient, ?Appointment $appointment, bool $complete = true): void
    {
        MedicalHistory::withoutPracticeScope()->create([
            'practice_id'      => self::PRACTICE_ID,
            'patient_id'       => $patient->id,
            'appointment_id'   => $appointment?->id,
            'status'           => $complete ? 'complete' : 'missing',
            'submitted_on'     => $complete && $appointment ? $appointment->start_datetime->copy()->subDays(2) : null,
            'discipline'       => $appointment ? (Str::startsWith($appointment->appointmentType->name, 'Acupuncture') ? 'acupuncture' : 'massage') : null,
            'reason_for_visit' => $complete ? 'Demo intake completed for treatment planning.' : null,
            'current_concerns' => $complete ? 'Symptoms are manageable but still recurring between visits.' : null,
            'relevant_history' => $complete ? 'No major red flags reported. Demo-safe medical history completed.' : null,
            'chief_complaint'  => $complete ? 'Clinic demo intake summary.' : null,
            'onset_duration'   => $complete ? '6 months' : null,
            'onset_type'       => $complete ? 'gradual' : null,
            'aggravating_factors' => $complete ? 'Work stress, prolonged sitting, and sleep disruption.' : null,
            'relieving_factors'   => $complete ? 'Rest, movement breaks, and treatment.' : null,
            'pain_scale'          => $complete ? 6 : null,
            'exercise_frequency'  => $complete ? '1-2x_week' : null,
            'sleep_quality'       => $complete ? 'fair' : null,
            'sleep_hours'         => $complete ? 6 : null,
            'stress_level'        => $complete ? 'moderate' : null,
            'diet_description'    => $complete ? 'Balanced diet with busy workdays.' : null,
            'had_previous_treatment' => $complete ? true : false,
            'previous_treatments_tried' => $complete ? ['Massage', 'Stretching', 'Rest'] : [],
            'treatment_goals'     => $complete ? 'Reduce flare-ups and support regular movement.' : null,
            'success_indicators'  => $complete ? 'Pain-free workdays and better sleep.' : null,
            'discipline_responses'=> $complete && $appointment ? [
                Str::startsWith($appointment->appointmentType->name, 'Acupuncture')
                    ? 'tcm' : 'massage' => Str::startsWith($appointment->appointmentType->name, 'Acupuncture')
                        ? ['tongue' => 'Pale', 'pulse' => 'Wiry', 'pattern' => 'Qi stagnation']
                        : ['pressure' => 'medium', 'focus' => 'neck and shoulders'],
            ] : [],
            'consent_given'       => $complete,
            'consent_signed_at'   => $complete && $appointment ? $appointment->start_datetime->copy()->subHours(6) : null,
            'consent_signed_by'   => $complete ? $patient->full_name : null,
            'consent_ip_address'  => $complete ? '127.0.0.1' : null,
            'summary_text'        => $complete ? 'Demo intake completed and ready for treatment planning.' : null,
            'notes'               => $complete ? 'Demo clinic record.' : null,
        ]);
    }

    private function createConsentRecord(Patient $patient, ?Appointment $appointment, bool $complete = true): void
    {
        ConsentRecord::withoutPracticeScope()->create([
            'practice_id'      => self::PRACTICE_ID,
            'patient_id'       => $patient->id,
            'appointment_id'   => $appointment?->id,
            'status'           => $complete ? 'complete' : 'missing',
            'signed_on'        => $complete && $appointment ? $appointment->start_datetime->copy()->subHours(5) : null,
            'consent_given_by' => $complete ? $patient->full_name : null,
            'consent_summary'  => $complete
                ? 'Patient reviewed the treatment consent and agreed to proceed with care.'
                : null,
            'notes'            => $complete ? 'Signed digitally for demo purposes.' : null,
            'signed_at_ip'     => $complete ? '127.0.0.1' : null,
            'signed_at_user_agent' => $complete ? 'Demo Seeder' : null,
        ]);
    }

    private function patientBlueprints(): array
    {
        return [
            'olivia' => [
                'fields' => [
                    'first_name' => 'Olivia',
                    'last_name' => 'Bennett',
                    'email' => 'olivia.bennett@example.com',
                    'phone' => '(213) 555-0101',
                    'dob' => '1986-04-12',
                    'gender' => 'Female',
                    'address_line_1' => '1748 Venice Blvd',
                    'city' => 'Los Angeles',
                    'state' => 'CA',
                    'postal_code' => '90006',
                    'occupation' => 'Product Designer',
                    'emergency_contact_name' => 'Noah Bennett',
                    'emergency_contact_phone' => '(213) 555-0102',
                    'emergency_contact_relationship' => 'Spouse',
                    'notes' => 'Regular acupuncture patient for work-stress and neck tension.',
                ],
                'forms' => ['intake' => 'complete', 'consent' => 'complete'],
                'appointments' => [
                    ['days' => -57, 'time' => '09:00', 'type' => 'acu_initial', 'status' => 'completed', 'needs_follow_up' => true, 'products' => ['moxa' => 1]],
                    ['days' => -42, 'time' => '09:00', 'type' => 'acu_follow', 'status' => 'completed'],
                    ['days' => -28, 'time' => '09:00', 'type' => 'acu_follow', 'status' => 'closed'],
                    ['days' => -7, 'time' => '09:30', 'type' => 'acu_follow', 'status' => 'scheduled'],
                ],
            ],
            'marcus' => [
                'fields' => [
                    'first_name' => 'Marcus',
                    'last_name' => 'Lee',
                    'email' => 'marcus.lee@example.com',
                    'phone' => '(323) 555-0103',
                    'dob' => '1979-11-08',
                    'gender' => 'Male',
                    'address_line_1' => '5085 Fountain Ave',
                    'city' => 'Los Angeles',
                    'state' => 'CA',
                    'postal_code' => '90029',
                    'occupation' => 'Software Engineer',
                    'emergency_contact_name' => 'Irene Lee',
                    'emergency_contact_phone' => '(323) 555-0104',
                    'emergency_contact_relationship' => 'Partner',
                    'notes' => 'Massage patient with recurring desk-related shoulder tightness.',
                ],
                'forms' => ['intake' => 'complete', 'consent' => 'complete'],
                'appointments' => [
                    ['days' => -54, 'time' => '14:00', 'type' => 'massage_intake', 'status' => 'completed', 'products' => ['oil' => 1]],
                    ['days' => -39, 'time' => '14:30', 'type' => 'massage_60', 'status' => 'completed', 'billing' => 'discounted'],
                    ['days' => -12, 'time' => '14:00', 'type' => 'massage_60', 'status' => 'closed'],
                ],
            ],
            'nina' => [
                'fields' => [
                    'first_name' => 'Nina',
                    'last_name' => 'Patel',
                    'email' => 'nina.patel@example.com',
                    'phone' => '(310) 555-0105',
                    'dob' => '1990-07-21',
                    'gender' => 'Female',
                    'address_line_1' => '2110 3rd St',
                    'city' => 'Santa Monica',
                    'state' => 'CA',
                    'postal_code' => '90405',
                    'occupation' => 'Teacher',
                    'emergency_contact_name' => 'Rina Patel',
                    'emergency_contact_phone' => '(310) 555-0106',
                    'emergency_contact_relationship' => 'Sister',
                    'notes' => 'Acupuncture patient for migraines and sleep support.',
                ],
                'forms' => ['intake' => 'complete', 'consent' => 'complete'],
                'appointments' => [
                    ['days' => -52, 'time' => '08:30', 'type' => 'acu_initial', 'status' => 'completed', 'needs_follow_up' => true],
                    ['days' => -37, 'time' => '08:30', 'type' => 'acu_follow', 'status' => 'completed'],
                    ['days' => -20, 'time' => '08:30', 'type' => 'acu_follow', 'status' => 'closed'],
                    ['days' => 3, 'time' => '08:30', 'type' => 'acu_follow', 'status' => 'scheduled'],
                ],
            ],
            'daniel' => [
                'fields' => [
                    'first_name' => 'Daniel',
                    'last_name' => 'Foster',
                    'email' => 'daniel.foster@example.com',
                    'phone' => '(818) 555-0107',
                    'dob' => '1983-02-15',
                    'gender' => 'Male',
                    'address_line_1' => '9100 W Sunset Blvd',
                    'city' => 'West Hollywood',
                    'state' => 'CA',
                    'postal_code' => '90069',
                    'occupation' => 'Architect',
                    'emergency_contact_name' => 'Paula Foster',
                    'emergency_contact_phone' => '(818) 555-0108',
                    'emergency_contact_relationship' => 'Spouse',
                    'notes' => 'Mixed-modality patient: acupuncture for low back pain, massage for recovery.',
                ],
                'forms' => ['intake' => 'complete', 'consent' => 'complete'],
                'appointments' => [
                    ['days' => -50, 'time' => '10:00', 'type' => 'massage_intake', 'status' => 'completed'],
                    ['days' => -34, 'time' => '10:00', 'type' => 'acu_follow', 'status' => 'completed'],
                    ['days' => -18, 'time' => '10:00', 'type' => 'massage_60', 'status' => 'closed'],
                ],
            ],
            'priya' => [
                'fields' => [
                    'first_name' => 'Priya',
                    'last_name' => 'Shah',
                    'email' => 'priya.shah@example.com',
                    'phone' => '(424) 555-0109',
                    'dob' => '1992-09-03',
                    'gender' => 'Female',
                    'address_line_1' => '6400 Wilshire Blvd',
                    'city' => 'Los Angeles',
                    'state' => 'CA',
                    'postal_code' => '90048',
                    'occupation' => 'Marketing Manager',
                    'emergency_contact_name' => 'Anil Shah',
                    'emergency_contact_phone' => '(424) 555-0110',
                    'emergency_contact_relationship' => 'Parent',
                    'notes' => 'Regular acupuncture visits for stress and sleep regulation.',
                ],
                'forms' => ['intake' => 'complete', 'consent' => 'complete'],
                'appointments' => [
                    ['days' => -49, 'time' => '11:30', 'type' => 'acu_initial', 'status' => 'completed', 'needs_follow_up' => true],
                    ['days' => -25, 'time' => '11:30', 'type' => 'acu_follow', 'status' => 'completed', 'billing' => 'payment_due'],
                    ['days' => -9, 'time' => '11:30', 'type' => 'acu_follow', 'status' => 'scheduled'],
                ],
            ],
            'ethan' => [
                'fields' => [
                    'first_name' => 'Ethan',
                    'last_name' => 'Brooks',
                    'email' => 'ethan.brooks@example.com',
                    'phone' => '(213) 555-0111',
                    'dob' => '1989-12-17',
                    'gender' => 'Male',
                    'address_line_1' => '400 Main St',
                    'city' => 'El Segundo',
                    'state' => 'CA',
                    'postal_code' => '90245',
                    'occupation' => 'Cycling Coach',
                    'emergency_contact_name' => 'Megan Brooks',
                    'emergency_contact_phone' => '(213) 555-0112',
                    'emergency_contact_relationship' => 'Spouse',
                    'notes' => 'Massage patient with sports recovery and calf tightness.',
                ],
                'forms' => ['intake' => 'complete', 'consent' => 'complete'],
                'appointments' => [
                    ['days' => -46, 'time' => '16:00', 'type' => 'massage_intake', 'status' => 'completed'],
                    ['days' => -30, 'time' => '16:00', 'type' => 'massage_sports', 'status' => 'completed', 'billing' => 'package_credit', 'credit' => 75, 'products' => ['arnica' => 1]],
                    ['days' => -15, 'time' => '16:00', 'type' => 'massage_sports', 'status' => 'closed'],
                ],
            ],
            'sarah' => [
                'fields' => [
                    'first_name' => 'Sarah',
                    'last_name' => 'Kim',
                    'email' => 'sarah.kim@example.com',
                    'phone' => '(310) 555-0113',
                    'dob' => '1984-05-28',
                    'gender' => 'Female',
                    'address_line_1' => '1221 Ocean Ave',
                    'city' => 'Santa Monica',
                    'state' => 'CA',
                    'postal_code' => '90401',
                    'occupation' => 'Real Estate Agent',
                    'emergency_contact_name' => 'David Kim',
                    'emergency_contact_phone' => '(310) 555-0114',
                    'emergency_contact_relationship' => 'Brother',
                    'notes' => 'Acupuncture for sleep and jaw tension; current same-day flare-up demo.',
                ],
                'forms' => ['intake' => 'complete', 'consent' => 'complete'],
                'appointments' => [
                    ['days' => -40, 'time' => '13:00', 'type' => 'acu_initial', 'status' => 'completed', 'needs_follow_up' => true],
                    ['days' => -22, 'time' => '13:00', 'type' => 'acu_follow', 'status' => 'completed'],
                    ['days' => 0, 'time' => '13:00', 'type' => 'acu_short', 'status' => 'in_progress', 'billing' => 'open'],
                    ['days' => 14, 'time' => '13:00', 'type' => 'acu_follow', 'status' => 'scheduled'],
                ],
            ],
            'jordan' => [
                'fields' => [
                    'first_name' => 'Jordan',
                    'last_name' => 'Reyes',
                    'email' => 'jordan.reyes@example.com',
                    'phone' => '(323) 555-0115',
                    'dob' => '1991-01-11',
                    'gender' => 'Non-binary',
                    'address_line_1' => '6210 Melrose Ave',
                    'city' => 'Los Angeles',
                    'state' => 'CA',
                    'postal_code' => '90038',
                    'occupation' => 'Graphic Artist',
                    'emergency_contact_name' => 'Alex Reyes',
                    'emergency_contact_phone' => '(323) 555-0116',
                    'emergency_contact_relationship' => 'Sibling',
                    'notes' => 'Massage patient with a missed appointment and a same-week cancellation.',
                ],
                'forms' => ['intake' => 'complete', 'consent' => 'complete'],
                'appointments' => [
                    ['days' => -38, 'time' => '09:30', 'type' => 'massage_intake', 'status' => 'completed'],
                    ['days' => -17, 'time' => '09:30', 'type' => 'massage_60', 'status' => 'no_show', 'billing' => 'payment_due'],
                    ['days' => -5, 'time' => '09:30', 'type' => 'massage_60', 'status' => 'cancelled', 'billing' => 'voided'],
                    ['days' => 20, 'time' => '09:30', 'type' => 'massage_60', 'status' => 'scheduled'],
                ],
            ],
            'tessa' => [
                'fields' => [
                    'first_name' => 'Tessa',
                    'last_name' => 'Nguyen',
                    'email' => 'tessa.nguyen@example.com',
                    'phone' => '(213) 555-0117',
                    'dob' => '1998-06-09',
                    'gender' => 'Female',
                    'address_line_1' => '7510 La Brea Ave',
                    'city' => 'Los Angeles',
                    'state' => 'CA',
                    'postal_code' => '90046',
                    'occupation' => 'Graduate Student',
                    'emergency_contact_name' => 'Linh Nguyen',
                    'emergency_contact_phone' => '(213) 555-0118',
                    'emergency_contact_relationship' => 'Parent',
                    'notes' => 'New acupuncture patient with first visit completed and follow-up on the schedule.',
                ],
                'forms' => ['intake' => 'missing', 'consent' => 'missing'],
                'appointments' => [
                    ['days' => -8, 'time' => '15:00', 'type' => 'acu_initial', 'status' => 'completed', 'needs_follow_up' => true],
                    ['days' => 7, 'time' => '15:00', 'type' => 'acu_follow', 'status' => 'scheduled'],
                ],
            ],
            'michael' => [
                'fields' => [
                    'first_name' => 'Michael',
                    'last_name' => 'Grant',
                    'email' => 'michael.grant@example.com',
                    'phone' => '(310) 555-0119',
                    'dob' => '1976-08-24',
                    'gender' => 'Male',
                    'address_line_1' => '2920 Sepulveda Blvd',
                    'city' => 'Manhattan Beach',
                    'state' => 'CA',
                    'postal_code' => '90266',
                    'occupation' => 'Operations Director',
                    'emergency_contact_name' => 'Jenna Grant',
                    'emergency_contact_phone' => '(310) 555-0120',
                    'emergency_contact_relationship' => 'Spouse',
                    'notes' => 'Massage patient with intake complete but no consent file yet.',
                ],
                'forms' => ['intake' => 'complete', 'consent' => 'partial'],
                'appointments' => [
                    ['days' => -33, 'time' => '17:00', 'type' => 'massage_intake', 'status' => 'completed'],
                    ['days' => -2, 'time' => '17:00', 'type' => 'massage_60', 'status' => 'closed', 'billing' => 'paid'],
                    ['days' => 21, 'time' => '17:00', 'type' => 'massage_60', 'status' => 'scheduled'],
                ],
            ],
            'lauren' => [
                'fields' => [
                    'first_name' => 'Lauren',
                    'last_name' => 'Brooks',
                    'email' => 'lauren.brooks@example.com',
                    'phone' => '(424) 555-0121',
                    'dob' => '1987-03-30',
                    'gender' => 'Female',
                    'address_line_1' => '1400 Santa Monica Blvd',
                    'city' => 'Santa Monica',
                    'state' => 'CA',
                    'postal_code' => '90404',
                    'occupation' => 'Nurse',
                    'emergency_contact_name' => 'Kevin Brooks',
                    'emergency_contact_phone' => '(424) 555-0122',
                    'emergency_contact_relationship' => 'Spouse',
                    'notes' => 'Massage client with consent complete but intake still pending.',
                ],
                'forms' => ['intake' => 'missing', 'consent' => 'complete'],
                'appointments' => [
                    ['days' => -31, 'time' => '12:00', 'type' => 'massage_intake', 'status' => 'completed'],
                    ['days' => -11, 'time' => '12:00', 'type' => 'massage_60', 'status' => 'completed', 'billing' => 'discounted'],
                    ['days' => 10, 'time' => '12:00', 'type' => 'massage_60', 'status' => 'scheduled'],
                ],
            ],
            'carlos' => [
                'fields' => [
                    'first_name' => 'Carlos',
                    'last_name' => 'Martinez',
                    'email' => 'carlos.martinez@example.com',
                    'phone' => '(213) 555-0123',
                    'dob' => '1982-10-16',
                    'gender' => 'Male',
                    'address_line_1' => '3200 S Central Ave',
                    'city' => 'Los Angeles',
                    'state' => 'CA',
                    'postal_code' => '90011',
                    'occupation' => 'Personal Trainer',
                    'emergency_contact_name' => 'Rosa Martinez',
                    'emergency_contact_phone' => '(213) 555-0124',
                    'emergency_contact_relationship' => 'Spouse',
                    'notes' => 'Sports-recovery patient who alternates between acupuncture and massage.',
                ],
                'forms' => ['intake' => 'complete', 'consent' => 'complete'],
                'appointments' => [
                    ['days' => -29, 'time' => '08:00', 'type' => 'acu_follow', 'status' => 'completed'],
                    ['days' => -13, 'time' => '08:00', 'type' => 'massage_sports', 'status' => 'completed', 'products' => ['heat_pack' => 1]],
                    ['days' => 5, 'time' => '08:00', 'type' => 'massage_sports', 'status' => 'scheduled'],
                    ['days' => 19, 'time' => '08:00', 'type' => 'acu_follow', 'status' => 'scheduled'],
                ],
            ],
            'emma' => [
                'fields' => [
                    'first_name' => 'Emma',
                    'last_name' => 'Wilson',
                    'email' => 'emma.wilson@example.com',
                    'phone' => '(310) 555-0125',
                    'dob' => '1994-04-05',
                    'gender' => 'Female',
                    'address_line_1' => '8080 Beverly Blvd',
                    'city' => 'Los Angeles',
                    'state' => 'CA',
                    'postal_code' => '90048',
                    'occupation' => 'Account Manager',
                    'emergency_contact_name' => 'Grace Wilson',
                    'emergency_contact_phone' => '(310) 555-0126',
                    'emergency_contact_relationship' => 'Mother',
                    'notes' => 'Acupuncture patient for headaches and screen fatigue.',
                ],
                'forms' => ['intake' => 'complete', 'consent' => 'complete'],
                'appointments' => [
                    ['days' => -26, 'time' => '10:30', 'type' => 'acu_initial', 'status' => 'completed'],
                    ['days' => -4, 'time' => '10:30', 'type' => 'acu_follow', 'status' => 'closed'],
                    ['days' => 9, 'time' => '10:30', 'type' => 'acu_follow', 'status' => 'scheduled'],
                ],
            ],
            'hannah' => [
                'fields' => [
                    'first_name' => 'Hannah',
                    'last_name' => 'Reed',
                    'email' => 'hannah.reed@example.com',
                    'phone' => '(818) 555-0127',
                    'dob' => '1978-01-19',
                    'gender' => 'Female',
                    'address_line_1' => '6100 Hollywood Blvd',
                    'city' => 'Los Angeles',
                    'state' => 'CA',
                    'postal_code' => '90028',
                    'occupation' => 'School Counselor',
                    'emergency_contact_name' => 'Mara Reed',
                    'emergency_contact_phone' => '(818) 555-0128',
                    'emergency_contact_relationship' => 'Daughter',
                    'notes' => 'Massage patient with a single completed visit and a scheduled return.',
                ],
                'forms' => ['intake' => 'partial', 'consent' => 'partial'],
                'appointments' => [
                    ['days' => -24, 'time' => '15:30', 'type' => 'massage_intake', 'status' => 'completed'],
                    ['days' => 2, 'time' => '15:30', 'type' => 'massage_60', 'status' => 'scheduled'],
                ],
            ],
            'victor' => [
                'fields' => [
                    'first_name' => 'Victor',
                    'last_name' => 'Chen',
                    'email' => 'victor.chen@example.com',
                    'phone' => '(213) 555-0129',
                    'dob' => '1990-11-02',
                    'gender' => 'Male',
                    'address_line_1' => '1717 Sawtelle Blvd',
                    'city' => 'Los Angeles',
                    'state' => 'CA',
                    'postal_code' => '90025',
                    'occupation' => 'Software Sales',
                    'emergency_contact_name' => 'Elaine Chen',
                    'emergency_contact_phone' => '(213) 555-0130',
                    'emergency_contact_relationship' => 'Sister',
                    'notes' => 'Acupuncture patient for posture and race-training recovery.',
                ],
                'forms' => ['intake' => 'partial', 'consent' => 'partial'],
                'appointments' => [
                    ['days' => -21, 'time' => '09:00', 'type' => 'acu_initial', 'status' => 'completed'],
                    ['days' => 8, 'time' => '09:00', 'type' => 'acu_follow', 'status' => 'scheduled'],
                ],
            ],
            'alyssa' => [
                'fields' => [
                    'first_name' => 'Alyssa',
                    'last_name' => 'Price',
                    'email' => 'alyssa.price@example.com',
                    'phone' => '(424) 555-0131',
                    'dob' => '1988-07-27',
                    'gender' => 'Female',
                    'address_line_1' => '9550 Culver Blvd',
                    'city' => 'Culver City',
                    'state' => 'CA',
                    'postal_code' => '90232',
                    'occupation' => 'Event Producer',
                    'emergency_contact_name' => 'Tanya Price',
                    'emergency_contact_phone' => '(424) 555-0132',
                    'emergency_contact_relationship' => 'Mother',
                    'notes' => 'Mixed-modality patient with a pending balance and a canceled follow-up.',
                ],
                'forms' => ['intake' => 'partial', 'consent' => 'partial'],
                'appointments' => [
                    ['days' => -19, 'time' => '14:30', 'type' => 'massage_intake', 'status' => 'completed', 'billing' => 'payment_due'],
                    ['days' => -6, 'time' => '14:30', 'type' => 'acu_follow', 'status' => 'cancelled', 'billing' => 'voided'],
                    ['days' => 16, 'time' => '14:30', 'type' => 'massage_60', 'status' => 'scheduled'],
                ],
            ],
        ];
    }
}
