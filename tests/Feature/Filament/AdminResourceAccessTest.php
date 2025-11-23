<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminResourceAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\TestDatabaseSeeder::class);
    }

    // Properties Resource Tests
    public function test_admin_can_access_properties_index(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/admin/properties');

        $response->assertStatus(200);
    }

    public function test_admin_can_create_property(): void
    {
        $admin = $this->actingAsAdmin();
        $building = Building::factory()->create(['tenant_id' => $admin->tenant_id]);

        $response = $this->get('/admin/properties/create');

        $response->assertStatus(200);
    }

    public function test_admin_can_edit_own_property(): void
    {
        $admin = $this->actingAsAdmin();
        $property = $this->createTestProperty(['tenant_id' => $admin->tenant_id]);

        $response = $this->get("/admin/properties/{$property->id}/edit");

        $response->assertStatus(200);
    }

    public function test_admin_cannot_edit_other_tenant_property(): void
    {
        $this->actingAsAdmin();
        
        // Create property for different tenant
        $otherProperty = Property::factory()->create([
            'tenant_id' => 'other_tenant_' . uniqid(),
        ]);

        $response = $this->get("/admin/properties/{$otherProperty->id}/edit");

        $response->assertStatus(404);
    }

    // Buildings Resource Tests
    public function test_admin_can_access_buildings_index(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/admin/buildings');

        $response->assertStatus(200);
    }

    public function test_admin_can_create_building(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/admin/buildings/create');

        $response->assertStatus(200);
    }

    public function test_admin_can_edit_own_building(): void
    {
        $admin = $this->actingAsAdmin();
        $building = Building::factory()->create(['tenant_id' => $admin->tenant_id]);

        $response = $this->get("/admin/buildings/{$building->id}/edit");

        $response->assertStatus(200);
    }

    // Meters Resource Tests
    public function test_admin_can_access_meters_index(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/admin/meters');

        $response->assertStatus(200);
    }

    public function test_admin_can_create_meter(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/admin/meters/create');

        $response->assertStatus(200);
    }

    public function test_admin_can_edit_own_meter(): void
    {
        $admin = $this->actingAsAdmin();
        $property = $this->createTestProperty(['tenant_id' => $admin->tenant_id]);
        $meter = $property->meters()->first();

        $response = $this->get("/admin/meters/{$meter->id}/edit");

        $response->assertStatus(200);
    }

    // Meter Readings Resource Tests
    public function test_admin_can_access_meter_readings_index(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/admin/meter-readings');

        $response->assertStatus(200);
    }

    public function test_admin_can_create_meter_reading(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/admin/meter-readings/create');

        $response->assertStatus(200);
    }

    public function test_admin_can_edit_own_meter_reading(): void
    {
        $admin = $this->actingAsAdmin();
        $property = $this->createTestProperty(['tenant_id' => $admin->tenant_id]);
        $meter = $property->meters()->first();
        $reading = $this->createTestMeterReading(['meter_id' => $meter->id]);

        $response = $this->get("/admin/meter-readings/{$reading->id}/edit");

        $response->assertStatus(200);
    }

    // Invoices Resource Tests
    public function test_admin_can_access_invoices_index(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/admin/invoices');

        $response->assertStatus(200);
    }

    public function test_admin_can_create_invoice(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/admin/invoices/create');

        $response->assertStatus(200);
    }

    public function test_admin_can_view_own_invoice(): void
    {
        $admin = $this->actingAsAdmin();
        $property = $this->createTestProperty(['tenant_id' => $admin->tenant_id]);
        $invoice = Invoice::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'property_id' => $property->id,
        ]);

        $response = $this->get("/admin/invoices/{$invoice->id}");

        $response->assertStatus(200);
    }

    public function test_admin_can_edit_draft_invoice(): void
    {
        $admin = $this->actingAsAdmin();
        $property = $this->createTestProperty(['tenant_id' => $admin->tenant_id]);
        $invoice = Invoice::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'property_id' => $property->id,
            'finalized_at' => null,
        ]);

        $response = $this->get("/admin/invoices/{$invoice->id}/edit");

        $response->assertStatus(200);
    }

    // Tariffs Resource Tests
    public function test_admin_can_access_tariffs_index(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/admin/tariffs');

        $response->assertStatus(200);
    }

    public function test_admin_can_create_tariff(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/admin/tariffs/create');

        $response->assertStatus(200);
    }

    public function test_admin_can_edit_tariff(): void
    {
        $this->actingAsAdmin();
        $tariff = Tariff::factory()->create();

        $response = $this->get("/admin/tariffs/{$tariff->id}/edit");

        $response->assertStatus(200);
    }

    // Providers Resource Tests
    public function test_admin_can_access_providers_index(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/admin/providers');

        $response->assertStatus(200);
    }

    public function test_admin_can_create_provider(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/admin/providers/create');

        $response->assertStatus(200);
    }

    public function test_admin_can_edit_provider(): void
    {
        $this->actingAsAdmin();
        $provider = Provider::factory()->create();

        $response = $this->get("/admin/providers/{$provider->id}/edit");

        $response->assertStatus(200);
    }

    // Users Resource Tests
    public function test_admin_can_access_users_index(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/admin/users');

        $response->assertStatus(200);
    }

    public function test_admin_can_create_user(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/admin/users/create');

        $response->assertStatus(200);
    }

    public function test_admin_can_edit_own_tenant_user(): void
    {
        $admin = $this->actingAsAdmin();
        $user = User::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'role' => UserRole::TENANT,
        ]);

        $response = $this->get("/admin/users/{$user->id}/edit");

        $response->assertStatus(200);
    }

    public function test_admin_cannot_edit_other_tenant_user(): void
    {
        $this->actingAsAdmin();
        
        $otherUser = User::factory()->create([
            'tenant_id' => 'other_tenant_' . uniqid(),
            'role' => UserRole::TENANT,
        ]);

        $response = $this->get("/admin/users/{$otherUser->id}/edit");

        $response->assertStatus(404);
    }

    // Manager Access Tests
    public function test_manager_can_access_properties(): void
    {
        $this->actingAsManager();

        $response = $this->get('/admin/properties');

        $response->assertStatus(200);
    }

    public function test_manager_can_access_meters(): void
    {
        $this->actingAsManager();

        $response = $this->get('/admin/meters');

        $response->assertStatus(200);
    }

    public function test_manager_can_access_meter_readings(): void
    {
        $this->actingAsManager();

        $response = $this->get('/admin/meter-readings');

        $response->assertStatus(200);
    }

    public function test_manager_can_access_invoices(): void
    {
        $this->actingAsManager();

        $response = $this->get('/admin/invoices');

        $response->assertStatus(200);
    }

    public function test_manager_cannot_access_users(): void
    {
        $this->actingAsManager();

        $response = $this->get('/admin/users');

        $response->assertStatus(403);
    }

    // Tenant Access Tests
    public function test_tenant_cannot_access_admin_panel(): void
    {
        $this->actingAsTenant();

        $response = $this->get('/admin');

        $response->assertStatus(403);
    }

    public function test_tenant_cannot_access_properties(): void
    {
        $this->actingAsTenant();

        $response = $this->get('/admin/properties');

        $response->assertStatus(403);
    }

    public function test_tenant_cannot_access_users(): void
    {
        $this->actingAsTenant();

        $response = $this->get('/admin/users');

        $response->assertStatus(403);
    }

    // Guest Access Tests
    public function test_guest_redirected_from_all_resources(): void
    {
        $resources = [
            '/admin/properties',
            '/admin/buildings',
            '/admin/meters',
            '/admin/meter-readings',
            '/admin/invoices',
            '/admin/tariffs',
            '/admin/providers',
            '/admin/users',
        ];

        foreach ($resources as $resource) {
            $response = $this->get($resource);
            $response->assertRedirect('/admin/login');
        }
    }

    // Navigation Visibility Tests
    public function test_admin_sees_all_navigation_items(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('Properties');
        $response->assertSee('Buildings');
        $response->assertSee('Meters');
        $response->assertSee('Invoices');
        $response->assertSee('Users');
    }

    public function test_manager_sees_limited_navigation(): void
    {
        $this->actingAsManager();

        $response = $this->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('Properties');
        $response->assertSee('Meters');
        $response->assertDontSee('Users');
    }
}
