<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Property;
use App\Models\Building;
use App\Models\Meter;
use App\Models\Invoice;
use App\Enums\InvoiceStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Simple Tenant Panel Test
 * 
 * Basic functionality test for tenant panel without complex dependencies
 */
final class TenantPanelSimpleTest extends TestCase
{
    use RefreshDatabase;

    private User $tenant;
    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a building
        $building = Building::factory()->create([
            'name' => 'Test Building',
            'address' => 'Test Address 123',
        ]);

        // Create a property
        $this->property = Property::factory()->create([
            'building_id' => $building->id,
            'name' => 'Test Property',
            'address' => 'Test Address 123, Apt 1',
            'floor' => 1,
            'apartment_number' => '1',
            'area' => 50.0,
        ]);

        // Create a tenant user
        $this->tenant = User::factory()->create([
            'role' => UserRole::TENANT,
            'property_id' => $this->property->id,
            'name' => 'Test Tenant',
            'email' => 'tenant@test.com',
        ]);
    }

    public function test_tenant_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->tenant)
            ->get('/tenant');

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
    }

    public function test_tenant_can_view_property(): void
    {
        $response = $this->actingAs($this->tenant)
            ->get('/tenant/properties');

        $response->assertStatus(200);
        $response->assertSee($this->property->name);
        $response->assertSee($this->property->address);
    }

    public function test_tenant_can_view_meter_readings(): void
    {
        // Create a meter for the property
        $meter = Meter::factory()->create([
            'property_id' => $this->property->id,
            'name' => 'Test Meter',
        ]);

        $response = $this->actingAs($this->tenant)
            ->get('/tenant/meter-readings');

        $response->assertStatus(200);
    }

    public function test_tenant_can_view_invoices(): void
    {
        // Create an invoice for the property
        Invoice::factory()->create([
            'property_id' => $this->property->id,
            'number' => 'INV-2024-001',
            'status' => InvoiceStatus::FINALIZED,
            'total_amount' => 100.00,
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
        ]);

        $response = $this->actingAs($this->tenant)
            ->get('/tenant/invoices');

        $response->assertStatus(200);
        $response->assertSee('INV-2024-001');
    }

    public function test_tenant_cannot_access_admin_panel(): void
    {
        $response = $this->actingAs($this->tenant)
            ->get('/app');

        $response->assertStatus(403);
    }

    public function test_non_tenant_cannot_access_tenant_panel(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $response = $this->actingAs($admin)
            ->get('/tenant');

        $response->assertStatus(403);
    }

    public function test_tenant_dashboard_shows_widgets(): void
    {
        // Create some test data
        Meter::factory()->count(3)->create([
            'property_id' => $this->property->id,
        ]);

        Invoice::factory()->create([
            'property_id' => $this->property->id,
            'status' => InvoiceStatus::FINALIZED,
            'total_amount' => 150.00,
        ]);

        $response = $this->actingAs($this->tenant)
            ->get('/tenant');

        $response->assertStatus(200);
        $response->assertSee('Total Meters');
        $response->assertSee('Recent Invoices');
    }
}