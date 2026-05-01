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
use App\Models\ConsentRecord;
use App\Models\Encounter;
use App\Models\InventoryProduct;
use App\Models\MedicalHistory;
use App\Models\MessageTemplate;
use App\Models\Patient;
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
use App\Support\PracticeAccessRoles;
use App\Support\PracticeType;
use Illuminate\Console\Command;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class IslandMassageAcupunctureSeeder extends Seeder
{
    public const MARKER = 'ISLAND_MASSAGE_ACUPUNCTURE_SEED';
    public const PRACTICE_NAME = 'Island Massage and Acupuncture';
    public const PATIENT_PREFIX = 'Island Demo - ';
    public const TEMPLATE_PREFIX = 'Island Demo - ';
    public const PASSWORD = 'PractiqLocalTest!2026';

    private Practice $practice;
    private \App\Models\User $admin;
    private Practitioner $fiveElementPractitioner;
    private Practitioner $massagePractitioner;
    private array $patients = [];
    private array $serviceFees = [];
    private array $appointmentTypes = [];
    private array $requestLinks = [];
    private string $patientEmail = 'laggneralfred@gmail.com';
    private string $baseUrl = 'https://app.practiqapp.com';

    public function run(
        string $adminEmail = 'maria-demo@practiq.local',
        string $patientEmail = 'laggneralfred@gmail.com',
        string $baseUrl = 'https://app.practiqapp.com',
        bool $resetDemoData = false,
        bool $demoMode = false,
    ): int {
        $this->patientEmail = $patientEmail;
        $this->baseUrl = rtrim($baseUrl, '/');

        DB::transaction(function () use ($adminEmail, $resetDemoData, $demoMode): void {
            PracticeAccessRoles::ensureRoles();

            $this->practice = Practice::query()->updateOrCreate(
                ['name' => self::PRACTICE_NAME],
                [
                    'slug' => 'island-massage-and-acupuncture',
                    'timezone' => 'America/Los_Angeles',
                    'is_active' => true,
                    'is_demo' => $demoMode,
                    'discipline' => 'acupuncture',
                    'practice_type' => PracticeType::FIVE_ELEMENT_ACUPUNCTURE,
                    'insurance_billing_enabled' => false,
                    'setup_completed_at' => now(),
                ],
            );

            PracticePaymentMethod::ensureDefaultsForPractice($this->practice);

            $this->admin = \App\Models\User::query()->updateOrCreate(
                ['email' => $adminEmail],
                [
                    'name' => 'Maria Cook',
                    'password' => Hash::make(self::PASSWORD),
                    'practice_id' => $this->practice->id,
                ],
            );
            PracticeAccessRoles::assignOwner($this->admin);
            $this->admin->assignRole(\App\Models\User::ROLE_PRACTITIONER);

            if ($resetDemoData) {
                $this->clearExistingDemoData();
            } else {
                $this->clearExistingDemoData();
            }

            $this->seedPractitioners();
            $this->seedServiceFees();
            $this->seedAppointmentTypes();
            $this->seedInventory();
            $this->seedTemplatesAndRules();
            $this->seedPatients();
            $this->seedHistoriesAndForms();
            $this->seedPatientScenarios();
            $this->seedCheckoutScenarios();
            $this->seedAppointmentRequests();
        });

        $this->report($adminEmail, $demoMode);

        return Command::SUCCESS;
    }

    private function clearExistingDemoData(): void
    {
        $patientIds = Patient::withoutPracticeScope()
            ->where('practice_id', $this->practice->id)
            ->where(function ($query): void {
                $query->where('name', 'like', self::PATIENT_PREFIX.'%')
                    ->orWhere('notes', 'like', '%'.self::MARKER.'%');
            })
            ->pluck('id');

        $appointmentIds = Appointment::withoutPracticeScope()->where('practice_id', $this->practice->id)->whereIn('patient_id', $patientIds)->pluck('id');
        $encounterIds = Encounter::withoutPracticeScope()->where('practice_id', $this->practice->id)->whereIn('patient_id', $patientIds)->pluck('id');
        $checkoutIds = CheckoutSession::withoutPracticeScope()
            ->where('practice_id', $this->practice->id)
            ->where(fn ($query) => $query->whereIn('patient_id', $patientIds)->orWhere('notes', 'like', '%'.self::MARKER.'%'))
            ->pluck('id');

        CheckoutPayment::withoutPracticeScope()->whereIn('checkout_session_id', $checkoutIds)->delete();
        CheckoutLine::withoutPracticeScope()->whereIn('checkout_session_id', $checkoutIds)->delete();
        CheckoutSession::withoutPracticeScope()->whereIn('id', $checkoutIds)->delete();
        AcupunctureEncounter::query()->whereIn('encounter_id', $encounterIds)->delete();
        MedicalHistory::withoutPracticeScope()->whereIn('patient_id', $patientIds)->delete();
        ConsentRecord::withoutPracticeScope()->whereIn('patient_id', $patientIds)->delete();
        AppointmentRequest::withoutPracticeScope()->where('practice_id', $this->practice->id)->whereIn('patient_id', $patientIds)->delete();
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
            ->where('sku', 'like', 'ISLAND-%')
            ->forceDelete();

        Practitioner::withoutPracticeScope()
            ->where('practice_id', $this->practice->id)
            ->where('license_number', 'like', 'ISLAND-DEMO-%')
            ->delete();
    }

    private function seedPractitioners(): void
    {
        $this->fiveElementPractitioner = Practitioner::withoutPracticeScope()->create([
            'practice_id' => $this->practice->id,
            'user_id' => $this->admin->id,
            'license_number' => 'ISLAND-DEMO-FIVE-ELEMENT',
            'specialty' => 'Five Element Acupuncture',
            'clinical_style' => PracticeType::FIVE_ELEMENT_ACUPUNCTURE,
            'is_active' => true,
        ]);

        $this->massagePractitioner = Practitioner::withoutPracticeScope()->create([
            'practice_id' => $this->practice->id,
            'user_id' => $this->admin->id,
            'license_number' => 'ISLAND-DEMO-MASSAGE',
            'specialty' => 'Massage Therapy',
            'clinical_style' => PracticeType::MASSAGE_THERAPY,
            'is_active' => true,
        ]);
    }

    private function seedServiceFees(): void
    {
        foreach ($this->priceList() as $name => [$price, $description]) {
            $this->serviceFees[$name] = ServiceFee::withoutPracticeScope()->updateOrCreate(
                ['practice_id' => $this->practice->id, 'name' => $name],
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
            'initial_fe' => ['Initial Five Element Treatment', 90, 'Initial Five Element Consultation + Treatment'],
            'follow_up_fe' => ['Five Element Follow-Up', 60, 'Five Element Follow-Up Treatment'],
            'extended_fe' => ['Extended Five Element Treatment', 75, 'Extended Five Element Treatment'],
            'moxa' => ['Moxa / Adjunctive Treatment', 30, 'Moxa / Adjunctive Treatment'],
            'lifestyle' => ['Herbal or Lifestyle Consultation', 30, 'Herbal or Lifestyle Consultation'],
            'massage_30' => ['Massage Therapy 30 min', 30, 'Massage Therapy 30 min'],
            'massage_60' => ['Massage Therapy 60 min', 60, 'Massage Therapy 60 min'],
            'massage_75' => ['Massage Therapy 75 min', 75, 'Massage Therapy 75 min'],
            'massage_90' => ['Massage Therapy 90 min', 90, 'Massage Therapy 90 min'],
            'bodywork_follow_up' => ['Therapeutic Bodywork Follow-Up', 60, 'Therapeutic Bodywork Follow-Up'],
            'no_default_fee' => ['No Default Fee Island Demo Visit', 45, null],
        ];

        foreach ($types as $key => [$name, $duration, $feeName]) {
            $this->appointmentTypes[$key] = AppointmentType::withoutPracticeScope()->updateOrCreate(
                ['practice_id' => $this->practice->id, 'name' => $name],
                [
                    'duration_minutes' => $duration,
                    'is_active' => true,
                    'default_service_fee_id' => $feeName ? $this->serviceFees[$feeName]->id : null,
                ],
            );
        }
    }

    private function seedInventory(): void
    {
        foreach ([
            ['ISLAND-MOXA-ROLL', 'Moxa Roll Pack', 'Other', 'pack', 18.00, 18],
            ['ISLAND-HERB-CALM', 'Calm Evening Herbal Formula', 'Herbal Formula', 'bottle', 34.00, 10],
            ['ISLAND-EPSOM', 'Magnesium Bath Soak', 'Other', 'bag', 16.00, 14],
        ] as [$sku, $name, $category, $unit, $price, $stock]) {
            InventoryProduct::withoutPracticeScope()->create([
                'practice_id' => $this->practice->id,
                'name' => $name,
                'sku' => $sku,
                'description' => self::MARKER.' Fake Island demo inventory item.',
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

    private function seedTemplatesAndRules(): void
    {
        $templates = [
            ['Appointment reminder 48 hours', 'reminder_48h', 'Your visit at {{ practice_name }}', 'Hi {{ patient_name }}, this is a gentle reminder of your {{ appointment_type }} on {{ appointment_date }} at {{ appointment_time }}.', -2880],
            ['Appointment reminder 24 hours', 'reminder_24h', 'Reminder: your visit is tomorrow', 'Hi {{ patient_name }}, we look forward to seeing you tomorrow at {{ appointment_time }}.', -1440],
            ['Same-day reminder', 'custom', 'Today at {{ practice_name }}', 'Hi {{ patient_name }}, a same-day reminder that we will see you at {{ appointment_time }} today.', -180],
            ['Post-visit check-in', 'appointment_followup', 'Checking in after your visit', 'Hi {{ patient_name }}, we hope you are settling well after your visit.', 2880],
            ['Follow-up invitation', 'custom', 'Would you like to come back in?', 'Hi {{ patient_name }}, we were thinking of you and wanted to invite you back when care feels helpful.', 30240],
            ['Reactivation check-in', 'custom', 'Checking in from {{ practice_name }}', 'Hi {{ patient_name }}, it has been a while since we saw you. We are here when you are ready.', 86400],
            ['No-show check-in', 'missed_appointment', 'Sorry we missed you', 'Hi {{ patient_name }}, we missed you at your appointment. Please call when you are ready to find another time.', 60],
            ['Cancelled not rescheduled check-in', 'custom', 'Would another time help?', 'Hi {{ patient_name }}, we noticed your visit was cancelled and not yet rescheduled. Let us know if you would like help.', 1440],
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
        $languages = [
            Patient::LANGUAGE_ENGLISH,
            Patient::LANGUAGE_SPANISH,
            Patient::LANGUAGE_CHINESE,
            Patient::LANGUAGE_VIETNAMESE,
            Patient::LANGUAGE_FRENCH,
            Patient::LANGUAGE_GERMAN,
            Patient::LANGUAGE_OTHER,
        ];

        for ($i = 1; $i <= 20; $i++) {
            $baseName = $i === 19 ? 'Opted Out Five Element Patient' : sprintf('Five Element Patient %02d', $i);
            $this->createPatient($baseName, 'five_element', $languages[($i - 1) % count($languages)], $this->patientEmail);
        }

        for ($i = 1; $i <= 20; $i++) {
            $baseName = $i === 20 ? 'No Email Massage Patient' : sprintf('Massage Patient %02d', $i);
            $this->createPatient($baseName, 'massage', $languages[($i + 2) % count($languages)], $i === 20 ? null : $this->patientEmail);
        }
    }

    private function createPatient(string $baseName, string $track, string $language, ?string $email): Patient
    {
        $name = self::PATIENT_PREFIX.$baseName;
        [$firstName, $lastName] = $this->splitName($name);

        $patient = Patient::withoutPracticeScope()->create([
            'practice_id' => $this->practice->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => $name,
            'email' => $email,
            'phone' => '(555) 019-'.str_pad((string) (100 + count($this->patients)), 4, '0', STR_PAD_LEFT),
            'dob' => now()->subYears(31 + (count($this->patients) % 27))->toDateString(),
            'gender' => 'Not specified',
            'preferred_language' => $language,
            'address_line_1' => (200 + count($this->patients)).' Island Demo Lane',
            'city' => 'Friday Harbor',
            'state' => 'WA',
            'postal_code' => '98250',
            'country' => 'USA',
            'occupation' => $track === 'massage' ? 'Desk-based worker' : 'Small business owner',
            'notes' => self::MARKER." Fake {$track} demo patient. Do not use for real clinical care.",
            'is_patient' => true,
        ]);

        $this->patients[$baseName] = $patient;

        PatientCommunicationPreference::withoutPracticeScope()->create([
            'practice_id' => $this->practice->id,
            'patient_id' => $patient->id,
            'email_opt_in' => $baseName !== 'Opted Out Five Element Patient',
            'sms_opt_in' => false,
            'preferred_channel' => 'email',
            'opted_out_at' => $baseName === 'Opted Out Five Element Patient' ? now() : null,
        ]);

        return $patient;
    }

    private function seedHistoriesAndForms(): void
    {
        foreach ($this->patients as $baseName => $patient) {
            $isMassage = str_contains($baseName, 'Massage');
            $appointment = null;

            if (in_array($baseName, ['Massage Patient 06', 'Five Element Patient 06'], true)) {
                $appointment = $this->appointment($baseName, $isMassage ? $this->massagePractitioner : $this->fiveElementPractitioner, $isMassage ? 'massage_60' : 'follow_up_fe', Scheduled::$name, now()->setTime(15, 0), 'Missing forms demo.');
            }

            $status = $appointment ? 'missing' : 'complete';

            MedicalHistory::withoutPracticeScope()->create([
                'practice_id' => $this->practice->id,
                'patient_id' => $patient->id,
                'appointment_id' => $appointment?->id,
                'practitioner_id' => $isMassage ? $this->massagePractitioner->id : $this->fiveElementPractitioner->id,
                'status' => $status,
                'submitted_on' => $status === 'complete' ? now()->subDays(21) : null,
                'discipline' => $isMassage ? 'massage' : 'acupuncture',
                'reason_for_visit' => $isMassage ? 'Neck and shoulder tension with low back stiffness.' : 'Stress, sleep concerns, fatigue, and low back coldness.',
                'current_concerns' => $isMassage ? 'Desk posture strain, stress-related holding, and reduced neck range of motion.' : 'Fatigue, sleep disruption, digestive sensitivity, and tension during stress.',
                'relevant_history' => 'Fake Island demo history with no major red flags.',
                'chief_complaint' => $isMassage ? 'Upper back and low back tension' : 'Fatigue and stress response',
                'onset_duration' => 'Several months',
                'onset_type' => 'gradual',
                'aggravating_factors' => 'Stress, prolonged sitting, and poor sleep.',
                'relieving_factors' => 'Warmth, gentle movement, massage, acupuncture, and rest.',
                'pain_scale' => $isMassage ? 5 : 3,
                'sleep_quality' => 'Variable',
                'sleep_hours' => 6,
                'stress_level' => 'Moderate',
                'diet_description' => 'Generally balanced with some digestive sensitivity.',
                'has_pacemaker' => false,
                'takes_blood_thinners' => false,
                'has_bleeding_disorder' => false,
                'has_infectious_disease' => false,
                'is_pregnant' => false,
                'discipline_responses' => $isMassage
                    ? ['massage' => ['pressure' => 'Moderate pressure preferred', 'areas' => 'Neck, shoulders, QL, glutes, hamstrings']]
                    : ['five_element' => ['cf' => 'Water observed gently over time', 'csoe' => 'CSOE noted without fixing a conclusion']],
                'consent_given' => $status === 'complete',
                'consent_signed_at' => $status === 'complete' ? now()->subDays(21) : null,
                'consent_signed_by' => $status === 'complete' ? $patient->name : null,
                'notes' => self::MARKER.' Fake intake record for Island demo.',
                'summary_text' => $isMassage ? 'Bodywork intake: posture strain, neck tension, low back stiffness.' : 'Five Element intake: stress, fatigue, sleep concerns, no major red flags.',
            ]);

            if ($appointment) {
                ConsentRecord::withoutPracticeScope()->create([
                    'practice_id' => $this->practice->id,
                    'patient_id' => $patient->id,
                    'appointment_id' => $appointment->id,
                    'status' => 'missing',
                ]);
            }
        }
    }

    private function seedPatientScenarios(): void
    {
        $today = now($this->practice->timezone);

        $this->appointment('Five Element Patient 01', $this->fiveElementPractitioner, 'initial_fe', Scheduled::$name, $today->copy()->setTime(9, 0), 'New Five Element patient today.');
        $this->appointment('Five Element Patient 02', $this->fiveElementPractitioner, 'follow_up_fe', Scheduled::$name, $today->copy()->addDays(3)->setTime(10, 0), 'Active future appointment.');
        $this->completedEncounter('Five Element Patient 03', $this->fiveElementPractitioner, 'follow_up_fe', $today->copy()->subDays(10), $this->fiveElementNote(), true);
        $this->completedEncounter('Five Element Patient 04', $this->fiveElementPractitioner, 'follow_up_fe', $today->copy()->subDays(35), $this->fiveElementNote(), true);
        $this->completedEncounter('Five Element Patient 05', $this->fiveElementPractitioner, 'follow_up_fe', $today->copy()->subDays(60), $this->fiveElementNote(), true);
        $this->completedEncounter('Five Element Patient 07', $this->fiveElementPractitioner, 'follow_up_fe', $today->copy()->subDays(120), $this->fiveElementNote(), true);
        $this->appointment('Five Element Patient 08', $this->fiveElementPractitioner, 'follow_up_fe', Cancelled::$name, $today->copy()->subDays(5)->setTime(11, 0), 'Cancelled not rescheduled.');
        $this->appointment('Five Element Patient 09', $this->fiveElementPractitioner, 'follow_up_fe', NoShow::$name, $today->copy()->subDays(3)->setTime(13, 0), 'No-show appointment.');
        $this->appointment('Five Element Patient 10', $this->fiveElementPractitioner, 'follow_up_fe', InProgress::$name, $today->copy()->setTime(11, 0), 'In-progress Five Element visit.');
        $this->appointment('Five Element Patient 11', $this->fiveElementPractitioner, 'follow_up_fe', Checkout::$name, $today->copy()->setTime(13, 0), 'Ready for checkout.');
        $this->appointment('Five Element Patient 12', $this->fiveElementPractitioner, 'extended_fe', Closed::$name, $today->copy()->subDays(1)->setTime(14, 0), 'Closed Five Element visit.');
        $this->completedEncounter('Opted Out Five Element Patient', $this->fiveElementPractitioner, 'follow_up_fe', $today->copy()->subDays(38), $this->fiveElementNote(), true);

        $this->appointment('Massage Patient 01', $this->massagePractitioner, 'massage_60', Scheduled::$name, $today->copy()->setTime(10, 30), 'New massage patient today.');
        $this->appointment('Massage Patient 02', $this->massagePractitioner, 'massage_90', Scheduled::$name, $today->copy()->addDays(4)->setTime(15, 0), 'Active future massage appointment.');
        $this->completedEncounter('Massage Patient 03', $this->massagePractitioner, 'massage_60', $today->copy()->subDays(9), $this->massageNote(), false, 'massage');
        $this->completedEncounter('Massage Patient 04', $this->massagePractitioner, 'bodywork_follow_up', $today->copy()->subDays(33), $this->massageNote(), false, 'massage');
        $this->completedEncounter('Massage Patient 05', $this->massagePractitioner, 'massage_75', $today->copy()->subDays(58), $this->massageNote(), false, 'massage');
        $this->completedEncounter('Massage Patient 07', $this->massagePractitioner, 'massage_60', $today->copy()->subDays(118), $this->massageNote(), false, 'massage');
        $this->appointment('Massage Patient 08', $this->massagePractitioner, 'massage_60', Cancelled::$name, $today->copy()->subDays(4)->setTime(15, 0), 'Massage cancellation.');
        $this->appointment('Massage Patient 09', $this->massagePractitioner, 'massage_60', NoShow::$name, $today->copy()->subDays(2)->setTime(16, 0), 'Massage no-show.');
        $this->appointment('Massage Patient 10', $this->massagePractitioner, 'massage_90', Checkout::$name, $today->copy()->setTime(14, 30), 'Massage ready for checkout.');
        $this->completedEncounter('No Email Massage Patient', $this->massagePractitioner, 'massage_60', $today->copy()->subDays(40), $this->massageNote(), false, 'massage');

        foreach (['Five Element Patient 13', 'Five Element Patient 14', 'Five Element Patient 15', 'Five Element Patient 16', 'Five Element Patient 17', 'Five Element Patient 18'] as $index => $baseName) {
            $this->completedEncounter($baseName, $this->fiveElementPractitioner, 'follow_up_fe', $today->copy()->subDays(20 + ($index * 7)), $this->fiveElementNote(), true);
        }

        foreach (['Massage Patient 11', 'Massage Patient 12', 'Massage Patient 13', 'Massage Patient 14', 'Massage Patient 15', 'Massage Patient 16', 'Massage Patient 17', 'Massage Patient 18', 'Massage Patient 19'] as $index => $baseName) {
            $this->completedEncounter($baseName, $this->massagePractitioner, 'massage_60', $today->copy()->subDays(18 + ($index * 6)), $this->massageNote(), false, 'massage');
        }
    }

    private function seedCheckoutScenarios(): void
    {
        $this->checkoutFor('Five Element Patient 11', $this->fiveElementPractitioner, 'follow_up_fe', [$this->serviceLine('Five Element Follow-Up Treatment')]);
        $this->checkoutFor('Five Element Patient 12', $this->fiveElementPractitioner, 'extended_fe', [$this->serviceLine('Extended Five Element Treatment')], paid: true);
        $this->checkoutFor('Five Element Patient 13', $this->fiveElementPractitioner, 'moxa', [$this->serviceLine('Moxa / Adjunctive Treatment'), $this->inventoryLine('ISLAND-MOXA-ROLL', 1)]);
        $this->checkoutFor('Massage Patient 10', $this->massagePractitioner, 'massage_90', [$this->serviceLine('Massage Therapy 90 min')]);
        $this->checkoutFor('Massage Patient 11', $this->massagePractitioner, 'massage_60', [$this->serviceLine('Massage Therapy 60 min')], partialAmount: 45.00);
        $this->checkoutFor('Massage Patient 12', $this->massagePractitioner, 'bodywork_follow_up', [$this->serviceLine('Therapeutic Bodywork Follow-Up')], paid: true);
        $this->checkoutFor('Massage Patient 13', $this->massagePractitioner, 'no_default_fee', []);
    }

    private function seedAppointmentRequests(): void
    {
        $this->request('Five Element Patient 04', AppointmentRequest::STATUS_PENDING, 'Tuesday morning or Thursday after 2', 'Would like Five Element follow-up.');
        $this->request('Massage Patient 04', AppointmentRequest::STATUS_PENDING, 'Friday afternoon or Monday morning', 'Prefers massage after work.');
        $this->request('Five Element Patient 05', AppointmentRequest::STATUS_CONTACTED, 'Any afternoon next week', 'Staff left voicemail.');
        $this->request('Massage Patient 05', AppointmentRequest::STATUS_SCHEDULED, 'Wednesday after 3', 'Scheduled manually.');
        $this->request('Massage Patient 09', AppointmentRequest::STATUS_DISMISSED, 'No preference', 'Dismissed for demo history.');

        $this->freshRequestLink('Five Element Patient 04');
        $this->freshRequestLink('Massage Patient 04');
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
            'subjective' => 'Fake demo visit with no real patient data.',
            'objective' => $discipline === 'massage' ? 'Moderate pressure tolerated; range of motion improved slightly.' : 'Five Element observation documented without over-concluding.',
            'assessment' => $discipline === 'massage' ? 'Responded well to bodywork.' : 'Five Element treatment focused on restoring harmony and warmth.',
            'plan' => 'Follow up as clinically appropriate.',
            'visit_notes' => self::MARKER."\n".$note,
        ]);

        if ($discipline === 'acupuncture') {
            AcupunctureEncounter::query()->create($this->fiveElementDetails($encounter));
        }

        return $encounter;
    }

    private function checkoutFor(string $baseName, Practitioner $practitioner, string $typeKey, array $lines, bool $paid = false, ?float $partialAmount = null): CheckoutSession
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
            'visit_notes' => self::MARKER.' Completed Island demo visit for checkout testing.',
        ]);

        $checkout = CheckoutSession::withoutPracticeScope()->create([
            'practice_id' => $this->practice->id,
            'appointment_id' => $appointment->id,
            'encounter_id' => $encounter->id,
            'patient_id' => $this->patients[$baseName]->id,
            'practitioner_id' => $practitioner->id,
            'state' => Open::$name,
            'charge_label' => $this->appointmentTypes[$typeKey]->name,
            'notes' => self::MARKER.' Island checkout demo for '.$baseName,
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
                'payment_method' => CheckoutPayment::METHOD_CASH,
                'paid_at' => now()->subHours(2),
                'reference' => 'ISLAND-PARTIAL',
                'notes' => self::MARKER.' Partial payment demo.',
                'created_by_user_id' => $this->admin->id,
            ]);
        }

        if ($paid && (float) $checkout->amount_due > 0) {
            CheckoutPayment::withoutPracticeScope()->create([
                'practice_id' => $this->practice->id,
                'checkout_session_id' => $checkout->id,
                'amount' => $checkout->amount_due,
                'payment_method' => CheckoutPayment::METHOD_CARD_EXTERNAL,
                'paid_at' => now()->subHours(1),
                'reference' => 'ISLAND-PAID',
                'notes' => self::MARKER.' Paid checkout demo.',
                'created_by_user_id' => $this->admin->id,
            ]);
        }

        return $checkout->refresh();
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
            'submitted_at' => $status === AppointmentRequest::STATUS_PENDING ? now()->subMinutes(25) : now()->subDays(2),
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
        $product = InventoryProduct::withoutPracticeScope()->where('practice_id', $this->practice->id)->where('sku', $sku)->firstOrFail();

        return [
            'line_type' => CheckoutLine::TYPE_INVENTORY,
            'inventory_product_id' => $product->id,
            'quantity' => $quantity,
        ];
    }

    private function priceList(): array
    {
        return [
            'Initial Five Element Consultation + Treatment' => ['150.00', 'Initial Worsley/Classical Five Element visit.'],
            'Five Element Follow-Up Treatment' => ['110.00', 'Five Element follow-up treatment.'],
            'Extended Five Element Treatment' => ['135.00', 'Extended Five Element treatment session.'],
            'Moxa / Adjunctive Treatment' => ['45.00', 'Adjunctive moxa/supportive treatment.'],
            'Herbal or Lifestyle Consultation' => ['65.00', 'Focused herbal or lifestyle consult.'],
            'Massage Therapy 30 min' => ['55.00', 'Focused massage therapy session.'],
            'Massage Therapy 60 min' => ['95.00', 'Standard massage therapy session.'],
            'Massage Therapy 75 min' => ['120.00', 'Longer massage therapy session.'],
            'Massage Therapy 90 min' => ['145.00', 'Extended massage therapy session.'],
            'Therapeutic Bodywork Follow-Up' => ['105.00', 'Therapeutic bodywork follow-up.'],
        ];
    }

    private function fiveElementDetails(Encounter $encounter): array
    {
        return [
            'encounter_id' => $encounter->id,
            'five_elements' => ['Water', 'Earth'],
            'csor_color' => 'Blue-black cast observed gently',
            'csor_sound' => 'Groaning tone under stress',
            'csor_odor' => 'Mild scorched quality noted',
            'csor_emotion' => 'Fear with effort to stay composed',
            'pulse_before_treatment' => 'K --, Sp --, Ht -, PC -; St ++, GB ++.',
            'pulse_after_treatment' => 'K +, Sp =, Ht =, PC =; St +, GB +. Overall more even.',
            'pulse_change_interpretation' => 'Pulses became more harmonious; K and Sp improved; GB remained relatively strong.',
            'points_used' => 'AE clear. Roman IV 3, Roman III 60, Roman VII 40. Moxa used gently.',
            'meridians' => 'Roman IV Kidney, Roman III Bladder, Roman VII Gallbladder, Roman VIII Liver.',
            'treatment_protocol' => 'Support Water official; consider Entry-Exit or Husband-Wife treatment only if signs are present.',
            'session_notes' => 'CF / Causative Factor, Officials, CSOE, AE, and pulse movement documented as observations.',
        ];
    }

    private function fiveElementNote(): string
    {
        return 'Patient reports fatigue, low back coldness, and increased fearfulness during stress. CSOE suggests Water imbalance. Pulses pre: K --, B -, Ht -, PC -; St ++, GB ++. Treatment focused on restoring warmth and movement. Moxa used gently. Pulses post more even; K improved to +. Practitioner considered Officials, CF, AE, Entry-Exit blocks, Husband-Wife treatment, Akabane testing, command/source/horary points, and tonification/sedation points without inventing findings.';
    }

    private function massageNote(): string
    {
        return 'Client reports bilateral upper trapezius tension and low back stiffness after prolonged sitting. Moderate pressure tolerated. Focused work to cervical paraspinals, upper traps, QL, glutes, and hamstrings. Neck rotation improved slightly after session. Recommended hydration, gentle stretching, and maintenance care.';
    }

    private function splitName(string $name): array
    {
        $parts = explode(' ', $name, 2);

        return [$parts[0], $parts[1] ?? 'Patient'];
    }

    private function report(string $adminEmail, bool $demoMode): void
    {
        if (! $this->command) {
            return;
        }

        $patientCount = Patient::withoutPracticeScope()->where('practice_id', $this->practice->id)->where('notes', 'like', '%'.self::MARKER.'%')->count();
        $pendingRequests = AppointmentRequest::withoutPracticeScope()->where('practice_id', $this->practice->id)->where('status', AppointmentRequest::STATUS_PENDING)->count();

        $this->command->info('Island Massage and Acupuncture seed complete.');
        $this->command->line("Practice: {$this->practice->name} (ID {$this->practice->id})");
        $this->command->line("Demo mode: ".($demoMode ? 'enabled' : 'disabled / live-like'));
        $this->command->line("Login: {$adminEmail}");
        $this->command->line('Password: '.self::PASSWORD);
        $this->command->line("Patients created: {$patientCount}");
        $this->command->line('Practitioner records: Maria Cook — Five Element Acupuncture; Maria Cook — Massage Therapy');
        $this->command->line('Service fees created/updated: '.count($this->serviceFees));
        $this->command->line('Appointment types created/updated: '.count($this->appointmentTypes));
        $this->command->line('Pending appointment requests visible on Today: '.$pendingRequests);
        $this->command->line('Normal seeded patient email: '.$this->patientEmail);
        $this->command->line('');
        $this->command->line('Fresh appointment request links:');

        foreach ($this->requestLinks as $link) {
            $this->command->line("- {$link['patient']}: {$link['url']}");
        }

        $this->command->line('');
        $this->command->line('Start with Today, Calendar, Follow-Up, Visits, and Checkout.');
    }
}
