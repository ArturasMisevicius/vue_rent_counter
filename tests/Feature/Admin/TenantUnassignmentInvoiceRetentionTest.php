<?php

use App\Filament\Actions\Admin\Invoices\GenerateBulkInvoicesAction;
use App\Filament\Actions\Admin\Properties\UnassignTenantFromPropertyAction;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('keeps historical invoices visible after a tenant is unassigned', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $tenant = User::factory()->tenant()->for($organization)->create();
    $admin = User::factory()->admin()->for($organization)->create();

    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'assigned_at' => now()->subMonths(3),
            'unassigned_at' => null,
        ]);

    $invoice = Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-HISTORY-001',
        ]);

    app(UnassignTenantFromPropertyAction::class)->handle($property);

    expect($invoice->fresh()->tenant_user_id)->toBe($tenant->id);

    $this->actingAs($tenant)
        ->get(route('tenant.invoices.index'))
        ->assertSuccessful()
        ->assertSeeText('INV-HISTORY-001');

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.tenants.view', $tenant))
        ->assertSuccessful()
        ->assertSeeText('INV-HISTORY-001');

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.invoices.index'))
        ->assertSuccessful()
        ->assertSeeText('INV-HISTORY-001');
});

it('skips invoice generation for billing periods that start after the tenant was unassigned', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $tenant = User::factory()->tenant()->for($organization)->create();

    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'assigned_at' => now()->subMonths(3),
            'unassigned_at' => now()->subMonth(),
        ]);

    $generatedInvoices = app(GenerateBulkInvoicesAction::class)
        ->handle(
            $organization,
            now()->startOfMonth(),
            now()->endOfMonth(),
        );

    expect($generatedInvoices)->toHaveCount(0)
        ->and(Invoice::query()->count())->toBe(0);
});

it('still generates invoices for billing periods that began before the tenant was unassigned', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $tenant = User::factory()->tenant()->for($organization)->create();

    $billingPeriodStart = now()->startOfMonth()->subMonth();
    $billingPeriodEnd = $billingPeriodStart->copy()->endOfMonth();

    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'assigned_at' => $billingPeriodStart->copy()->subMonth(),
            'unassigned_at' => $billingPeriodStart->copy()->addDays(10),
        ]);

    $generatedInvoices = app(GenerateBulkInvoicesAction::class)
        ->handle($organization, $billingPeriodStart, $billingPeriodEnd);

    expect($generatedInvoices)->toHaveCount(1)
        ->and($generatedInvoices->first()->tenant_user_id)->toBe($tenant->id)
        ->and($generatedInvoices->first()->property_id)->toBe($property->id)
        ->and(Invoice::query()->count())->toBe(1);
});
