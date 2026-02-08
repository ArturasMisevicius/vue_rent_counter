<?php

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Feature: filament-admin-panel, Property 8: Invoice items visibility
// Validates: Requirements 4.3
test('InvoiceResource displays all invoice items with snapshotted pricing details', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 1000);
    
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
    
    // Create an invoice (DRAFT status so it can be edited)
    $invoice = Invoice::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'tenant_renter_id' => $tenantRenter->id,
        'billing_period_start' => now()->subMonth()->startOfMonth(),
        'billing_period_end' => now()->subMonth()->endOfMonth(),
        'total_amount' => fake()->randomFloat(2, 50, 500),
        'status' => InvoiceStatus::DRAFT,
    ]);
    
    // Generate random number of invoice items (between 1 and 10)
    $itemsCount = fake()->numberBetween(1, 10);
    $createdItems = [];
    
    for ($i = 0; $i < $itemsCount; $i++) {
        $quantity = fake()->randomFloat(2, 1, 1000);
        $unitPrice = fake()->randomFloat(4, 0.01, 10);
        $total = round($quantity * $unitPrice, 2);
        
        // Create meter reading snapshot data
        $meterReadingSnapshot = [
            'meter_id' => fake()->numberBetween(1, 100),
            'previous_reading' => fake()->randomFloat(2, 0, 10000),
            'current_reading' => fake()->randomFloat(2, 0, 10000),
            'consumption' => fake()->randomFloat(2, 0, 1000),
            'reading_date' => now()->subDays(fake()->numberBetween(1, 30))->format('Y-m-d'),
        ];
        
        $item = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => fake()->sentence(3),
            'quantity' => $quantity,
            'unit' => fake()->randomElement(['kWh', 'm³', 'unit', 'month']),
            'unit_price' => $unitPrice,
            'total' => $total,
            'meter_reading_snapshot' => $meterReadingSnapshot,
        ]);
        
        $createdItems[] = $item;
    }
    
    // Create a manager for the tenant
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    // Act as the manager
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    // Property: When viewing an invoice, all invoice items should be accessible
    $component = Livewire::test(InvoiceResource\Pages\ViewInvoice::class, [
        'record' => $invoice->id,
    ]);
    
    $component->assertSuccessful();
    
    // Verify the invoice is loaded
    expect($component->instance()->record->id)->toBe($invoice->id);
    
    // Get the invoice items through the relationship
    $invoiceItems = $component->instance()->record->items;
    
    // Property: All created invoice items should be present
    expect($invoiceItems)->toHaveCount($itemsCount);
    
    // Property: Each invoice item should have all required pricing details
    foreach ($createdItems as $createdItem) {
        $foundItem = $invoiceItems->firstWhere('id', $createdItem->id);
        
        expect($foundItem)->not->toBeNull();
        expect($foundItem->description)->toBe($createdItem->description);
        expect($foundItem->quantity)->toBe($createdItem->quantity);
        expect($foundItem->unit)->toBe($createdItem->unit);
        expect($foundItem->unit_price)->toBe($createdItem->unit_price);
        expect($foundItem->total)->toBe($createdItem->total);
        
        // Property: Snapshotted pricing details should be preserved
        expect($foundItem->meter_reading_snapshot)->not->toBeNull();
        expect($foundItem->meter_reading_snapshot)->toBeArray();
        expect($foundItem->meter_reading_snapshot)->toHaveKeys(['meter_id', 'previous_reading', 'current_reading', 'consumption', 'reading_date']);
    }
    
    // Verify the relation manager can be accessed
    $relationManager = Livewire::test(
        InvoiceResource\RelationManagers\ItemsRelationManager::class,
        [
            'ownerRecord' => $invoice,
            'pageClass' => InvoiceResource\Pages\ViewInvoice::class,
        ]
    );
    
    $relationManager->assertSuccessful();
    
    // Get table records from the relation manager
    $tableRecords = $relationManager->instance()->getTableRecords();
    
    // Property: All invoice items should be visible in the relation manager table
    expect($tableRecords)->toHaveCount($itemsCount);
    
    // Property: Each item in the table should match the created items
    $tableRecords->each(function ($tableItem) use ($createdItems) {
        $matchingItem = collect($createdItems)->firstWhere('id', $tableItem->id);
        
        expect($matchingItem)->not->toBeNull();
        expect($tableItem->description)->toBe($matchingItem->description);
        expect(number_format((float) $tableItem->quantity, 2))->toBe(number_format((float) $matchingItem->quantity, 2));
        expect($tableItem->unit)->toBe($matchingItem->unit);
        expect(number_format((float) $tableItem->unit_price, 4))->toBe(number_format((float) $matchingItem->unit_price, 4));
        expect(number_format((float) $tableItem->total, 2))->toBe(number_format((float) $matchingItem->total, 2));
        expect($tableItem->meter_reading_snapshot)->toBeArray();
    });
})->repeat(100);

// Feature: filament-admin-panel, Property 8: Invoice items visibility
// Validates: Requirements 4.3
test('InvoiceResource displays invoice items even when invoice has no items', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 1000);
    
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
    
    // Create an invoice without any items
    $invoice = Invoice::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'tenant_renter_id' => $tenantRenter->id,
        'billing_period_start' => now()->subMonth()->startOfMonth(),
        'billing_period_end' => now()->subMonth()->endOfMonth(),
        'total_amount' => 0,
        'status' => InvoiceStatus::DRAFT,
    ]);
    
    // Create a manager for the tenant
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    // Act as the manager
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    // Property: When viewing an invoice with no items, the relation manager should still be accessible
    $component = Livewire::test(InvoiceResource\Pages\ViewInvoice::class, [
        'record' => $invoice->id,
    ]);
    
    $component->assertSuccessful();
    
    // Get the invoice items through the relationship
    $invoiceItems = $component->instance()->record->items;
    
    // Property: Invoice should have zero items
    expect($invoiceItems)->toHaveCount(0);
    
    // Verify the relation manager can be accessed even with no items
    $relationManager = Livewire::test(
        InvoiceResource\RelationManagers\ItemsRelationManager::class,
        [
            'ownerRecord' => $invoice,
            'pageClass' => InvoiceResource\Pages\ViewInvoice::class,
        ]
    );
    
    $relationManager->assertSuccessful();
    
    // Get table records from the relation manager
    $tableRecords = $relationManager->instance()->getTableRecords();
    
    // Property: Table should show zero items
    expect($tableRecords)->toHaveCount(0);
})->repeat(100);

// Feature: filament-admin-panel, Property 8: Invoice items visibility
// Validates: Requirements 4.3
test('InvoiceResource preserves snapshotted pricing details across different invoice statuses', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 1000);
    
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
    
    // Create invoices with different statuses
    $statuses = [InvoiceStatus::DRAFT, InvoiceStatus::FINALIZED, InvoiceStatus::PAID];
    $randomStatus = fake()->randomElement($statuses);
    
    $invoice = Invoice::withoutGlobalScopes()->create([
        'tenant_id' => $tenantId,
        'tenant_renter_id' => $tenantRenter->id,
        'billing_period_start' => now()->subMonth()->startOfMonth(),
        'billing_period_end' => now()->subMonth()->endOfMonth(),
        'total_amount' => fake()->randomFloat(2, 50, 500),
        'status' => $randomStatus,
        'finalized_at' => $randomStatus === InvoiceStatus::DRAFT ? null : now(),
    ]);
    
    // Create random number of invoice items
    $itemsCount = fake()->numberBetween(1, 5);
    $createdItems = [];
    
    for ($i = 0; $i < $itemsCount; $i++) {
        $quantity = fake()->randomFloat(2, 1, 1000);
        $unitPrice = fake()->randomFloat(4, 0.01, 10);
        $total = round($quantity * $unitPrice, 2);
        
        // Create detailed meter reading snapshot
        $meterReadingSnapshot = [
            'meter_id' => fake()->numberBetween(1, 100),
            'meter_serial' => fake()->uuid(),
            'previous_reading' => fake()->randomFloat(2, 0, 10000),
            'current_reading' => fake()->randomFloat(2, 0, 10000),
            'consumption' => fake()->randomFloat(2, 0, 1000),
            'reading_date' => now()->subDays(fake()->numberBetween(1, 30))->format('Y-m-d'),
            'tariff_rate' => $unitPrice,
            'service_type' => fake()->randomElement(['electricity', 'water', 'heating']),
        ];
        
        $item = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => fake()->sentence(3),
            'quantity' => $quantity,
            'unit' => fake()->randomElement(['kWh', 'm³', 'unit', 'month']),
            'unit_price' => $unitPrice,
            'total' => $total,
            'meter_reading_snapshot' => $meterReadingSnapshot,
        ]);
        
        $createdItems[] = $item;
    }
    
    // Create a manager for the tenant
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
    
    // Act as the manager
    $this->actingAs($manager);
    session(['tenant_id' => $tenantId]);
    
    // Property: Invoice items should be visible regardless of invoice status
    $component = Livewire::test(InvoiceResource\Pages\ViewInvoice::class, [
        'record' => $invoice->id,
    ]);
    
    $component->assertSuccessful();
    
    // Get the invoice items
    $invoiceItems = $component->instance()->record->items;
    
    // Property: All items should be present
    expect($invoiceItems)->toHaveCount($itemsCount);
    
    // Property: Snapshotted pricing details should be preserved regardless of status
    foreach ($createdItems as $createdItem) {
        $foundItem = $invoiceItems->firstWhere('id', $createdItem->id);
        
        expect($foundItem)->not->toBeNull();
        
        // Verify all snapshot fields are preserved
        expect($foundItem->meter_reading_snapshot)->not->toBeNull();
        expect($foundItem->meter_reading_snapshot)->toBeArray();
        expect($foundItem->meter_reading_snapshot)->toHaveKeys([
            'meter_id',
            'meter_serial',
            'previous_reading',
            'current_reading',
            'consumption',
            'reading_date',
            'tariff_rate',
            'service_type',
        ]);
        
        // Verify snapshot values match original
        $originalSnapshot = $createdItem->meter_reading_snapshot;
        expect($foundItem->meter_reading_snapshot['meter_id'])->toBe($originalSnapshot['meter_id']);
        expect($foundItem->meter_reading_snapshot['meter_serial'])->toBe($originalSnapshot['meter_serial']);
        expect($foundItem->meter_reading_snapshot['service_type'])->toBe($originalSnapshot['service_type']);
        
        // Verify pricing details are preserved
        expect($foundItem->unit_price)->toBe($createdItem->unit_price);
        expect($foundItem->total)->toBe($createdItem->total);
    }
})->repeat(100);
