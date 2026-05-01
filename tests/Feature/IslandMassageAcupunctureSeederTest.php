<?php

namespace Tests\Feature;

use App\Models\AcupunctureEncounter;
use App\Models\AppointmentRequest;
use App\Models\AppointmentType;
use App\Models\CheckoutPayment;
use App\Models\CheckoutSession;
use App\Models\CommunicationRule;
use App\Models\MessageTemplate;
use App\Models\Patient;
use App\Models\PatientCommunicationPreference;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\ServiceFee;
use App\Models\User;
use App\Services\PatientCareStatusService;
use Database\Seeders\IslandMassageAcupunctureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class IslandMassageAcupunctureSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_island_massage_acupuncture_seed_command_creates_expected_demo_clinic(): void
    {
        $exitCode = Artisan::call('demo:seed-island-massage-acupuncture', [
            '--base-url' => 'http://127.0.0.1:8002',
            '--reset-demo-data' => true,
        ]);

        $this->assertSame(0, $exitCode);

        $practice = Practice::query()
            ->where('name', IslandMassageAcupunctureSeeder::PRACTICE_NAME)
            ->firstOrFail();

        $this->assertFalse($practice->is_demo);
        $this->assertFalse($practice->insurance_billing_enabled);
        $this->assertSame('five_element_acupuncture', $practice->practice_type);

        $admin = User::query()->where('email', 'maria-demo@practiq.local')->firstOrFail();
        $this->assertSame('Maria Cook', $admin->name);
        $this->assertSame($practice->id, $admin->practice_id);

        $this->assertSame(2, Practitioner::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('user_id', $admin->id)
            ->where('license_number', 'like', 'ISLAND-DEMO-%')
            ->count());
        $this->assertTrue(Practitioner::withoutPracticeScope()->where('practice_id', $practice->id)->where('specialty', 'Five Element Acupuncture')->exists());
        $this->assertTrue(Practitioner::withoutPracticeScope()->where('practice_id', $practice->id)->where('specialty', 'Massage Therapy')->exists());

        $patients = Patient::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('notes', 'like', '%'.IslandMassageAcupunctureSeeder::MARKER.'%')
            ->get();

        $this->assertCount(40, $patients);
        $this->assertSame(20, $patients->filter(fn (Patient $patient): bool => str_contains($patient->name, 'Five Element'))->count());
        $this->assertSame(20, $patients->filter(fn (Patient $patient): bool => str_contains($patient->name, 'Massage'))->count());

        $missingEmail = $patients->firstWhere('name', 'Island Demo - No Email Massage Patient');
        $this->assertNull($missingEmail->email);

        $normalEmails = $patients
            ->reject(fn (Patient $patient): bool => $patient->is($missingEmail))
            ->pluck('email')
            ->unique()
            ->values();
        $this->assertSame(['laggneralfred@gmail.com'], $normalEmails->all());

        $optedOut = $patients->firstWhere('name', 'Island Demo - Opted Out Five Element Patient');
        $this->assertFalse(PatientCommunicationPreference::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('patient_id', $optedOut->id)
            ->firstOrFail()
            ->canReceiveEmail());

        foreach ([
            'en',
            'es',
            'zh',
            'vi',
            'fr',
            'de',
            'other',
        ] as $language) {
            $this->assertTrue($patients->contains('preferred_language', $language), "Missing language {$language}");
        }

        $this->assertServiceFee($practice, 'Initial Five Element Consultation + Treatment', '150.00');
        $this->assertServiceFee($practice, 'Five Element Follow-Up Treatment', '110.00');
        $this->assertServiceFee($practice, 'Massage Therapy 60 min', '95.00');
        $this->assertServiceFee($practice, 'Massage Therapy 90 min', '145.00');

        $this->assertNotNull(AppointmentType::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('name', 'Five Element Follow-Up')
            ->firstOrFail()
            ->default_service_fee_id);
        $this->assertNull(AppointmentType::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('name', 'No Default Fee Island Demo Visit')
            ->firstOrFail()
            ->default_service_fee_id);

        $this->assertGreaterThanOrEqual(8, MessageTemplate::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('name', 'like', IslandMassageAcupunctureSeeder::TEMPLATE_PREFIX.'%')
            ->count());
        $this->assertGreaterThanOrEqual(8, CommunicationRule::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->count());

        $this->assertSame(2, AppointmentRequest::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('status', AppointmentRequest::STATUS_PENDING)
            ->count());
        $this->assertSame(2, AppointmentRequest::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('status', AppointmentRequest::STATUS_LINK_SENT)
            ->count());
        $this->assertTrue(AppointmentRequest::withoutPracticeScope()->where('practice_id', $practice->id)->where('status', AppointmentRequest::STATUS_CONTACTED)->exists());
        $this->assertTrue(AppointmentRequest::withoutPracticeScope()->where('practice_id', $practice->id)->where('status', AppointmentRequest::STATUS_SCHEDULED)->exists());
        $this->assertTrue(AppointmentRequest::withoutPracticeScope()->where('practice_id', $practice->id)->where('status', AppointmentRequest::STATUS_DISMISSED)->exists());

        $this->assertTrue(CheckoutSession::withoutPracticeScope()->where('practice_id', $practice->id)->where('state', 'open')->exists());
        $this->assertTrue(CheckoutPayment::withoutPracticeScope()->where('practice_id', $practice->id)->where('reference', 'ISLAND-PARTIAL')->exists());
        $this->assertTrue(CheckoutPayment::withoutPracticeScope()->where('practice_id', $practice->id)->where('reference', 'ISLAND-PAID')->exists());
        $this->assertTrue(CheckoutSession::withoutPracticeScope()->where('practice_id', $practice->id)->where('charge_label', 'No Default Fee Island Demo Visit')->exists());

        $this->assertTrue(AcupunctureEncounter::query()
            ->whereNotNull('pulse_before_treatment')
            ->where('pulse_before_treatment', 'like', '%K --%')
            ->where('pulse_after_treatment', 'like', '%Overall more even%')
            ->exists());

        $statuses = $this->careStatuses($practice);
        foreach (['new', 'active', 'needs_follow_up', 'cooling', 'inactive', 'at_risk'] as $status) {
            $this->assertContains($status, $statuses, "Missing care status {$status}");
        }

        Artisan::call('demo:seed-island-massage-acupuncture', [
            '--base-url' => 'http://127.0.0.1:8002',
        ]);

        $this->assertSame(40, Patient::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('notes', 'like', '%'.IslandMassageAcupunctureSeeder::MARKER.'%')
            ->count());
    }

    private function assertServiceFee(Practice $practice, string $name, string $price): void
    {
        $fee = ServiceFee::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('name', $name)
            ->firstOrFail();

        $this->assertSame($price, $fee->default_price);
    }

    private function careStatuses(Practice $practice): array
    {
        $service = app(PatientCareStatusService::class);

        return Patient::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->with(['appointments', 'encounters'])
            ->get()
            ->map(fn (Patient $patient): string => $service->forPatient($patient)['key'])
            ->unique()
            ->values()
            ->all();
    }
}
