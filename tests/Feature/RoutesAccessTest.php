<?php

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\Tenant;
use App\Models\User;

beforeEach(function () {
    // Create users for each role
    $this->admin = User::factory()->create([
        'tenant_id' => 1,
        'role' => UserRole::ADMIN,
        'email' => 'admin@test.com',
    ]);

    $this->manager = User::factory()->create([
        'tenant_id' => 1,
        'role' => UserRole::MANAGER,
        'email' => 'manager@test.com',
    ]);

    $this->tenant = User::factory()->create([
        'tenant_id' => 1,
        'role' => UserRole::TENANT,
        'email' => 'tenant@test.com',
    ]);

    // Create test data
    $this->building = Building::factory()->create(['tenant_id' => 1]);
    $this->property = Property::factory()->create([
        'tenant_id' => 1,
        'building_id' => $this->building->id,
    ]);
    $this->tenantModel = Tenant::factory()->create([
        'tenant_id' => 1,
        'property_id' => $this->property->id,
    ]);
    $this->meter = Meter::factory()->create([
        'tenant_id' => 1,
        'property_id' => $this->property->id,
    ]);
    $this->meterReading = MeterReading::factory()->create([
        'tenant_id' => 1,
        'meter_id' => $this->meter->id,
        'entered_by' => $this->manager->id,
    ]);
    $this->invoice = Invoice::factory()->create([
        'tenant_id' => 1,
        'tenant_renter_id' => $this->tenantModel->id,
    ]);
    $this->provider = Provider::factory()->create();
    $this->tariff = Tariff::factory()->create([
        'provider_id' => $this->provider->id,
    ]);
});

// ============================================================================
// ADMIN ROUTES TESTS
// ============================================================================

test('admin can access admin dashboard', function () {
    $response = $this->actingAs($this->admin)->get('/admin/dashboard');
    $response->assertStatus(200);
});

test('admin can access user management routes', function () {
    $this->actingAs($this->admin)->get('/admin/users')->assertStatus(200);
    $this->actingAs($this->admin)->get('/admin/users/create')->assertStatus(200);
    $this->actingAs($this->admin)->get("/admin/users/{$this->manager->id}")->assertStatus(200);
    $this->actingAs($this->admin)->get("/admin/users/{$this->manager->id}/edit")->assertStatus(200);
});

test('admin can access provider management routes', function () {
    $this->actingAs($this->admin)->get('/admin/providers')->assertStatus(200);
    $this->actingAs($this->admin)->get('/admin/providers/create')->assertStatus(200);
    $this->actingAs($this->admin)->get("/admin/providers/{$this->provider->id}")->assertStatus(200);
    $this->actingAs($this->admin)->get("/admin/providers/{$this->provider->id}/edit")->assertStatus(200);
});

test('admin can access tariff management routes', function () {
    $this->actingAs($this->admin)->get('/admin/tariffs')->assertStatus(200);
    $this->actingAs($this->admin)->get('/admin/tariffs/create')->assertStatus(200);
    $this->actingAs($this->admin)->get("/admin/tariffs/{$this->tariff->id}")->assertStatus(200);
    $this->actingAs($this->admin)->get("/admin/tariffs/{$this->tariff->id}/edit")->assertStatus(200);
});

test('admin can access audit routes', function () {
    $this->actingAs($this->admin)->get('/admin/audit')->assertStatus(200);
});

test('admin can access system settings routes', function () {
    $this->actingAs($this->admin)->get('/admin/settings')->assertStatus(200);
});

// ============================================================================
// MANAGER ROUTES TESTS
// ============================================================================

test('manager can access manager dashboard', function () {
    $response = $this->actingAs($this->manager)->get('/manager/dashboard');
    $response->assertStatus(200);
});

test('manager can access profile routes', function () {
    $this->actingAs($this->manager)->get('/manager/profile')->assertStatus(200);
});

test('manager can view providers (read-only)', function () {
    $this->actingAs($this->manager)->get('/manager/providers')->assertStatus(200);
    $this->actingAs($this->manager)->get("/manager/providers/{$this->provider->id}")->assertStatus(200);
});

test('manager can view tariffs (read-only)', function () {
    $this->actingAs($this->manager)->get('/manager/tariffs')->assertStatus(200);
    $this->actingAs($this->manager)->get("/manager/tariffs/{$this->tariff->id}")->assertStatus(200);
});

test('manager cannot access admin routes', function () {
    $this->actingAs($this->manager)->get('/admin/dashboard')->assertStatus(403);
    $this->actingAs($this->manager)->get('/admin/users')->assertStatus(403);
    $this->actingAs($this->manager)->get('/admin/settings')->assertStatus(403);
});

test('manager can access shared routes', function () {
    $this->actingAs($this->manager)->get('/buildings')->assertStatus(200);
    $this->actingAs($this->manager)->get('/properties')->assertStatus(200);
    $this->actingAs($this->manager)->get('/tenants')->assertStatus(200);
    $this->actingAs($this->manager)->get('/meters')->assertStatus(200);
    $this->actingAs($this->manager)->get('/meter-readings')->assertStatus(200);
    $this->actingAs($this->manager)->get('/invoices')->assertStatus(200);
    $this->actingAs($this->manager)->get('/reports')->assertStatus(200);
});

// ============================================================================
// TENANT ROUTES TESTS
// ============================================================================

test('tenant can access tenant dashboard', function () {
    $response = $this->actingAs($this->tenant)->get('/tenant/dashboard');
    $response->assertStatus(200);
});

test('tenant can access profile routes', function () {
    $this->actingAs($this->tenant)->get('/tenant/profile')->assertStatus(200);
});

test('tenant can view own property', function () {
    $this->actingAs($this->tenant)->get('/tenant/property')->assertStatus(200);
    $this->actingAs($this->tenant)->get('/tenant/property/meters')->assertStatus(200);
});

test('tenant can view own meters', function () {
    $this->actingAs($this->tenant)->get('/tenant/meters')->assertStatus(200);
});

test('tenant can view own meter readings', function () {
    $this->actingAs($this->tenant)->get('/tenant/meter-readings')->assertStatus(200);
});

test('tenant can view own invoices', function () {
    $this->actingAs($this->tenant)->get('/tenant/invoices')->assertStatus(200);
});

test('tenant cannot access admin routes', function () {
    $this->actingAs($this->tenant)->get('/admin/dashboard')->assertStatus(403);
    $this->actingAs($this->tenant)->get('/admin/users')->assertStatus(403);
});

test('tenant cannot access manager routes', function () {
    $this->actingAs($this->tenant)->get('/manager/dashboard')->assertStatus(403);
});

test('tenant cannot access shared management routes', function () {
    $this->actingAs($this->tenant)->get('/buildings')->assertStatus(403);
    $this->actingAs($this->tenant)->get('/properties')->assertStatus(403);
    $this->actingAs($this->tenant)->get('/tenants')->assertStatus(403);
    $this->actingAs($this->tenant)->get('/meters')->assertStatus(403);
    $this->actingAs($this->tenant)->get('/meter-readings')->assertStatus(403);
    $this->actingAs($this->tenant)->get('/invoices')->assertStatus(403);
    $this->actingAs($this->tenant)->get('/reports')->assertStatus(403);
});

// ============================================================================
// SHARED ROUTES TESTS (ADMIN & MANAGER)
// ============================================================================

test('admin and manager can access building routes', function () {
    $this->actingAs($this->admin)->get('/buildings')->assertStatus(200);
    $this->actingAs($this->manager)->get('/buildings')->assertStatus(200);
    
    $this->actingAs($this->admin)->get('/buildings/create')->assertStatus(200);
    $this->actingAs($this->manager)->get('/buildings/create')->assertStatus(200);
    
    $this->actingAs($this->admin)->get("/buildings/{$this->building->id}")->assertStatus(200);
    $this->actingAs($this->manager)->get("/buildings/{$this->building->id}")->assertStatus(200);
});

test('admin and manager can access property routes', function () {
    $this->actingAs($this->admin)->get('/properties')->assertStatus(200);
    $this->actingAs($this->manager)->get('/properties')->assertStatus(200);
    
    $this->actingAs($this->admin)->get("/properties/{$this->property->id}")->assertStatus(200);
    $this->actingAs($this->manager)->get("/properties/{$this->property->id}")->assertStatus(200);
});

test('admin and manager can access tenant routes', function () {
    $this->actingAs($this->admin)->get('/tenants')->assertStatus(200);
    $this->actingAs($this->manager)->get('/tenants')->assertStatus(200);
    
    $this->actingAs($this->admin)->get("/tenants/{$this->tenantModel->id}")->assertStatus(200);
    $this->actingAs($this->manager)->get("/tenants/{$this->tenantModel->id}")->assertStatus(200);
});

test('admin and manager can access meter routes', function () {
    $this->actingAs($this->admin)->get('/meters')->assertStatus(200);
    $this->actingAs($this->manager)->get('/meters')->assertStatus(200);
    
    $this->actingAs($this->admin)->get("/meters/{$this->meter->id}")->assertStatus(200);
    $this->actingAs($this->manager)->get("/meters/{$this->meter->id}")->assertStatus(200);
});

test('admin and manager can access meter reading routes', function () {
    $this->actingAs($this->admin)->get('/meter-readings')->assertStatus(200);
    $this->actingAs($this->manager)->get('/meter-readings')->assertStatus(200);
    
    $this->actingAs($this->admin)->get("/meter-readings/{$this->meterReading->id}")->assertStatus(200);
    $this->actingAs($this->manager)->get("/meter-readings/{$this->meterReading->id}")->assertStatus(200);
});

test('admin and manager can access invoice routes', function () {
    $this->actingAs($this->admin)->get('/invoices')->assertStatus(200);
    $this->actingAs($this->manager)->get('/invoices')->assertStatus(200);
    
    $this->actingAs($this->admin)->get("/invoices/{$this->invoice->id}")->assertStatus(200);
    $this->actingAs($this->manager)->get("/invoices/{$this->invoice->id}")->assertStatus(200);
});

test('admin and manager can access report routes', function () {
    $this->actingAs($this->admin)->get('/reports')->assertStatus(200);
    $this->actingAs($this->manager)->get('/reports')->assertStatus(200);
    
    $this->actingAs($this->admin)->get('/reports/consumption')->assertStatus(200);
    $this->actingAs($this->manager)->get('/reports/consumption')->assertStatus(200);
});

// ============================================================================
// AUTHENTICATION TESTS
// ============================================================================

test('guest cannot access protected routes', function () {
    $this->get('/admin/dashboard')->assertRedirect('/login');
    $this->get('/manager/dashboard')->assertRedirect('/login');
    $this->get('/tenant/dashboard')->assertRedirect('/login');
    $this->get('/buildings')->assertRedirect('/login');
    $this->get('/properties')->assertRedirect('/login');
    $this->get('/invoices')->assertRedirect('/login');
});

test('guest can access login and register pages', function () {
    $this->get('/login')->assertStatus(200);
    $this->get('/register')->assertStatus(200);
});

// ============================================================================
// ROLE SEPARATION TESTS
// ============================================================================

test('roles are properly separated', function () {
    // Admin can access everything
    $this->actingAs($this->admin)->get('/admin/dashboard')->assertStatus(200);
    $this->actingAs($this->admin)->get('/buildings')->assertStatus(200);
    
    // Manager cannot access admin routes
    $this->actingAs($this->manager)->get('/admin/dashboard')->assertStatus(403);
    $this->actingAs($this->manager)->get('/admin/users')->assertStatus(403);
    
    // Manager can access shared routes
    $this->actingAs($this->manager)->get('/buildings')->assertStatus(200);
    $this->actingAs($this->manager)->get('/properties')->assertStatus(200);
    
    // Tenant cannot access admin or manager routes
    $this->actingAs($this->tenant)->get('/admin/dashboard')->assertStatus(403);
    $this->actingAs($this->tenant)->get('/manager/dashboard')->assertStatus(403);
    $this->actingAs($this->tenant)->get('/buildings')->assertStatus(403);
    
    // Tenant can only access own routes
    $this->actingAs($this->tenant)->get('/tenant/dashboard')->assertStatus(200);
    $this->actingAs($this->tenant)->get('/tenant/invoices')->assertStatus(200);
});
