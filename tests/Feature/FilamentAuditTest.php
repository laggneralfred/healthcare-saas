<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\CheckoutSession;
use App\Models\ConsentRecord;
use App\Models\Encounter;
use App\Models\IntakeSubmission;
use App\Models\InventoryMovement;
use App\Models\InventoryProduct;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\ServiceFee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentAuditTest extends TestCase
{
    use RefreshDatabase;

    protected $practice;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->practice = Practice::factory()->create([
            'name' => 'Test Practice',
            'trial_ends_at' => now()->addDays(30),
        ]);
        $this->admin = User::factory()->create([
            'practice_id' => $this->practice->id,
            'name' => 'Admin User',
        ]);
    }

    protected function getResourceUrl(string $resource, string $action = 'index', $record = null): string
    {
        $slug = str($resource)->kebab()->plural();
        
        return match ($action) {
            'index' => "/admin/{$slug}",
            'edit' => "/admin/{$slug}/{$record->getKey()}/edit",
            default => "/admin/{$slug}",
        };
    }

    protected function createRecordForResource(string $resourceName)
    {
        return match ($resourceName) {
            'Appointments' => Appointment::factory()->create(['practice_id' => $this->practice->id]),
            'AppointmentTypes' => AppointmentType::factory()->create(['practice_id' => $this->practice->id]),
            'CheckoutSessions' => CheckoutSession::factory()->create(['practice_id' => $this->practice->id]),
            'ConsentRecords' => ConsentRecord::factory()->create(['practice_id' => $this->practice->id]),
            'Encounters' => Encounter::factory()->create([
                'practice_id' => $this->practice->id,
                'patient_id' => Patient::factory()->create(['practice_id' => $this->practice->id])->id,
                'practitioner_id' => Practitioner::factory()->create(['practice_id' => $this->practice->id])->id,
                'appointment_id' => Appointment::factory()->create(['practice_id' => $this->practice->id])->id,
            ]),
            'IntakeSubmissions' => IntakeSubmission::factory()->create(['practice_id' => $this->practice->id]),
            'InventoryProducts' => InventoryProduct::factory()->create(['practice_id' => $this->practice->id]),
            'Patients' => Patient::factory()->create(['practice_id' => $this->practice->id]),
            'Practices' => $this->practice,
            'Practitioners' => Practitioner::factory()->create(['practice_id' => $this->practice->id]),
            'ServiceFees' => ServiceFee::factory()->create(['practice_id' => $this->practice->id]),
            'ActivityLogs' => ActivityLog::record('test', $this->practice),
            'InventoryMovements' => InventoryMovement::factory()->create([
                'practice_id' => $this->practice->id,
                'inventory_product_id' => InventoryProduct::factory()->create(['practice_id' => $this->practice->id])->id,
            ]),
        };
    }

    protected function getResources(): array
    {
        return [
       //     'ActivityLogs',
       //     'Appointments',
       //     'AppointmentTypes',
       //     'CheckoutSessions',
       //     'ConsentRecords',
       //     'Encounters',
        //    'IntakeSubmissions',
        //    'InventoryMovements',
        //    'InventoryProducts',
        //    'Patients',
            'Practices',
            'Practitioners',
        //    'ServiceFees',
        ];
    }

    public function test_all_index_pages_load()
    {
        foreach ($this->getResources() as $resource) {
            $this->createRecordForResource($resource);
            $url = $this->getResourceUrl($resource, 'index');
            
            $response = $this->actingAs($this->admin)->get($url);
            
            if ($response->status() !== 200) {
                dump("FAILED INDEX: {$resource} at {$url}");
                dump($response->getContent());
            }

            $response->assertStatus(200);
        }
    }

    public function test_all_edit_pages_load()
    {
        foreach ($this->getResources() as $resource) {
            if (in_array($resource, ['ActivityLogs', 'InventoryMovements'])) {
                continue;
            }

            $record = $this->createRecordForResource($resource);
            $url = $this->getResourceUrl($resource, 'edit', $record);
            
            $response = $this->actingAs($this->admin)->get($url);

            if ($response->status() !== 200) {
                dump("FAILED EDIT: {$resource} at {$url}");
            }

            $response->assertStatus(200);
        }
    }
}
