<?php

namespace Database\Seeders;

use App\Models\AcupunctureEncounter;
use App\Models\Appointment;
use App\Models\AppointmentRequest;
use App\Models\AppointmentType;
use App\Models\CheckoutLine;
use App\Models\CheckoutPayment;
use App\Models\CheckoutSession;
use App\Models\CommunicationRule;
use App\Models\Encounter;
use App\Models\InventoryProduct;
use App\Models\MedicalHistory;
use App\Models\MessageLog;
use App\Models\MessageTemplate;
use App\Models\Patient;
use App\Models\PatientCommunication;
use App\Models\PatientCommunicationPreference;
use App\Models\Practice;
use App\Models\PracticePaymentMethod;
use App\Models\Practitioner;
use App\Models\ServiceFee;
use App\Models\States\Appointment\Cancelled;
use App\Models\States\Appointment\Checkout;
use App\Models\States\Appointment\Closed;
use App\Models\States\Appointment\Completed;
use App\Models\States\Appointment\InProgress;
use App\Models\States\Appointment\NoShow;
use App\Models\States\Appointment\Scheduled;
use App\Models\States\CheckoutSession\Open;
use App\Models\States\CheckoutSession\PaymentDue;
use App\Models\User;
use App\Support\PracticeAccessRoles;
use App\Support\PracticeType;
use Illuminate\Console\Command;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RealisticPracticeDemoSeeder extends Seeder
{
    public const MARKER = 'REALISTIC_PRACTICE_DEMO_SEED';
    public const PATIENT_PREFIX = 'Realistic Demo - ';
    public const TEMPLATE_PREFIX = 'Realistic Demo - ';
    public const TEST_EMAIL = 'laggneralfred@gmail.com';

    private Practice $practice;
    private User $targetUser;
    private Practitioner $tcmPractitioner;
    private Practitioner $fiveElementPractitioner;
    private Practitioner $massagePractitioner;
    private Practitioner $wellnessPractitioner;
    private array $serviceFees = [];
    private array $appointmentTypes = [];
    private array $patients = [];
    private array $requestLinks = [];
    private string $baseUrl = 'https://app.practiqapp.com';

    public function run(string $userEmail = 'admin@healthcare.test', string $baseUrl = 'https://app.practiqapp.com', bool $resetDemoData = false): int
    {
        $this->baseUrl = rtrim($baseUrl, '/');

        $this->targetUser = User::query()
            ->where('email', $userEmail)
            ->firstOrFail();

        if (! $this->targetUser->practice_id) {
            $this->command?->error("User {$userEmail} is not linked to a practice_id. Choose a practice-linked user.");

            return Command::FAILURE;
        }

        $this->practice = Practice::query()->findOrFail($this->targetUser->practice_id);

        DB::transaction(function () use ($resetDemoData): void {
            PracticeAccessRoles::ensureRoles();
            PracticePaymentMethod::ensureDefaultsForPractice($this->practice);

            if ($resetDemoData) {
                $this->clearExistingDemoData();
            } else {
                // The seeder remains idempotent by resetting only its clearly marked records.
                $this->clearExistingDemoData();
            }

            $this->seedPractitioners();
            $this->seedServiceFees();
            $this->seedAppointmentTypes();
            $this->seedInventoryProducts();
            $this->seedMessageTemplatesAndRules();
            $this->seedPatients();
            $this->seedMedicalHistories();
            $this->seedCareStatusScenarios();
            $this->seedTodayAndCalendarScenarios();
            $this->seedCheckoutScenarios();
            $this->seedAppointmentRequests();
            $this->seedCommunicationHistory();
        });

        $this->report($userEmail);

        return Command::SUCCESS;
    }

    private function clearExistingDemoData(): void
    {
        $patientIds = Patient::withoutPracticeScope()
            ->where('practice_id', $this->practice->id)
            ->where(function ($query): void {
                $query->where('name', 'like', self::PATIENT_PREFIX.'%')
                    ->orWhereIn('name', ['No Email Demo Patient', 'Opted Out Demo Patient'])
                    ->orWhere('notes', 'like', '%'.self::MARKER.'%');
            })
            ->pluck('id');

        $appointmentIds = Appointment::withoutPracticeScope()
            ->where('practice_id', $this->practice->id)
            ->whereIn('patient_id', $patientIds)
            ->pluck('id');

        $encounterIds = Encounter::withoutPracticeScope()
            ->where('practice_id', $this->practice->id)
            ->whereIn('patient_id', $patientIds)
            ->pluck('id');

        $checkoutIds = CheckoutSession::withoutPracticeScope()
            ->where('practice_id', $this->practice->id)
            ->where(function ($query) use ($patientIds): void {
                $query->whereIn('patient_id', $patientIds)
                    ->orWhere('notes', 'like', '%'.self::MARKER.'%');
            })
            ->pluck('id');

        CheckoutPayment::withoutPracticeScope()->whereIn('checkout_session_id', $checkoutIds)->delete();
        CheckoutLine::withoutPracticeScope()->whereIn('checkout_session_id', $checkoutIds)->delete();
        CheckoutSession::withoutPracticeScope()->whereIn('id', $checkoutIds)->delete();
        AcupunctureEncounter::query()->whereIn('encounter_id', $encounterIds)->delete();
        MedicalHistory::withoutPracticeScope()->whereIn('patient_id', $patientIds)->delete();
        MessageLog::withoutPracticeScope()->where('practice_id', $this->practice->id)->whereIn('patient_id', $patientIds)->delete();
        AppointmentRequest::withoutPracticeScope()->where('practice_id', $this->practice->id)->whereIn('patient_id', $patientIds)->delete();
        PatientCommunication::withoutPracticeScope()->where('practice_id', $this->practice->id)->whereIn('patient_id', $patientIds)->delete();
        PatientCommunicationPreference::withoutPracticeScope()->where('practice_id', $this->practice->id)->whereIn('patient_id', $patientIds)->delete();
        Encounter::withoutPracticeScope()->whereIn('id', $encounterIds)->delete();
        Appointment::withoutPracticeScope()->whereIn('id', $appointmentIds)->delete();
        Patient::withoutPracticeScope()->whereIn('id', $patientIds)->delete();

        CommunicationRule::withoutPracticeScope()
            ->withTrashed()
            ->where('practice_id', $this->practice->id)
            ->whereHas('messageTemplate', fn ($query) => $query->withTrashed()->where('name', 'like', self::TEMPLATE_PREFIX.'%'))
            ->forceDelete();

        MessageTemplate::withoutPracticeScope()
            ->withTrashed()
            ->where('practice_id', $this->practice->id)
            ->where('name', 'like', self::TEMPLATE_PREFIX.'%')
            ->forceDelete();

        InventoryProduct::withoutPracticeScope()
            ->withTrashed()
            ->where('practice_id', $this->practice->id)
            ->where('sku', 'like', 'DEMO-%')
            ->forceDelete();
    }

    private function seedPractitioners(): void
    {
        $this->tcmPractitioner = $this->practitioner('realistic-demo-tcm@practiq.local', 'Dr. Mira Chen', 'TCM Acupuncture', PracticeType::TCM_ACUPUNCTURE);
        $this->fiveElementPractitioner = $this->practitioner('realistic-demo-five-element@practiq.local', 'Dr. Rowan Hart', 'Five Element Acupuncture', PracticeType::FIVE_ELEMENT_ACUPUNCTURE);
        $this->massagePractitioner = $this->practitioner('realistic-demo-massage@practiq.local', 'Sam Rivera', 'Massage Therapy', PracticeType::MASSAGE_THERAPY);
        $this->wellnessPractitioner = $this->practitioner('realistic-demo-wellness@practiq.local', 'Avery Brooks', 'Wellness', PracticeType::GENERAL_WELLNESS);
    }

    private function practitioner(string $email, string $name, string $specialty, string $clinicalStyle): Practitioner
    {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'practice_id' => $this->practice->id,
            ],
        );
        $user->assignRole(User::ROLE_PRACTITIONER);

        return Practitioner::withoutPracticeScope()->updateOrCreate(
            [
                'practice_id' => $this->practice->id,
                'user_id' => $user->id,
            ],
            [
                'specialty' => $specialty,
                'clinical_style' => $clinicalStyle,
                'license_number' => 'DEMO-'.Str::upper(Str::slug($specialty)),
                'is_active' => true,
            ],
        );
    }

    private function seedServiceFees(): void
    {
        foreach ($this->priceList() as $name => [$price, $description]) {
            $this->serviceFees[$name] = ServiceFee::withoutPracticeScope()->updateOrCreate(
                [
                    'practice_id' => $this->practice->id,
                    'name' => $name,
                ],
                [
                    'short_description' => self::MARKER.' '.$description,
                    'default_price' => $price,
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedAppointmentTypes(): void
    {
        $types = [
            'initial_acu' => ['Initial Acupuncture Consultation + Treatment', 90, 'Initial Acupuncture Consultation + Treatment'],
            'follow_up_acu' => ['Follow-Up Acupuncture Treatment', 50, 'Follow-Up Acupuncture Treatment'],
            'five_element' => ['Five Element Acupuncture Treatment', 60, 'Five Element Acupuncture Treatment'],
            'extended_acu' => ['Extended Acupuncture Session', 75, 'Extended Acupuncture Session'],
            'herbal' => ['Herbal Consultation', 30, 'Herbal Consultation'],
            'moxa' => ['Moxa / Adjunctive Treatment', 30, 'Moxa / Adjunctive Treatment'],
            'cupping' => ['Cupping Add-on', 20, 'Cupping Add-on'],
            'massage_30' => ['Massage Therapy 30 min', 30, 'Massage Therapy 30 min'],
            'massage_60' => ['Massage Therapy 60 min', 60, 'Massage Therapy 60 min'],
            'massage_75' => ['Massage Therapy 75 min', 75, 'Massage Therapy 75 min'],
            'massage_90' => ['Massage Therapy 90 min', 90, 'Massage Therapy 90 min'],
            'bodywork_follow_up' => ['Therapeutic Bodywork Follow-Up', 60, 'Therapeutic Bodywork Follow-Up'],
            'wellness_consult' => ['Wellness Consultation', 45, 'Wellness Consultation'],
            'wellness_follow_up' => ['Follow-Up Wellness Visit', 45, 'Follow-Up Wellness Visit'],
            'no_default_fee' => ['No Default Fee Demo Visit', 45, null],
        ];

        foreach ($types as $key => [$name, $duration, $feeName]) {
            $this->appointmentTypes[$key] = AppointmentType::withoutPracticeScope()->updateOrCreate(
                [
                    'practice_id' => $this->practice->id,
                    'name' => $name,
                ],
                [
                    'duration_minutes' => $duration,
                    'is_active' => true,
                    'default_service_fee_id' => $feeName ? $this->serviceFees[$feeName]->id : null,
                ],
            );
        }
    }

    private function seedInventoryProducts(): void
    {
        foreach ([
            ['DEMO-HERB-SLEEP', 'Calm Sleep Herbal Formula', 'Herbal Formula', 'bottle', 32.00, 12],
            ['DEMO-MOXA-ROLL', 'Moxa Roll Pack', 'Other', 'pack', 18.00, 20],
            ['DEMO-MAG-GLY', 'Magnesium Glycinate', 'Supplement', 'bottle', 28.00, 8],
        ] as [$sku, $name, $category, $unit, $price, $stock]) {
            InventoryProduct::withoutPracticeScope()->create([
                'practice_id' => $this->practice->id,
                'name' => $name,
                'sku' => $sku,
                'description' => self::MARKER.' Fake demo inventory item.',
                'category' => $category,
                'unit' => $unit,
                'selling_price' => $price,
                'cost_price' => round($price * 0.45, 2),
                'stock_quantity' => $stock,
                'low_stock_threshold' => 5,
                'is_active' => true,
            ]);
        }
    }

    private function seedMessageTemplatesAndRules(): void
    {
        $templates = [
            ['Appointment reminder 48 hours', 'reminder_48h', 'Your upcoming visit at {{ practice_name }}', "Hi {{ patient_name }}, this is a gentle reminder of your {{ appointment_type }} on {{ appointment_date }} at {{ appointment_time }} with {{ practitioner_name }}. Reply or call us if anything changes.", -2880],
            ['Appointment reminder 24 hours', 'reminder_24h', 'Reminder: your visit is tomorrow', "Hi {{ patient_name }}, we look forward to seeing you tomorrow at {{ appointment_time }} for your {{ appointment_type }}.", -1440],
            ['Same-day appointment reminder', 'custom', 'Today at {{ practice_name }}', "Hi {{ patient_name }}, a quick same-day reminder that we will see you at {{ appointment_time }} today.", -180],
            ['Post-visit check-in', 'appointment_followup', 'Checking in after your visit', "Hi {{ patient_name }}, we hope you are settling well after your visit. Please call us if you have questions or would like to schedule follow-up care.", 2880],
            ['Follow-up invitation', 'custom', 'Would you like to schedule a follow-up?', "Hi {{ patient_name }}, we were thinking of you and wanted to invite you back for gentle follow-up care when it feels right.", 30240],
            ['Reactivation check-in', 'custom', 'Checking in from {{ practice_name }}', "Hi {{ patient_name }}, it has been a little while since we saw you. If care would be helpful again, we are here.", 86400],
            ['Missed appointment check-in', 'missed_appointment', 'Sorry we missed you', "Hi {{ patient_name }}, we missed you at your appointment. Please call when you are ready to find another time.", 60],
            ['Cancelled not rescheduled check-in', 'custom', 'Would you like another time?', "Hi {{ patient_name }}, we noticed your visit was cancelled and not yet rescheduled. Let us know if you would like help finding another time.", 1440],
        ];

        foreach ($templates as [$name, $event, $subject, $body, $offset]) {
            $template = MessageTemplate::withoutPracticeScope()->create([
                'practice_id' => $this->practice->id,
                'name' => self::TEMPLATE_PREFIX.$name,
                'channel' => 'email',
                'trigger_event' => $event,
                'subject' => $subject,
                'body' => self::MARKER."\n".$body,
                'is_active' => true,
                'is_default' => false,
            ]);

            CommunicationRule::withoutPracticeScope()->create([
                'practice_id' => $this->practice->id,
                'message_template_id' => $template->id,
                'is_active' => true,
                'send_at_offset_minutes' => $offset,
            ]);
        }
    }

    private function seedPatients(): void
    {
        $patients = [
            ['New Patient', Patient::LANGUAGE_ENGLISH, self::TEST_EMAIL],
            ['Active Future Appointment Patient', Patient::LANGUAGE_ENGLISH, self::TEST_EMAIL],
            ['Active Recent Visit Patient', Patient::LANGUAGE_ENGLISH, self::TEST_EMAIL],
            ['Needs Follow-Up Patient', Patient::LANGUAGE_ENGLISH, self::TEST_EMAIL],
            ['Cooling Patient', Patient::LANGUAGE_SPANISH, self::TEST_EMAIL],
            ['Inactive Patient', Patient::LANGUAGE_CHINESE, self::TEST_EMAIL],
            ['At Risk Cancelled Patient', Patient::LANGUAGE_VIETNAMESE, self::TEST_EMAIL],
            ['At Risk No-Show Patient', Patient::LANGUAGE_FRENCH, self::TEST_EMAIL],
            ['German Translation Patient', Patient::LANGUAGE_GERMAN, self::TEST_EMAIL],
            ['Other Language Patient', Patient::LANGUAGE_OTHER, self::TEST_EMAIL],
            ['Five Element Demo Patient', Patient::LANGUAGE_ENGLISH, self::TEST_EMAIL],
            ['TCM Demo Patient', Patient::LANGUAGE_ENGLISH, self::TEST_EMAIL],
            ['Massage Demo Patient', Patient::LANGUAGE_ENGLISH, self::TEST_EMAIL],
            ['Checkout Open Patient', Patient::LANGUAGE_ENGLISH, self::TEST_EMAIL],
            ['Checkout Paid Patient', Patient::LANGUAGE_ENGLISH, self::TEST_EMAIL],
            ['Checkout Partial Patient', Patient::LANGUAGE_ENGLISH, self::TEST_EMAIL],
            ['Checkout Product Patient', Patient::LANGUAGE_ENGLISH, self::TEST_EMAIL],
            ['No Default Fee Patient', Patient::LANGUAGE_ENGLISH, self::TEST_EMAIL],
            ['Direct Visit Demo Patient', Patient::LANGUAGE_ENGLISH, self::TEST_EMAIL],
            ['No Email Demo Patient', Patient::LANGUAGE_ENGLISH, null],
            ['Opted Out Demo Patient', Patient::LANGUAGE_ENGLISH, self::TEST_EMAIL],
        ];

        foreach ($patients as [$baseName, $language, $email]) {
            $name = in_array($baseName, ['No Email Demo Patient', 'Opted Out Demo Patient'], true)
                ? $baseName
                : self::PATIENT_PREFIX.$baseName;
            [$firstName, $lastName] = $this->splitName($name);

            $patient = Patient::withoutPracticeScope()->create([
                'practice_id' => $this->practice->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'name' => $name,
                'email' => $email,
                'phone' => '(555) 013-'.str_pad((string) (100 + count($this->patients)), 4, '0', STR_PAD_LEFT),
                'dob' => now()->subYears(38 + (count($this->patients) % 18))->toDateString(),
                'gender' => 'Not specified',
                'pronouns' => null,
                'preferred_language' => $language,
                'address_line_1' => (100 + count($this->patients)).' Demo Wellness Way',
                'city' => 'Demo City',
                'state' => 'CA',
                'postal_code' => '90000',
                'country' => 'USA',
                'occupation' => 'Demo patient',
                'notes' => self::MARKER.' Fake realistic demo patient. Do not use for real clinical care.',
                'is_patient' => true,
            ]);

            $this->patients[$baseName] = $patient;

            PatientCommunicationPreference::withoutPracticeScope()->create([
                'practice_id' => $this->practice->id,
                'patient_id' => $patient->id,
                'email_opt_in' => $baseName !== 'Opted Out Demo Patient',
                'sms_opt_in' => false,
                'preferred_channel' => 'email',
                'opted_out_at' => $baseName === 'Opted Out Demo Patient' ? now() : null,
            ]);
        }
    }

    private function seedMedicalHistories(): void
    {
        foreach ([
            'Five Element Demo Patient' => ['acupuncture', 'Long-standing fatigue, sleep disruption, and low back coldness.', ['five_element' => ['cf' => 'Water observation to assess', 'csoe' => 'Color, sound, odor, emotion observed over time']]],
            'TCM Demo Patient' => ['acupuncture', 'Neck and shoulder tension worsened by stress.', ['tcm' => ['pattern' => 'Liver qi constraint with spleen qi deficiency tendencies']]],
            'Massage Demo Patient' => ['massage', 'Upper trapezius tension and low back stiffness after prolonged sitting.', ['massage' => ['pressure' => 'Moderate pressure preferred', 'areas' => 'Neck, shoulders, QL, glutes']]],
            'Needs Follow-Up Patient' => ['acupuncture', 'Stress, sleep concerns, and recurring neck tension.', ['tcm' => ['red_flags' => 'No major red flags reported']]],
        ] as $baseName => [$discipline, $complaint, $responses]) {
            $patient = $this->patients[$baseName];

            MedicalHistory::withoutPracticeScope()->create([
                'practice_id' => $this->practice->id,
                'patient_id' => $patient->id,
                'practitioner_id' => $discipline === 'massage' ? $this->massagePractitioner->id : $this->tcmPractitioner->id,
                'status' => 'complete',
                'submitted_on' => now()->subDays(14),
                'discipline' => $discipline,
                'reason_for_visit' => $complaint,
                'current_concerns' => 'Symptoms are worse with stress and improve with rest, warmth, and supportive care.',
                'relevant_history' => 'Fake demo history. No real patient data.',
                'chief_complaint' => $complaint,
                'onset_duration' => 'Several months',
                'onset_type' => 'gradual',
                'aggravating_factors' => 'Stress, prolonged sitting, poor sleep.',
                'relieving_factors' => 'Heat, gentle movement, bodywork, and acupuncture.',
                'pain_scale' => 4,
                'sleep_quality' => 'Restless',
                'sleep_hours' => 6,
                'stress_level' => 'Moderate to high',
                'diet_description' => 'Generally balanced with some digestive sensitivity.',
                'has_pacemaker' => false,
                'takes_blood_thinners' => false,
                'has_bleeding_disorder' => false,
                'has_infectious_disease' => false,
                'is_pregnant' => false,
                'discipline_responses' => $responses,
                'consent_given' => true,
                'consent_signed_at' => now()->subDays(14),
                'consent_signed_by' => $patient->name,
                'notes' => self::MARKER.' Fake demo intake with no major red flags.',
                'summary_text' => 'Stress and sleep concerns with neck or low back tension; no major red flags reported.',
            ]);
        }
    }

    private function seedCareStatusScenarios(): void
    {
        $this->appointment('Active Future Appointment Patient', $this->tcmPractitioner, 'follow_up_acu', Scheduled::$name, now()->addDays(2)->setTime(10, 0), 'Future active appointment.');
        $this->completedEncounter('Active Recent Visit Patient', $this->tcmPractitioner, 'follow_up_acu', now()->subDays(10), $this->tcmNote());
        $this->completedEncounter('Needs Follow-Up Patient', $this->tcmPractitioner, 'follow_up_acu', now()->subDays(35), 'Patient improved after treatment for neck tension and sleep; follow-up recommended.');
        $this->completedEncounter('Cooling Patient', $this->fiveElementPractitioner, 'five_element', now()->subDays(60), $this->fiveElementNote(), fiveElement: true);
        $this->completedEncounter('Inactive Patient', $this->massagePractitioner, 'massage_60', now()->subDays(120), $this->massageNote(), discipline: 'massage');
        $this->appointment('At Risk Cancelled Patient', $this->tcmPractitioner, 'follow_up_acu', Cancelled::$name, now()->subDays(5)->setTime(13, 0), 'Recent cancelled appointment not rescheduled.');
        $this->appointment('At Risk No-Show Patient', $this->tcmPractitioner, 'follow_up_acu', NoShow::$name, now()->subDays(4)->setTime(13, 0), 'Recent no-show appointment.');
        $this->completedEncounter('German Translation Patient', $this->tcmPractitioner, 'follow_up_acu', now()->subDays(48), 'Sleep support and maintenance care discussed.');
        $this->completedEncounter('Other Language Patient', $this->tcmPractitioner, 'follow_up_acu', now()->subDays(52), 'Gentle follow-up recommended.');
        $this->completedEncounter('No Email Demo Patient', $this->tcmPractitioner, 'follow_up_acu', now()->subDays(38), 'Follow-up eligible patient with no email.');
        $this->completedEncounter('Opted Out Demo Patient', $this->tcmPractitioner, 'follow_up_acu', now()->subDays(39), 'Follow-up eligible patient with opt-out preference.');
    }

    private function seedTodayAndCalendarScenarios(): void
    {
        $today = now($this->practice->timezone);

        $this->appointment('New Patient', $this->wellnessPractitioner, 'wellness_consult', Scheduled::$name, $today->copy()->setTime(9, 0), 'New patient scheduled today.');
        $this->appointment('TCM Demo Patient', $this->tcmPractitioner, 'follow_up_acu', Scheduled::$name, $today->copy()->setTime(10, 30), 'Scheduled TCM visit today.');
        $this->appointment('Massage Demo Patient', $this->massagePractitioner, 'massage_60', InProgress::$name, $today->copy()->setTime(11, 30), 'Massage visit currently in progress.');
        $this->appointment('Five Element Demo Patient', $this->fiveElementPractitioner, 'five_element', Checkout::$name, $today->copy()->setTime(13, 30), 'Five Element visit ready for checkout.');
        $this->appointment('Checkout Paid Patient', $this->tcmPractitioner, 'extended_acu', Closed::$name, $today->copy()->subDays(1)->setTime(14, 0), 'Closed visit for paid checkout history.');
        $this->appointment('TCM Demo Patient', $this->tcmPractitioner, 'initial_acu', Completed::$name, $today->copy()->subDays(3)->setTime(9, 30), 'Completed TCM appointment in calendar.');
        $this->appointment('Massage Demo Patient', $this->massagePractitioner, 'massage_90', Scheduled::$name, $today->copy()->addDay()->setTime(15, 0), 'Upcoming massage appointment.');

        $this->completedEncounter('Five Element Demo Patient', $this->fiveElementPractitioner, 'five_element', $today->copy()->subDays(20), $this->fiveElementNote(), fiveElement: true);
        $this->completedEncounter('TCM Demo Patient', $this->tcmPractitioner, 'follow_up_acu', $today->copy()->subDays(18), $this->tcmNote());
        $this->completedEncounter('Massage Demo Patient', $this->massagePractitioner, 'massage_60', $today->copy()->subDays(16), $this->massageNote(), discipline: 'massage');

        Encounter::withoutPracticeScope()->create([
            'practice_id' => $this->practice->id,
            'patient_id' => $this->patients['Direct Visit Demo Patient']->id,
            'appointment_id' => null,
            'practitioner_id' => $this->wellnessPractitioner->id,
            'status' => 'draft',
            'visit_date' => $today->toDateString(),
            'discipline' => 'general',
            'chief_complaint' => 'Direct wellness note',
            'visit_notes' => "Direct/no-appointment demo encounter.\n\nPatient reports stress and sleep concerns. This record is for simple visit note workflow testing.",
        ]);
    }

    private function seedCheckoutScenarios(): void
    {
        $this->checkoutFor('Checkout Open Patient', $this->tcmPractitioner, 'follow_up_acu', Open::$name, [$this->serviceLine('Follow-Up Acupuncture Treatment')]);
        $this->checkoutFor('Checkout Paid Patient', $this->tcmPractitioner, 'extended_acu', Open::$name, [$this->serviceLine('Extended Acupuncture Session')], paid: true);
        $this->checkoutFor('Checkout Partial Patient', $this->tcmPractitioner, 'initial_acu', Open::$name, [$this->serviceLine('Initial Acupuncture Consultation + Treatment')], partialAmount: 75.00);
        $this->checkoutFor('Checkout Product Patient', $this->tcmPractitioner, 'herbal', Open::$name, [
            $this->serviceLine('Herbal Consultation'),
            $this->inventoryLine('DEMO-HERB-SLEEP', 1),
        ], paid: true);
        $this->checkoutFor('Five Element Demo Patient', $this->fiveElementPractitioner, 'five_element', PaymentDue::$name, [
            $this->serviceLine('Five Element Acupuncture Treatment'),
            ['type' => CheckoutLine::TYPE_CUSTOM, 'description' => 'Demo courtesy adjustment', 'amount' => -10.00],
        ]);
        $this->checkoutFor('No Default Fee Patient', $this->wellnessPractitioner, 'no_default_fee', Open::$name, []);
    }

    private function seedAppointmentRequests(): void
    {
        $this->request('Needs Follow-Up Patient', AppointmentRequest::STATUS_PENDING, 'Tuesday morning or Thursday after 2', 'Prefers the same practitioner if possible.');
        $this->request('Cooling Patient', AppointmentRequest::STATUS_PENDING, 'Viernes por la tarde o lunes por la mañana', 'Spanish-speaking patient; review message before sending.');
        $this->request('Inactive Patient', AppointmentRequest::STATUS_CONTACTED, 'Any afternoon next week', 'Staff called and left voicemail.');
        $this->request('At Risk Cancelled Patient', AppointmentRequest::STATUS_SCHEDULED, 'Wednesday after 3', 'Scheduled manually by front desk.');
        $this->request('At Risk No-Show Patient', AppointmentRequest::STATUS_DISMISSED, 'No preference', 'Dismissed for demo history.');

        $this->freshRequestLink('Needs Follow-Up Patient');
        $this->freshRequestLink('Cooling Patient');
    }

    private function seedCommunicationHistory(): void
    {
        foreach (['Needs Follow-Up Patient', 'Cooling Patient', 'Inactive Patient'] as $baseName) {
            $patient = $this->patients[$baseName];
            $communication = PatientCommunication::withoutPracticeScope()->create([
                'practice_id' => $this->practice->id,
                'patient_id' => $patient->id,
                'type' => PatientCommunication::TYPE_INVITE_BACK,
                'channel' => PatientCommunication::CHANNEL_EMAIL,
                'language' => $patient->preferred_language,
                'subject' => 'A gentle follow-up from '.$this->practice->name,
                'body' => self::MARKER.' We would be happy to see you again when the timing feels right.',
                'status' => PatientCommunication::STATUS_SENT,
                'created_by' => $this->targetUser->id,
                'sent_at' => now()->subDays(2),
            ]);

            MessageLog::withoutPracticeScope()->create([
                'practice_id' => $this->practice->id,
                'patient_id' => $patient->id,
                'appointment_id' => null,
                'practitioner_id' => $this->tcmPractitioner->id,
                'message_template_id' => null,
                'channel' => 'email',
                'recipient' => $patient->email ?? '',
                'subject' => $communication->subject,
                'body' => $communication->body,
                'status' => 'sent',
                'sent_at' => now()->subDays(2),
                'provider_message_id' => 'demo-'.Str::random(10),
            ]);
        }
    }

    private function appointment(string $baseName, Practitioner $practitioner, string $typeKey, string $status, \DateTimeInterface $start, string $notes): Appointment
    {
        $type = $this->appointmentTypes[$typeKey];

        return Appointment::withoutPracticeScope()->create([
            'practice_id' => $this->practice->id,
            'patient_id' => $this->patients[$baseName]->id,
            'practitioner_id' => $practitioner->id,
            'appointment_type_id' => $type->id,
            'status' => $status,
            'start_datetime' => $start,
            'end_datetime' => (clone $start)->modify('+'.$type->duration_minutes.' minutes'),
            'notes' => self::MARKER.' '.$notes,
        ]);
    }

    private function completedEncounter(string $baseName, Practitioner $practitioner, string $typeKey, \DateTimeInterface $date, string $note, bool $fiveElement = false, string $discipline = 'acupuncture'): Encounter
    {
        $appointment = $this->appointment($baseName, $practitioner, $typeKey, Completed::$name, $date->setTime(10, 0), $note);

        $encounter = Encounter::withoutPracticeScope()->create([
            'practice_id' => $this->practice->id,
            'patient_id' => $this->patients[$baseName]->id,
            'appointment_id' => $appointment->id,
            'practitioner_id' => $practitioner->id,
            'status' => 'complete',
            'visit_date' => $date->format('Y-m-d'),
            'completed_on' => $date->setTime(11, 0),
            'discipline' => $discipline,
            'chief_complaint' => $discipline === 'massage' ? 'Neck and shoulder tension' : 'Stress and sleep support',
            'subjective' => 'Patient reports stress, sleep concerns, neck tension, and low back stiffness. Fake demo note only.',
            'objective' => 'No major red flags in demo record.',
            'assessment' => $fiveElement ? 'Five Element care focused on restoring warmth and movement.' : 'Supportive care appropriate; patient responded well.',
            'plan' => 'Follow up as clinically appropriate. Staff can use Follow-Up Center when no future visit is scheduled.',
            'visit_notes' => self::MARKER."\n".$note,
        ]);

        if ($discipline === 'acupuncture') {
            AcupunctureEncounter::query()->create($fiveElement
                ? $this->fiveElementDetails($encounter)
                : $this->tcmDetails($encounter));
        }

        return $encounter;
    }

    private function checkoutFor(string $baseName, Practitioner $practitioner, string $typeKey, string $state, array $lines, bool $paid = false, ?float $partialAmount = null): CheckoutSession
    {
        $appointment = $this->appointment($baseName, $practitioner, $typeKey, Checkout::$name, now()->subDay()->setTime(12, 0), 'Checkout demo appointment.');
        $encounter = Encounter::withoutPracticeScope()->create([
            'practice_id' => $this->practice->id,
            'patient_id' => $this->patients[$baseName]->id,
            'appointment_id' => $appointment->id,
            'practitioner_id' => $practitioner->id,
            'status' => 'complete',
            'visit_date' => now()->subDay()->toDateString(),
            'completed_on' => now()->subDay()->setTime(13, 0),
            'discipline' => $practitioner->id === $this->massagePractitioner->id ? 'massage' : 'acupuncture',
            'chief_complaint' => 'Checkout workflow demo',
            'visit_notes' => self::MARKER.' Completed demo visit for checkout testing.',
        ]);

        $checkout = CheckoutSession::withoutPracticeScope()->create([
            'practice_id' => $this->practice->id,
            'appointment_id' => $appointment->id,
            'encounter_id' => $encounter->id,
            'patient_id' => $this->patients[$baseName]->id,
            'practitioner_id' => $practitioner->id,
            'state' => $state,
            'charge_label' => $this->appointmentTypes[$typeKey]->name,
            'notes' => self::MARKER.' Checkout demo scenario for '.$baseName,
            'diagnosis_codes' => 'Demo only',
            'procedure_codes' => 'Demo only',
        ]);

        foreach (array_values($lines) as $index => $line) {
            CheckoutLine::withoutPracticeScope()->create(array_merge([
                'practice_id' => $this->practice->id,
                'checkout_session_id' => $checkout->id,
                'sequence' => $index + 1,
            ], $line));
        }

        $checkout->refresh();

        if ($partialAmount !== null) {
            CheckoutPayment::withoutPracticeScope()->create([
                'practice_id' => $this->practice->id,
                'checkout_session_id' => $checkout->id,
                'amount' => $partialAmount,
                'payment_method' => CheckoutPayment::METHOD_CHECK,
                'paid_at' => now()->subHours(3),
                'reference' => 'DEMO-PARTIAL',
                'notes' => self::MARKER.' Partial payment demo.',
                'created_by_user_id' => $this->targetUser->id,
            ]);
        }

        if ($paid && (float) $checkout->amount_due > 0) {
            CheckoutPayment::withoutPracticeScope()->create([
                'practice_id' => $this->practice->id,
                'checkout_session_id' => $checkout->id,
                'amount' => $checkout->amount_due,
                'payment_method' => CheckoutPayment::METHOD_CARD_EXTERNAL,
                'paid_at' => now()->subHours(2),
                'reference' => 'DEMO-PAID',
                'notes' => self::MARKER.' Paid checkout demo.',
                'created_by_user_id' => $this->targetUser->id,
            ]);
        }

        return $checkout->refresh();
    }

    private function serviceLine(string $feeName): array
    {
        return [
            'line_type' => CheckoutLine::TYPE_SERVICE,
            'service_fee_id' => $this->serviceFees[$feeName]->id,
            'quantity' => 1,
        ];
    }

    private function inventoryLine(string $sku, int $quantity): array
    {
        $product = InventoryProduct::withoutPracticeScope()
            ->where('practice_id', $this->practice->id)
            ->where('sku', $sku)
            ->firstOrFail();

        return [
            'line_type' => CheckoutLine::TYPE_INVENTORY,
            'inventory_product_id' => $product->id,
            'quantity' => $quantity,
        ];
    }

    private function request(string $baseName, string $status, string $preferredTimes, string $note): void
    {
        AppointmentRequest::withoutPracticeScope()->create([
            'practice_id' => $this->practice->id,
            'patient_id' => $this->patients[$baseName]->id,
            'token_hash' => hash('sha256', Str::random(64)),
            'status' => $status,
            'preferred_times' => $preferredTimes,
            'note' => self::MARKER.' '.$note,
            'submitted_at' => $status === AppointmentRequest::STATUS_PENDING ? now()->subMinutes(30) : now()->subDays(2),
        ]);
    }

    private function freshRequestLink(string $baseName): void
    {
        [$request, $token] = AppointmentRequest::createLinkFor($this->patients[$baseName]);

        $this->requestLinks[] = [
            'patient' => $this->patients[$baseName]->name,
            'url' => $this->baseUrl.'/appointment-request/'.$token,
            'request_id' => $request->id,
        ];
    }

    private function priceList(): array
    {
        return [
            'Initial Acupuncture Consultation + Treatment' => ['145.00', 'Comprehensive first acupuncture visit.'],
            'Follow-Up Acupuncture Treatment' => ['95.00', 'Standard acupuncture follow-up.'],
            'Five Element Acupuncture Treatment' => ['110.00', 'Worsley/Classical Five Element treatment.'],
            'Extended Acupuncture Session' => ['135.00', 'Extended treatment session.'],
            'Herbal Consultation' => ['65.00', 'Focused herbal consult.'],
            'Moxa / Adjunctive Treatment' => ['45.00', 'Adjunctive moxa or supportive care.'],
            'Cupping Add-on' => ['35.00', 'Cupping add-on.'],
            'Massage Therapy 30 min' => ['55.00', 'Focused massage therapy session.'],
            'Massage Therapy 60 min' => ['95.00', 'Standard massage therapy session.'],
            'Massage Therapy 75 min' => ['120.00', 'Longer massage therapy session.'],
            'Massage Therapy 90 min' => ['145.00', 'Extended massage therapy session.'],
            'Therapeutic Bodywork Follow-Up' => ['105.00', 'Bodywork follow-up.'],
            'Wellness Consultation' => ['85.00', 'General wellness consultation.'],
            'Follow-Up Wellness Visit' => ['75.00', 'General wellness follow-up.'],
        ];
    }

    private function fiveElementDetails(Encounter $encounter): array
    {
        return [
            'encounter_id' => $encounter->id,
            'five_elements' => ['Water', 'Earth'],
            'csor_color' => 'Blue-black cast around eyes observed gently',
            'csor_sound' => 'Groaning tone under stress',
            'csor_odor' => 'Mild scorched quality noted',
            'csor_emotion' => 'Fear with effort to stay composed',
            'pulse_before_treatment' => 'K --, Sp --, Ht -, PC -; St ++, GB ++.',
            'pulse_after_treatment' => 'K +, Sp =, Ht =, PC =; St +, GB +. Overall more even.',
            'pulse_change_interpretation' => 'Pulses became more harmonious; K and Sp improved; GB remained relatively strong.',
            'points_used' => 'AE check clear. IV 3, III 60, VII 40, moxa gently. Consider Entry-Exit if block signs persist.',
            'meridians' => 'Roman IV Kidney, Roman III Bladder, Roman VII Gallbladder, Roman VIII Liver.',
            'treatment_protocol' => 'Support Water official; preserve warmth and movement. No invented CF beyond observed Water tendency.',
            'session_notes' => 'CF / Causative Factor and Officials discussed as observation, not fixed conclusion.',
        ];
    }

    private function tcmDetails(Encounter $encounter): array
    {
        return [
            'encounter_id' => $encounter->id,
            'tcm_diagnosis' => 'Liver qi constraint with upper jiao tension; mild spleen qi deficiency tendency.',
            'tongue_body' => 'Pale sides, slight scalloping',
            'tongue_coating' => 'Thin white coat',
            'pulse_quality' => 'Wiry in left guan, slightly weak in right guan',
            'zang_fu_diagnosis' => 'Liver overacting on spleen pattern tendency',
            'points_used' => 'LI4, LV3, GB20, GB21, ST36',
            'meridians' => 'Liver, Gallbladder, Large Intestine, Stomach',
            'treatment_protocol' => 'Move liver qi, release neck/shoulder tension, support qi.',
            'needle_count' => 10,
            'session_notes' => 'Patient reported easier neck rotation after treatment.',
        ];
    }

    private function fiveElementNote(): string
    {
        return 'Patient reports long-standing fatigue with increased fearfulness and low back coldness. CSOE suggests Water imbalance. Pulses pre: K --, B -, Ht -, PC -; St ++, GB ++. Treatment focused on restoring movement and warmth. Moxa used gently. Pulses post more even; K improved to +. Concepts reviewed: Officials, CF / Causative Factor, AE, Entry-Exit blocks, Husband-Wife treatment, source and horary points when clinically appropriate.';
    }

    private function tcmNote(): string
    {
        return 'Patient reports neck and shoulder tension worsened by stress. Liver qi constraint with upper jiao tension and mild spleen qi deficiency tendency. Treatment included LI4, LV3, GB20, GB21, ST36. Tongue slightly pale with thin coat; pulse wiry in left guan. Patient reported easier neck rotation after treatment.';
    }

    private function massageNote(): string
    {
        return 'Client reports bilateral upper trapezius tension and low back stiffness after prolonged sitting. Moderate pressure tolerated. Focused work to cervical paraspinals, upper traps, QL, and glutes. Range of motion improved after session. Recommended hydration, gentle stretching, and maintenance care plan.';
    }

    private function splitName(string $name): array
    {
        $parts = explode(' ', $name, 2);

        return [$parts[0], $parts[1] ?? 'Patient'];
    }

    private function report(string $userEmail): void
    {
        if (! $this->command) {
            return;
        }

        $patientCount = Patient::withoutPracticeScope()->where('practice_id', $this->practice->id)->where('notes', 'like', '%'.self::MARKER.'%')->count();
        $pendingRequests = AppointmentRequest::withoutPracticeScope()->where('practice_id', $this->practice->id)->where('status', AppointmentRequest::STATUS_PENDING)->count();

        $this->command->info('Realistic practice demo seed complete.');
        $this->command->line("Practice: {$this->practice->name} (ID {$this->practice->id})");
        $this->command->line("Login user: {$userEmail}");
        $this->command->line("Documentation mode: use Practice Settings -> Documentation & Billing Mode to switch between Simple Visit Note and SOAP / Insurance modes.");
        $this->command->line("Patients created: {$patientCount}");
        $this->command->line('Service fees created/updated: '.count($this->serviceFees));
        $this->command->line('Appointment types created/updated: '.count($this->appointmentTypes));
        $this->command->line('Pending appointment requests visible on Today: '.$pendingRequests);
        $this->command->line('Normal seeded patient email: '.self::TEST_EMAIL.'; No Email Demo Patient intentionally has no email.');
        $this->command->line('');
        $this->command->line('Fresh appointment request links:');

        foreach ($this->requestLinks as $link) {
            $this->command->line("- {$link['patient']}: {$link['url']}");
        }

        $this->command->line('');
        $this->command->line('Start with Today, Calendar, Follow-Up, Visits, and Checkout to test the full workflow.');
    }
}
