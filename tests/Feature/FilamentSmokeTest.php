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

class FilamentSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected Practice $practice;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->practice = Practice::factory()->create();
        $this->admin = User::factory()->create([
            'practice_id' => $this->practice->id,
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

    protected function getResources(): array
    {
        return [
            'Appointments',
            'AppointmentTypes',
            'CheckoutSessions',
            'ConsentRecords',
            'Encounters',
            'IntakeSubmissions',
            'InventoryProducts',
            'Patients',
            'Practices',
            'Practitioners',
            'ServiceFees',
            'ActivityLogs',
            'InventoryMovements',
        ];
    }

    public function test_all_resource_index_pages_load(): void
    {
        foreach ($this->getResources() as $resourceName) {
            $this->createRecordForResource($resourceName);
            $url = $this->getResourceUrl($resourceName, 'index');
            
            $response = $this->actingAs($this->admin)->get($url);
            
            if ($response->status() !== 200) {
                dump("Failed: Index page for {$resourceName} returned {$response->status()}");
            }

            $response->assertSuccessful("Index page for {$resourceName} failed to load at {$url}");
        }
    }

    public function test_all_resource_edit_pages_load(): void
    {
        foreach ($this->getResources() as $resourceName) {
            // Skip Edit page test for resources known to only have an Index
            if (in_array($resourceName, ['ActivityLogs', 'InventoryMovements'])) {
                continue;
            }

            $record = $this->createRecordForResource($resourceName);
            $url = $this->getResourceUrl($resourceName, 'edit', $record);
            
            $response = $this->actingAs($this->admin)->get($url);
            
            if ($response->status() !== 200) {
                dump("Failed: Edit page for {$resourceName} returned {$response->status()}");
            }

            $response->assertSuccessful("Edit page for {$resourceName} failed to load at {$url}");
        }
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
            'ActivityLogs' => $this->actingAs($this->admin)->get('/') && ActivityLog::record('test', $this->practice),
            'InventoryMovements' => InventoryMovement::factory()->create([
                'practice_id' => $this->practice->id,
                'inventory_product_id' => InventoryProduct::factory()->create(['practice_id' => $this->practice->id])->id,
            ]),
        };
    }
}
