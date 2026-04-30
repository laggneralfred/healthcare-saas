<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\AppointmentRequest;
use App\Models\AppointmentType;
use App\Models\CheckoutLine;
use App\Models\CheckoutPayment;
use App\Models\CheckoutSession;
use App\Models\Encounter;
use App\Models\MessageLog;
use App\Models\Patient;
use App\Models\PatientCommunication;
use App\Models\PatientCommunicationPreference;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\ServiceFee;
use App\Models\States\Appointment\Cancelled;
use App\Models\States\Appointment\Completed;
use App\Models\States\Appointment\NoShow;
use App\Models\States\Appointment\Scheduled;
use App\Models\States\CheckoutSession\Open;
use App\Models\User;
use App\Support\PracticeAccessRoles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LocalFollowUpWorkflowSeeder extends Seeder
{
    public const PRACTICE_NAME = 'Local Follow-Up Test Practice';
    public const ADMIN_EMAIL = 'followup-admin@practiq.local';
    public const PRACTITIONER_EMAIL = 'followup-practitioner@practiq.local';
    public const PASSWORD = 'password';
    public const TEST_EMAIL = 'laggneralfred@gmail.com';

    private Practice $practice;
    private User $admin;
    private Practitioner $practitioner;
    private AppointmentType $followUpType;
    private array $appointmentTypes = [];
    private array $serviceFees = [];
    private array $patients = [];
    private array $requestLinks = [];

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new \RuntimeException('LocalFollowUpWorkflowSeeder is for local/dev/demo environments only.');
        }

        DB::transaction(function (): void {
            $this->setupPracticeAndUsers();
            $this->clearExistingLocalWorkflowData();
            $this->seedServiceFees();
            $this->setupAppointmentTypes();
            $this->seedPatients();
            $this->seedCareStatusScenarios();
            $this->seedAppointmentRequests();
        });

        $this->report();
    }

    private function setupPracticeAndUsers(): void
    {
        PracticeAccessRoles::ensureRoles();

        $this->practice = Practice::query()->updateOrCreate(
            ['name' => self::PRACTICE_NAME],
            [
                'slug' => 'local-follow-up-test-practice',
                'timezone' => 'America/Los_Angeles',
                'is_active' => true,
                'is_demo' => false,
                'trial_ends_at' => now()->addYear(),
                'setup_completed_at' => now(),
                'default_appointment_duration' => 45,
                'default_reminder_hours' => 24,
                'insurance_billing_enabled' => false,
            ],
        );

        $this->admin = User::query()->updateOrCreate(
            ['email' => self::ADMIN_EMAIL],
            [
                'name' => 'Follow-Up Local Admin',
                'password' => Hash::make(self::PASSWORD),
                'practice_id' => $this->practice->id,
            ],
        );
        PracticeAccessRoles::assignOwner($this->admin);

        $practitionerUser = User::query()->updateOrCreate(
            ['email' => self::PRACTITIONER_EMAIL],
            [
                'name' => 'Dr. Local Follow-Up',
                'password' => Hash::make(self::PASSWORD),
                'practice_id' => $this->practice->id,
            ],
        );
        $practitionerUser->assignRole(User::ROLE_PRACTITIONER);

        $this->practitioner = Practitioner::withoutPracticeScope()->updateOrCreate(
            [
                'practice_id' => $this->practice->id,
                'user_id' => $practitionerUser->id,
            ],
            [
                'specialty' => 'Acupuncture',
                'clinical_style' => 'acupuncture',
                'license_number' => 'LOCAL-FOLLOW-UP',
                'is_active' => true,
            ],
        );
    }

    private function clearExistingLocalWorkflowData(): void
    {
        $checkoutSessionIds = CheckoutSession::withoutPracticeScope()
            ->where('practice_id', $this->practice->id)
            ->pluck('id');

        CheckoutPayment::withoutPracticeScope()
            ->whereIn('checkout_session_id', $checkoutSessionIds)
            ->delete();
        CheckoutLine::withoutPracticeScope()
            ->whereIn('checkout_session_id', $checkoutSessionIds)
            ->delete();
        CheckoutSession::withoutPracticeScope()
            ->where('practice_id', $this->practice->id)
            ->delete();

        AppointmentRequest::withoutPracticeScope()->where('practice_id', $this->practice->id)->delete();
        MessageLog::withoutPracticeScope()->where('practice_id', $this->practice->id)->delete();
        PatientCommunication::withoutPracticeScope()->where('practice_id', $this->practice->id)->delete();
        PatientCommunicationPreference::withoutPracticeScope()->where('practice_id', $this->practice->id)->delete();
        Encounter::withoutPracticeScope()->where('practice_id', $this->practice->id)->delete();
        Appointment::withoutPracticeScope()->where('practice_id', $this->practice->id)->delete();
        Patient::withoutPracticeScope()->where('practice_id', $this->practice->id)->delete();
    }

    private function seedServiceFees(): void
    {
        $fees = [
            'Initial Acupuncture Visit' => ['125.00', 'Longer first visit for intake, assessment, and treatment.'],
            'Follow-Up Acupuncture Visit' => ['95.00', 'Standard follow-up acupuncture treatment.'],
            'Five Element Acupuncture Treatment' => ['110.00', 'Five Element acupuncture treatment visit.'],
            'Herbal Consultation' => ['65.00', 'Focused herbal consultation or formula review.'],
            'Moxa / Adjunctive Treatment' => ['45.00', 'Adjunctive moxa or supportive treatment.'],
            'Cupping Add-on' => ['35.00', 'Cupping added to a visit when appropriate.'],
            'Wellness Consultation' => ['85.00', 'General wellness consultation visit.'],
        ];

        foreach ($fees as $name => [$price, $description]) {
            $this->serviceFees[$name] = ServiceFee::withoutPracticeScope()->updateOrCreate(
                [
                    'practice_id' => $this->practice->id,
                    'name' => $name,
                ],
                [
                    'short_description' => $description,
                    'default_price' => $price,
                    'is_active' => true,
                ],
            );
        }
    }

    private function setupAppointmentTypes(): void
    {
        $types = [
            'initial_acupuncture' => ['Initial Acupuncture Visit', 75, 'Initial Acupuncture Visit'],
            'follow_up_acupuncture' => ['Follow-Up Acupuncture Visit', 45, 'Follow-Up Acupuncture Visit'],
            'five_element' => ['Five Element Acupuncture Treatment', 60, 'Five Element Acupuncture Treatment'],
            'herbal_consultation' => ['Herbal Consultation', 30, 'Herbal Consultation'],
            'wellness_consultation' => ['Wellness Consultation', 45, 'Wellness Consultation'],
            'local_follow_up' => ['Local Follow-Up Visit', 45, 'Follow-Up Acupuncture Visit'],
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
                    'default_service_fee_id' => $this->serviceFees[$feeName]->id,
                ],
            );
        }

        $this->followUpType = $this->appointmentTypes['local_follow_up'];
    }

    private function seedPatients(): void
    {
        $patients = [
            ['New Patient', Patient::LANGUAGE_ENGLISH, self::TEST_EMAIL],
            ['Active Future Appointment Patient', Patient::LANGUAGE_ENGLISH, self::TEST_EMAIL],
            ['Active Recent Visit Patient', Patient::LANGUAGE_ENGLISH, self::TEST_EMAIL],
            ['Needs Follow-Up Patient', Patient::LANGUAGE_ENGLISH, self::TEST_EMAIL],
            ['Cooling Patient', Patient::LANGUAGE_ENGLISH, self::TEST_EMAIL],
            ['Inactive Patient', Patient::LANGUAGE_ENGLISH, self::TEST_EMAIL],
            ['At Risk Cancelled Patient', Patient::LANGUAGE_ENGLISH, self::TEST_EMAIL],
            ['At Risk No-Show Patient', Patient::LANGUAGE_ENGLISH, self::TEST_EMAIL],
            ['English Followup Patient', Patient::LANGUAGE_ENGLISH, self::TEST_EMAIL],
            ['Spanish Followup Patient', Patient::LANGUAGE_SPANISH, self::TEST_EMAIL],
            ['Chinese Translation Patient', Patient::LANGUAGE_CHINESE, self::TEST_EMAIL],
            ['Vietnamese Translation Patient', Patient::LANGUAGE_VIETNAMESE, self::TEST_EMAIL],
            ['French Translation Patient', Patient::LANGUAGE_FRENCH, self::TEST_EMAIL],
            ['German Translation Patient', Patient::LANGUAGE_GERMAN, self::TEST_EMAIL],
            ['Other Language Patient', Patient::LANGUAGE_OTHER, self::TEST_EMAIL],
            ['No Email Test Patient', Patient::LANGUAGE_ENGLISH, null],
            ['Opted Out Test Patient', Patient::LANGUAGE_ENGLISH, self::TEST_EMAIL],
            ['Mobile Visit Note Patient', Patient::LANGUAGE_ENGLISH, self::TEST_EMAIL],
            ['Checkout Service Fee Patient', Patient::LANGUAGE_ENGLISH, self::TEST_EMAIL],
            ['Five Element Fee Patient', Patient::LANGUAGE_ENGLISH, self::TEST_EMAIL],
        ];

        foreach ($patients as [$name, $language, $email]) {
            [$firstName, $lastName] = $this->splitName($name);

            $patient = Patient::withoutPracticeScope()->create([
                'practice_id' => $this->practice->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'name' => $name,
                'email' => $email,
                'phone' => '(555) 010-1234',
                'dob' => now()->subYears(42)->toDateString(),
                'gender' => 'Not specified',
                'preferred_language' => $language,
                'notes' => 'Local follow-up workflow seed patient. Fake data for testing only.',
                'is_patient' => true,
            ]);

            $this->patients[$name] = $patient;

            PatientCommunicationPreference::withoutPracticeScope()->create([
                'practice_id' => $this->practice->id,
                'patient_id' => $patient->id,
                'email_opt_in' => $name !== 'Opted Out Test Patient',
                'sms_opt_in' => false,
                'preferred_channel' => 'email',
                'opted_out_at' => $name === 'Opted Out Test Patient' ? now() : null,
            ]);
        }
    }

    private function seedCareStatusScenarios(): void
    {
        $this->createFutureAppointment('Active Future Appointment Patient', daysFromNow: 1);
        $this->createCompletedVisit('Active Recent Visit Patient', daysAgo: 10, note: 'Maintenance care for neck tension and sleep support.');
        $this->createCompletedVisit('Needs Follow-Up Patient', daysAgo: 35, note: 'Patient reported stress-related neck tension improving with care.');
        $this->createCompletedVisit('Cooling Patient', daysAgo: 60, note: 'Low back stiffness improved; patient planned maintenance follow-up.');
        $this->createCompletedVisit('Inactive Patient', daysAgo: 120, note: 'Last visit focused on low back stiffness and sleep concerns.');
        $this->createRiskAppointment('At Risk Cancelled Patient', Cancelled::$name, daysAgo: 5, note: 'Cancelled a follow-up for stress and neck tension.');
        $this->createRiskAppointment('At Risk No-Show Patient', NoShow::$name, daysAgo: 4, note: 'No-showed for maintenance care visit.');

        $this->createCompletedVisit('English Followup Patient', daysAgo: 35, note: 'Neck tension better after last treatment; follow-up recommended.');
        $this->createCompletedVisit('Spanish Followup Patient', daysAgo: 40, note: 'Stress-related neck tension; patient may benefit from a gentle invite back.');
        $this->createCompletedVisit('Chinese Translation Patient', daysAgo: 50, note: 'Sleep concerns and upper back tension discussed at last visit.');
        $this->createCompletedVisit('Vietnamese Translation Patient', daysAgo: 55, note: 'Low back stiffness and stress symptoms improved with care.');
        $this->createCompletedVisit('French Translation Patient', daysAgo: 65, note: 'Maintenance care plan for neck and shoulder tension.');
        $this->createCompletedVisit('German Translation Patient', daysAgo: 70, note: 'Follow-up recommended for low back stiffness and sleep support.');
        $this->createCompletedVisit('Other Language Patient', daysAgo: 75, note: 'Patient may benefit from maintenance care follow-up.');
        $this->createCompletedVisit('No Email Test Patient', daysAgo: 38, note: 'Eligible follow-up patient without email on file.');
        $this->createCompletedVisit('Opted Out Test Patient', daysAgo: 39, note: 'Eligible follow-up patient with email opt-out enabled.');
        $this->createCheckoutReadyVisit(
            'Checkout Service Fee Patient',
            $this->appointmentTypes['follow_up_acupuncture'],
            'Checkout-ready follow-up visit with a seeded default service fee.'
        );
        $this->createCheckoutReadyVisit(
            'Five Element Fee Patient',
            $this->appointmentTypes['five_element'],
            'Five Element checkout-ready visit with a seeded default service fee.'
        );

        $appointment = $this->createFutureAppointment('Mobile Visit Note Patient', daysFromNow: 0, hour: 15);
        Encounter::withoutPracticeScope()->create([
            'practice_id' => $this->practice->id,
            'patient_id' => $appointment->patient_id,
            'appointment_id' => $appointment->id,
            'practitioner_id' => $this->practitioner->id,
            'status' => 'draft',
            'visit_date' => now($this->practice->timezone)->toDateString(),
            'discipline' => 'acupuncture',
            'chief_complaint' => 'Neck tension and stress',
            'visit_notes' => "Patient reports neck tension and stress.\n\nDictation-friendly test note for mobile visit note workflow.",
        ]);
    }

    private function seedAppointmentRequests(): void
    {
        $this->createSubmittedRequest('English Followup Patient', AppointmentRequest::STATUS_PENDING, 'Tuesday morning or Thursday after 2', 'Prefers the same practitioner if possible.');
        $this->createSubmittedRequest('Spanish Followup Patient', AppointmentRequest::STATUS_PENDING, 'Viernes por la tarde o lunes por la mañana', 'Prefiere una cita tranquila.');
        $this->createSubmittedRequest('Chinese Translation Patient', AppointmentRequest::STATUS_CONTACTED, 'Any afternoon next week', 'Staff called and left a voicemail.');
        $this->createSubmittedRequest('French Translation Patient', AppointmentRequest::STATUS_SCHEDULED, 'Wednesday after 3', 'Scheduled manually by staff.');
        $this->createSubmittedRequest('German Translation Patient', AppointmentRequest::STATUS_DISMISSED, 'No preference', 'Dismissed during local workflow testing.');

        $this->createFreshRequestLink('English Followup Patient');
        $this->createFreshRequestLink('Spanish Followup Patient');
    }

    private function createCompletedVisit(string $patientName, int $daysAgo, string $note, ?AppointmentType $appointmentType = null): Encounter
    {
        $patient = $this->patients[$patientName];
        $appointmentType ??= $this->followUpType;
        $start = now($this->practice->timezone)->subDays($daysAgo)->setTime(10, 0);

        $appointment = Appointment::withoutPracticeScope()->create([
            'practice_id' => $this->practice->id,
            'patient_id' => $patient->id,
            'practitioner_id' => $this->practitioner->id,
            'appointment_type_id' => $appointmentType->id,
            'status' => Completed::$name,
            'start_datetime' => $start,
            'end_datetime' => $start->copy()->addMinutes($appointmentType->duration_minutes),
            'notes' => $note,
        ]);

        return Encounter::withoutPracticeScope()->create([
            'practice_id' => $this->practice->id,
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'practitioner_id' => $this->practitioner->id,
            'status' => 'complete',
            'visit_date' => $start->toDateString(),
            'completed_on' => $start->copy()->addMinutes(50),
            'discipline' => 'acupuncture',
            'chief_complaint' => 'Wellness follow-up',
            'subjective' => 'Patient reports stress, sleep concerns, and neck or low back tension.',
            'objective' => 'Gentle maintenance care visit. No red flags in this fake local demo record.',
            'assessment' => 'Responding to supportive care; follow-up may help maintain progress.',
            'plan' => 'Recommend gentle follow-up and home care as appropriate.',
            'visit_notes' => $note,
        ]);
    }

    private function createCheckoutReadyVisit(string $patientName, AppointmentType $appointmentType, string $note): void
    {
        $encounter = $this->createCompletedVisit($patientName, daysAgo: 1, note: $note, appointmentType: $appointmentType);
        $appointment = $encounter->appointment()->with('appointmentType.defaultServiceFee')->firstOrFail();
        $serviceFee = $appointment->appointmentType?->defaultServiceFee;

        $checkout = CheckoutSession::withoutPracticeScope()->create([
            'practice_id' => $this->practice->id,
            'appointment_id' => $appointment->id,
            'encounter_id' => $encounter->id,
            'patient_id' => $encounter->patient_id,
            'practitioner_id' => $encounter->practitioner_id,
            'state' => Open::$name,
            'charge_label' => $appointmentType->name,
            'notes' => 'Local demo checkout session with seeded service fee line.',
        ]);

        if ($serviceFee) {
            CheckoutLine::withoutPracticeScope()->create([
                'checkout_session_id' => $checkout->id,
                'practice_id' => $this->practice->id,
                'sequence' => 1,
                'line_type' => CheckoutLine::TYPE_SERVICE,
                'service_fee_id' => $serviceFee->id,
            ]);
        }
    }

    private function createFutureAppointment(string $patientName, int $daysFromNow, int $hour = 11): Appointment
    {
        $patient = $this->patients[$patientName];
        $start = now($this->practice->timezone)->addDays($daysFromNow)->setTime($hour, 0);

        return Appointment::withoutPracticeScope()->create([
            'practice_id' => $this->practice->id,
            'patient_id' => $patient->id,
            'practitioner_id' => $this->practitioner->id,
            'appointment_type_id' => $this->followUpType->id,
            'status' => Scheduled::$name,
            'start_datetime' => $start,
            'end_datetime' => $start->copy()->addMinutes(45),
            'notes' => 'Local demo appointment for schedule care-status and language badge testing.',
        ]);
    }

    private function createRiskAppointment(string $patientName, string $status, int $daysAgo, string $note): void
    {
        $patient = $this->patients[$patientName];
        $start = now($this->practice->timezone)->subDays($daysAgo)->setTime(13, 0);

        Appointment::withoutPracticeScope()->create([
            'practice_id' => $this->practice->id,
            'patient_id' => $patient->id,
            'practitioner_id' => $this->practitioner->id,
            'appointment_type_id' => $this->followUpType->id,
            'status' => $status,
            'start_datetime' => $start,
            'end_datetime' => $start->copy()->addMinutes(45),
            'notes' => $note,
        ]);
    }

    private function createSubmittedRequest(string $patientName, string $status, string $preferredTimes, ?string $note = null): AppointmentRequest
    {
        $patient = $this->patients[$patientName];

        return AppointmentRequest::withoutPracticeScope()->create([
            'practice_id' => $this->practice->id,
            'patient_id' => $patient->id,
            'token_hash' => hash('sha256', Str::random(64)),
            'status' => $status,
            'preferred_times' => $preferredTimes,
            'note' => $note,
            'submitted_at' => now()->subMinutes(15),
        ]);
    }

    private function createFreshRequestLink(string $patientName): void
    {
        [$request, $token] = AppointmentRequest::createLinkFor($this->patients[$patientName]);

        $this->requestLinks[] = [
            'patient' => $patientName,
            'url' => rtrim((string) config('app.url'), '/') . '/appointment-request/' . $token,
        ];
    }

    private function splitName(string $name): array
    {
        $parts = explode(' ', $name, 2);

        return [$parts[0], $parts[1] ?? 'Patient'];
    }

    private function report(): void
    {
        if (! $this->command) {
            return;
        }

        $this->command->info('Local Follow-Up workflow seed complete.');
        $this->command->line('Practice: ' . self::PRACTICE_NAME);
        $this->command->line('Login: ' . self::ADMIN_EMAIL . ' / ' . self::PASSWORD);
        $this->command->line('Seeded patient email: ' . self::TEST_EMAIL . ' (except No Email Test Patient)');
        $this->command->line('');
        $this->command->line('Fresh appointment request links for public form testing:');

        foreach ($this->requestLinks as $link) {
            $this->command->line('- ' . $link['patient'] . ': ' . $link['url']);
        }

        $this->command->line('');
        $this->command->line('Today shows only pending requests: English Followup Patient and Spanish Followup Patient.');
    }
}
