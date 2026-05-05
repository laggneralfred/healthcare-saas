<?php

namespace Tests\Feature;

use App\Filament\Pages\FrontDeskDashboard;
use App\Filament\Resources\CheckoutSessions\CheckoutSessionResource;
use App\Filament\Resources\Encounters\EncounterResource;
use App\Mail\BookingConfirmationMail;
use App\Models\Appointment;
use App\Models\AppointmentRequest;
use App\Models\AppointmentType;
use App\Models\CheckoutPayment;
use App\Models\CheckoutSession;
use App\Models\ConsentRecord;
use App\Models\Encounter;
use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\States\Appointment\Checkout;
use App\Models\States\Appointment\Completed;
use App\Models\States\Appointment\InProgress;
use App\Models\States\Appointment\Scheduled;
use App\Models\States\CheckoutSession\Paid;
use App\Models\User;
use App\Support\PracticeAccessRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class FrontDeskDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        PracticeAccessRoles::ensureRoles();
        $this->travelTo('2026-04-27 10:00:00');
    }

    public function test_front_desk_dashboard_filters_today_flow_to_current_practice(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();
        $otherPractice = Practice::factory()->create(['timezone' => 'UTC']);

        $todayPatient = Patient::factory()->create([
            'practice_id' => $practice->id,
            'first_name' => 'Current',
            'last_name' => 'Patient',
            'preferred_language' => 'es',
        ]);
        $otherPatient = Patient::factory()->create([
            'practice_id' => $otherPractice->id,
            'first_name' => 'Other',
            'last_name' => 'Practice',
        ]);

        $this->appointmentFor($practice, $todayPatient, [
            'start_datetime' => now()->setTime(11, 0),
        ]);
        $this->appointmentFor($otherPractice, $otherPatient, [
            'start_datetime' => now()->setTime(11, 30),
        ]);

        $this->actingAs($admin);

        Livewire::test(FrontDeskDashboard::class)
            ->assertSee('Today’s Schedule')
            ->assertSee('Current Patient')
            ->assertSee('Care Status: Active')
            ->assertSee('Spanish')
            ->assertDontSee('Other Practice');
    }

    public function test_front_desk_dashboard_shows_pending_appointment_requests(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();
        $patient = Patient::factory()->create([
            'practice_id' => $practice->id,
            'first_name' => 'Nora',
            'last_name' => 'Request',
            'name' => 'Nora Request',
        ]);

        AppointmentRequest::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'token_hash' => hash('sha256', 'front-desk-token'),
            'status' => AppointmentRequest::STATUS_PENDING,
            'preferred_times' => 'Tuesday morning or Thursday after 2',
            'note' => 'Prefers late afternoon if possible.',
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin);

        Livewire::test(FrontDeskDashboard::class)
            ->assertSee('Appointment Requests')
            ->assertSee('These patients requested a follow-up. Review their preferences and schedule manually.')
            ->assertSee('Nora Request')
            ->assertSee('Tuesday morning or Thursday after 2')
            ->assertSee('Prefers late afternoon if possible.')
            ->assertSee('Appointment requests')
            ->assertSee('View Request')
            ->assertSee('Mark Contacted')
            ->assertSee('Mark Scheduled')
            ->assertSee('Dismiss')
            ->assertSee('Create Appointment');
    }

    public function test_front_desk_appointment_request_status_actions_hide_request_from_pending_list(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();
        $contactedPatient = Patient::factory()->create(['practice_id' => $practice->id, 'first_name' => 'Cora', 'last_name' => 'Contacted']);
        $scheduledPatient = Patient::factory()->create(['practice_id' => $practice->id, 'first_name' => 'Sam', 'last_name' => 'Scheduled']);
        $dismissedPatient = Patient::factory()->create(['practice_id' => $practice->id, 'first_name' => 'Dana', 'last_name' => 'Dismissed']);

        $contacted = $this->appointmentRequestFor($practice, $contactedPatient, 'Cora wants Wednesday');
        $scheduled = $this->appointmentRequestFor($practice, $scheduledPatient, 'Sam wants Thursday');
        $dismissed = $this->appointmentRequestFor($practice, $dismissedPatient, 'Dana wants Friday');

        $this->actingAs($admin);

        $component = Livewire::test(FrontDeskDashboard::class)
            ->assertSee('Cora Contacted')
            ->assertSee('Sam Scheduled')
            ->assertSee('Dana Dismissed');

        $component
            ->call('markAppointmentRequestContacted', $contacted->id)
            ->call('markAppointmentRequestScheduled', $scheduled->id)
            ->call('dismissAppointmentRequest', $dismissed->id)
            ->assertDontSee('Cora wants Wednesday')
            ->assertDontSee('Sam wants Thursday')
            ->assertDontSee('Dana wants Friday');

        $this->assertSame(AppointmentRequest::STATUS_CONTACTED, $contacted->refresh()->status);
        $this->assertSame(AppointmentRequest::STATUS_SCHEDULED, $scheduled->refresh()->status);
        $this->assertSame(AppointmentRequest::STATUS_DISMISSED, $dismissed->refresh()->status);
    }

    public function test_front_desk_appointment_request_actions_are_practice_scoped(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();
        $otherPractice = Practice::factory()->create(['timezone' => 'UTC']);
        $otherPatient = Patient::factory()->create(['practice_id' => $otherPractice->id]);
        $otherRequest = $this->appointmentRequestFor($otherPractice, $otherPatient, 'Other practice request');

        $this->actingAs($admin);

        Livewire::test(FrontDeskDashboard::class)
            ->assertDontSee('Other practice request')
            ->call('markAppointmentRequestScheduled', $otherRequest->id);

        $this->assertSame(AppointmentRequest::STATUS_PENDING, $otherRequest->refresh()->status);
    }

    public function test_front_desk_create_appointment_link_prefills_request_patient(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);
        $request = $this->appointmentRequestFor($practice, $patient, 'Any afternoon');

        $this->actingAs($admin);

        $url = Livewire::test(FrontDeskDashboard::class)
            ->instance()
            ->createAppointmentUrl($request);

        $this->assertStringContainsString('/appointments/create', $url);
        $this->assertStringContainsString('appointment_request_id=' . $request->id, $url);
        $this->assertStringContainsString('patient_id=' . $patient->id, $url);
    }

    public function test_front_desk_dashboard_is_available_to_operations_roles_not_practitioners(): void
    {
        [$practice, $administrator] = $this->practiceWithAdmin();
        $owner = User::factory()->create(['practice_id' => $practice->id]);
        $owner->assignRole(User::ROLE_OWNER);
        $practitionerUser = User::factory()->create(['practice_id' => $practice->id]);
        $practitionerUser->assignRole(User::ROLE_PRACTITIONER);

        $this->actingAs($administrator);
        $this->assertTrue(FrontDeskDashboard::canAccess());

        $this->actingAs($owner);
        $this->assertTrue(FrontDeskDashboard::canAccess());

        $this->actingAs($practitionerUser);
        $this->assertFalse(FrontDeskDashboard::canAccess());
    }

    public function test_front_desk_dashboard_shows_demo_mode_notice_for_demo_practice_only(): void
    {
        [$demoPractice, $demoAdmin] = $this->practiceWithAdmin();
        $demoPractice->update(['is_demo' => true]);

        $this->actingAs($demoAdmin);

        Livewire::test(FrontDeskDashboard::class)
            ->assertSee('Demo Mode')
            ->assertSee('this practice uses seeded test data')
            ->assertSee('Some payment, reminder, and reset behavior may differ from a live practice.');

        [$livePractice, $liveAdmin] = $this->practiceWithAdmin();

        $this->actingAs($liveAdmin);

        Livewire::test(FrontDeskDashboard::class)
            ->assertDontSee('Demo Mode')
            ->assertDontSee('this practice uses seeded test data');
    }

    public function test_front_desk_dashboard_surfaces_arrivals_intake_and_checkout_work(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();

        $waitingPatient = Patient::factory()->create([
            'practice_id' => $practice->id,
            'first_name' => 'Waiting',
            'last_name' => 'Patient',
        ]);
        $formsPatient = Patient::factory()->create([
            'practice_id' => $practice->id,
            'first_name' => 'Forms',
            'last_name' => 'Patient',
        ]);
        $checkoutPatient = Patient::factory()->create([
            'practice_id' => $practice->id,
            'first_name' => 'Checkout',
            'last_name' => 'Patient',
        ]);
        $resendPatient = Patient::factory()->create([
            'practice_id' => $practice->id,
            'first_name' => 'Resend',
            'last_name' => 'Patient',
            'email' => 'resend@example.test',
        ]);
        $tomorrowPatient = Patient::factory()->create([
            'practice_id' => $practice->id,
            'first_name' => 'Tomorrow',
            'last_name' => 'Patient',
        ]);

        $this->appointmentFor($practice, $waitingPatient, [
            'status' => InProgress::$name,
            'start_datetime' => now()->setTime(10, 30),
        ]);
        $this->appointmentFor($practice, $formsPatient, [
            'start_datetime' => now()->setTime(12, 0),
        ]);
        $this->appointmentFor($practice, $checkoutPatient, [
            'start_datetime' => now()->setTime(13, 0),
        ]);
        CheckoutSession::factory()->open()->create([
            'practice_id' => $practice->id,
            'appointment_id' => null,
            'patient_id' => $checkoutPatient->id,
            'charge_label' => 'Visit',
        ]);
        $resendAppointment = $this->appointmentFor($practice, $resendPatient, [
            'start_datetime' => now()->setTime(14, 0),
        ]);
        MedicalHistory::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $resendPatient->id,
            'appointment_id' => $resendAppointment->id,
            'status' => 'missing',
        ]);
        ConsentRecord::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $resendPatient->id,
            'appointment_id' => $resendAppointment->id,
            'status' => 'missing',
        ]);
        $this->appointmentFor($practice, $tomorrowPatient, [
            'status' => Checkout::$name,
            'start_datetime' => now()->addDay()->setTime(9, 0),
        ]);

        $this->actingAs($admin);

        Livewire::test(FrontDeskDashboard::class)
            ->assertSee('Alerts')
            ->assertSee('Arrivals / Waiting')
            ->assertSee('Waiting Patient')
            ->assertSee('Intake & Forms', false)
            ->assertSee('Forms Patient')
            ->assertSee('Missing Intake, Consent')
            ->assertSee('Start Visit')
            ->assertSee('Resend Intake Link')
            ->assertSee('Ready for Checkout')
            ->assertSee('Checkout Patient')
            ->assertSee('Collect Payment');
    }

    public function test_front_desk_patient_search_is_practice_scoped(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();
        $otherPractice = Practice::factory()->create(['timezone' => 'UTC']);

        Patient::factory()->create([
            'practice_id' => $practice->id,
            'first_name' => 'Jordan',
            'last_name' => 'Local',
        ]);
        Patient::factory()->create([
            'practice_id' => $otherPractice->id,
            'first_name' => 'Jordan',
            'last_name' => 'Elsewhere',
        ]);

        $this->actingAs($admin);

        Livewire::test(FrontDeskDashboard::class)
            ->set('patientSearch', 'Jordan')
            ->assertSee('Jordan Local')
            ->assertDontSee('Jordan Elsewhere');
    }

    public function test_front_desk_quick_actions_are_policy_checked_and_practice_scoped(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();
        $otherPractice = Practice::factory()->create(['timezone' => 'UTC']);

        $checkoutAppointment = $this->appointmentFor($practice, Patient::factory()->create(['practice_id' => $practice->id]), [
            'status' => Completed::$name,
            'start_datetime' => now()->setTime(11, 0),
        ]);
        $crossPracticeAppointment = $this->appointmentFor($otherPractice, Patient::factory()->create(['practice_id' => $otherPractice->id]), [
            'status' => Scheduled::$name,
            'start_datetime' => now()->setTime(12, 0),
        ]);

        $this->actingAs($admin);

        Livewire::test(FrontDeskDashboard::class)
            ->call('openCheckout', $checkoutAppointment->id)
            ->call('checkInAppointment', $crossPracticeAppointment->id);

        $this->assertInstanceOf(Checkout::class, $checkoutAppointment->fresh()->status);
        $this->assertInstanceOf(Scheduled::class, $crossPracticeAppointment->fresh()->status);
    }

    public function test_front_desk_ready_for_checkout_queue_shows_open_unpaid_sessions_by_practice(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();
        $otherPractice = Practice::factory()->create(['timezone' => 'UTC']);
        $appointmentPatient = Patient::factory()->create([
            'practice_id' => $practice->id,
            'first_name' => 'Appointment',
            'last_name' => 'Checkout',
        ]);
        $readyPatient = Patient::factory()->create([
            'practice_id' => $practice->id,
            'first_name' => 'Ready',
            'last_name' => 'Patient',
        ]);
        $paidPatient = Patient::factory()->create([
            'practice_id' => $practice->id,
            'first_name' => 'Paid',
            'last_name' => 'Patient',
        ]);
        $otherPatient = Patient::factory()->create([
            'practice_id' => $otherPractice->id,
            'first_name' => 'Other',
            'last_name' => 'Checkout',
        ]);

        $appointment = $this->appointmentFor($practice, $appointmentPatient, [
            'start_datetime' => now()->setTime(11, 0),
        ]);
        $appointmentCheckout = CheckoutSession::factory()->open()->create([
            'practice_id' => $practice->id,
            'appointment_id' => $appointment->id,
            'patient_id' => $appointmentPatient->id,
            'practitioner_id' => $appointment->practitioner_id,
            'charge_label' => 'Appointment visit',
            'created_at' => now()->subMinutes(10),
        ]);
        $ready = CheckoutSession::factory()->open()->create([
            'practice_id' => $practice->id,
            'appointment_id' => null,
            'patient_id' => $readyPatient->id,
            'charge_label' => 'Direct visit',
            'created_at' => now(),
        ]);
        CheckoutSession::factory()->create([
            'practice_id' => $practice->id,
            'appointment_id' => null,
            'patient_id' => $paidPatient->id,
            'state' => Paid::$name,
            'charge_label' => 'Paid visit',
        ]);
        CheckoutSession::factory()->open()->create([
            'practice_id' => $otherPractice->id,
            'appointment_id' => null,
            'patient_id' => $otherPatient->id,
            'charge_label' => 'Other visit',
        ]);

        $this->actingAs($admin);

        Livewire::test(FrontDeskDashboard::class)
            ->assertSee('Ready for Checkout')
            ->assertSee('Ready Patient')
            ->assertSee('Appointment Checkout')
            ->assertSeeInOrder(['Direct visit', 'Appointment visit'])
            ->assertSee('Collect Payment')
            ->assertSee(CheckoutSessionResource::getUrl('edit', ['record' => $ready]), false)
            ->assertSee(CheckoutSessionResource::getUrl('edit', ['record' => $appointmentCheckout]), false)
            ->assertDontSee('Paid visit')
            ->assertDontSee('Other visit')
            ->call('collectPayment', $ready->id)
            ->assertRedirect(CheckoutSessionResource::getUrl('edit', ['record' => $ready]));
    }

    public function test_front_desk_collect_payment_links_point_to_each_specific_checkout_session(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();

        $openCheckout = CheckoutSession::factory()->open()->create([
            'practice_id' => $practice->id,
            'appointment_id' => null,
            'patient_id' => Patient::factory()->create([
                'practice_id' => $practice->id,
                'first_name' => 'Checkout',
                'last_name' => 'Open',
            ])->id,
            'charge_label' => 'Open checkout visit',
            'amount_total' => 9500,
            'amount_paid' => 0,
            'created_at' => now()->subMinutes(3),
        ]);

        $partialCheckout = CheckoutSession::factory()->open()->create([
            'practice_id' => $practice->id,
            'appointment_id' => null,
            'patient_id' => Patient::factory()->create([
                'practice_id' => $practice->id,
                'first_name' => 'Checkout',
                'last_name' => 'Partial',
            ])->id,
            'charge_label' => 'Partial checkout visit',
            'amount_total' => 12000,
            'amount_paid' => 0,
            'created_at' => now()->subMinutes(2),
        ]);
        $partialCheckout->recordPayment([
            'amount' => 5000,
            'payment_method' => CheckoutPayment::METHOD_CASH,
            'paid_at' => now(),
        ]);

        $noDefaultFeeCheckout = CheckoutSession::factory()->open()->create([
            'practice_id' => $practice->id,
            'appointment_id' => null,
            'patient_id' => Patient::factory()->create([
                'practice_id' => $practice->id,
                'first_name' => 'Checkout',
                'last_name' => 'No Default Fee',
            ])->id,
            'charge_label' => 'No default fee visit',
            'amount_total' => 0,
            'amount_paid' => 0,
            'created_at' => now()->subMinute(),
        ]);

        $this->actingAs($admin);

        Livewire::test(FrontDeskDashboard::class)
            ->assertSee('Checkout Open')
            ->assertSee('Checkout Partial')
            ->assertSee('Checkout No Default Fee')
            ->assertSee(CheckoutSessionResource::getUrl('edit', ['record' => $openCheckout]), false)
            ->assertSee(CheckoutSessionResource::getUrl('edit', ['record' => $partialCheckout]), false)
            ->assertSee(CheckoutSessionResource::getUrl('edit', ['record' => $noDefaultFeeCheckout]), false);
    }

    public function test_front_desk_ready_for_checkout_queue_shows_all_open_sessions_newest_first(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();
        $otherPractice = Practice::factory()->create(['timezone' => 'UTC']);
        $labels = [];

        for ($i = 1; $i <= 11; $i++) {
            $patient = Patient::factory()->create([
                'practice_id' => $practice->id,
                'first_name' => 'Queue',
                'last_name' => "Patient {$i}",
            ]);
            $label = sprintf('Queue visit %02d', $i);
            $labels[] = $label;

            CheckoutSession::factory()->open()->create([
                'practice_id' => $practice->id,
                'appointment_id' => null,
                'patient_id' => $patient->id,
                'charge_label' => $label,
                'created_at' => now()->addMinutes($i),
            ]);
        }

        CheckoutSession::factory()->create([
            'practice_id' => $practice->id,
            'appointment_id' => null,
            'patient_id' => Patient::factory()->create(['practice_id' => $practice->id])->id,
            'state' => Paid::$name,
            'charge_label' => 'Paid queue visit',
            'created_at' => now()->addMinutes(20),
        ]);
        CheckoutSession::factory()->open()->create([
            'practice_id' => $otherPractice->id,
            'appointment_id' => null,
            'patient_id' => Patient::factory()->create(['practice_id' => $otherPractice->id])->id,
            'charge_label' => 'Other practice queue visit',
            'created_at' => now()->addMinutes(21),
        ]);

        $this->actingAs($admin);

        Livewire::test(FrontDeskDashboard::class)
            ->assertSee($labels)
            ->assertSeeInOrder(array_reverse($labels))
            ->assertDontSee('Paid queue visit')
            ->assertDontSee('Other practice queue visit');
    }

    public function test_front_desk_ready_for_checkout_excludes_checkout_after_full_payment(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();
        $patient = Patient::factory()->create([
            'practice_id' => $practice->id,
            'first_name' => 'Paid',
            'last_name' => 'After Payment',
        ]);
        $checkout = CheckoutSession::factory()->open()->create([
            'practice_id' => $practice->id,
            'appointment_id' => null,
            'patient_id' => $patient->id,
            'charge_label' => 'Paid after record payment',
            'amount_total' => 10000,
            'amount_paid' => 0,
        ]);

        $checkout->recordPayment([
            'amount' => 10000,
            'payment_method' => CheckoutPayment::METHOD_CASH,
            'paid_at' => now(),
        ]);

        $this->actingAs($admin);

        Livewire::test(FrontDeskDashboard::class)
            ->assertSee('Ready for Checkout')
            ->assertDontSee('Paid after record payment');
    }

    public function test_front_desk_ready_for_checkout_includes_partial_payment_checkout_with_balance(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();
        $patient = Patient::factory()->create([
            'practice_id' => $practice->id,
            'first_name' => 'Partial',
            'last_name' => 'Balance',
        ]);
        $checkout = CheckoutSession::factory()->open()->create([
            'practice_id' => $practice->id,
            'appointment_id' => null,
            'patient_id' => $patient->id,
            'charge_label' => 'Partial balance visit',
            'amount_total' => 10000,
            'amount_paid' => 0,
        ]);

        $checkout->recordPayment([
            'amount' => 4000,
            'payment_method' => CheckoutPayment::METHOD_CHECK,
            'paid_at' => now(),
        ]);

        $this->actingAs($admin);

        Livewire::test(FrontDeskDashboard::class)
            ->assertSee('Ready for Checkout')
            ->assertSee('Partial Balance')
            ->assertSee('Partial balance visit')
            ->assertSee('Collect Payment');
    }

    public function test_front_desk_collect_payment_only_opens_ready_checkout_sessions(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);
        $paid = CheckoutSession::factory()->create([
            'practice_id' => $practice->id,
            'appointment_id' => null,
            'patient_id' => $patient->id,
            'state' => Paid::$name,
            'charge_label' => 'Paid visit',
        ]);

        $this->actingAs($admin);

        Livewire::test(FrontDeskDashboard::class)
            ->call('collectPayment', $paid->id)
            ->assertNoRedirect();
    }

    public function test_front_desk_start_visit_creates_encounter_and_redirects_to_edit(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);
        $appointment = $this->appointmentFor($practice, $patient, [
            'status' => Scheduled::$name,
            'start_datetime' => now()->setTime(9, 0),
        ]);

        $this->actingAs($admin);

        $component = Livewire::test(FrontDeskDashboard::class)
            ->call('checkInAppointment', $appointment->id);

        $encounter = Encounter::query()->where('appointment_id', $appointment->id)->firstOrFail();

        $this->assertInstanceOf(InProgress::class, $appointment->fresh()->status);
        $this->assertSame($practice->id, $encounter->practice_id);
        $this->assertSame($patient->id, $encounter->patient_id);
        $this->assertSame($appointment->practitioner_id, $encounter->practitioner_id);
        $this->assertSame('draft', $encounter->status);
        $this->assertSame('2026-04-27', $encounter->visit_date->toDateString());

        $component->assertRedirect(EncounterResource::getUrl('edit', ['record' => $encounter]));
    }

    public function test_front_desk_start_visit_reuses_existing_encounter(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();
        $patient = Patient::factory()->create(['practice_id' => $practice->id]);
        $appointment = $this->appointmentFor($practice, $patient, [
            'status' => Scheduled::$name,
            'start_datetime' => now()->setTime(9, 0),
        ]);
        $encounter = Encounter::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'practitioner_id' => $appointment->practitioner_id,
            'status' => 'draft',
        ]);

        $this->actingAs($admin);

        Livewire::test(FrontDeskDashboard::class)
            ->call('markInProgress', $appointment->id)
            ->assertRedirect(EncounterResource::getUrl('edit', ['record' => $encounter]));

        $this->assertInstanceOf(InProgress::class, $appointment->fresh()->status);
        $this->assertSame(1, Encounter::query()->where('appointment_id', $appointment->id)->count());
    }

    public function test_front_desk_resends_existing_intake_link_flow_only_when_supported(): void
    {
        Mail::fake();

        [$practice, $admin] = $this->practiceWithAdmin();
        $patient = Patient::factory()->create([
            'practice_id' => $practice->id,
            'email' => 'patient@example.test',
        ]);
        $appointment = $this->appointmentFor($practice, $patient);
        $intake = MedicalHistory::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'status' => 'missing',
        ]);
        $consent = ConsentRecord::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'status' => 'missing',
        ]);
        $unsupportedAppointment = $this->appointmentFor($practice, Patient::factory()->create([
            'practice_id' => $practice->id,
            'email' => 'unsupported@example.test',
        ]));

        $this->actingAs($admin);

        Livewire::test(FrontDeskDashboard::class)
            ->call('resendIntakeLink', $appointment->id)
            ->call('resendIntakeLink', $unsupportedAppointment->id);

        Mail::assertSent(BookingConfirmationMail::class, function (BookingConfirmationMail $mail) use ($appointment, $intake, $consent): bool {
            return $mail->hasTo('patient@example.test')
                && $mail->appointment->is($appointment)
                && $mail->intake->is($intake)
                && $mail->consent->is($consent);
        });
        Mail::assertSent(BookingConfirmationMail::class, 1);
    }

    private function practiceWithAdmin(): array
    {
        $practice = Practice::factory()->create(['timezone' => 'UTC']);
        $admin = User::factory()->create(['practice_id' => $practice->id]);
        $admin->assignRole(User::ROLE_ADMINISTRATOR);

        return [$practice, $admin];
    }

    private function appointmentFor(Practice $practice, Patient $patient, array $attributes = []): Appointment
    {
        $practitioner = Practitioner::factory()->create(['practice_id' => $practice->id]);
        $appointmentType = AppointmentType::factory()->create(['practice_id' => $practice->id]);

        $appointment = Appointment::factory()->create(array_merge([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'appointment_type_id' => $appointmentType->id,
            'start_datetime' => now()->setTime(9, 0),
            'end_datetime' => now()->setTime(10, 0),
        ], $attributes));

        if (($attributes['forms_complete'] ?? false) === true) {
            MedicalHistory::factory()->create([
                'practice_id' => $practice->id,
                'patient_id' => $patient->id,
                'appointment_id' => $appointment->id,
                'status' => 'complete',
            ]);
            ConsentRecord::factory()->complete()->create([
                'practice_id' => $practice->id,
                'patient_id' => $patient->id,
                'appointment_id' => $appointment->id,
            ]);
        }

        return $appointment;
    }

    private function appointmentRequestFor(Practice $practice, Patient $patient, string $preferredTimes): AppointmentRequest
    {
        return AppointmentRequest::withoutPracticeScope()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'token_hash' => hash('sha256', $preferredTimes . $patient->id),
            'status' => AppointmentRequest::STATUS_PENDING,
            'preferred_times' => $preferredTimes,
            'submitted_at' => now(),
        ]);
    }
}
