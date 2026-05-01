<?php

namespace Tests\Feature;

use App\Models\AppointmentRequest;
use App\Models\AppointmentType;
use App\Models\CheckoutSession;
use App\Models\CommunicationRule;
use App\Models\MessageTemplate;
use App\Models\Patient;
use App\Models\PatientCommunicationPreference;
use App\Models\Practice;
use App\Models\ServiceFee;
use App\Models\User;
use App\Services\PatientCareStatusService;
use Database\Seeders\RealisticPracticeDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class RealisticPracticeDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_realistic_practice_demo_seed_command_creates_expected_demo_data(): void
    {
        $practice = Practice::factory()->create(['name' => 'Admin Healthcare Demo Practice']);
        User::factory()->create([
            'name' => 'Healthcare Admin',
            'email' => 'admin@healthcare.test',
            'practice_id' => $practice->id,
        ]);

        $exitCode = Artisan::call('demo:seed-practice-realistic', [
            '--user' => 'admin@healthcare.test',
            '--base-url' => 'http://127.0.0.1:8002',
            '--reset-demo-data' => true,
        ]);

        $this->assertSame(0, $exitCode);

        $patients = Patient::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('notes', 'like', '%'.RealisticPracticeDemoSeeder::MARKER.'%')
            ->get();

        $this->assertGreaterThanOrEqual(20, $patients->count());
        $this->assertTrue($patients->where('name', 'No Email Demo Patient')->first()->email === null);
        $this->assertSame(
            RealisticPracticeDemoSeeder::TEST_EMAIL,
            $patients->where('name', '!=', 'No Email Demo Patient')->pluck('email')->unique()->sole(),
        );

        $optedOut = $patients->where('name', 'Opted Out Demo Patient')->first();
        $this->assertFalse(
            PatientCommunicationPreference::withoutPracticeScope()
                ->where('practice_id', $practice->id)
                ->where('patient_id', $optedOut->id)
                ->firstOrFail()
                ->canReceiveEmail(),
        );

        $this->assertServiceFee($practice, 'Initial Acupuncture Consultation + Treatment', '145.00');
        $this->assertServiceFee($practice, 'Follow-Up Acupuncture Treatment', '95.00');
        $this->assertServiceFee($practice, 'Five Element Acupuncture Treatment', '110.00');
        $this->assertServiceFee($practice, 'Massage Therapy 90 min', '145.00');

        $this->assertNotNull(
            AppointmentType::withoutPracticeScope()
                ->where('practice_id', $practice->id)
                ->where('name', 'Follow-Up Acupuncture Treatment')
                ->firstOrFail()
                ->default_service_fee_id,
        );
        $this->assertNull(
            AppointmentType::withoutPracticeScope()
                ->where('practice_id', $practice->id)
                ->where('name', 'No Default Fee Demo Visit')
                ->firstOrFail()
                ->default_service_fee_id,
        );

        $this->assertSame(2, AppointmentRequest::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('status', AppointmentRequest::STATUS_PENDING)
            ->count());

        $this->assertGreaterThanOrEqual(8, MessageTemplate::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('name', 'like', RealisticPracticeDemoSeeder::TEMPLATE_PREFIX.'%')
            ->count());
        $this->assertGreaterThanOrEqual(8, CommunicationRule::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->count());

        $this->assertTrue(CheckoutSession::withoutPracticeScope()->where('practice_id', $practice->id)->where('state', 'open')->exists());
        $this->assertTrue(CheckoutSession::withoutPracticeScope()->where('practice_id', $practice->id)->where('state', 'paid')->exists());
        $this->assertTrue(CheckoutSession::withoutPracticeScope()->where('practice_id', $practice->id)->where('state', 'payment_due')->exists());

        $statuses = $this->careStatuses($practice);
        $this->assertSame('new', $statuses['Realistic Demo - New Patient']);
        $this->assertSame('active', $statuses['Realistic Demo - Active Future Appointment Patient']);
        $this->assertSame('active', $statuses['Realistic Demo - Active Recent Visit Patient']);
        $this->assertSame('needs_follow_up', $statuses['Realistic Demo - Needs Follow-Up Patient']);
        $this->assertSame('cooling', $statuses['Realistic Demo - Cooling Patient']);
        $this->assertSame('inactive', $statuses['Realistic Demo - Inactive Patient']);
        $this->assertSame('at_risk', $statuses['Realistic Demo - At Risk Cancelled Patient']);
        $this->assertSame('at_risk', $statuses['Realistic Demo - At Risk No-Show Patient']);

        Artisan::call('demo:seed-practice-realistic', [
            '--user' => 'admin@healthcare.test',
            '--base-url' => 'http://127.0.0.1:8002',
        ]);

        $this->assertSame($patients->count(), Patient::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('notes', 'like', '%'.RealisticPracticeDemoSeeder::MARKER.'%')
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
            ->mapWithKeys(fn (Patient $patient): array => [
                $patient->name => $service->forPatient($patient)['key'],
            ])
            ->all();
    }
}
