<?php

use App\Models\User;
use App\Models\Property;
use App\Models\Meter;
use App\Models\Invoice;
use App\Models\Provider;
use App\Models\Tariff;
use App\Enums\UserRole;
use App\Helpers\BreadcrumbHelper;

test('breadcrumbs are generated for admin pages', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    
    $this->actingAs($admin)->get(route('admin.users.index'));
    
    $breadcrumbs = BreadcrumbHelper::generate();
    
    expect($breadcrumbs)->toHaveCount(2);
    expect($breadcrumbs[0]['label'])->toBe('Dashboard');
    expect($breadcrumbs[1]['label'])->toBe('Users');
});

test('breadcrumbs are generated for manager pages', function () {
    $manager = User::factory()->create(['role' => UserRole::MANAGER]);
    
    $this->actingAs($manager)->get(route('manager.properties.index'));
    
    $breadcrumbs = BreadcrumbHelper::generate();
    
    expect($breadcrumbs)->toHaveCount(2);
    expect($breadcrumbs[0]['label'])->toBe('Dashboard');
    expect($breadcrumbs[1]['label'])->toBe('Properties');
});

test('breadcrumbs are generated for tenant pages', function () {
    $tenant = User::factory()->create(['role' => UserRole::TENANT]);
    
    $this->actingAs($tenant)->get(route('tenant.meters.index'));
    
    $breadcrumbs = BreadcrumbHelper::generate();
    
    expect($breadcrumbs)->toHaveCount(2);
    expect($breadcrumbs[0]['label'])->toBe('Dashboard');
    expect($breadcrumbs[1]['label'])->toBe('Meters');
});

test('breadcrumbs show hierarchical path for nested resources', function () {
    $manager = User::factory()->create(['role' => UserRole::MANAGER]);
    $property = Property::factory()->create(['tenant_id' => $manager->tenant_id]);
    
    $this->actingAs($manager)->get(route('manager.properties.show', $property));
    
    $breadcrumbs = BreadcrumbHelper::generate();
    
    expect($breadcrumbs)->toHaveCount(3);
    expect($breadcrumbs[0]['label'])->toBe('Dashboard');
    expect($breadcrumbs[1]['label'])->toBe('Properties');
    expect($breadcrumbs[2]['active'])->toBeTrue();
});

test('breadcrumbs include clickable links to parent pages', function () {
    $manager = User::factory()->create(['role' => UserRole::MANAGER]);
    $property = Property::factory()->create(['tenant_id' => $manager->tenant_id]);
    
    $this->actingAs($manager)->get(route('manager.properties.show', $property));
    
    $breadcrumbs = BreadcrumbHelper::generate();
    
    // Dashboard should have a URL
    expect($breadcrumbs[0]['url'])->toBe(route('manager.dashboard'));
    // Properties should have a URL
    expect($breadcrumbs[1]['url'])->toBe(route('manager.properties.index'));
    // Current page should not have a URL
    expect($breadcrumbs[2]['url'])->toBeNull();
});

test('breadcrumb helper generates correct path for index pages', function () {
    $manager = User::factory()->create(['role' => UserRole::MANAGER]);
    
    $this->actingAs($manager);
    
    // Simulate being on properties index
    $this->get(route('manager.properties.index'));
    
    $breadcrumbs = BreadcrumbHelper::generate();
    
    expect($breadcrumbs)->toHaveCount(2);
    expect($breadcrumbs[0]['label'])->toBe('Dashboard');
    expect($breadcrumbs[0]['active'])->toBeFalse();
    expect($breadcrumbs[1]['label'])->toBe('Properties');
    expect($breadcrumbs[1]['active'])->toBeTrue();
});

test('breadcrumb helper generates correct path for show pages', function () {
    $manager = User::factory()->create(['role' => UserRole::MANAGER]);
    $property = Property::factory()->create(['tenant_id' => $manager->tenant_id]);
    
    $this->actingAs($manager);
    
    // Simulate being on property show
    $this->get(route('manager.properties.show', $property));
    
    $breadcrumbs = BreadcrumbHelper::generate();
    
    expect($breadcrumbs)->toHaveCount(3);
    expect($breadcrumbs[0]['label'])->toBe('Dashboard');
    expect($breadcrumbs[1]['label'])->toBe('Properties');
    expect($breadcrumbs[2]['active'])->toBeTrue();
});

test('breadcrumb helper generates correct path for create pages', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    
    $this->actingAs($admin);
    
    // Simulate being on user create
    $this->get(route('admin.users.create'));
    
    $breadcrumbs = BreadcrumbHelper::generate();
    
    expect($breadcrumbs)->toHaveCount(3);
    expect($breadcrumbs[0]['label'])->toBe('Dashboard');
    expect($breadcrumbs[1]['label'])->toBe('Users');
    expect($breadcrumbs[2]['label'])->toBe('Create');
    expect($breadcrumbs[2]['active'])->toBeTrue();
});

test('breadcrumb helper generates correct path for edit pages', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $provider = Provider::factory()->create();
    
    $this->actingAs($admin);
    
    // Simulate being on provider edit
    $this->get(route('admin.providers.edit', $provider));
    
    $breadcrumbs = BreadcrumbHelper::generate();
    
    expect($breadcrumbs)->toHaveCount(3);
    expect($breadcrumbs[0]['label'])->toBe('Dashboard');
    expect($breadcrumbs[1]['label'])->toBe('Providers');
    expect($breadcrumbs[2]['active'])->toBeTrue();
});

test('breadcrumb helper returns only dashboard for dashboard pages', function () {
    $manager = User::factory()->create(['role' => UserRole::MANAGER]);
    
    $this->actingAs($manager);
    
    // Simulate being on dashboard
    $this->get(route('manager.dashboard'));
    
    $breadcrumbs = BreadcrumbHelper::generate();
    
    expect($breadcrumbs)->toHaveCount(1);
    expect($breadcrumbs[0]['label'])->toBe('Dashboard');
    expect($breadcrumbs[0]['active'])->toBeTrue();
});

test('breadcrumb helper handles resources with hyphens in name', function () {
    $manager = User::factory()->create(['role' => UserRole::MANAGER]);
    
    $this->actingAs($manager);
    
    // Simulate being on meter-readings index
    $this->get(route('manager.meter-readings.index'));
    
    $breadcrumbs = BreadcrumbHelper::generate();
    
    expect($breadcrumbs)->toHaveCount(2);
    expect($breadcrumbs[1]['label'])->toBe('Meter readings');
});

test('breadcrumb helper displays model name for show pages', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $provider = Provider::factory()->create(['name' => 'Test Provider']);
    
    $this->actingAs($admin)->get(route('admin.providers.show', $provider));
    
    $breadcrumbs = BreadcrumbHelper::generate();
    
    expect($breadcrumbs)->toHaveCount(3);
    expect($breadcrumbs[2]['label'])->toBe('Test Provider');
});

test('breadcrumb helper displays property address for property pages', function () {
    $manager = User::factory()->create(['role' => UserRole::MANAGER]);
    $property = Property::factory()->create([
        'tenant_id' => $manager->tenant_id,
        'address' => '123 Test Street'
    ]);
    
    $this->actingAs($manager)->get(route('manager.properties.show', $property));
    
    $breadcrumbs = BreadcrumbHelper::generate();
    
    expect($breadcrumbs)->toHaveCount(3);
    expect($breadcrumbs[2]['label'])->toBe('123 Test Street');
});

test('breadcrumb helper displays meter serial number for meter pages', function () {
    $manager = User::factory()->create(['role' => UserRole::MANAGER]);
    $property = Property::factory()->create(['tenant_id' => $manager->tenant_id]);
    $meter = Meter::factory()->create([
        'property_id' => $property->id,
        'serial_number' => 'MTR-12345'
    ]);
    
    $this->actingAs($manager)->get(route('manager.meters.show', $meter));
    
    $breadcrumbs = BreadcrumbHelper::generate();
    
    expect($breadcrumbs)->toHaveCount(3);
    expect($breadcrumbs[2]['label'])->toBe('MTR-12345');
});

test('breadcrumb helper falls back to ID when no display name available', function () {
    $manager = User::factory()->create(['role' => UserRole::MANAGER]);
    $property = Property::factory()->create(['tenant_id' => $manager->tenant_id]);
    $tenant = \App\Models\Tenant::factory()->create();
    $invoice = Invoice::factory()->create([
        'tenant_id' => $manager->tenant_id,
        'tenant_renter_id' => $tenant->id
    ]);
    
    $this->actingAs($manager)->get(route('manager.invoices.show', $invoice));
    
    $breadcrumbs = BreadcrumbHelper::generate();
    
    expect($breadcrumbs)->toHaveCount(3);
    expect($breadcrumbs[2]['label'])->toContain('#');
});

test('breadcrumbs provide back to list navigation', function () {
    $manager = User::factory()->create(['role' => UserRole::MANAGER]);
    $property = Property::factory()->create(['tenant_id' => $manager->tenant_id]);
    
    $this->actingAs($manager)->get(route('manager.properties.show', $property));
    
    $breadcrumbs = BreadcrumbHelper::generate();
    
    // The breadcrumb to Properties should be a link back to the list
    expect($breadcrumbs[1]['url'])->toBe(route('manager.properties.index'));
    expect($breadcrumbs[1]['active'])->toBeFalse();
});
