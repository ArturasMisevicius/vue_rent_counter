<?php

use App\Actions\Admin\Invoices\GenerateBulkInvoicesAction;
use App\Actions\Admin\Properties\UnassignTenantFromPropertyAction;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

it('keeps historical invoices visible to the tenant after unassignment', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create();

    $invoice = Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-HIST-001',
        ]);

    app(UnassignTenantFromPropertyAction::class)->handle(
        property: $property,
        unassignedAt: now()->subHour(),
    );

    $this->actingAs($tenant)
        ->get(route('tenant.invoices.index'))
        ->assertSuccessful()
        ->assertSeeText('INV-HIST-001');

    expect($invoice->fresh()->tenant_user_id)->toBe($tenant->id)
        ->and(Gate::forUser($tenant)->allows('view', $invoice))->toBeTrue()
        ->and(Gate::forUser($tenant)->allows('download', $invoice))->toBeTrue();
});

it('blocks bulk invoice generation for billing periods that start after unassignment', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create();

    $unassignedAt = now()->subDay();

    app(UnassignTenantFromPropertyAction::class)->handle(
        property: $property,
        unassignedAt: $unassignedAt,
    );

    $generatedInvoices = app(GenerateBulkInvoicesAction::class)->handle(
        organization: $organization,
        billingPeriodStart: $unassignedAt->copy()->addDay()->startOfDay(),
        billingPeriodEnd: $unassignedAt->copy()->addMonth()->endOfMonth(),
    );

    expect($generatedInvoices)->toHaveCount(0)
        ->and(Invoice::query()->count())->toBe(0);
});

it('keeps historical invoices visible to organization admins after unassignment', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create();

    $invoice = Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create();

    app(UnassignTenantFromPropertyAction::class)->handle(
        property: $property,
        unassignedAt: now()->subHour(),
    );

    expect(Gate::forUser($admin)->allows('view', $invoice))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('download', $invoice))->toBeTrue();
});
