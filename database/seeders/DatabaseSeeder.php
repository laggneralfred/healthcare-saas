<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\AcupunctureEncounter;
use App\Models\CheckoutLine;
use App\Models\CheckoutSession;
use App\Models\ConsentRecord;
use App\Models\Encounter;
use App\Models\MedicalHistory;
use App\Models\InventoryMovement;
use App\Models\InventoryProduct;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\ServiceFee;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
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
    private Practice $practice;
    private User $admin;
    private array $practitioners = [];
    private array $appointmentTypes = [];

    public function run(): void
    {
        try {
            // ── 1. Setup Practice & Admin ──────────────────────────────────────
            $this->setupPractice();

            // ── 2. Setup Practitioners ─────────────────────────────────────────
            $this->setupPractitioners();

            // ── 3. Setup Appointment Types ─────────────────────────────────────
            $this->setupAppointmentTypes();

            // ── 4. Named Story Patients ────────────────────────────────────────
            $this->seedJaneSmith();        // Acupuncture, 6 visits, pain progression
            $this->seedRobertJohnson();    // Physiotherapy, 8 visits, ACL rehab progression
            $this->seedMariaGarcia();      // New patient, acupuncture, intake pending
            $this->seedDavidChen();        // Massage, 3 visits, outstanding payment

            // ── 5. Supporting Cast (46 random patients) ────────────────────────
            $this->seedSupportingPatients();

            // ── 6. Billing States Demo ─────────────────────────────────────────
            // 4 appointments per practitioner showing Paid/Open/PaymentDue/Cancelled states
            $this->seedBillingStates();

            // ── 7. Inventory ───────────────────────────────────────────────────
            $this->seedInventory();

            // ── 8. Subscription Plans ──────────────────────────────────────────
            $this->seedSubscriptionPlans();

            // ── 9. Communication Templates ────────────────────────────────────
            (new DefaultMessageTemplatesSeeder())->seedForPractice($this->practice);

            // ── Report ─────────────────────────────────────────────────────────
            $this->reportCounts();

        } catch (\Throwable $e) {
            $this->command->error('DatabaseSeeder failed: ' . $e->getMessage());
            $this->command->error('  at ' . $e->getFile() . ':' . $e->getLine());
            throw $e;
        }
    }

    // ════════════════════════════════════════════════════════════════════════
    // Setup Methods
    // ════════════════════════════════════════════════════════════════════════

    private function setupPractice(): void
    {
        $this->practice = Practice::updateOrCreate(
            ['name' => 'Eureka Integrated Health'],
            [
                'slug'          => 'eureka-health',
                'is_active'     => true,
                'is_demo'       => true,
                'trial_ends_at' => now()->addYears(10),
            ]
        );

        $this->admin = User::where('email', 'admin@healthcare.test')->first()
            ?? User::factory()->create([
                'name'        => 'Alfred Laggner',
                'email'       => 'admin@healthcare.test',
                'password'    => Hash::make('password'),
                'practice_id' => $this->practice->id
            ]);
    }

    private function setupPractitioners(): void
    {
        $team = [
            'Acupuncture' => ['name' => 'Acu Anna', 'specialty' => 'Acupuncture'],
            'Chiro'       => ['name' => 'Dr. Bone', 'specialty' => 'Chiropractic'],
            'Massage'     => ['name' => 'Sarah Massage', 'specialty' => 'Massage Therapy'],
            'PT'          => ['name' => 'PT Paul', 'specialty' => 'Physical Therapy'],
        ];

        foreach ($team as $key => $data) {
            $user = User::factory()->create([
                'name'        => $data['name'],
                'email'       => strtolower(str_replace(' ', '.', $data['name'])) . '@healthcare.test',
                'practice_id' => $this->practice->id,
            ]);

            $this->practitioners[$key] = Practitioner::updateOrCreate(
                ['user_id' => $user->id, 'practice_id' => $this->practice->id],
                ['specialty' => $data['specialty'], 'is_active' => true]
            );
        }
    }

    private function setupAppointmentTypes(): void
    {
        $this->appointmentTypes = [
            'consultation' => AppointmentType::create([
                'practice_id'     => $this->practice->id,
                'name'            => 'Initial Consultation',
                'duration_minutes' => 60
            ]),
            'followup' => AppointmentType::create([
                'practice_id'      => $this->practice->id,
                'name'             => 'Follow-up Visit',
                'duration_minutes' => 30
            ]),
            'emergency' => AppointmentType::create([
                'practice_id'      => $this->practice->id,
                'name'             => 'Emergency Session',
                'duration_minutes' => 45
            ]),
        ];
    }

    // ════════════════════════════════════════════════════════════════════════
    // Named Story Patients
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Jane Smith — Acupuncture, 6 visits for chronic lower back pain, clear progression
     */
    private function seedJaneSmith(): void
    {
        $patient = Patient::create([
            'practice_id'                    => $this->practice->id,
            'first_name'                     => 'Jane',
            'last_name'                      => 'Smith',
            'name'                           => 'Jane Smith',
            'email'                          => 'jane.smith@example.com',
            'phone'                          => '(415) 555-0123',
            'dob'                            => '1978-03-15',
            'gender'                         => 'Female',
            'address_line_1'                 => '1234 Oak Street',
            'city'                           => 'San Francisco',
            'state'                          => 'CA',
            'postal_code'                    => '94102',
            'country'                        => 'USA',
            'emergency_contact_name'         => 'John Smith',
            'emergency_contact_phone'        => '(415) 555-0124',
            'emergency_contact_relationship' => 'Spouse',
            'is_patient'                     => true,
        ]);

        // Visit 1: 7 weeks ago — initial assessment
        $this->createAcupunctureVisit(
            patient: $patient,
            practitioner: $this->practitioners['Acupuncture'],
            daysAgo: 49,
            visitNumber: 1,
            soapNotes: [
                'chief_complaint' => 'Chronic lower back pain, 8/10',
                'subjective'      => 'New patient. Pain present 3 years, recently worse. Disrupting sleep nightly. NSAIDs only temporary relief.',
                'objective'       => 'Severe paraspinal tension L2–S1 bilaterally. Lumbar flexion 30°. Straight leg raise positive R at 45°.',
                'assessment'      => 'Lumbar Bi syndrome — Kidney Yin deficiency with Qi and Blood stagnation. First treatment.',
                'plan'            => 'Begin 12-week protocol. GB30, BL23, BL40, GV4, K3. Moxa on BL23. Lifestyle: gentle walking, avoid cold.',
            ],
            tcmDiagnosis: 'Kidney Yin Deficiency with Qi Stagnation',
            pointsUsed: 'GB30, BL23, BL40, GV4, K3',
            isFirstVisit: true,
            intakeData: [
                'discipline'      => 'acupuncture',
                'chief_complaint' => 'Chronic lower back pain',
                'pain_scale'      => 8,
                'onset_duration'  => '3 years',
                'onset_type'      => 'gradual',
            ],
        );

        // Visits 2-6: progression
        $visits = [
            [
                'daysAgo' => 42,
                'visitNum' => 2,
                'soapNotes' => [
                    'chief_complaint' => 'Lower back pain, 7/10',
                    'subjective'      => 'Reports slight improvement in sleep — waking once vs 3 times. Pain still constant but less sharp.',
                    'objective'       => 'Moderate paraspinal tension. Lumbar flexion improved to 40°. SLR positive R at 55°.',
                    'assessment'      => 'Responding to initial treatment. Continue Kidney tonification.',
                    'plan'            => 'Repeat points + add SP6, LV3. Encourage daily cat-cow stretch.',
                ],
            ],
            [
                'daysAgo' => 35,
                'visitNum' => 3,
                'soapNotes' => [
                    'chief_complaint' => 'Lower back pain, 5/10',
                    'subjective'      => 'Patient reports "best week in months." Managing stairs without gripping rail. Sleeping 6 hrs uninterrupted.',
                    'objective'       => 'Paraspinal tension reduced, primarily left side now. Lumbar flexion 50°. SLR negative bilaterally.',
                    'assessment'      => 'Good progress — Blood stagnation clearing. Kidney Yin still deficient.',
                    'plan'            => 'Reduce BL40. Add LU7 + K6 for Yin. Continue moxa. Increase walking to 20 min/day.',
                ],
            ],
            [
                'daysAgo' => 28,
                'visitNum' => 4,
                'soapNotes' => [
                    'chief_complaint' => 'Lower back pain, 4/10',
                    'subjective'      => 'Doing 20 min walks daily. One episode of sharp pain after prolonged sitting at work — resolved within 1 hr.',
                    'objective'       => 'Mild residual tension at L4–L5. ROM near full. Posture improved.',
                    'assessment'      => 'Continuing positive trajectory. Work ergonomics a contributing factor.',
                    'plan'            => 'Advise ergonomic assessment. Add GB34 for sinew. Taper to every 10 days.',
                ],
            ],
            [
                'daysAgo' => 21,
                'visitNum' => 5,
                'soapNotes' => [
                    'chief_complaint' => 'Lower back pain, 2/10',
                    'subjective'      => '"Feeling 80% normal." Returned to weekend gardening without issue. Occasional ache only.',
                    'objective'       => 'Minimal tension on palpation. Full lumbar ROM. SLR negative.',
                    'assessment'      => 'Near resolution. Maintenance phase indicated.',
                    'plan'            => 'Maintain current protocol. Book monthly maintenance from next visit. Discuss herbal support.',
                ],
            ],
            [
                'daysAgo' => 7,
                'visitNum' => 6,
                'soapNotes' => [
                    'chief_complaint' => 'Occasional low back ache, 1/10',
                    'subjective'      => 'Reports living normally. No sleep disruption. Full activity. Came in proactively.',
                    'objective'       => 'Essentially clear. Slight fatigue at right BL23.',
                    'assessment'      => 'Resolved Bi syndrome. Kidney Yin restored. Maintenance treatment.',
                    'plan'            => 'Monthly maintenance. Continue home exercise. Patient very satisfied.',
                ],
            ],
        ];

        foreach ($visits as $visit) {
            $this->createAcupunctureVisit(
                patient: $patient,
                practitioner: $this->practitioners['Acupuncture'],
                daysAgo: $visit['daysAgo'],
                visitNumber: $visit['visitNum'],
                soapNotes: $visit['soapNotes'],
                tcmDiagnosis: 'Kidney Yin Deficiency',
                pointsUsed: 'GB30, BL23, BL40, SP6, LV3, GV4, K3',
            );
        }

        // Upcoming appointment (next week)
        Appointment::create([
            'practice_id'        => $this->practice->id,
            'patient_id'         => $patient->id,
            'practitioner_id'    => $this->practitioners['Acupuncture']->id,
            'appointment_type_id' => $this->appointmentTypes['followup']->id,
            'status'             => 'scheduled',
            'start_datetime'     => now()->addWeeks(1)->setHour(9)->setMinute(0),
            'end_datetime'       => now()->addWeeks(1)->setHour(10)->setMinute(0),
            'notes'              => 'Monthly maintenance',
        ]);
    }

    /**
     * Robert Johnson — Physiotherapy, 8 visits, post-ACL surgery progression
     */
    private function seedRobertJohnson(): void
    {
        $patient = Patient::create([
            'practice_id'                    => $this->practice->id,
            'first_name'                     => 'Robert',
            'last_name'                      => 'Johnson',
            'name'                           => 'Robert Johnson',
            'email'                          => 'robert.johnson@example.com',
            'phone'                          => '(415) 555-0125',
            'dob'                            => '1985-07-22',
            'gender'                         => 'Male',
            'address_line_1'                 => '567 Pine Street',
            'city'                           => 'Oakland',
            'state'                          => 'CA',
            'postal_code'                    => '94607',
            'country'                        => 'USA',
            'emergency_contact_name'         => 'Sarah Johnson',
            'emergency_contact_phone'        => '(415) 555-0126',
            'emergency_contact_relationship' => 'Sister',
            'occupation'                     => 'Software Engineer',
            'is_patient'                     => true,
        ]);

        $visits = [
            ['daysAgo' => 56, 'visitNum' => 1, 'chief_complaint' => 'Right knee — 3 weeks post-ACL reconstruction. Pain 7/10.', 'subjective' => 'Significant swelling, very limited mobility. Crutch-dependent. Off work. Cleared by surgeon for physiotherapy.', 'objective' => 'ROM 20–45°. Effusion ++. Quad inhibition. VMO activation absent.', 'assessment' => 'Acute post-surgical phase. Priority: reduce swelling, restore quad activation.', 'plan' => 'Ice/elevation 3×/day. Quad sets, ankle pumps, SLR. No weight-bearing without crutches.'],
            ['daysAgo' => 49, 'visitNum' => 2, 'chief_complaint' => 'Right knee pain 5/10. Swelling reducing.', 'subjective' => 'Managed 5 min walking without crutches at home. Sleeping better. Encouraged by early progress.', 'objective' => 'ROM 0–75°. Effusion reducing. SLR 3×10 reps unaided.', 'assessment' => 'Ahead of schedule. Good early quad re-activation.', 'plan' => 'Progress to partial weight-bearing. Add mini-squats 0–30°. Cycle ergometer 10 min.'],
            ['daysAgo' => 42, 'visitNum' => 3, 'chief_complaint' => 'Right knee pain 4/10. Mild ache after activity.', 'subjective' => 'Full weight-bearing at home. Stairs 1-step method. Discharged from crutches this week.', 'objective' => 'ROM 0–95°. Effusion trace. VMO visible on quad set. Single leg stance 10 sec.', 'assessment' => 'Week 5 post-op milestones achieved. Focus shifts to strength.', 'plan' => 'Progressive leg press, step-ups. Pool walking if accessible. Proprioception work begins.'],
            ['daysAgo' => 35, 'visitNum' => 4, 'chief_complaint' => 'Right knee 3/10. Stiffness in morning.', 'subjective' => 'Back at desk work. Walking 1km without symptoms. Morning stiffness resolves within 15 min.', 'objective' => 'ROM 0–115°. No effusion. Single leg squat — mild trunk deviation. Hamstring strength 65% of left.', 'assessment' => 'Good functional progress. Hamstring deficit remains — critical for graft protection.', 'plan' => 'Emphasise hamstring curls, Nordic curls. Add lateral band walks, clamshells.'],
            ['daysAgo' => 28, 'visitNum' => 5, 'chief_complaint' => 'Right knee 2/10. Occasional ache on stairs.', 'subjective' => 'Cycling 30 min outdoors without pain. Returned to light gym work (upper body). Eager to progress.', 'objective' => 'ROM 0–130°. Strength 80% L vs R (hamstring), 85% quad. Jogging assessment: mild antalgic gait pattern.', 'assessment' => 'Strong recovery. Jogging cleared at reduced pace. Watch gait pattern.', 'plan' => 'Introduce jog-walk intervals. Agility ladder basics. Continue strength.'],
            ['daysAgo' => 21, 'visitNum' => 6, 'chief_complaint' => 'Right knee 1/10. Mild awareness only during jog.', 'subjective' => 'Jogging 3km continuously without stopping. No significant pain after. Asks about return to squash.', 'objective' => 'ROM full. Strength 90% symmetry. Single leg hop 85% limb symmetry index.', 'assessment' => 'Approaching return-to-sport milestones. Cutting/pivoting not yet tested.', 'plan' => 'Introduce lateral cutting drills. Squash cleared for hitting only (no games yet).'],
            ['daysAgo' => 14, 'visitNum' => 7, 'chief_complaint' => 'Right knee trace discomfort. Confidence low for pivoting.', 'subjective' => 'Completed 2 squash hitting sessions. No swelling. Mental hesitation with pivoting.', 'objective' => 'Full ROM. Strength 95% symmetry. Hop tests: 88–91% limb symmetry. Pivot assessment reasonable.', 'assessment' => 'Physically ready. Some psychological hesitancy — normal post-ACL.', 'plan' => 'Address confidence with progressive pivot drills. Clear for restricted singles play.'],
            ['daysAgo' => 7, 'visitNum' => 8, 'chief_complaint' => 'Right knee — no pain.', 'subjective' => 'Played full squash match (3 games). Felt completely normal. No swelling overnight. Extremely pleased.', 'objective' => 'Full ROM. Strength 98% symmetry. Hop tests 93–96% limb symmetry.', 'assessment' => 'Discharged — full return to sport achieved.', 'plan' => 'Discharge + home programme. Return if symptoms recur. Annual check-in recommended.'],
        ];

        foreach ($visits as $index => $visit) {
            $apt = Appointment::create([
                'practice_id'        => $this->practice->id,
                'patient_id'         => $patient->id,
                'practitioner_id'    => $this->practitioners['PT']->id,
                'appointment_type_id' => $this->appointmentTypes['followup']->id,
                'status'             => Closed::class,
                'start_datetime'     => now()->subDays($visit['daysAgo'])->setHour(10)->setMinute(0),
                'end_datetime'       => now()->subDays($visit['daysAgo'])->setHour(10)->minute(30),
            ]);

            $encounter = Encounter::create([
                'practice_id'     => $this->practice->id,
                'patient_id'      => $patient->id,
                'appointment_id'  => $apt->id,
                'practitioner_id' => $this->practitioners['PT']->id,
                'status'          => 'complete',
                'visit_date'      => now()->subDays($visit['daysAgo'])->format('Y-m-d'),
                'discipline'      => 'physiotherapy',
                'chief_complaint' => $visit['chief_complaint'],
                'subjective'      => $visit['subjective'],
                'objective'       => $visit['objective'],
                'assessment'      => $visit['assessment'],
                'plan'            => $visit['plan'],
                'completed_on'    => now()->subDays($visit['daysAgo']),
            ]);

            // First visit: create intake + consent
            if ($index === 0) {
                MedicalHistory::create([
                    'practice_id'       => $this->practice->id,
                    'patient_id'        => $patient->id,
                    'appointment_id'    => $apt->id,
                    'status'            => 'complete',
                    'submitted_on'      => now()->subDays($visit['daysAgo']),
                    'discipline'        => 'physiotherapy',
                    'chief_complaint'   => 'Right knee post-ACL surgery',
                    'pain_scale'        => 7,
                    'onset_duration'    => '3 weeks',
                    'onset_type'        => 'sudden',
                    'consent_given'     => true,
                    'consent_signed_at' => now()->subDays($visit['daysAgo']),
                    'consent_signed_by' => 'Robert Johnson',
                ]);

                ConsentRecord::create([
                    'practice_id'        => $this->practice->id,
                    'patient_id'         => $patient->id,
                    'appointment_id'     => $apt->id,
                    'status'             => 'complete',
                    'signed_on'          => now()->subDays($visit['daysAgo']),
                    'consent_given_by'   => 'Robert Johnson',
                ]);
            }

            // All visits: checkout session (paid)
            CheckoutSession::create([
                'practice_id'     => $this->practice->id,
                'appointment_id'  => $apt->id,
                'patient_id'      => $patient->id,
                'practitioner_id' => $this->practitioners['PT']->id,
                'state'           => Paid::$name,
                'charge_label'    => 'Physiotherapy Session',
                'amount_total'    => 120,
                'amount_paid'     => 120,
                'tender_type'     => 'card',
                'paid_on'         => now()->subDays($visit['daysAgo']),
            ]);
        }
    }

    /**
     * Maria Garcia — New patient, acupuncture, intake pending
     */
    private function seedMariaGarcia(): void
    {
        $patient = Patient::create([
            'practice_id'                    => $this->practice->id,
            'first_name'                     => 'Maria',
            'last_name'                      => 'Garcia',
            'name'                           => 'Maria Garcia',
            'email'                          => 'maria.garcia@example.com',
            'phone'                          => '(415) 555-0127',
            'dob'                            => '1992-11-08',
            'gender'                         => 'Female',
            'address_line_1'                 => '789 Mission Street',
            'city'                           => 'San Francisco',
            'state'                          => 'CA',
            'postal_code'                    => '94103',
            'country'                        => 'USA',
            'emergency_contact_name'         => 'Carlos Garcia',
            'emergency_contact_phone'        => '(415) 555-0128',
            'emergency_contact_relationship' => 'Brother',
            'is_patient'                     => true,
        ]);

        // Upcoming appointment (tomorrow)
        $apt = Appointment::create([
            'practice_id'        => $this->practice->id,
            'patient_id'         => $patient->id,
            'practitioner_id'    => $this->practitioners['Acupuncture']->id,
            'appointment_type_id' => $this->appointmentTypes['consultation']->id,
            'status'             => 'scheduled',
            'start_datetime'     => now()->addDay()->setHour(9)->setMinute(0),
            'end_datetime'       => now()->addDay()->setHour(10)->setMinute(0),
            'notes'              => 'New patient intake',
        ]);

        // Pending intake form (not submitted)
        MedicalHistory::create([
            'practice_id'    => $this->practice->id,
            'patient_id'     => $patient->id,
            'appointment_id' => $apt->id,
            'status'         => 'missing',
        ]);

        // Pending consent
        ConsentRecord::create([
            'practice_id'    => $this->practice->id,
            'patient_id'     => $patient->id,
            'appointment_id' => $apt->id,
            'status'         => 'missing',
        ]);
    }

    /**
     * David Chen — Massage therapy, 3 visits, one with outstanding payment
     */
    private function seedDavidChen(): void
    {
        $patient = Patient::create([
            'practice_id'                    => $this->practice->id,
            'first_name'                     => 'David',
            'last_name'                      => 'Chen',
            'name'                           => 'David Chen',
            'email'                          => 'david.chen@example.com',
            'phone'                          => '(415) 555-0129',
            'dob'                            => '1980-05-10',
            'gender'                         => 'Male',
            'address_line_1'                 => '321 Market Street',
            'city'                           => 'San Francisco',
            'state'                          => 'CA',
            'postal_code'                    => '94102',
            'country'                        => 'USA',
            'emergency_contact_name'         => 'Linda Chen',
            'emergency_contact_phone'        => '(415) 555-0130',
            'emergency_contact_relationship' => 'Spouse',
            'occupation'                     => 'Project Manager',
            'is_patient'                     => true,
        ]);

        $visits = [
            [
                'daysAgo' => 21,
                'soapNotes' => [
                    'chief_complaint' => 'Tension headaches daily, neck/shoulder tightness 7/10',
                    'subjective'      => 'Headaches started 6 weeks ago after major work project. Paracetamol not effective. Difficulty sleeping. Sits at desk 10 hrs/day.',
                    'objective'       => 'Severe upper trapezius and levator scapulae tension bilaterally. Cervical ROM restricted 50%. Trigger points: upper traps, suboccipitals.',
                    'assessment'      => 'Classic occupational tension pattern. Postural and stress-related. Will require regular treatment + lifestyle change.',
                    'plan'            => 'Deep tissue to upper traps, neck, suboccipitals. Cervical mobility exercises. Heat at home. Advised to take screen breaks.',
                ],
                'paid' => true,
            ],
            [
                'daysAgo' => 14,
                'soapNotes' => [
                    'chief_complaint' => 'Tension headaches 2–3×/week, 5/10',
                    'subjective'      => 'Improved after last treatment — 3 pain-free days. Headaches less severe but returning mid-week. Still working long hours.',
                    'objective'       => 'Moderate upper trap tension. Cervical ROM improved to 70%. Trigger points reduced.',
                    'assessment'      => 'Responding well. Frequency down from daily to 2–3×/week. Continue protocol.',
                    'plan'            => 'Swedish + deep tissue to neck and shoulders. Add pec minor work. Postural advice: monitor height, screen distance.',
                ],
                'paid' => true,
            ],
            [
                'daysAgo' => 5,
                'soapNotes' => [
                    'chief_complaint' => 'Headaches 1–2×/week, 3/10',
                    'subjective'      => 'Sleeping better. Headache-free for 5 days this week — personal record. Work still stressful but managing better. Taking walks at lunch.',
                    'objective'       => 'Mild residual tension upper traps R > L. Full cervical ROM. No active trigger points.',
                    'assessment'      => 'Good progress. Tension significantly reduced. Maintenance phase approaching.',
                    'plan'            => 'Maintenance massage. Reinforce home stretching. Recommend fortnightly sessions to prevent accumulation.',
                ],
                'paid' => false,  // Outstanding payment
            ],
        ];

        foreach ($visits as $index => $visitData) {
            $apt = Appointment::create([
                'practice_id'        => $this->practice->id,
                'patient_id'         => $patient->id,
                'practitioner_id'    => $this->practitioners['Massage']->id,
                'appointment_type_id' => $this->appointmentTypes['followup']->id,
                'status'             => $visitData['paid'] ? Closed::class : Checkout::class,
                'start_datetime'     => now()->subDays($visitData['daysAgo'])->setHour(14)->setMinute(0),
                'end_datetime'       => now()->subDays($visitData['daysAgo'])->setHour(15)->setMinute(0),
            ]);

            $encounter = Encounter::create([
                'practice_id'     => $this->practice->id,
                'patient_id'      => $patient->id,
                'appointment_id'  => $apt->id,
                'practitioner_id' => $this->practitioners['Massage']->id,
                'status'          => 'complete',
                'visit_date'      => now()->subDays($visitData['daysAgo'])->format('Y-m-d'),
                'discipline'      => 'massage',
                'chief_complaint' => $visitData['soapNotes']['chief_complaint'],
                'subjective'      => $visitData['soapNotes']['subjective'],
                'objective'       => $visitData['soapNotes']['objective'],
                'assessment'      => $visitData['soapNotes']['assessment'],
                'plan'            => $visitData['soapNotes']['plan'],
                'completed_on'    => now()->subDays($visitData['daysAgo']),
            ]);

            // First visit: create intake + consent
            if ($index === 0) {
                MedicalHistory::create([
                    'practice_id'       => $this->practice->id,
                    'patient_id'        => $patient->id,
                    'appointment_id'    => $apt->id,
                    'status'            => 'complete',
                    'submitted_on'      => now()->subDays($visitData['daysAgo']),
                    'discipline'        => 'massage',
                    'chief_complaint'   => 'Tension headaches and neck/shoulder tightness',
                    'pain_scale'        => 7,
                    'onset_duration'    => '6 weeks',
                    'onset_type'        => 'sudden',
                    'stress_level'      => 'high',
                    'consent_given'     => true,
                    'consent_signed_at' => now()->subDays($visitData['daysAgo']),
                    'consent_signed_by' => 'David Chen',
                ]);

                ConsentRecord::create([
                    'practice_id'        => $this->practice->id,
                    'patient_id'         => $patient->id,
                    'appointment_id'     => $apt->id,
                    'status'             => 'complete',
                    'signed_on'          => now()->subDays($visitData['daysAgo']),
                    'consent_given_by'   => 'David Chen',
                ]);
            }

            // Checkout session
            $state = $visitData['paid'] ? Paid::$name : PaymentDue::$name;
            $amountPaid = $visitData['paid'] ? 100 : 0;

            $checkout = CheckoutSession::create([
                'practice_id'     => $this->practice->id,
                'appointment_id'  => $apt->id,
                'patient_id'      => $patient->id,
                'practitioner_id' => $this->practitioners['Massage']->id,
                'state'           => $state,
                'charge_label'    => 'Massage Therapy Session',
                'amount_total'    => 100,
                'amount_paid'     => $amountPaid,
                'tender_type'     => $visitData['paid'] ? 'card' : null,
                'paid_on'         => $visitData['paid'] ? now()->subDays($visitData['daysAgo']) : null,
            ]);
        }
    }

    // ════════════════════════════════════════════════════════════════════════
    // Supporting Patients & Billing States
    // ════════════════════════════════════════════════════════════════════════

    private function seedSupportingPatients(): void
    {
        // Create 46 random patients with random clinical data
        Patient::factory()->count(46)->create(['practice_id' => $this->practice->id])
            ->each(function (Patient $patient) {
                // 2-4 intakes per patient
                $intakeCount = rand(2, 4);
                for ($i = 0; $i < $intakeCount; $i++) {
                    if (rand(0, 1)) {
                        MedicalHistory::factory()->complete()->create([
                            'practice_id' => $this->practice->id,
                            'patient_id'  => $patient->id,
                        ]);
                    } else {
                        MedicalHistory::factory()->missing()->create([
                            'practice_id' => $this->practice->id,
                            'patient_id'  => $patient->id,
                        ]);
                    }
                }

                // 3-6 encounters per patient
                $encounterCount = rand(3, 6);
                for ($i = 0; $i < $encounterCount; $i++) {
                    $practitioner = $this->practitioners[array_rand($this->practitioners)];
                    $aptType = $this->appointmentTypes[array_rand($this->appointmentTypes)];

                    $apt = Appointment::create([
                        'practice_id'        => $this->practice->id,
                        'patient_id'         => $patient->id,
                        'practitioner_id'    => $practitioner->id,
                        'appointment_type_id' => $aptType->id,
                        'status'             => Closed::class,
                        'start_datetime'     => now()->subDays(rand(1, 60)),
                        'end_datetime'       => now()->subDays(rand(1, 60))->addHour(),
                    ]);

                    if (rand(0, 2) > 0) {
                        $encounter = Encounter::factory()->complete()->create([
                            'practice_id'     => $this->practice->id,
                            'patient_id'      => $patient->id,
                            'appointment_id'  => $apt->id,
                            'practitioner_id' => $practitioner->id,
                        ]);

                        // Acupuncture encounters for Acu Anna
                        if ($practitioner->id === $this->practitioners['Acupuncture']->id && rand(0, 1)) {
                            AcupunctureEncounter::factory()->withClinicalData()->create([
                                'encounter_id' => $encounter->id,
                            ]);
                        }
                    } else {
                        Encounter::factory()->draft()->create([
                            'practice_id'     => $this->practice->id,
                            'patient_id'      => $patient->id,
                            'appointment_id'  => $apt->id,
                            'practitioner_id' => $practitioner->id,
                        ]);
                    }
                }
            });
    }

    private function seedBillingStates(): void
    {
        // 4 appointments per practitioner: Paid, Open, PaymentDue, Cancelled
        foreach ($this->practitioners as $practitioner) {
            // Paid
            $this->createBillingStateAppointment(
                $practitioner,
                Closed::class,
                Paid::$name,
                $this->appointmentTypes['followup'],
                95
            );

            // Open checkout
            $this->createBillingStateAppointment(
                $practitioner,
                Checkout::class,
                Open::$name,
                $this->appointmentTypes['followup'],
                110
            );

            // Overdue (45 days past due)
            $this->createBillingStateAppointment(
                $practitioner,
                Checkout::class,
                PaymentDue::$name,
                $this->appointmentTypes['followup'],
                125,
                -45
            );

            // Cancelled
            $apt = Appointment::create([
                'practice_id'        => $this->practice->id,
                'patient_id'         => Patient::factory()->create(['practice_id' => $this->practice->id])->id,
                'practitioner_id'    => $practitioner->id,
                'appointment_type_id' => $this->appointmentTypes['emergency']->id,
                'status'             => Cancelled::class,
                'start_datetime'     => now()->subHours(rand(1, 72)),
                'end_datetime'       => now()->subHours(rand(1, 72))->addMinutes(45),
            ]);

            CheckoutSession::create([
                'practice_id'     => $this->practice->id,
                'appointment_id'  => $apt->id,
                'patient_id'      => $apt->patient_id,
                'practitioner_id' => $practitioner->id,
                'state'           => Voided::$name,
                'charge_label'    => 'Cancelled Appointment',
                'amount_total'    => 0,
                'amount_paid'     => 0,
            ]);
        }
    }

    private function createBillingStateAppointment($practitioner, $status, $checkoutState, $aptType, $amount, $daysAgo = null)
    {
        $daysAgo = $daysAgo ?? -rand(1, 7);

        $apt = Appointment::create([
            'practice_id'        => $this->practice->id,
            'patient_id'         => Patient::factory()->create(['practice_id' => $this->practice->id])->id,
            'practitioner_id'    => $practitioner->id,
            'appointment_type_id' => $aptType->id,
            'status'             => $status,
            'start_datetime'     => now()->addDays($daysAgo)->subHours(rand(1, 72)),
            'end_datetime'       => now()->addDays($daysAgo)->subHours(rand(1, 72))->addMinutes(30),
        ]);

        $paid = $checkoutState === Paid::$name ? $amount : 0;

        CheckoutSession::create([
            'practice_id'     => $this->practice->id,
            'appointment_id'  => $apt->id,
            'patient_id'      => $apt->patient_id,
            'practitioner_id' => $practitioner->id,
            'state'           => $checkoutState,
            'charge_label'    => $aptType->name,
            'amount_total'    => $amount,
            'amount_paid'     => $paid,
            'tender_type'     => $paid > 0 ? 'card' : null,
            'paid_on'         => $paid > 0 ? now()->addDays($daysAgo) : null,
        ]);
    }

    // ════════════════════════════════════════════════════════════════════════
    // Inventory & Plans
    // ════════════════════════════════════════════════════════════════════════

    private function seedInventory(): void
    {
        $productCount = rand(20, 30);
        $products = [];

        for ($i = 0; $i < $productCount; $i++) {
            $product = InventoryProduct::factory()->create(['practice_id' => $this->practice->id]);
            $products[] = $product;
        }

        foreach ($products as $product) {
            $movementCount = rand(3, 8);
            for ($i = 0; $i < $movementCount; $i++) {
                InventoryMovement::factory()->create([
                    'practice_id'         => $this->practice->id,
                    'inventory_product_id' => $product->id,
                ]);
            }
        }
    }

    private function seedSubscriptionPlans(): void
    {
        $plans = [
            [
                'key'               => 'solo',
                'name'              => 'Solo Plan',
                'price_monthly'     => 4900,
                'max_practitioners' => 1,
                'features'          => ['Core clinical tools', '1 Practitioner', 'Basic reporting']
            ],
            [
                'key'               => 'clinic',
                'name'              => 'Clinic Plan',
                'price_monthly'     => 9900,
                'max_practitioners' => 5,
                'features'          => ['Up to 5 Practitioners', 'Advanced reporting', 'Inventory management']
            ],
            [
                'key'               => 'enterprise',
                'name'              => 'Enterprise Plan',
                'price_monthly'     => 19900,
                'max_practitioners' => -1,
                'features'          => ['Unlimited Practitioners', 'Custom reporting', 'Priority support']
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(['key' => $plan['key']], $plan);
        }
    }

    // ════════════════════════════════════════════════════════════════════════
    // Helpers & Reporting
    // ════════════════════════════════════════════════════════════════════════

    private function createAcupunctureVisit($patient, $practitioner, $daysAgo, $visitNumber, $soapNotes, $tcmDiagnosis, $pointsUsed, $isFirstVisit = false, $intakeData = []): void
    {
        $apt = Appointment::create([
            'practice_id'        => $this->practice->id,
            'patient_id'         => $patient->id,
            'practitioner_id'    => $practitioner->id,
            'appointment_type_id' => $visitNumber === 1 ? $this->appointmentTypes['consultation']->id : $this->appointmentTypes['followup']->id,
            'status'             => Closed::class,
            'start_datetime'     => now()->subDays($daysAgo)->setHour(9)->setMinute(0),
            'end_datetime'       => now()->subDays($daysAgo)->setHour(10)->setMinute(0),
        ]);

        $encounter = Encounter::create([
            'practice_id'     => $this->practice->id,
            'patient_id'      => $patient->id,
            'appointment_id'  => $apt->id,
            'practitioner_id' => $practitioner->id,
            'status'          => 'complete',
            'visit_date'      => now()->subDays($daysAgo)->format('Y-m-d'),
            'discipline'      => 'acupuncture',
            'chief_complaint' => $soapNotes['chief_complaint'],
            'subjective'      => $soapNotes['subjective'],
            'objective'       => $soapNotes['objective'],
            'assessment'      => $soapNotes['assessment'],
            'plan'            => $soapNotes['plan'],
            'completed_on'    => now()->subDays($daysAgo),
        ]);

        AcupunctureEncounter::create([
            'encounter_id'      => $encounter->id,
            'tcm_diagnosis'     => $tcmDiagnosis,
            'points_used'       => $pointsUsed,
            'needle_count'      => rand(6, 12),
            'tongue_body'       => 'Normal',
            'tongue_coating'    => 'Thin white',
            'pulse_quality'     => 'Wiry',
            'zang_fu_diagnosis' => 'Kidney Yin Deficiency',
            'five_elements'     => ['Water'],
            'csor_color'        => 'Pale',
            'csor_sound'        => 'Soft',
            'csor_odor'         => 'Sweet',
            'csor_emotion'      => 'Fear',
            'treatment_protocol' => 'Tonify Kidney, regulate Qi flow',
        ]);

        if ($isFirstVisit) {
            MedicalHistory::create([
                'practice_id'       => $this->practice->id,
                'patient_id'        => $patient->id,
                'appointment_id'    => $apt->id,
                'status'            => 'complete',
                'submitted_on'      => now()->subDays($daysAgo),
                'discipline'        => $intakeData['discipline'] ?? 'acupuncture',
                'chief_complaint'   => $intakeData['chief_complaint'] ?? $soapNotes['chief_complaint'],
                'pain_scale'        => $intakeData['pain_scale'] ?? 8,
                'onset_duration'    => $intakeData['onset_duration'] ?? '3 years',
                'onset_type'        => $intakeData['onset_type'] ?? 'gradual',
                'consent_given'     => true,
                'consent_signed_at' => now()->subDays($daysAgo),
                'consent_signed_by' => $patient->name,
            ]);

            ConsentRecord::create([
                'practice_id'        => $this->practice->id,
                'patient_id'         => $patient->id,
                'appointment_id'     => $apt->id,
                'status'             => 'complete',
                'signed_on'          => now()->subDays($daysAgo),
                'consent_given_by'   => $patient->name,
            ]);
        }

        CheckoutSession::create([
            'practice_id'     => $this->practice->id,
            'appointment_id'  => $apt->id,
            'patient_id'      => $patient->id,
            'practitioner_id' => $practitioner->id,
            'state'           => Paid::$name,
            'charge_label'    => 'Acupuncture Session',
            'amount_total'    => 120,
            'amount_paid'     => 120,
            'tender_type'     => 'card',
            'paid_on'         => now()->subDays($daysAgo),
        ]);
    }

    private function reportCounts(): void
    {
        $this->command->info(sprintf(
            'Clinic seeded: %d practitioners, %d patients, %d appointments, %d intake submissions, %d encounters, %d inventory products.',
            Practitioner::where('practice_id', $this->practice->id)->count(),
            Patient::where('practice_id', $this->practice->id)->count(),
            Appointment::where('practice_id', $this->practice->id)->count(),
            MedicalHistory::where('practice_id', $this->practice->id)->count(),
            Encounter::where('practice_id', $this->practice->id)->count(),
            InventoryProduct::where('practice_id', $this->practice->id)->count(),
        ));
    }
}
