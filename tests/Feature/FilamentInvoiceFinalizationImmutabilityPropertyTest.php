<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Feature: filament-admin-panel, Property 9: Invoice finalization immutability
// Validates: Requirements 4.5

test('Property 9: Once an invoice is finalized through Filament, it cannot be modified', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 1000);

    $tenant = Tenant::factory()->forTenantId($tenantId)->create();
    
    // Create a manager for the tenant
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant->tenant_id,
    ]);

    // Create a draft invoice with random number of items
    $itemCount = fake()->numberBetween(1, 5);
    $totalAmount = fake()->randomFloat(2, 50, 500);
    
    $invoice = Invoice::factory()
        ->forTenantRenter($tenant)
        ->create([
            'tenant_id' => $tenant->tenant_id,
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => $totalAmount,
            'billing_period_start' => now()->subMonth(),
            'billing_period_end' => now(),
        ]);
    
    InvoiceItem::factory()->count($itemCount)->create([
        'invoice_id' => $invoice->id,
    ]);

    $this->actingAs($manager);
    session(['tenant_id' => $tenant->tenant_id]);

    // Property: Draft invoice should have finalize action visible
    $component = Livewire::test(InvoiceResource\Pages\ViewInvoice::class, [
        'record' => $invoice->id,
    ]);

    $component->assertSuccessful();
    $component->assertActionVisible('edit');
    $component->assertActionVisible('finalize');

    // Finalize the invoice
    $component->callAction('finalize');

    // Refresh invoice
    $invoice = $invoice->fresh();

    // Property: Invoice should now be finalized
    expect($invoice)
        ->status->toBe(InvoiceStatus::FINALIZED)
        ->finalized_at->not->toBeNull();

    // Property: Edit and finalize actions should be hidden for finalized invoice
    $component2 = Livewire::test(InvoiceResource\Pages\ViewInvoice::class, [
        'record' => $invoice->id,
    ]);

    $component2->assertSuccessful();
    $component2->assertActionHidden('edit');
    $component2->assertActionHidden('finalize');

    // Property: Direct model updates should be prevented by model observer
    $originalTotal = $invoice->total_amount;
    
    try {
        $invoice->total_amount = $originalTotal + 100;
        $invoice->save();
        
        // If save succeeded, verify it was rolled back
        expect($invoice->fresh()->total_amount)->toBe($originalTotal);
    } catch (\App\Exceptions\InvoiceAlreadyFinalizedException $e) {
        // This is the expected behavior
        expect(true)->toBeTrue();
    }
})->repeat(100);

test('Property 9: Finalized invoices can only have status changes (to PAID)', function () {
    // Generate random tenant ID
    $tenantId = fake()->numberBetween(1, 1000);
    
    // Create an admin for the tenant (admins can change status)
    $admin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
    ]);

    // Create a finalized invoice
    $originalTotal = fake()->randomFloat(2, 50, 500);
    $invoice = Invoice::factory()->create([
        'tenant_id' => $tenantId,
        'status' => InvoiceStatus::FINALIZED,
        'finalized_at' => now()->subDay(),
        'total_amount' => $originalTotal,
    ]);
    
    InvoiceItem::factory()->count(2)->create([
        'invoice_id' => $invoice->id,
    ]);

    $this->actingAs($admin);

    // Property: Status change from FINALIZED to PAID should be allowed
    $invoice->status = InvoiceStatus::PAID;
    $invoice->save();

    expect($invoice->fresh())
        ->status->toBe(InvoiceStatus::PAID);

    // Property: Attempting to change status AND other fields should only persist status
    $invoice = $invoice->fresh(); // Reload to get PAID status
    $invoice->status = InvoiceStatus::FINALIZED; // Change back to finalized
    $invoice->total_amount = $originalTotal + 100; // Try to change amount too
    $invoice->save();

    // Only status should have changed, total_amount should be reverted
    $freshInvoice = $invoice->fresh();
    expect($freshInvoice->status)->toBe(InvoiceStatus::FINALIZED);
    expect((float) $freshInvoice->total_amount)->toBe((float) $originalTotal);
})->repeat(100);
