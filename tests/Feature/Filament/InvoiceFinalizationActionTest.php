<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\TestDatabaseSeeder::class);
});

test('finalize action is visible for draft invoices', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
    $invoice = Invoice::factory()->create(['tenant_id' => 1, 'status' => InvoiceStatus::DRAFT, 'total_amount' => 100.00]);
    InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'description' => 'Test', 'unit_price' => 100, 'quantity' => 1, 'total_amount' => 100]);
    
    $this->actingAs($admin);
    livewire(InvoiceResource\Pages\ViewInvoice::class, ['record' => $invoice->id])->assertActionVisible('finalize');
});

test('successfully finalizes valid invoice', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
    $invoice = Invoice::factory()->create([
        'tenant_id' => 1,
        'status' => InvoiceStatus::DRAFT,
        'total_amount' => 100.00,
        'billing_period_start' => now()->startOfMonth(),
        'billing_period_end' => now()->endOfMonth(),
    ]);
    InvoiceItem::factory()->create(['invoice_id' => $invoice->id, 'description' => 'Test', 'unit_price' => 100, 'quantity' => 1, 'total_amount' => 100]);
    
    $this->actingAs($admin);
    livewire(InvoiceResource\Pages\ViewInvoice::class, ['record' => $invoice->id])
        ->callAction('finalize')
        ->assertNotified();
    
    expect($invoice->fresh()->status)->toBe(InvoiceStatus::FINALIZED);
});
