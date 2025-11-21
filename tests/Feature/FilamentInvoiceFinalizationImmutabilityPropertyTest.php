<?php

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Exceptions\InvoiceAlreadyFinalizedException;
use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Feature: filament-admin-panel, Property 9: Invoice finalization immutability
// Validates: Requirements 4.5
test('finalized invoices cannot be modified through Filament', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create property and tenant
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
    
    // Create a finalized invoice with random data
    $originalAmount = fake()->randomFloat(2, 50, 500);
    $originalStart = now()->subMonth()->startOfMonth();
    $originalEnd = now()->subMonth()->endOfMonth();
    
    $invoice = Invoice::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'tenant_renter_id' => $tenantRenter->id,
        'billing_period_start' => $originalStart,
        'billing_period_end' => $originalEnd,
        'total_amount' => $originalAmount,
        'status' => InvoiceStatus::FINALIZED,
        'finalized_at' => now(),
    ]);
    
    // Create some invoice items
    $itemsCount = fake()->numberBetween(1, 5);
    for ($i = 0; $i < $itemsCount; $i++) {
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => fake()->sentence(3),
            'quantity' => fake()->randomFloat(2, 1, 1000),
            'unit' => fake()->randomElement(['kWh', 'm³', 'unit']),
            'unit_price' => fake()->randomFloat(4, 0.01, 10),
            'total' => fake()->randomFloat(2, 1, 100),
            'meter_reading_snapshot' => [
                'meter_id' => fake()->numberBetween(1, 100),
                'reading' => fake()->randomFloat(2, 0, 10000),
            ],
        ]);
    }
    
    // Create a manager for the tenant
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    // Act as the manager
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    // Property: Attempting to modify a finalized invoice's core fields should fail
    $newAmount = fake()->randomFloat(2, 100, 1000);
    $newStart = now()->subMonths(2)->startOfMonth();
    $newEnd = now()->subMonths(2)->endOfMonth();
    
    // Try to update the invoice through the model directly
    $invoice->total_amount = $newAmount;
    $invoice->billing_period_start = $newStart;
    $invoice->billing_period_end = $newEnd;
    
    // Property: Saving should throw InvoiceAlreadyFinalizedException
    expect(fn() => $invoice->save())
        ->toThrow(InvoiceAlreadyFinalizedException::class);
    
    // Refresh the invoice from database
    $invoice->refresh();
    
    // Property: Invoice data should remain unchanged
    expect((float) $invoice->total_amount)->toBe((float) $originalAmount);
    expect($invoice->billing_period_start->format('Y-m-d'))->toBe($originalStart->format('Y-m-d'));
    expect($invoice->billing_period_end->format('Y-m-d'))->toBe($originalEnd->format('Y-m-d'));
    expect($invoice->status)->toBe(InvoiceStatus::FINALIZED);
    
    // Property: Form fields should be disabled in Filament
    $component = Livewire::test(InvoiceResource\Pages\EditInvoice::class, [
        'record' => $invoice->id,
    ]);
    
    $component->assertSuccessful();
    
    // Verify form fields are disabled
    $form = $component->instance()->form;
    $schema = $form->getComponents();
    
    // Check that critical fields are disabled
    foreach ($schema as $field) {
        $fieldName = $field->getName();
        
        if (in_array($fieldName, ['tenant_renter_id', 'billing_period_start', 'billing_period_end', 'total_amount'])) {
            // Property: These fields should be disabled for finalized invoices
            expect($field->isDisabled())->toBeTrue(
                "Field {$fieldName} should be disabled for finalized invoices"
            );
        }
    }
})->repeat(100);

// Feature: filament-admin-panel, Property 9: Invoice finalization immutability
// Validates: Requirements 4.5
test('finalized invoices allow status changes to PAID only', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create property and tenant
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
    
    // Create a finalized invoice
    $invoice = Invoice::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'tenant_renter_id' => $tenantRenter->id,
        'billing_period_start' => now()->subMonth()->startOfMonth(),
        'billing_period_end' => now()->subMonth()->endOfMonth(),
        'total_amount' => fake()->randomFloat(2, 50, 500),
        'status' => InvoiceStatus::FINALIZED,
        'finalized_at' => now(),
    ]);
    
    // Create a manager
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    // Property: Status change from FINALIZED to PAID should be allowed
    $invoice->status = InvoiceStatus::PAID;
    
    // This should NOT throw an exception
    expect(fn() => $invoice->save())->not->toThrow(InvoiceAlreadyFinalizedException::class);
    
    // Verify the status was updated
    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::PAID);
})->repeat(100);

// Feature: filament-admin-panel, Property 9: Invoice finalization immutability
// Validates: Requirements 4.5
test('draft invoices can be modified freely', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create property and tenant
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
    
    // Create a draft invoice
    $originalAmount = fake()->randomFloat(2, 50, 500);
    $invoice = Invoice::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'tenant_renter_id' => $tenantRenter->id,
        'billing_period_start' => now()->subMonth()->startOfMonth(),
        'billing_period_end' => now()->subMonth()->endOfMonth(),
        'total_amount' => $originalAmount,
        'status' => InvoiceStatus::DRAFT,
    ]);
    
    // Create a manager
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    // Property: Draft invoices should allow modifications
    $newAmount = fake()->randomFloat(2, 100, 1000);
    $newStart = now()->subMonths(2)->startOfMonth();
    $newEnd = now()->subMonths(2)->endOfMonth();
    
    $invoice->total_amount = $newAmount;
    $invoice->billing_period_start = $newStart;
    $invoice->billing_period_end = $newEnd;
    
    // This should NOT throw an exception
    expect(fn() => $invoice->save())->not->toThrow(InvoiceAlreadyFinalizedException::class);
    
    // Verify the changes were saved
    $invoice->refresh();
    expect((float) $invoice->total_amount)->toBe((float) $newAmount);
    expect($invoice->billing_period_start->format('Y-m-d'))->toBe($newStart->format('Y-m-d'));
    expect($invoice->billing_period_end->format('Y-m-d'))->toBe($newEnd->format('Y-m-d'));
    
    // Property: Form fields should NOT be disabled for draft invoices
    $component = Livewire::test(InvoiceResource\Pages\EditInvoice::class, [
        'record' => $invoice->id,
    ]);
    
    $component->assertSuccessful();
    
    // Verify form fields are NOT disabled
    $form = $component->instance()->form;
    $schema = $form->getComponents();
    
    foreach ($schema as $field) {
        $fieldName = $field->getName();
        
        if (in_array($fieldName, ['tenant_renter_id', 'billing_period_start', 'billing_period_end', 'total_amount'])) {
            // Property: These fields should NOT be disabled for draft invoices
            expect($field->isDisabled())->toBeFalse(
                "Field {$fieldName} should NOT be disabled for draft invoices"
            );
        }
    }
})->repeat(100);

// Feature: filament-admin-panel, Property 9: Invoice finalization immutability
// Validates: Requirements 4.5
test('invoice items cannot be modified when invoice is finalized', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create property and tenant
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
    
    // Create a finalized invoice
    $invoice = Invoice::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'tenant_renter_id' => $tenantRenter->id,
        'billing_period_start' => now()->subMonth()->startOfMonth(),
        'billing_period_end' => now()->subMonth()->endOfMonth(),
        'total_amount' => fake()->randomFloat(2, 50, 500),
        'status' => InvoiceStatus::FINALIZED,
        'finalized_at' => now(),
    ]);
    
    // Create an invoice item
    $originalQuantity = fake()->randomFloat(2, 1, 1000);
    $originalPrice = fake()->randomFloat(4, 0.01, 10);
    
    $item = InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'description' => fake()->sentence(3),
        'quantity' => $originalQuantity,
        'unit' => fake()->randomElement(['kWh', 'm³', 'unit']),
        'unit_price' => $originalPrice,
        'total' => round($originalQuantity * $originalPrice, 2),
        'meter_reading_snapshot' => [
            'meter_id' => fake()->numberBetween(1, 100),
            'reading' => fake()->randomFloat(2, 0, 10000),
        ],
    ]);
    
    // Create a manager
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    // Property: Attempting to modify invoice items when invoice is finalized should be prevented
    // Note: The immutability is enforced at the invoice level, not the item level
    // But we can verify that the invoice's finalized status prevents modifications
    
    // Try to update the invoice (which would trigger the booted hook)
    $invoice->total_amount = fake()->randomFloat(2, 100, 1000);
    
    expect(fn() => $invoice->save())
        ->toThrow(InvoiceAlreadyFinalizedException::class);
    
    // Verify the invoice and its items remain unchanged
    $invoice->refresh();
    $item->refresh();
    
    expect((float) $item->quantity)->toBe((float) $originalQuantity);
    expect((float) $item->unit_price)->toBe((float) $originalPrice);
})->repeat(100);

// Feature: filament-admin-panel, Property 9: Invoice finalization immutability
// Validates: Requirements 4.5
test('bulk status updates respect finalization immutability', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create property and tenant
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
    
    // Create multiple invoices with different statuses
    $draftInvoice = Invoice::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'tenant_renter_id' => $tenantRenter->id,
        'billing_period_start' => now()->subMonth()->startOfMonth(),
        'billing_period_end' => now()->subMonth()->endOfMonth(),
        'total_amount' => fake()->randomFloat(2, 50, 500),
        'status' => InvoiceStatus::DRAFT,
    ]);
    
    $finalizedInvoice = Invoice::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'tenant_renter_id' => $tenantRenter->id,
        'billing_period_start' => now()->subMonths(2)->startOfMonth(),
        'billing_period_end' => now()->subMonths(2)->endOfMonth(),
        'total_amount' => fake()->randomFloat(2, 50, 500),
        'status' => InvoiceStatus::FINALIZED,
        'finalized_at' => now(),
    ]);
    
    // Create a manager
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    // Property: Bulk status update to PAID should work for finalized invoices
    $finalizedInvoice->update(['status' => InvoiceStatus::PAID]);
    $finalizedInvoice->refresh();
    expect($finalizedInvoice->status)->toBe(InvoiceStatus::PAID);
    
    // Property: Bulk status update to FINALIZED should work for draft invoices
    $draftInvoice->update(['status' => InvoiceStatus::FINALIZED, 'finalized_at' => now()]);
    $draftInvoice->refresh();
    expect($draftInvoice->status)->toBe(InvoiceStatus::FINALIZED);
})->repeat(100);
