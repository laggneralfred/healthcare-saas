<?php

namespace Tests\Feature;

use App\Models\AppointmentRequest;
use App\Models\AppointmentType;
use App\Models\CheckoutLine;
use App\Models\CheckoutSession;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\ServiceFee;
use App\Services\PatientCareStatusService;
use Database\Seeders\LocalFollowUpWorkflowSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class LocalFollowUpWorkflowSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_follow_up_workflow_seed_command_creates_demo_records(): void
    {
        $exitCode = Artisan::call('demo:seed-follow-up-workflow', [
            '--base-url' => 'http://127.0.0.1:8002',
        ]);

        $this->assertSame(0, $exitCode);

        $practice = Practice::query()->where('name', LocalFollowUpWorkflowSeeder::PRACTICE_NAME)->firstOrFail();

        $this->assertDatabaseHas('users', [
            'email' => LocalFollowUpWorkflowSeeder::ADMIN_EMAIL,
            'practice_id' => $practice->id,
        ]);

        $seededPatients = Patient::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->pluck('email', 'name');

        $this->assertSame(LocalFollowUpWorkflowSeeder::TEST_EMAIL, $seededPatients['English Followup Patient']);
        $this->assertSame(LocalFollowUpWorkflowSeeder::TEST_EMAIL, $seededPatients['Spanish Followup Patient']);
        $this->assertNull($seededPatients['No Email Test Patient']);

        Patient::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('name', '!=', 'No Email Test Patient')
            ->each(function (Patient $patient): void {
                $this->assertSame(LocalFollowUpWorkflowSeeder::TEST_EMAIL, $patient->email);
            });

        $this->assertSame(2, AppointmentRequest::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('status', AppointmentRequest::STATUS_PENDING)
            ->count());

        $statuses = Patient::withoutPracticeScope()
            ->with(['appointments', 'encounters'])
            ->where('practice_id', $practice->id)
            ->get()
            ->mapWithKeys(fn (Patient $patient): array => [
                $patient->name => app(PatientCareStatusService::class)->forPatient($patient)['key'],
            ]);

        $this->assertSame(PatientCareStatusService::STATUS_NEEDS_FOLLOW_UP, $statuses['English Followup Patient']);
        $this->assertSame(PatientCareStatusService::STATUS_COOLING, $statuses['Chinese Translation Patient']);
        $this->assertSame(PatientCareStatusService::STATUS_INACTIVE, $statuses['Inactive Patient']);
        $this->assertSame(PatientCareStatusService::STATUS_AT_RISK, $statuses['At Risk No-Show Patient']);

        $this->assertDatabaseHas('service_fees', [
            'practice_id' => $practice->id,
            'name' => 'Initial Acupuncture Visit',
            'default_price' => '125.00',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('service_fees', [
            'practice_id' => $practice->id,
            'name' => 'Follow-Up Acupuncture Visit',
            'default_price' => '95.00',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('service_fees', [
            'practice_id' => $practice->id,
            'name' => 'Five Element Acupuncture Treatment',
            'default_price' => '110.00',
            'is_active' => true,
        ]);

        ServiceFee::withoutPracticeScope()
            ->whereIn('name', [
                'Initial Acupuncture Visit',
                'Follow-Up Acupuncture Visit',
                'Five Element Acupuncture Treatment',
                'Herbal Consultation',
                'Moxa / Adjunctive Treatment',
                'Cupping Add-on',
                'Wellness Consultation',
            ])
            ->each(function (ServiceFee $fee) use ($practice): void {
                $this->assertSame($practice->id, $fee->practice_id);
            });

        $followUpType = AppointmentType::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('name', 'Follow-Up Acupuncture Visit')
            ->with('defaultServiceFee')
            ->firstOrFail();
        $fiveElementType = AppointmentType::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('name', 'Five Element Acupuncture Treatment')
            ->with('defaultServiceFee')
            ->firstOrFail();

        $this->assertSame('Follow-Up Acupuncture Visit', $followUpType->defaultServiceFee->name);
        $this->assertSame('Five Element Acupuncture Treatment', $fiveElementType->defaultServiceFee->name);

        $checkoutPatient = Patient::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('name', 'Checkout Service Fee Patient')
            ->firstOrFail();
        $checkout = CheckoutSession::withoutPracticeScope()
            ->where('practice_id', $practice->id)
            ->where('patient_id', $checkoutPatient->id)
            ->with('checkoutLines.serviceFee')
            ->firstOrFail();

        $this->assertSame('Follow-Up Acupuncture Visit', $checkout->charge_label);
        $this->assertEquals(95.00, (float) $checkout->amount_total);
        $this->assertTrue($checkout->checkoutLines->contains(function (CheckoutLine $line): bool {
            return $line->line_type === CheckoutLine::TYPE_SERVICE
                && $line->serviceFee?->name === 'Follow-Up Acupuncture Visit'
                && (float) $line->amount === 95.00;
        }));
    }
}
