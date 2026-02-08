<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;

beforeEach(function (): void {
    $tenantId = 1;

    $this->admin = User::factory()->create([
        'tenant_id' => $tenantId,
        'role' => UserRole::ADMIN,
    ]);

    $this->manager = User::factory()->create([
        'tenant_id' => $tenantId,
        'role' => UserRole::MANAGER,
    ]);

    $this->property = Property::factory()->create([
        'tenant_id' => $tenantId,
    ]);

    $this->tenant = User::factory()->create([
        'tenant_id' => $tenantId,
        'role' => UserRole::TENANT,
        'property_id' => $this->property->id,
        'parent_user_id' => $this->admin->id,
    ]);

    Subscription::factory()->active()->create([
        'user_id' => $this->admin->id,
    ]);
});

test('admin can access key admin routes', function (): void {
    $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();
    $this->actingAs($this->admin)->get(route('admin.users.index'))->assertOk();
    $this->actingAs($this->admin)->get(route('admin.providers.index'))->assertOk();
    $this->actingAs($this->admin)->get(route('admin.tariffs.index'))->assertOk();
    $this->actingAs($this->admin)->get(route('admin.tenants.index'))->assertOk();
    $this->actingAs($this->admin)->get(route('admin.audit.index'))->assertOk();
    $this->actingAs($this->admin)->get(route('admin.settings.index'))->assertOk();
});

test('manager can access key manager routes', function (): void {
    $this->actingAs($this->manager)->get(route('manager.dashboard'))->assertOk();
    $this->actingAs($this->manager)->get(route('manager.profile.show'))->assertOk();
    $this->actingAs($this->manager)->get(route('manager.buildings.index'))->assertOk();
    $this->actingAs($this->manager)->get(route('manager.properties.index'))->assertOk();
    $this->actingAs($this->manager)->get(route('manager.meters.index'))->assertOk();
    $this->actingAs($this->manager)->get(route('manager.meter-readings.index'))->assertOk();
    $this->actingAs($this->manager)->get(route('manager.invoices.index'))->assertOk();
    $this->actingAs($this->manager)->get(route('manager.reports.index'))->assertOk();
});

test('tenant can access key tenant routes', function (): void {
    $this->actingAs($this->tenant)->get(route('tenant.dashboard'))->assertOk();
    $this->actingAs($this->tenant)->get(route('tenant.profile.show'))->assertOk();
    $this->actingAs($this->tenant)->get(route('tenant.property.show'))->assertOk();
    $this->actingAs($this->tenant)->get(route('tenant.meters.index'))->assertOk();
    $this->actingAs($this->tenant)->get(route('tenant.meter-readings.index'))->assertOk();
    $this->actingAs($this->tenant)->get(route('tenant.invoices.index'))->assertOk();
});

test('cross-role dashboard access is forbidden', function (): void {
    $this->actingAs($this->manager)->get(route('admin.dashboard'))->assertForbidden();
    $this->actingAs($this->tenant)->get(route('admin.dashboard'))->assertForbidden();
    $this->actingAs($this->tenant)->get(route('manager.dashboard'))->assertForbidden();
    $this->actingAs($this->admin)->get(route('tenant.dashboard'))->assertForbidden();
});

test('guest users are redirected to login for protected dashboards', function (): void {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    $this->get(route('manager.dashboard'))->assertRedirect(route('login'));
    $this->get(route('tenant.dashboard'))->assertRedirect(route('login'));
});

test('legacy unprefixed management routes are not exposed', function (): void {
    $this->actingAs($this->admin)->get('/buildings')->assertNotFound();
    $this->actingAs($this->admin)->get('/properties')->assertNotFound();
    $this->actingAs($this->admin)->get('/meters')->assertNotFound();
    $this->actingAs($this->admin)->get('/meter-readings')->assertNotFound();
    $this->actingAs($this->admin)->get('/reports')->assertNotFound();
});

test('locale set endpoint is available for authenticated users', function (): void {
    $this->actingAs($this->admin)
        ->from(route('admin.dashboard'))
        ->post(route('locale.set'), ['locale' => 'en'])
        ->assertRedirect(route('admin.dashboard'));
});
