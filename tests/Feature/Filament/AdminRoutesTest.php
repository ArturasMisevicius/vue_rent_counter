<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
        'is_active' => true,
    ]);
});

test('admin can access dashboard', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('filament.admin.pages.dashboard'));

    $response->assertStatus(200);
});

test('admin can access properties index', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('filament.admin.resources.properties.index'));

    $response->assertStatus(200);
});

test('admin can access properties create', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('filament.admin.resources.properties.create'));

    $response->assertStatus(200);
});

test('admin can access buildings index', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('filament.admin.resources.buildings.index'));

    $response->assertStatus(200);
});

test('admin can access buildings create', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('filament.admin.resources.buildings.create'));

    $response->assertStatus(200);
});

test('admin can access meters index', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('filament.admin.resources.meters.index'));

    $response->assertStatus(200);
});

test('admin can access meters create', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('filament.admin.resources.meters.create'));

    $response->assertStatus(200);
});

test('admin can access meter readings index', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('filament.admin.resources.meter-readings.index'));

    $response->assertStatus(200);
});

test('admin can access meter readings create', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('filament.admin.resources.meter-readings.create'));

    $response->assertStatus(200);
});

test('admin can access invoices index', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('filament.admin.resources.invoices.index'));

    $response->assertStatus(200);
});

test('admin can access invoices create', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('filament.admin.resources.invoices.create'));

    $response->assertStatus(200);
});

test('admin can access tariffs index', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('filament.admin.resources.tariffs.index'));

    $response->assertStatus(200);
});

test('admin can access tariffs create', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('filament.admin.resources.tariffs.create'));

    $response->assertStatus(200);
});

test('admin can access providers index', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('filament.admin.resources.providers.index'));

    $response->assertStatus(200);
});

test('admin can access providers create', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('filament.admin.resources.providers.create'));

    $response->assertStatus(200);
});

test('admin can access users index', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('filament.admin.resources.users.index'));

    $response->assertStatus(200);
});

test('admin can access users create', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('filament.admin.resources.users.create'));

    $response->assertStatus(200);
});

test('admin can access subscriptions index', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('filament.admin.resources.subscriptions.index'));

    $response->assertStatus(200);
});

test('admin cannot access languages index (superadmin only)', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('filament.admin.resources.languages.index'));

    $response->assertStatus(403);
});

test('admin cannot access translations index (superadmin only)', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('filament.admin.resources.translations.index'));

    $response->assertStatus(403);
});

test('superadmin cannot access admin panel (uses separate superadmin routes)', function () {
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'is_active' => true,
    ]);

    $response = $this->actingAs($superadmin)
        ->get(route('filament.admin.pages.dashboard'));

    $response->assertStatus(403);
});

test('admin can access faqs index', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('filament.admin.resources.faqs.index'));

    $response->assertStatus(200);
});

test('admin can access GDPR compliance page', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('filament.admin.pages.g-d-p-r-compliance'));

    $response->assertStatus(200);
});

test('admin can access privacy policy page', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('filament.admin.pages.privacy-policy'));

    $response->assertStatus(200);
});

test('admin can access terms of service page', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('filament.admin.pages.terms-of-service'));

    $response->assertStatus(200);
});

test('admin can edit property', function () {
    $property = Property::factory()->create([
        'tenant_id' => $this->admin->tenant_id,
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('filament.admin.resources.properties.edit', $property));

    $response->assertStatus(200);
});

test('admin can edit building', function () {
    $building = Building::factory()->create([
        'tenant_id' => $this->admin->tenant_id,
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('filament.admin.resources.buildings.edit', $building));

    $response->assertStatus(200);
});

test('admin can edit meter', function () {
    $property = Property::factory()->create([
        'tenant_id' => $this->admin->tenant_id,
    ]);
    
    $meter = Meter::factory()->create([
        'property_id' => $property->id,
        'tenant_id' => $this->admin->tenant_id,
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('filament.admin.resources.meters.edit', $meter));

    $response->assertStatus(200);
});

test('admin can view invoice', function () {
    $invoice = Invoice::factory()->create([
        'tenant_id' => $this->admin->tenant_id,
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('filament.admin.resources.invoices.view', $invoice));

    $response->assertStatus(200);
});

test('admin can edit invoice', function () {
    $invoice = Invoice::factory()->create([
        'tenant_id' => $this->admin->tenant_id,
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('filament.admin.resources.invoices.edit', $invoice));

    $response->assertStatus(200);
});

test('unauthenticated user cannot access admin dashboard', function () {
    $response = $this->get(route('filament.admin.pages.dashboard'));

    $response->assertRedirect(route('filament.admin.auth.login'));
});

test('tenant cannot access admin dashboard', function () {
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => 1,
        'is_active' => true,
    ]);

    $response = $this->actingAs($tenant)
        ->get(route('filament.admin.pages.dashboard'));

    $response->assertStatus(403);
});

test('manager can access admin dashboard', function () {
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => 1,
        'is_active' => true,
    ]);

    $response = $this->actingAs($manager)
        ->get(route('filament.admin.pages.dashboard'));

    $response->assertStatus(200);
});
