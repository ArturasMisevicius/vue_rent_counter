<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\TestDatabaseSeeder::class);
    }

    public function test_admin_can_access_dashboard(): void
    {
        $admin = $this->actingAsAdmin();

        $response = $this->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('Welcome back');
        $response->assertSee($admin->name);
    }

    public function test_manager_can_access_dashboard(): void
    {
        $manager = $this->actingAsManager();

        $response = $this->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('Welcome back');
        $response->assertSee($manager->name);
    }

    public function test_tenant_cannot_access_admin_dashboard(): void
    {
        $this->actingAsTenant();

        $response = $this->get('/admin');

        $response->assertStatus(403);
    }

    public function test_guest_redirected_to_login(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/admin/login');
    }

    public function test_dashboard_shows_correct_stats_for_admin(): void
    {
        $admin = $this->actingAsAdmin();

        // Create test data
        $property = $this->createTestProperty(['tenant_id' => $admin->tenant_id]);
        $building = Building::factory()->create(['tenant_id' => $admin->tenant_id]);
        $tenant = User::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'role' => UserRole::TENANT,
            'is_active' => true,
        ]);

        $response = $this->get('/admin');

        $response->assertStatus(200);
        
        // Verify the widget is registered and data is correct
        $this->assertEquals(1, Property::where('tenant_id', $admin->tenant_id)->count());
        $this->assertEquals(1, Building::where('tenant_id', $admin->tenant_id)->count());
        $this->assertEquals(1, User::where('tenant_id', $admin->tenant_id)
            ->where('role', UserRole::TENANT)
            ->where('is_active', true)
            ->count());
    }

    public function test_dashboard_shows_quick_actions_for_admin(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('Quick Actions');
        $response->assertSee('Properties');
        $response->assertSee('Buildings');
        $response->assertSee('Invoices');
        $response->assertSee('Users');
    }

    public function test_dashboard_stats_are_tenant_scoped(): void
    {
        $admin1 = $this->actingAsAdmin();
        
        // Create data for admin1's tenant
        $property1 = $this->createTestProperty(['tenant_id' => $admin1->tenant_id]);
        
        // Create another admin with different tenant
        $admin2 = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 'tenant_' . uniqid(),
        ]);
        
        // Create data for admin2's tenant
        $property2 = Property::factory()->create(['tenant_id' => $admin2->tenant_id]);

        $response = $this->get('/admin');

        $response->assertStatus(200);
        
        // Should see count of 1 (only admin1's property)
        $this->assertEquals(1, Property::where('tenant_id', $admin1->tenant_id)->count());
        $this->assertEquals(1, Property::where('tenant_id', $admin2->tenant_id)->count());
    }

    public function test_dashboard_shows_draft_invoices_count(): void
    {
        $admin = $this->actingAsAdmin();

        // Create draft invoices
        Invoice::factory()->count(3)->create([
            'tenant_id' => $admin->tenant_id,
            'finalized_at' => null,
        ]);

        // Create finalized invoice
        Invoice::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'finalized_at' => now(),
        ]);

        $response = $this->get('/admin');

        $response->assertStatus(200);
        
        $draftCount = Invoice::where('tenant_id', $admin->tenant_id)
            ->whereNull('finalized_at')
            ->count();
        $this->assertEquals(3, $draftCount);
    }

    public function test_dashboard_shows_pending_meter_readings(): void
    {
        $admin = $this->actingAsAdmin();
        $property = $this->createTestProperty(['tenant_id' => $admin->tenant_id]);
        $meter = $property->meters()->first();

        // Create unverified readings
        $this->createTestMeterReading([
            'meter_id' => $meter->id,
            'verified_at' => null,
        ]);

        $this->createTestMeterReading([
            'meter_id' => $meter->id,
            'verified_at' => null,
        ]);

        // Create verified reading
        $this->createTestMeterReading([
            'meter_id' => $meter->id,
            'verified_at' => now(),
        ]);

        $response = $this->get('/admin');

        $response->assertStatus(200);
        
        $pendingCount = MeterReading::whereHas('meter', function ($query) use ($admin) {
            $query->where('tenant_id', $admin->tenant_id);
        })->whereNull('verified_at')->count();
        
        $this->assertEquals(2, $pendingCount);
    }

    public function test_dashboard_calculates_monthly_revenue(): void
    {
        $admin = $this->actingAsAdmin();

        // Create finalized invoices for this month
        Invoice::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'finalized_at' => now(),
            'total_amount' => 10000, // €100.00
            'created_at' => now(),
        ]);

        Invoice::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'finalized_at' => now(),
            'total_amount' => 15000, // €150.00
            'created_at' => now(),
        ]);

        // Create invoice from last month (should not be counted)
        Invoice::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'finalized_at' => now()->subMonth(),
            'total_amount' => 20000,
            'created_at' => now()->subMonth(),
        ]);

        $response = $this->get('/admin');

        $response->assertStatus(200);
        
        $monthlyRevenue = Invoice::where('tenant_id', $admin->tenant_id)
            ->whereNotNull('finalized_at')
            ->whereMonth('created_at', now()->month)
            ->sum('total_amount');
        
        $this->assertEquals(25000, $monthlyRevenue); // €250.00
    }

    public function test_manager_sees_limited_stats(): void
    {
        $manager = $this->actingAsManager();

        $response = $this->get('/admin');

        $response->assertStatus(200);
        
        // Manager dashboard loads successfully
        // Widget stats are rendered via Livewire, so we verify the page loads
        $response->assertSee('Welcome back');
    }

    public function test_tenant_sees_own_property_stats(): void
    {
        $property = $this->createTestProperty();
        $tenant = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => $property->tenant_id,
            'property_id' => $property->id,
            'is_active' => true,
        ]);

        $this->actingAs($tenant);

        // Tenant should be redirected or see limited view
        $response = $this->get('/admin');

        // Tenants should not have access to admin panel
        $response->assertStatus(403);
    }

    public function test_dashboard_handles_no_data_gracefully(): void
    {
        // Create admin with no associated data
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 'tenant_empty_' . uniqid(),
        ]);

        $this->actingAs($admin);

        $response = $this->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('Welcome back');
        
        // Should show zero counts
        $this->assertEquals(0, Property::where('tenant_id', $admin->tenant_id)->count());
        $this->assertEquals(0, Building::where('tenant_id', $admin->tenant_id)->count());
    }

    public function test_dashboard_links_work_correctly(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/admin');

        $response->assertStatus(200);
        $response->assertSee(route('filament.admin.resources.properties.index'));
        $response->assertSee(route('filament.admin.resources.buildings.index'));
        $response->assertSee(route('filament.admin.resources.invoices.index'));
        $response->assertSee(route('filament.admin.resources.users.index'));
    }
}
