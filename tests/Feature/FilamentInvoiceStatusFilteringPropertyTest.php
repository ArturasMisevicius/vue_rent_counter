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

// Feature: filament-admin-panel, Property 10: Invoice status filtering
// Validates: Requirements 4.6
test('InvoiceResource filters invoices by status correctly', function () {
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
    
    // Create random number of invoices with different statuses
    $draftCount = fake()->numberBetween(1, 5);
    $finalizedCount = fake()->numberBetween(1, 5);
    $paidCount = fake()->numberBetween(1, 5);
    
    $draftInvoices = [];
    $finalizedInvoices = [];
    $paidInvoices = [];
    
    // Create draft invoices
    for ($i = 0; $i < $draftCount; $i++) {
        $draftInvoices[] = Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'tenant_renter_id' => $tenantRenter->id,
            'billing_period_start' => now()->subMonths($i + 1)->startOfMonth(),
            'billing_period_end' => now()->subMonths($i + 1)->endOfMonth(),
            'total_amount' => fake()->randomFloat(2, 50, 500),
            'status' => InvoiceStatus::DRAFT,
        ]);
    }
    
    // Create finalized invoices
    for ($i = 0; $i < $finalizedCount; $i++) {
        $finalizedInvoices[] = Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'tenant_renter_id' => $tenantRenter->id,
            'billing_period_start' => now()->subMonths($i + 10)->startOfMonth(),
            'billing_period_end' => now()->subMonths($i + 10)->endOfMonth(),
            'total_amount' => fake()->randomFloat(2, 50, 500),
            'status' => InvoiceStatus::FINALIZED,
            'finalized_at' => now(),
        ]);
    }
    
    // Create paid invoices
    for ($i = 0; $i < $paidCount; $i++) {
        $paidInvoices[] = Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'tenant_renter_id' => $tenantRenter->id,
            'billing_period_start' => now()->subMonths($i + 20)->startOfMonth(),
            'billing_period_end' => now()->subMonths($i + 20)->endOfMonth(),
            'total_amount' => fake()->randomFloat(2, 50, 500),
            'status' => InvoiceStatus::PAID,
            'finalized_at' => now(),
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
    
    // Property: When filtering by DRAFT status, only draft invoices should be returned
    $component = Livewire::test(InvoiceResource\Pages\ListInvoices::class);
    $component->assertSuccessful();
    
    // Apply DRAFT filter
    $component->filterTable('status', InvoiceStatus::DRAFT->value);
    
    $filteredRecords = $component->instance()->getTableRecords();
    
    // Property: All returned invoices should have DRAFT status
    expect($filteredRecords)->toHaveCount($draftCount);
    $filteredRecords->each(function ($invoice) {
        expect($invoice->status)->toBe(InvoiceStatus::DRAFT);
    });
    
    // Verify all draft invoice IDs are present
    $draftIds = collect($draftInvoices)->pluck('id')->toArray();
    $filteredIds = $filteredRecords->pluck('id')->toArray();
    expect($filteredIds)->toEqualCanonicalizing($draftIds);
    
    // Property: When filtering by FINALIZED status, only finalized invoices should be returned
    $component2 = Livewire::test(InvoiceResource\Pages\ListInvoices::class);
    $component2->assertSuccessful();
    
    // Apply FINALIZED filter
    $component2->filterTable('status', InvoiceStatus::FINALIZED->value);
    
    $filteredRecords2 = $component2->instance()->getTableRecords();
    
    // Property: All returned invoices should have FINALIZED status
    expect($filteredRecords2)->toHaveCount($finalizedCount);
    $filteredRecords2->each(function ($invoice) {
        expect($invoice->status)->toBe(InvoiceStatus::FINALIZED);
    });
    
    // Verify all finalized invoice IDs are present
    $finalizedIds = collect($finalizedInvoices)->pluck('id')->toArray();
    $filteredIds2 = $filteredRecords2->pluck('id')->toArray();
    expect($filteredIds2)->toEqualCanonicalizing($finalizedIds);
    
    // Property: When filtering by PAID status, only paid invoices should be returned
    $component3 = Livewire::test(InvoiceResource\Pages\ListInvoices::class);
    $component3->assertSuccessful();
    
    // Apply PAID filter
    $component3->filterTable('status', InvoiceStatus::PAID->value);
    
    $filteredRecords3 = $component3->instance()->getTableRecords();
    
    // Property: All returned invoices should have PAID status
    expect($filteredRecords3)->toHaveCount($paidCount);
    $filteredRecords3->each(function ($invoice) {
        expect($invoice->status)->toBe(InvoiceStatus::PAID);
    });
    
    // Verify all paid invoice IDs are present
    $paidIds = collect($paidInvoices)->pluck('id')->toArray();
    $filteredIds3 = $filteredRecords3->pluck('id')->toArray();
    expect($filteredIds3)->toEqualCanonicalizing($paidIds);
})->repeat(100);

// Feature: filament-admin-panel, Property 10: Invoice status filtering
// Validates: Requirements 4.6
test('InvoiceResource shows all invoices when no status filter is applied', function () {
    // Generate unique tenant ID to avoid cross-contamination between iterations
    $tenantId = fake()->unique()->numberBetween(1, 100000);
    
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
    
    // Create random number of invoices with mixed statuses (limited to 10 for pagination)
    $totalCount = fake()->numberBetween(5, 10);
    $allInvoices = [];
    
    for ($i = 0; $i < $totalCount; $i++) {
        $randomStatus = fake()->randomElement([
            InvoiceStatus::DRAFT,
            InvoiceStatus::FINALIZED,
            InvoiceStatus::PAID,
        ]);
        
        $allInvoices[] = Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'tenant_renter_id' => $tenantRenter->id,
            'billing_period_start' => now()->subMonths($i + 1)->startOfMonth(),
            'billing_period_end' => now()->subMonths($i + 1)->endOfMonth(),
            'total_amount' => fake()->randomFloat(2, 50, 500),
            'status' => $randomStatus,
            'finalized_at' => $randomStatus !== InvoiceStatus::DRAFT ? now() : null,
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
    
    // Property: When no filter is applied, all invoices should be returned
    $component = Livewire::test(InvoiceResource\Pages\ListInvoices::class);
    $component->assertSuccessful();
    
    $allRecords = $component->instance()->getTableRecords();
    
    // Property: All created invoices should be present
    expect($allRecords)->toHaveCount($totalCount);
    
    // Verify all invoice IDs are present
    $allIds = collect($allInvoices)->pluck('id')->toArray();
    $recordIds = $allRecords->pluck('id')->toArray();
    expect($recordIds)->toEqualCanonicalizing($allIds);
})->repeat(100);

// Feature: filament-admin-panel, Property 10: Invoice status filtering
// Validates: Requirements 4.6
test('InvoiceResource status filter respects tenant scope', function () {
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
    
    // Create draft invoices for both tenants
    $tenant1DraftCount = fake()->numberBetween(2, 5);
    $tenant2DraftCount = fake()->numberBetween(2, 5);
    
    $tenant1Drafts = [];
    for ($i = 0; $i < $tenant1DraftCount; $i++) {
        $tenant1Drafts[] = Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId1,
            'tenant_renter_id' => $tenantRenter1->id,
            'billing_period_start' => now()->subMonths($i + 1)->startOfMonth(),
            'billing_period_end' => now()->subMonths($i + 1)->endOfMonth(),
            'total_amount' => fake()->randomFloat(2, 50, 500),
            'status' => InvoiceStatus::DRAFT,
        ]);
    }
    
    $tenant2Drafts = [];
    for ($i = 0; $i < $tenant2DraftCount; $i++) {
        $tenant2Drafts[] = Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId2,
            'tenant_renter_id' => $tenantRenter2->id,
            'billing_period_start' => now()->subMonths($i + 1)->startOfMonth(),
            'billing_period_end' => now()->subMonths($i + 1)->endOfMonth(),
            'total_amount' => fake()->randomFloat(2, 50, 500),
            'status' => InvoiceStatus::DRAFT,
        ]);
    }
    
    // Create a manager for tenant 1
    $manager1 = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId1,
    ]);
    
    // Act as manager from tenant 1
    $this->actingAs($manager1);
    session(['tenant_id' => $tenantId1]);
    
    // Property: When filtering by DRAFT status, only tenant 1's draft invoices should be returned
    $component = Livewire::test(InvoiceResource\Pages\ListInvoices::class);
    $component->assertSuccessful();
    
    // Apply DRAFT filter
    $component->filterTable('status', InvoiceStatus::DRAFT->value);
    
    $filteredRecords = $component->instance()->getTableRecords();
    
    // Property: Only tenant 1's draft invoices should be present
    expect($filteredRecords)->toHaveCount($tenant1DraftCount);
    
    $filteredRecords->each(function ($invoice) use ($tenantId1) {
        expect($invoice->status)->toBe(InvoiceStatus::DRAFT);
        expect($invoice->tenant_id)->toBe($tenantId1);
    });
    
    // Verify tenant 1's draft invoice IDs are present
    $tenant1DraftIds = collect($tenant1Drafts)->pluck('id')->toArray();
    $filteredIds = $filteredRecords->pluck('id')->toArray();
    expect($filteredIds)->toEqualCanonicalizing($tenant1DraftIds);
    
    // Property: Tenant 2's invoices should not be accessible
    $tenant2DraftIds = collect($tenant2Drafts)->pluck('id')->toArray();
    foreach ($tenant2DraftIds as $tenant2Id) {
        expect($filteredIds)->not->toContain($tenant2Id);
    }
})->repeat(100);

// Feature: filament-admin-panel, Property 10: Invoice status filtering
// Validates: Requirements 4.6
test('InvoiceResource status filter returns empty result when no invoices match', function () {
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
    
    // Create only draft invoices (no finalized or paid)
    $draftCount = fake()->numberBetween(2, 5);
    
    for ($i = 0; $i < $draftCount; $i++) {
        Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'tenant_renter_id' => $tenantRenter->id,
            'billing_period_start' => now()->subMonths($i + 1)->startOfMonth(),
            'billing_period_end' => now()->subMonths($i + 1)->endOfMonth(),
            'total_amount' => fake()->randomFloat(2, 50, 500),
            'status' => InvoiceStatus::DRAFT,
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
    
    // Property: When filtering by PAID status (which doesn't exist), empty result should be returned
    $component = Livewire::test(InvoiceResource\Pages\ListInvoices::class);
    $component->assertSuccessful();
    
    // Apply PAID filter
    $component->filterTable('status', InvoiceStatus::PAID->value);
    
    $filteredRecords = $component->instance()->getTableRecords();
    
    // Property: No invoices should be returned
    expect($filteredRecords)->toHaveCount(0);
    
    // Property: When filtering by FINALIZED status (which doesn't exist), empty result should be returned
    $component2 = Livewire::test(InvoiceResource\Pages\ListInvoices::class);
    $component2->assertSuccessful();
    
    // Apply FINALIZED filter
    $component2->filterTable('status', InvoiceStatus::FINALIZED->value);
    
    $filteredRecords2 = $component2->instance()->getTableRecords();
    
    // Property: No invoices should be returned
    expect($filteredRecords2)->toHaveCount(0);
})->repeat(100);

// Feature: filament-admin-panel, Property 10: Invoice status filtering
// Validates: Requirements 4.6
test('InvoiceResource status filter can be cleared to show all invoices', function () {
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
    
    // Create invoices with different statuses
    $draftCount = fake()->numberBetween(1, 3);
    $finalizedCount = fake()->numberBetween(1, 3);
    $paidCount = fake()->numberBetween(1, 3);
    $totalCount = $draftCount + $finalizedCount + $paidCount;
    
    // Create draft invoices
    for ($i = 0; $i < $draftCount; $i++) {
        Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'tenant_renter_id' => $tenantRenter->id,
            'billing_period_start' => now()->subMonths($i + 1)->startOfMonth(),
            'billing_period_end' => now()->subMonths($i + 1)->endOfMonth(),
            'total_amount' => fake()->randomFloat(2, 50, 500),
            'status' => InvoiceStatus::DRAFT,
        ]);
    }
    
    // Create finalized invoices
    for ($i = 0; $i < $finalizedCount; $i++) {
        Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'tenant_renter_id' => $tenantRenter->id,
            'billing_period_start' => now()->subMonths($i + 10)->startOfMonth(),
            'billing_period_end' => now()->subMonths($i + 10)->endOfMonth(),
            'total_amount' => fake()->randomFloat(2, 50, 500),
            'status' => InvoiceStatus::FINALIZED,
            'finalized_at' => now(),
        ]);
    }
    
    // Create paid invoices
    for ($i = 0; $i < $paidCount; $i++) {
        Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'tenant_renter_id' => $tenantRenter->id,
            'billing_period_start' => now()->subMonths($i + 20)->startOfMonth(),
            'billing_period_end' => now()->subMonths($i + 20)->endOfMonth(),
            'total_amount' => fake()->randomFloat(2, 50, 500),
            'status' => InvoiceStatus::PAID,
            'finalized_at' => now(),
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
    
    // Property: Apply a filter first
    $component = Livewire::test(InvoiceResource\Pages\ListInvoices::class);
    $component->assertSuccessful();
    
    // Apply DRAFT filter
    $component->filterTable('status', InvoiceStatus::DRAFT->value);
    $filteredRecords = $component->instance()->getTableRecords();
    expect($filteredRecords)->toHaveCount($draftCount);
    
    // Property: Clear the filter and all invoices should be returned
    $component->resetTableFilters();
    $allRecords = $component->instance()->getTableRecords();
    
    // Property: All invoices should be present after clearing filter
    expect($allRecords)->toHaveCount($totalCount);
})->repeat(100);
