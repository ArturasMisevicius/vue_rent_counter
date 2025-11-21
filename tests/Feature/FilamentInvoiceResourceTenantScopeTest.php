<?php

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Property;
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
    
    // Create properties and tenants for tenant 1
    $property1 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'address' => fake()->unique()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $tenantRenter1 = Tenant::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'property_id' => $property1->id,
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'phone' => fake()->phoneNumber(),
        'lease_start' => now()->subMonths(6),
    ]);
    
    // Create invoices for tenant 1
    $invoices1 = [];
    for ($i = 0; $i < $invoicesCount1; $i++) {
        $invoices1[] = Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId1,
            'tenant_renter_id' => $tenantRenter1->id,
            'billing_period_start' => now()->subMonths($i + 1)->startOfMonth(),
            'billing_period_end' => now()->subMonths($i + 1)->endOfMonth(),
            'total_amount' => fake()->randomFloat(2, 50, 500),
            'status' => fake()->randomElement([InvoiceStatus::DRAFT, InvoiceStatus::FINALIZED, InvoiceStatus::PAID]),
        ]);
    }
    
    // Create properties and tenants for tenant 2
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'address' => fake()->unique()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $tenantRenter2 = Tenant::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'property_id' => $property2->id,
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'phone' => fake()->phoneNumber(),
        'lease_start' => now()->subMonths(6),
    ]);
    
    // Create invoices for tenant 2
    $invoices2 = [];
    for ($i = 0; $i < $invoicesCount2; $i++) {
        $invoices2[] = Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId2,
            'tenant_renter_id' => $tenantRenter2->id,
            'billing_period_start' => now()->subMonths($i + 1)->startOfMonth(),
            'billing_period_end' => now()->subMonths($i + 1)->endOfMonth(),
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
test('InvoiceResource edit page only allows editing invoices within tenant scope', function () {
    // Generate random tenant IDs
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    // Create property and tenant for tenant 1
    $property1 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $tenantRenter1 = Tenant::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'property_id' => $property1->id,
        'name' => fake()->name(),
        'email' => fake()->safeEmail(),
        'phone' => fake()->phoneNumber(),
        'lease_start' => now()->subMonths(6),
    ]);
    
    // Create an invoice for tenant 1
    $invoice1 = Invoice::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId1,
        'tenant_renter_id' => $tenantRenter1->id,
        'billing_period_start' => now()->subMonth()->startOfMonth(),
        'billing_period_end' => now()->subMonth()->endOfMonth(),
        'total_amount' => fake()->randomFloat(2, 50, 500),
        'status' => InvoiceStatus::DRAFT,
    ]);
    
    // Create property and tenant for tenant 2
    $property2 = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $tenantRenter2 = Tenant::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'property_id' => $property2->id,
        'name' => fake()->name(),
        'email' => fake()->safeEmail(),
        'phone' => fake()->phoneNumber(),
        'lease_start' => now()->subMonths(6),
    ]);
    
    // Create an invoice for tenant 2
    $invoice2 = Invoice::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId2,
        'tenant_renter_id' => $tenantRenter2->id,
        'billing_period_start' => now()->subMonth()->startOfMonth(),
        'billing_period_end' => now()->subMonth()->endOfMonth(),
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

// Feature: filament-admin-panel, Property 7: Tenant scope isolation for invoices
// Validates: Requirements 4.1
test('InvoiceResource create page automatically assigns tenant_id from authenticated user', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create a manager for the tenant
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    // Create property and tenant for the tenant
    $property = Property::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'address' => fake()->address(),
        'type' => fake()->randomElement(['apartment', 'house']),
        'area_sqm' => fake()->randomFloat(2, 20, 200),
    ]);
    
    $tenantRenter = Tenant::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'property_id' => $property->id,
        'name' => fake()->name(),
        'email' => fake()->safeEmail(),
        'phone' => fake()->phoneNumber(),
        'lease_start' => now()->subMonths(6),
    ]);
    
    // Act as the manager
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    // Generate random invoice data
    $billingStart = now()->subMonth()->startOfMonth();
    $billingEnd = now()->subMonth()->endOfMonth();
    $totalAmount = fake()->randomFloat(2, 50, 500);
    
    // Property: When creating an invoice through Filament, tenant_id should be automatically assigned
    $component = Livewire::test(InvoiceResource\Pages\CreateInvoice::class);
    
    $component->assertSuccessful();
    
    // Fill the form and submit
    $component
        ->fillForm([
            'tenant_renter_id' => $tenantRenter->id,
            'billing_period_start' => $billingStart->format('Y-m-d'),
            'billing_period_end' => $billingEnd->format('Y-m-d'),
            'total_amount' => $totalAmount,
            'status' => InvoiceStatus::DRAFT->value,
        ])
        ->call('create');
    
    // Verify the invoice was created with the correct tenant_id
    $createdInvoice = Invoice::withoutGlobalScopes()
        ->where('tenant_renter_id', $tenantRenter->id)
        ->where('total_amount', $totalAmount)
        ->first();
    
    expect($createdInvoice)->not->toBeNull();
    expect($createdInvoice->tenant_id)->toBe($tenantId);
    expect($createdInvoice->tenant_renter_id)->toBe($tenantRenter->id);
    expect(number_format((float) $createdInvoice->total_amount, 2))->toBe(number_format($totalAmount, 2));
    expect($createdInvoice->billing_period_start->format('Y-m-d'))->toBe($billingStart->format('Y-m-d'));
    expect($createdInvoice->billing_period_end->format('Y-m-d'))->toBe($billingEnd->format('Y-m-d'));
})->repeat(100);
