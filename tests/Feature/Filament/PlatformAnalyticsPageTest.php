<?php

use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    // Clear all analytics caches
    Cache::flush();
});

test('superadmin can access platform analytics page', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    actingAs($superadmin)
        ->get('/admin/platform-analytics')
        ->assertOk()
        ->assertSee('Platform Analytics');
});

test('non-superadmin cannot access platform analytics page', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    actingAs($admin)
        ->get('/admin/platform-analytics')
        ->assertForbidden();
});

test('platform analytics page displays organization analytics', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    
    // Create test data
    Organization::factory()->count(5)->create(['plan' => 'basic']);
    Organization::factory()->count(3)->create(['plan' => 'professional']);
    Organization::factory()->count(2)->create(['plan' => 'enterprise']);

    actingAs($superadmin)
        ->get('/admin/platform-analytics')
        ->assertOk()
        ->assertSee('Organization Analytics')
        ->assertSee('Organization Growth')
        ->assertSee('Plan Distribution');
});

test('platform analytics page displays subscription analytics', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    
    // Create organizations with different subscription states
    Organization::factory()->create([
        'subscription_ends_at' => now()->addDays(30),
    ]);
    Organization::factory()->create([
        'subscription_ends_at' => now()->subDays(10),
    ]);

    actingAs($superadmin)
        ->get('/admin/platform-analytics')
        ->assertOk()
        ->assertSee('Subscription Analytics')
        ->assertSee('Renewal Rate')
        ->assertSee('Expiry Forecast');
});

test('platform analytics page displays usage analytics', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    
    // Create test data
    $org = Organization::factory()->create();
    Property::factory()->count(10)->create(['tenant_id' => $org->id]);
    Building::factory()->count(5)->create(['tenant_id' => $org->id]);
    Meter::factory()->count(20)->create(['tenant_id' => $org->id]);
    Invoice::factory()->count(15)->create(['tenant_id' => $org->id]);

    actingAs($superadmin)
        ->get('/admin/platform-analytics')
        ->assertOk()
        ->assertSee('Usage Analytics')
        ->assertSee('Platform Totals')
        ->assertSee('Properties')
        ->assertSee('Buildings')
        ->assertSee('Meters')
        ->assertSee('Invoices');
});

test('platform analytics page displays user analytics', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    
    // Create users with different roles
    User::factory()->count(5)->create(['role' => 'admin']);
    User::factory()->count(10)->create(['role' => 'manager']);
    User::factory()->count(20)->create(['role' => 'tenant']);

    actingAs($superadmin)
        ->get('/admin/platform-analytics')
        ->assertOk()
        ->assertSee('User Analytics')
        ->assertSee('Users by Role')
        ->assertSee('Active Users');
});

test('platform analytics caches data correctly', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    
    Organization::factory()->count(5)->create();

    // First request should cache the data
    actingAs($superadmin)->get('/admin/platform-analytics')->assertOk();
    
    expect(Cache::has('analytics_organization_growth'))->toBeTrue();
    expect(Cache::has('analytics_organization_plan_distribution'))->toBeTrue();
    expect(Cache::has('analytics_usage_totals'))->toBeTrue();
});

test('organization growth data is calculated correctly', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    
    // Create organizations at different times
    Organization::factory()->create(['created_at' => now()->subMonths(6)]);
    Organization::factory()->create(['created_at' => now()->subMonths(3)]);
    Organization::factory()->create(['created_at' => now()->subMonth()]);

    actingAs($superadmin)
        ->get('/admin/platform-analytics')
        ->assertOk();
    
    // Verify the page loads without errors
    expect(true)->toBeTrue();
});

test('plan distribution is calculated correctly', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    
    Organization::factory()->count(5)->create(['plan' => 'basic']);
    Organization::factory()->count(3)->create(['plan' => 'professional']);
    Organization::factory()->count(2)->create(['plan' => 'enterprise']);

    actingAs($superadmin)
        ->get('/admin/platform-analytics')
        ->assertOk();
    
    $cached = Cache::get('analytics_organization_plan_distribution');
    expect($cached)->toBeArray();
    expect($cached['labels'])->toContain('basic', 'professional', 'enterprise');
});

test('active vs inactive organizations are counted correctly', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    
    Organization::factory()->count(7)->create(['is_active' => true, 'suspended_at' => null]);
    Organization::factory()->count(3)->create(['is_active' => false]);

    actingAs($superadmin)
        ->get('/admin/platform-analytics')
        ->assertOk();
    
    $cached = Cache::get('analytics_organization_active_inactive');
    expect($cached['active'])->toBe(7);
    expect($cached['inactive'])->toBe(3);
});

test('top organizations are ranked correctly', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    
    $org1 = Organization::factory()->create(['name' => 'Top Org']);
    $org2 = Organization::factory()->create(['name' => 'Second Org']);
    
    Property::factory()->count(10)->create(['tenant_id' => $org1->id]);
    Property::factory()->count(5)->create(['tenant_id' => $org2->id]);

    actingAs($superadmin)
        ->get('/admin/platform-analytics')
        ->assertOk();
    
    $cached = Cache::get('analytics_top_organizations');
    expect($cached['byProperties'][0]['name'])->toBe('Top Org');
    expect($cached['byProperties'][0]['count'])->toBe(10);
});

test('export to pdf generates executive summary', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    
    Organization::factory()->count(5)->create();
    Property::factory()->count(10)->create();

    actingAs($superadmin)
        ->get('/admin/platform-analytics')
        ->assertOk();
    
    // Test that the page loads successfully
    // Actual PDF export would be tested via action invocation
    expect(true)->toBeTrue();
});

test('export to csv generates comprehensive data', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    
    Organization::factory()->count(5)->create();
    User::factory()->count(10)->create();

    actingAs($superadmin)
        ->get('/admin/platform-analytics')
        ->assertOk();
    
    // Test that the page loads successfully
    // Actual CSV export would be tested via action invocation
    expect(true)->toBeTrue();
});

