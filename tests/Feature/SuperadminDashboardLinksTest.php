<?php

use App\Enums\SubscriptionStatus;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('superadmin dashboard widgets link to CRUD management', function () {
    $superadmin = User::factory()->superadmin()->create();
    $admin = User::factory()->admin(tenantId: 42)->create();
    Subscription::factory()->for($admin)->create([
        'status' => SubscriptionStatus::ACTIVE->value,
    ]);

    $building = Building::factory()->forTenantId($admin->tenant_id)->create();
    $property = Property::factory()->forTenantId($admin->tenant_id)->create([
        'building_id' => $building->id,
        'address' => 'Dashboard Property',
    ]);
    User::factory()->tenant(tenantId: $admin->tenant_id, propertyId: $property->id, parentUserId: $admin->id)->create();
    $tenant = Tenant::factory()->forProperty($property)->create(['tenant_id' => $admin->tenant_id]);
    Invoice::factory()->forTenantRenter($tenant)->create(['tenant_id' => $admin->tenant_id]);

    $response = $this->actingAs($superadmin)->get('/superadmin/dashboard');

    $response->assertOk();
    $response->assertSee(route('superadmin.subscriptions.index'), false);
    $response->assertSee(route('superadmin.subscriptions.index', ['status' => SubscriptionStatus::ACTIVE->value]), false);
    $response->assertSee(route('superadmin.subscriptions.index', ['status' => SubscriptionStatus::EXPIRED->value]), false);
    $response->assertSee(route('superadmin.subscriptions.index', ['status' => SubscriptionStatus::SUSPENDED->value]), false);
    $response->assertSee('id="resource-properties"', false);
    $response->assertSee('id="resource-buildings"', false);
    $response->assertSee('id="resource-tenants"', false);
    $response->assertSee('id="resource-invoices"', false);
});

test('superadmin dashboard shows drill-down data with manage links', function () {
    $superadmin = User::factory()->superadmin()->create();
    $admin = User::factory()->admin(tenantId: 77)->create(['organization_name' => 'Widget Org']);
    $subscription = Subscription::factory()->for($admin)->create([
        'status' => SubscriptionStatus::SUSPENDED->value,
    ]);

    $building = Building::factory()->forTenantId($admin->tenant_id)->create(['address' => '123 Dashboard St']);
    $property = Property::factory()->forTenantId($admin->tenant_id)->create([
        'building_id' => $building->id,
        'address' => 'Suite 12',
    ]);
    $tenantUser = User::factory()->tenant(tenantId: $admin->tenant_id, propertyId: $property->id, parentUserId: $admin->id)->create([
        'email' => 'tenant@example.com',
    ]);
    $tenant = Tenant::factory()->forProperty($property)->create(['tenant_id' => $admin->tenant_id]);
    $invoice = Invoice::factory()->forTenantRenter($tenant)->create([
        'tenant_id' => $admin->tenant_id,
        'total_amount' => 123.45,
    ]);

    $response = $this->actingAs($superadmin)->get('/superadmin/dashboard');

    $response->assertOk();
    $response->assertSee($admin->organization_name, false);
    $response->assertSee(route('superadmin.subscriptions.show', $subscription), false);
    $response->assertSee(route('superadmin.organizations.show', $admin->tenant_id), false);
    $response->assertSee($property->address, false);
    $response->assertSee($building->display_name, false);
    $response->assertSee($tenantUser->email, false);
    $response->assertSee('123.45', false);
});
