<?php

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Feature: filament-admin-panel, Property 7: Tenant scope isolation for invoices
// Validates: Requirements 4.1
test('InvoiceResource automatically filters invoices by authenticated user tenant_id', function () {
    // Generate random tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create random number of invoices for each tenant
    $invoicesCount1 = fake()->numberBetween(2, 8);
    $invoicesCount2 = fake()->numberBetween(2, 8);
    
    // Create properties and tenants (renters) for each tenant_id using factories
    $property1 = \App\Models\Property::factory()->create([
        'tenant_id' => $tenantId1,
    ]);
    
    $property2 = \App\Models\Property::factory()->create([
        'tenant_id' => $tenantId2,
    ]);
    
    $renter1 = Tenant::factory()->create([
        'tenant_id' => $tenantId1,
        'property_id' => $property1->id,
    ]);
    
    $renter2 = Tenant::factory()->create([
        'tenant_id' => $tenantId2,
        'property_id' => $property2->id,
    ]);
    
    // Create invoices for tenant 1 without global scopes
    $invoices1 = [];
    for ($i = 0; $i < $invoicesCount1; $i++) {
        $invoices1[] = Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId1,
            'tenant_renter_id' => $renter1->id,
            'billing_period_start' => now()->subMonth(),
            'billing_period_end' => now(),
            'total_amount' => fake()->randomFloat(2, 50, 500),
            'status' => fake()->randomElement([InvoiceStatus::DRAFT, InvoiceStatus::FINALIZED, InvoiceStatus::PAID]),
        ]);
    }
    
    // Create invoices for tenant 2 without global scopes
    $invoices2 = [];
    for ($i = 0; $i < $invoicesCount2; $i++) {
        $invoices2[] = Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId2,
            'tenant_renter_id' => $renter2->id,
            'billing_period_start' => now()->subMonth(),
            'billing_period_end' => now(),
            'total_amount' => fake()->randomFloat(2, 50, 500),
            'status' => fake()->randomElement([InvoiceStatus::DRAFT, InvoiceStatus::FINALIZED, InvoiceStatus::PAID]),
        ]);
    }
    
    // Create a manager for tenant 1
    $manager1 = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId1,
    ]);
    
    // Act as manager from tenant 1
    $this->actingAs($manager1);
    
    // Set session tenant_id (this is what TenantScope uses)
    session(['tenant_id' => $tenantId1]);
    
    // Property: When accessing InvoiceResource list page, only tenant 1's invoices should be visible
    $component = Livewire::test(InvoiceResource\Pages\ListInvoices::class);
    
    // Verify the component loaded successfully
    $component->assertSuccessful();
    
    // Get the table records from the component
    $tableRecords = $component->instance()->getTableRecords();
    
    // Property: All returned invoices should belong to tenant 1
    expect($tableRecords)->toHaveCount($invoicesCount1);
    
    $tableRecords->each(function ($invoice) use ($tenantId1) {
        expect($invoice->tenant_id)->toBe($tenantId1);
    });
    
    // Property: Tenant 2's invoices should not be accessible
    foreach ($invoices2 as $invoice2) {
        expect(Invoice::find($invoice2->id))->toBeNull();
    }
    
    // Verify tenant 1's invoices are all present in the table
    $invoiceIds1 = collect($invoices1)->pluck('id')->toArray();
    $tableRecordIds = $tableRecords->pluck('id')->toArray();
    
    expect($tableRecordIds)->toEqualCanonicalizing($invoiceIds1);
    
    // Now switch to manager from tenant 2
    $manager2 = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId2,
    ]);
    
    $this->actingAs($manager2);
    session(['tenant_id' => $tenantId2]);
    
    // Property: When accessing InvoiceResource list page, only tenant 2's invoices should be visible
    $component2 = Livewire::test(InvoiceResource\Pages\ListInvoices::class);
    
    $component2->assertSuccessful();
    
    $tableRecords2 = $component2->instance()->getTableRecords();
    
    // Property: All returned invoices should belong to tenant 2
    expect($tableRecords2)->toHaveCount($invoicesCount2);
    
    $tableRecords2->each(function ($invoice) use ($tenantId2) {
        expect($invoice->tenant_id)->toBe($tenantId2);
    });
    
    // Property: Tenant 1's invoices should not be accessible
    foreach ($invoices1 as $invoice1) {
        expect(Invoice::find($invoice1->id))->toBeNull();
    }
    
    // Verify tenant 2's invoices are all present in the table
    $invoiceIds2 = collect($invoices2)->pluck('id')->toArray();
    $tableRecordIds2 = $tableRecords2->pluck('id')->toArray();
    
    expect($tableRecordIds2)->toEqualCanonicalizing($invoiceIds2);
})->repeat(100);

// Feature: filament-admin-panel, Property 7: Tenant scope isolation for invoices
// Validates: Requirements 4.1
test('InvoiceResource view page only allows viewing invoices within tenant scope', function () {
    // Generate random tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create properties and tenants (renters) using factories
    $property1 = \App\Models\Property::factory()->create([
        'tenant_id' => $tenantId1,
    ]);
    
    $property2 = \App\Models\Property::factory()->create([
        'tenant_id' => $tenantId2,
    ]);
    
    $renter1 = Tenant::factory()->create([
        'tenant_id' => $tenantId1,
        'property_id' => $property1->id,
    ]);
    
    $renter2 = Tenant::factory()->create([
        'tenant_id' => $tenantId2,
        'property_id' => $property2->id,
    ]);
    
    // Create an invoice for tenant 1
    $invoice1 = Invoice::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'tenant_renter_id' => $renter1->id,
        'billing_period_start' => now()->subMonth(),
        'billing_period_end' => now(),
        'total_amount' => fake()->randomFloat(2, 50, 500),
        'status' => InvoiceStatus::DRAFT,
    ]);
    
    // Create an invoice for tenant 2
    $invoice2 = Invoice::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'tenant_renter_id' => $renter2->id,
        'billing_period_start' => now()->subMonth(),
        'billing_period_end' => now(),
        'total_amount' => fake()->randomFloat(2, 50, 500),
        'status' => InvoiceStatus::DRAFT,
    ]);
    
    // Create a manager for tenant 1
    $manager1 = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId1,
    ]);
    
    // Act as manager from tenant 1
    $this->actingAs($manager1);
    session(['tenant_id' => $tenantId1]);
    
    // Property: Manager should be able to access view page for their tenant's invoice
    $component = Livewire::test(InvoiceResource\Pages\ViewInvoice::class, [
        'record' => $invoice1->id,
    ]);
    
    $component->assertSuccessful();
    
    // Verify the correct invoice is loaded
    expect($component->instance()->record->id)->toBe($invoice1->id);
    expect($component->instance()->record->tenant_id)->toBe($tenantId1);
    
    // Property: Manager should NOT be able to access view page for another tenant's invoice
    // This should fail because the invoice won't be found due to tenant scope
    try {
        $component2 = Livewire::test(InvoiceResource\Pages\ViewInvoice::class, [
            'record' => $invoice2->id,
        ]);
        
        // If we get here, the test should fail because access should be denied
        expect(false)->toBeTrue('Manager should not be able to access another tenant\'s invoice');
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // This is expected - the invoice should not be found due to tenant scope
        expect(true)->toBeTrue();
    }
})->repeat(100);

// Feature: filament-admin-panel, Property 7: Tenant scope isolation for invoices
// Validates: Requirements 4.1
test('InvoiceResource edit page only allows editing invoices within tenant scope', function () {
    // Generate random tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create properties and tenants (renters) using factories
    $property1 = \App\Models\Property::factory()->create([
        'tenant_id' => $tenantId1,
    ]);
    
    $property2 = \App\Models\Property::factory()->create([
        'tenant_id' => $tenantId2,
    ]);
    
    $renter1 = Tenant::factory()->create([
        'tenant_id' => $tenantId1,
        'property_id' => $property1->id,
    ]);
    
    $renter2 = Tenant::factory()->create([
        'tenant_id' => $tenantId2,
        'property_id' => $property2->id,
    ]);
    
    // Create a draft invoice for tenant 1
    $invoice1 = Invoice::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'tenant_renter_id' => $renter1->id,
        'billing_period_start' => now()->subMonth(),
        'billing_period_end' => now(),
        'total_amount' => fake()->randomFloat(2, 50, 500),
        'status' => InvoiceStatus::DRAFT,
    ]);
    
    // Create a draft invoice for tenant 2
    $invoice2 = Invoice::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'tenant_renter_id' => $renter2->id,
        'billing_period_start' => now()->subMonth(),
        'billing_period_end' => now(),
        'total_amount' => fake()->randomFloat(2, 50, 500),
        'status' => InvoiceStatus::DRAFT,
    ]);
    
    // Create a manager for tenant 1
    $manager1 = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId1,
    ]);
    
    // Act as manager from tenant 1
    $this->actingAs($manager1);
    session(['tenant_id' => $tenantId1]);
    
    // Property: Manager should be able to access edit page for their tenant's invoice
    $component = Livewire::test(InvoiceResource\Pages\EditInvoice::class, [
        'record' => $invoice1->id,
    ]);
    
    $component->assertSuccessful();
    
    // Verify the correct invoice is loaded
    expect($component->instance()->record->id)->toBe($invoice1->id);
    expect($component->instance()->record->tenant_id)->toBe($tenantId1);
    
    // Property: Manager should NOT be able to access edit page for another tenant's invoice
    // This should fail because the invoice won't be found due to tenant scope
    try {
        $component2 = Livewire::test(InvoiceResource\Pages\EditInvoice::class, [
            'record' => $invoice2->id,
        ]);
        
        // If we get here, the test should fail because access should be denied
        expect(false)->toBeTrue('Manager should not be able to access another tenant\'s invoice');
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // This is expected - the invoice should not be found due to tenant scope
        expect(true)->toBeTrue();
    }
})->repeat(100);
