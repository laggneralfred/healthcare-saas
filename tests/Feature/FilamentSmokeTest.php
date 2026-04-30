<?php

namespace Tests\Feature;

use App\Filament\Pages\CommunicationsDashboard;
use App\Filament\Pages\DashboardPage;
use App\Filament\Pages\FrontDeskDashboard;
use App\Filament\Pages\SchedulePage;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\CheckoutSession;
use App\Models\CommunicationRule;
use App\Models\ConsentRecord;
use App\Models\Encounter;
use App\Models\MedicalHistory;
use App\Models\InventoryMovement;
use App\Models\InventoryProduct;
use App\Models\MessageLog;
use App\Models\MessageTemplate;
use App\Models\Patient;
use App\Models\Practice;
use App\Models\Practitioner;
use App\Models\ServiceFee;
use App\Models\User;
use App\Filament\Resources\AppointmentTypes\AppointmentTypeResource;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\CheckoutSessions\CheckoutSessionResource;
use App\Filament\Resources\CommunicationRules\CommunicationRuleResource;
use App\Filament\Resources\Encounters\EncounterResource;
use App\Filament\Resources\InventoryProducts\InventoryProductResource;
use App\Filament\Resources\MessageLogs\MessageLogResource;
use App\Filament\Resources\PracticePaymentMethods\PracticePaymentMethodResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
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
            'MedicalHistories',
            'InventoryProducts',
            'Patients',
            'Practices',
            'Practitioners',
            'ServiceFees',
            'ActivityLogs',
            'InventoryMovements',
            'MessageTemplates',
            'CommunicationRules',
            'MessageLogs',
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
            // Skip Edit page test for resources known to only have an Index or be read-only
            if (in_array($resourceName, ['ActivityLogs', 'InventoryMovements', 'MessageLogs'])) {
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

    public function test_communications_tables_exist(): void
    {
        $tables = [
            'message_templates',
            'communication_rules',
            'message_logs',
            'patient_communication_preferences',
            'patient_communications',
            'appointment_requests',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Table '{$table}' does not exist — run php artisan migrate"
            );
        }
    }

    public function test_create_pages_load(): void
    {
        $createUrls = [
            '/admin/patients/create',
            '/admin/practitioners/create',
            '/admin/inventory-products/create',
            '/admin/appointments/create',
        ];

        foreach ($createUrls as $url) {
            $response = $this->actingAs($this->admin)->get($url);

            if ($response->status() !== 200) {
                dump("Failed: Create page {$url} returned {$response->status()}");
            }

            $response->assertSuccessful("Create page {$url} failed to load");
        }
    }

    public function test_custom_pages_load(): void
    {
        $pages = [
            '/admin/export-data',
            '/admin/settings/import-patients',
            '/admin/communications-dashboard',
        ];

        foreach ($pages as $url) {
            $response = $this->actingAs($this->admin)->get($url);

            if ($response->status() !== 200) {
                dump("Failed: Custom page {$url} returned {$response->status()}");
            }

            $response->assertSuccessful("Custom page {$url} failed to load");
        }
    }

    public function test_navigation_uses_task_based_labels(): void
    {
        $this->assertSame('Today', FrontDeskDashboard::getNavigationLabel());
        $this->assertSame('Today', FrontDeskDashboard::getNavigationGroup());
        $this->assertSame('Calendar', SchedulePage::getNavigationLabel());
        $this->assertSame('Calendar', SchedulePage::getNavigationGroup());
        $this->assertSame('Reports', DashboardPage::getNavigationLabel());
        $this->assertSame('Reports', DashboardPage::getNavigationGroup());
        $this->assertSame('Follow-Up', CommunicationsDashboard::getNavigationLabel());
        $this->assertSame('Follow-Up', CommunicationsDashboard::getNavigationGroup());

        $this->assertSame('Calendar', AppointmentResource::getNavigationGroup());
        $this->assertSame('Settings', AppointmentTypeResource::getNavigationGroup());
        $this->assertSame('Visits', EncounterResource::getNavigationGroup());
        $this->assertSame('Checkout', CheckoutSessionResource::getNavigationGroup());
        $this->assertSame('Checkout', PracticePaymentMethodResource::getNavigationGroup());
        $this->assertSame('Checkout', InventoryProductResource::getNavigationGroup());
        $this->assertSame('Follow-Up', CommunicationRuleResource::getNavigationGroup());
        $this->assertSame('Message History', MessageLogResource::getNavigationLabel());
    }

    public function test_task_based_helper_copy_renders(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/front-desk')
            ->assertSuccessful()
            ->assertSee('Here is what needs your attention today.');

        $this->actingAs($this->admin)
            ->get('/admin/communications-dashboard')
            ->assertSuccessful()
            ->assertSee('Patients who may need a gentle follow-up will appear here.');
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
            'MedicalHistories' => MedicalHistory::factory()->create(['practice_id' => $this->practice->id]),
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
            'MessageTemplates' => MessageTemplate::withoutPracticeScope()->create([
                'practice_id'   => $this->practice->id,
                'name'          => 'Test Template',
                'channel'       => 'email',
                'trigger_event' => 'reminder_24h',
                'subject'       => 'Test Subject',
                'body'          => 'Test body',
                'is_active'     => true,
                'is_default'    => false,
            ]),
            'CommunicationRules' => (function () {
                $template = MessageTemplate::withoutPracticeScope()->create([
                    'practice_id'   => $this->practice->id,
                    'name'          => 'Rule Template',
                    'channel'       => 'email',
                    'trigger_event' => 'reminder_24h',
                    'subject'       => 'Subject',
                    'body'          => 'Body',
                    'is_active'     => true,
                    'is_default'    => false,
                ]);
                return CommunicationRule::withoutPracticeScope()->create([
                    'practice_id'            => $this->practice->id,
                    'message_template_id'    => $template->id,
                    'trigger_event'          => 'reminder_24h',
                    'send_at_offset_minutes' => -1440,
                    'is_active'              => true,
                ]);
            })(),
            'MessageLogs' => MessageLog::withoutPracticeScope()->create([
                'practice_id' => $this->practice->id,
                'patient_id'  => Patient::factory()->create(['practice_id' => $this->practice->id])->id,
                'channel'     => 'email',
                'recipient'   => 'test@example.com',
                'body'        => 'Test',
                'status'      => 'sent',
            ]),
        };
    }
}
