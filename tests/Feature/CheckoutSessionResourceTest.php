<?php

namespace Tests\Feature;

use App\Filament\Resources\CheckoutSessions\CheckoutSessionResource;
use App\Filament\Resources\CheckoutSessions\Pages\CreateCheckoutSession;
use App\Filament\Resources\CheckoutSessions\Pages\EditCheckoutSession;
use App\Filament\Resources\CheckoutSessions\Pages\ViewSuperbill;
use App\Filament\Resources\PracticePaymentMethods\Pages\EditPracticePaymentMethod;
use App\Filament\Resources\PracticePaymentMethods\PracticePaymentMethodResource;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\CheckoutLine;
use App\Models\CheckoutPayment;
use App\Models\CheckoutSession;
use App\Models\Encounter;
use App\Models\InventoryProduct;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\PracticePaymentMethod;
use App\Models\Practitioner;
use App\Models\ServiceFee;
use App\Models\User;
use App\Support\PracticeAccessRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutSessionResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        PracticeAccessRoles::ensureRoles();
    }

    public function test_direct_visit_checkout_edit_shows_visit_context_and_preserves_encounter_link(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();
        [$patient, $practitioner] = $this->patientAndPractitioner($practice, 'Emma', 'Nakamura', 'Dr. Rivera');
        $encounter = Encounter::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'appointment_id' => null,
            'practitioner_id' => $practitioner->id,
            'visit_date' => '2026-04-28',
        ]);
        $checkout = CheckoutSession::factory()->open()->create([
            'practice_id' => $practice->id,
            'appointment_id' => null,
            'encounter_id' => $encounter->id,
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'charge_label' => 'Visit',
        ]);

        $this->actingAs($admin);

        Livewire::test(EditCheckoutSession::class, ['record' => $checkout->id])
            ->assertSee('Checkout For')
            ->assertSee('Direct Visit - Apr 28, 2026')
            ->assertSee('Emma Nakamura')
            ->assertSee('Dr. Rivera')
            ->assertDontSee('Appointment Patient')
            ->set('data.appointment_id', Appointment::factory()->create(['practice_id' => $practice->id])->id)
            ->set('data.charge_label', 'Updated visit')
            ->call('save');

        $checkout->refresh();

        $this->assertNull($checkout->appointment_id);
        $this->assertSame($encounter->id, $checkout->encounter_id);
        $this->assertSame($patient->id, $checkout->patient_id);
        $this->assertSame('Updated visit', $checkout->charge_label);
    }

    public function test_appointment_checkout_edit_shows_linked_appointment_context_and_preserves_appointment_link(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();
        [$patient, $practitioner] = $this->patientAndPractitioner($practice, 'Emma', 'Nakamura', 'Dr. Rivera');
        $appointment = $this->appointmentFor($practice, $patient, $practitioner, '2026-04-28 09:30:00');
        $otherPatient = Patient::factory()->create([
            'practice_id' => $practice->id,
            'first_name' => 'Other',
            'last_name' => 'Patient',
        ]);
        $otherAppointment = $this->appointmentFor($practice, $otherPatient, $practitioner, '2026-04-29 10:00:00');
        $checkout = CheckoutSession::factory()->open()->create([
            'practice_id' => $practice->id,
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'charge_label' => 'Acupuncture Visit',
        ]);

        $this->actingAs($admin);

        Livewire::test(EditCheckoutSession::class, ['record' => $checkout->id])
            ->assertSee('Checkout For')
            ->assertSee('Appointment Visit - Apr 28, 2026 9:30 AM')
            ->assertSee('Emma Nakamura')
            ->assertSee('Dr. Rivera')
            ->assertDontSee('Other Patient')
            ->set('data.appointment_id', $otherAppointment->id)
            ->set('data.patient_id', $otherPatient->id)
            ->set('data.charge_label', 'Updated appointment visit')
            ->call('save');

        $checkout->refresh();

        $this->assertSame($appointment->id, $checkout->appointment_id);
        $this->assertSame($patient->id, $checkout->patient_id);
        $this->assertSame('Updated appointment visit', $checkout->charge_label);
    }

    public function test_manual_checkout_create_uses_only_current_practice_appointments_and_sets_patient_from_appointment(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();
        $otherPractice = Practice::factory()->create();
        [$patient, $practitioner] = $this->patientAndPractitioner($practice, 'Manual', 'Patient', 'Dr. Manual');
        [$otherPatient, $otherPractitioner] = $this->patientAndPractitioner($otherPractice, 'Other', 'Practice', 'Dr. Elsewhere');
        $appointment = $this->appointmentFor($practice, $patient, $practitioner, '2026-04-28 11:00:00');
        $otherAppointment = $this->appointmentFor($otherPractice, $otherPatient, $otherPractitioner, '2026-04-28 12:00:00');

        $this->actingAs($admin);

        Livewire::test(CreateCheckoutSession::class)
            ->fillForm([
                'appointment_id' => $appointment->id,
                'charge_label' => 'Manual visit',
                'checkoutLines' => [
                    ['line_type' => CheckoutLine::TYPE_CUSTOM, 'description' => 'Visit', 'amount' => 0],
                ],
            ])
            ->call('create')
            ->assertHasNoErrors();

        $checkout = CheckoutSession::withoutPracticeScope()
            ->where('appointment_id', $appointment->id)
            ->firstOrFail();

        $this->assertSame($practice->id, $checkout->practice_id);
        $this->assertSame($patient->id, $checkout->patient_id);
        $this->assertSame($practitioner->id, $checkout->practitioner_id);

        Livewire::test(CreateCheckoutSession::class)
            ->fillForm([
                'appointment_id' => $otherAppointment->id,
                'charge_label' => 'Cross-practice visit',
                'checkoutLines' => [
                    ['line_type' => CheckoutLine::TYPE_CUSTOM, 'description' => 'Visit', 'amount' => 0],
                ],
            ])
            ->call('create')
            ->assertHasErrors(['data.appointment_id']);
    }

    public function test_manual_checkout_create_rejects_missing_appointment_server_side(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();

        $this->actingAs($admin);

        Livewire::test(CreateCheckoutSession::class)
            ->fillForm([
                'charge_label' => 'Manual visit',
                'checkoutLines' => [
                    ['line_type' => CheckoutLine::TYPE_CUSTOM, 'description' => 'Visit', 'amount' => 0],
                ],
            ])
            ->call('create')
            ->assertHasErrors(['data.appointment_id']);

        $this->assertSame(0, CheckoutSession::withoutPracticeScope()->where('practice_id', $practice->id)->count());
    }

    public function test_manual_checkout_create_saves_service_inventory_and_custom_lines(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();
        config(['services.stripe.addon_prices.inventory' => 'price_inventory_addon']);
        $subscription = $practice->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_inventory_checkout_lines',
            'stripe_status' => 'active',
            'stripe_price' => 'price_main_tier',
            'quantity' => 1,
        ]);
        $subscription->items()->create([
            'stripe_id' => 'si_inventory_checkout_lines',
            'stripe_product' => 'prod_inventory',
            'stripe_price' => 'price_inventory_addon',
            'quantity' => 1,
        ]);
        [$patient, $practitioner] = $this->patientAndPractitioner($practice, 'Manual', 'Patient', 'Dr. Manual');
        $appointment = $this->appointmentFor($practice, $patient, $practitioner, '2026-04-28 11:00:00');
        $serviceFee = ServiceFee::factory()->create([
            'practice_id' => $practice->id,
            'name' => 'Follow-up Treatment',
            'default_price' => 95,
            'is_active' => true,
        ]);
        $product = InventoryProduct::factory()->create([
            'practice_id' => $practice->id,
            'name' => 'Herbal Formula',
            'selling_price' => 30,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        Livewire::test(CreateCheckoutSession::class)
            ->fillForm([
                'appointment_id' => $appointment->id,
                'charge_label' => 'Manual visit',
                'checkoutLines' => [
                    [
                        'line_type' => CheckoutLine::TYPE_SERVICE,
                        'service_fee_id' => $serviceFee->id,
                        'description' => 'Follow-up Treatment',
                        'unit_price' => 95,
                        'amount' => 95,
                    ],
                    [
                        'line_type' => CheckoutLine::TYPE_INVENTORY,
                        'inventory_product_id' => $product->id,
                        'description' => 'Herbal Formula (x2)',
                        'quantity' => 2,
                        'unit_price' => 30,
                        'amount' => 60,
                    ],
                    [
                        'line_type' => CheckoutLine::TYPE_CUSTOM,
                        'description' => 'Manual supply',
                        'amount' => 15,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoErrors();

        $checkout = CheckoutSession::withoutPracticeScope()
            ->where('appointment_id', $appointment->id)
            ->firstOrFail();

        $this->assertEquals(170, $checkout->amount_total);
        $this->assertDatabaseHas('checkout_lines', [
            'checkout_session_id' => $checkout->id,
            'line_type' => CheckoutLine::TYPE_SERVICE,
            'service_fee_id' => $serviceFee->id,
            'amount' => 95,
        ]);
        $this->assertDatabaseHas('checkout_lines', [
            'checkout_session_id' => $checkout->id,
            'line_type' => CheckoutLine::TYPE_INVENTORY,
            'inventory_product_id' => $product->id,
            'quantity' => 2,
            'amount' => 60,
        ]);
        $this->assertDatabaseHas('checkout_lines', [
            'checkout_session_id' => $checkout->id,
            'line_type' => CheckoutLine::TYPE_CUSTOM,
            'description' => 'Manual supply',
            'amount' => 15,
        ]);
    }

    public function test_practitioner_cannot_access_checkout_edit_or_superbill_directly(): void
    {
        [$practice] = $this->practiceWithAdmin();
        [$patient, $practitioner] = $this->patientAndPractitioner($practice, 'Emma', 'Nakamura', 'Dr. Rivera');
        $practitioner->user->assignRole(User::ROLE_PRACTITIONER);
        $checkout = CheckoutSession::factory()->open()->create([
            'practice_id' => $practice->id,
            'appointment_id' => null,
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'charge_label' => 'Visit',
            'amount_total' => 10000,
        ]);

        $this->actingAs($practitioner->user);

        $this->assertFalse(CheckoutSessionResource::canEdit($checkout));
        $this->get(CheckoutSessionResource::getUrl('edit', ['record' => $checkout]))
            ->assertForbidden();
        $this->get(CheckoutSessionResource::getUrl('superbill', ['record' => $checkout]))
            ->assertForbidden();
    }

    public function test_owner_or_admin_can_access_checkout_and_record_payment(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();
        [$patient, $practitioner] = $this->patientAndPractitioner($practice, 'Emma', 'Nakamura', 'Dr. Rivera');
        $checkout = CheckoutSession::factory()->open()->create([
            'practice_id' => $practice->id,
            'appointment_id' => null,
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'charge_label' => 'Visit',
            'amount_total' => 10000,
            'amount_paid' => 0,
        ]);

        $this->actingAs($admin);

        $this->assertTrue(CheckoutSessionResource::canEdit($checkout));

        Livewire::test(EditCheckoutSession::class, ['record' => $checkout->id])
            ->callAction('recordPayment', [
                'amount' => 10000,
                'payment_method' => CheckoutPayment::METHOD_CASH,
                'paid_at' => now()->toDateTimeString(),
            ])
            ->assertHasNoActionErrors();

        $checkout->refresh();

        $this->assertSame(1, $checkout->checkoutPayments()->count());
        $this->assertSame(10000.0, (float) $checkout->amount_paid);
    }

    public function test_record_payment_action_only_shows_enabled_practice_payment_methods(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();
        [$patient, $practitioner] = $this->patientAndPractitioner($practice, 'Emma', 'Nakamura', 'Dr. Rivera');
        PracticePaymentMethod::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('method_key', CheckoutPayment::METHOD_CHECK)
            ->update(['enabled' => false]);
        $checkout = CheckoutSession::factory()->open()->create([
            'practice_id' => $practice->id,
            'appointment_id' => null,
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'charge_label' => 'Visit',
            'amount_total' => 10000,
            'amount_paid' => 0,
        ]);

        $this->actingAs($admin);

        Livewire::test(EditCheckoutSession::class, ['record' => $checkout->id])
            ->mountAction('recordPayment')
            ->assertFormFieldExists('payment_method', function ($field): bool {
                $options = $field->getOptions();

                return array_key_exists(CheckoutPayment::METHOD_CASH, $options)
                    && ! array_key_exists(CheckoutPayment::METHOD_CHECK, $options);
            });
    }

    public function test_mark_paid_rejects_disabled_payment_method(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();
        [$patient, $practitioner] = $this->patientAndPractitioner($practice, 'Emma', 'Nakamura', 'Dr. Rivera');
        PracticePaymentMethod::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('method_key', CheckoutPayment::METHOD_CASH)
            ->update(['enabled' => false]);
        $checkout = CheckoutSession::factory()->open()->create([
            'practice_id' => $practice->id,
            'appointment_id' => null,
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'charge_label' => 'Visit',
            'amount_total' => 10000,
            'amount_paid' => 0,
        ]);

        $this->actingAs($admin);

        Livewire::test(EditCheckoutSession::class, ['record' => $checkout->id])
            ->callAction('markPaid', [
                'payment_method' => CheckoutPayment::METHOD_CASH,
            ]);

        $checkout->refresh();

        $this->assertSame(0, $checkout->checkoutPayments()->count());
        $this->assertSame(0.0, (float) $checkout->amount_paid);
    }

    public function test_record_payment_with_no_enabled_methods_creates_no_payment(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();
        [$patient, $practitioner] = $this->patientAndPractitioner($practice, 'Emma', 'Nakamura', 'Dr. Rivera');
        PracticePaymentMethod::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->update(['enabled' => false]);
        $checkout = CheckoutSession::factory()->open()->create([
            'practice_id' => $practice->id,
            'appointment_id' => null,
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'charge_label' => 'Visit',
            'amount_total' => 10000,
            'amount_paid' => 0,
        ]);

        $this->actingAs($admin);

        Livewire::test(EditCheckoutSession::class, ['record' => $checkout->id])
            ->callAction('recordPayment', [
                'amount' => 10000,
                'payment_method' => CheckoutPayment::METHOD_CASH,
                'paid_at' => now()->toDateTimeString(),
            ]);

        $checkout->refresh();

        $this->assertSame(0, $checkout->checkoutPayments()->count());
        $this->assertSame(0.0, (float) $checkout->amount_paid);
    }

    public function test_mark_paid_with_no_enabled_methods_creates_no_payment(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();
        [$patient, $practitioner] = $this->patientAndPractitioner($practice, 'Emma', 'Nakamura', 'Dr. Rivera');
        PracticePaymentMethod::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->update(['enabled' => false]);
        $checkout = CheckoutSession::factory()->open()->create([
            'practice_id' => $practice->id,
            'appointment_id' => null,
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'charge_label' => 'Visit',
            'amount_total' => 10000,
            'amount_paid' => 0,
        ]);

        $this->actingAs($admin);

        Livewire::test(EditCheckoutSession::class, ['record' => $checkout->id])
            ->callAction('markPaid', [
                'payment_method' => CheckoutPayment::METHOD_CASH,
            ]);

        $checkout->refresh();

        $this->assertSame(0, $checkout->checkoutPayments()->count());
        $this->assertSame(0.0, (float) $checkout->amount_paid);
    }

    public function test_historical_payment_with_disabled_method_still_renders(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();
        [$patient, $practitioner] = $this->patientAndPractitioner($practice, 'Emma', 'Nakamura', 'Dr. Rivera');
        $checkout = CheckoutSession::factory()->open()->create([
            'practice_id' => $practice->id,
            'appointment_id' => null,
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'charge_label' => 'Visit',
            'amount_total' => 10000,
            'amount_paid' => 10000,
        ]);
        CheckoutPayment::factory()->create([
            'practice_id' => $practice->id,
            'checkout_session_id' => $checkout->id,
            'amount' => 10000,
            'payment_method' => CheckoutPayment::METHOD_CHECK,
            'paid_at' => now(),
        ]);
        PracticePaymentMethod::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('method_key', CheckoutPayment::METHOD_CHECK)
            ->update(['enabled' => false]);

        $this->actingAs($admin);

        Livewire::test(ViewSuperbill::class, ['record' => $checkout->id])
            ->assertSee('Check');
    }

    public function test_owner_or_admin_can_manage_practice_payment_methods(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();
        $method = PracticePaymentMethod::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('method_key', CheckoutPayment::METHOD_CASH)
            ->firstOrFail();

        $this->actingAs($admin);

        $this->assertTrue(PracticePaymentMethodResource::canViewAny());
        $this->assertTrue(PracticePaymentMethodResource::canEdit($method));

        Livewire::test(EditPracticePaymentMethod::class, ['record' => $method->id])
            ->fillForm([
                'display_name' => 'Cash Drawer',
                'enabled' => false,
                'sort_order' => 30,
            ])
            ->call('save')
            ->assertHasNoErrors();

        $method->refresh();

        $this->assertSame('Cash Drawer', $method->display_name);
        $this->assertFalse($method->enabled);
        $this->assertSame(30, $method->sort_order);
        $this->assertSame(CheckoutPayment::METHOD_CASH, $method->method_key);
    }

    public function test_practitioner_cannot_manage_practice_payment_methods(): void
    {
        [$practice] = $this->practiceWithAdmin();
        [, $practitioner] = $this->patientAndPractitioner($practice, 'Emma', 'Nakamura', 'Dr. Rivera');
        $practitioner->user->assignRole(User::ROLE_PRACTITIONER);
        $method = PracticePaymentMethod::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('method_key', CheckoutPayment::METHOD_CASH)
            ->firstOrFail();

        $this->actingAs($practitioner->user);

        $this->assertFalse(PracticePaymentMethodResource::canViewAny());
        $this->assertFalse(PracticePaymentMethodResource::canEdit($method));
        $this->get(PracticePaymentMethodResource::getUrl('index'))->assertForbidden();
        $this->get(PracticePaymentMethodResource::getUrl('edit', ['record' => $method]))->assertForbidden();
    }

    public function test_superbill_view_renders_patient_visit_charges_payments_codes_and_disclaimer(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();
        [$patient, $practitioner] = $this->patientAndPractitioner($practice, 'Emma', 'Nakamura', 'Dr. Rivera');
        $appointment = $this->appointmentFor($practice, $patient, $practitioner, '2026-04-28 09:30:00');
        $checkout = CheckoutSession::factory()->open()->create([
            'practice_id' => $practice->id,
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'charge_label' => 'Acupuncture Visit',
            'amount_total' => 12000,
            'diagnosis_codes' => 'M54.50',
            'procedure_codes' => '97810',
        ]);
        CheckoutLine::factory()->create([
            'practice_id' => $practice->id,
            'checkout_session_id' => $checkout->id,
            'description' => 'Acupuncture treatment',
            'amount' => 12000,
        ]);
        CheckoutPayment::factory()->create([
            'practice_id' => $practice->id,
            'checkout_session_id' => $checkout->id,
            'amount' => 12000,
            'payment_method' => CheckoutPayment::METHOD_CARD_EXTERNAL,
            'paid_at' => '2026-04-28 10:30:00',
        ]);

        $this->actingAs($admin);

        Livewire::test(ViewSuperbill::class, ['record' => $checkout->id])
            ->assertSee('Superbill')
            ->assertSee('Emma Nakamura')
            ->assertSee('Dr. Rivera')
            ->assertSee('Apr 28, 2026')
            ->assertSee('Acupuncture Visit')
            ->assertSee('Acupuncture treatment')
            ->assertSee('M54.50')
            ->assertSee('97810')
            ->assertSee('Card')
            ->assertSee('Reimbursement is not guaranteed');
    }

    public function test_direct_visit_superbill_renders_visit_context(): void
    {
        [$practice, $admin] = $this->practiceWithAdmin();
        [$patient, $practitioner] = $this->patientAndPractitioner($practice, 'Direct', 'Patient', 'Dr. Direct');
        $encounter = Encounter::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'appointment_id' => null,
            'practitioner_id' => $practitioner->id,
            'visit_date' => '2026-04-28',
        ]);
        $checkout = CheckoutSession::factory()->open()->create([
            'practice_id' => $practice->id,
            'appointment_id' => null,
            'encounter_id' => $encounter->id,
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'charge_label' => 'Direct Visit',
            'amount_total' => 9000,
        ]);

        $this->actingAs($admin);

        Livewire::test(ViewSuperbill::class, ['record' => $checkout->id])
            ->assertSee('Direct Patient')
            ->assertSee('Dr. Direct')
            ->assertSee('Apr 28, 2026')
            ->assertSee('Direct visit')
            ->assertSee('Direct Visit')
            ->assertSee('Reimbursement is not guaranteed');
    }

    private function practiceWithAdmin(): array
    {
        $practice = Practice::factory()->create();
        $admin = User::factory()->create(['practice_id' => $practice->id]);
        $admin->assignRole(User::ROLE_ADMINISTRATOR);

        return [$practice, $admin];
    }

    private function patientAndPractitioner(Practice $practice, string $firstName, string $lastName, string $practitionerName): array
    {
        $patient = Patient::factory()->create([
            'practice_id' => $practice->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
        $practitionerUser = User::factory()->create([
            'practice_id' => $practice->id,
            'name' => $practitionerName,
        ]);
        $practitioner = Practitioner::factory()->create([
            'practice_id' => $practice->id,
            'user_id' => $practitionerUser->id,
        ]);

        return [$patient, $practitioner];
    }

    private function appointmentFor(Practice $practice, Patient $patient, Practitioner $practitioner, string $startsAt): Appointment
    {
        return Appointment::factory()->create([
            'practice_id' => $practice->id,
            'patient_id' => $patient->id,
            'practitioner_id' => $practitioner->id,
            'appointment_type_id' => AppointmentType::factory()->create(['practice_id' => $practice->id])->id,
            'start_datetime' => $startsAt,
        ]);
    }
}
