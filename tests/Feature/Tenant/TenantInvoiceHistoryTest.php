<?php

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\TenantPortalFactory;

uses(RefreshDatabase::class);

it('shows the tenant invoice history with paid and outstanding invoices', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withUnpaidInvoices(2)
        ->withPaidInvoices(1)
        ->withPaymentInstructions('Pay by bank transfer before the due date.')
        ->create();

    $this->actingAs($tenant->user)
        ->get(route('tenant.invoices.index'))
        ->assertSuccessful()
        ->assertSeeText('Invoice History')
        ->assertSeeText('UNPAID-001')
        ->assertSeeText('PAID-001')
        ->assertSeeText('Outstanding')
        ->assertSeeText('Paid')
        ->assertSeeText('Pay by bank transfer before the due date.');
});

it('filters the invoice history by status', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withUnpaidInvoices(1)
        ->withPaidInvoices(1)
        ->create();

    $this->actingAs($tenant->user)
        ->get(route('tenant.invoices.index', ['status' => 'paid']))
        ->assertSuccessful()
        ->assertSeeText('PAID-001')
        ->assertDontSeeText('UNPAID-001');
});

it('allows a tenant to download their own invoice document', function () {
    Storage::fake(config('filesystems.default', 'local'));

    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withUnpaidInvoices(1)
        ->create();

    $invoice = $tenant->invoices->firstOrFail()->forceFill([
        'document_path' => 'tenant-invoices/invoice-001.pdf',
    ]);
    $invoice->save();

    Storage::disk(config('filesystems.default', 'local'))
        ->put('tenant-invoices/invoice-001.pdf', 'pdf-content');

    $this->actingAs($tenant->user)
        ->get(route('tenant.invoices.download', $invoice))
        ->assertDownload('invoice-001.pdf');
});

it('forbids downloading another tenants invoice', function () {
    Storage::fake(config('filesystems.default', 'local'));

    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withUnpaidInvoices(1)
        ->create();

    $otherTenant = User::factory()->tenant()->create();
    $otherInvoice = Invoice::factory()->for($otherTenant, 'tenant')->create([
        'document_path' => 'tenant-invoices/other-invoice.pdf',
    ]);

    Storage::disk(config('filesystems.default', 'local'))
        ->put('tenant-invoices/other-invoice.pdf', 'pdf-content');

    $this->actingAs($tenant->user)
        ->get(route('tenant.invoices.download', $otherInvoice))
        ->assertForbidden();
});
