<?php

use App\Livewire\Tenant\InvoiceHistory;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
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
        ->get(route('filament.admin.pages.tenant-invoice-history'))
        ->assertSuccessful()
        ->assertSeeText('Invoice History')
        ->assertSeeText('All')
        ->assertSeeText('Unpaid')
        ->assertSeeText('Paid')
        ->assertSeeText('UNPAID-001')
        ->assertSeeText('PAID-001')
        ->assertSeeText('Overdue')
        ->assertSeeText('Unpaid')
        ->assertSeeText('Paid')
        ->assertSeeText('Pay by bank transfer before the due date.');
});

it('filters the invoice history by unpaid status', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withUnpaidInvoices(1)
        ->withPaidInvoices(1)
        ->create();

    $this->actingAs($tenant->user)
        ->get(route('filament.admin.pages.tenant-invoice-history', ['status' => 'unpaid']))
        ->assertSuccessful()
        ->assertSeeText('UNPAID-001')
        ->assertDontSee('>PAID-001<', false);
});

it('resets pagination to the first page when status filter changes', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withUnpaidInvoices(1)
        ->withPaidInvoices(11)
        ->create();

    Livewire::actingAs($tenant->user)
        ->withQueryParams(['page' => 2])
        ->test(InvoiceHistory::class)
        ->set('selectedStatus', 'unpaid')
        ->assertSet('paginators.page', 1)
        ->assertSeeText('UNPAID-001');
});

it('shows an all-paid-up empty state when the tenant has no unpaid invoices', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withPaidInvoices(1)
        ->create();

    $this->actingAs($tenant->user)
        ->get(route('filament.admin.pages.tenant-invoice-history', ['status' => 'unpaid']))
        ->assertSuccessful()
        ->assertSeeText('All paid up')
        ->assertSeeText('No outstanding invoices are waiting for payment.');
});

it('shows a localized empty payment guidance state instead of hardcoded fallback instructions', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withUnpaidInvoices(1)
        ->withoutPaymentInstructions()
        ->create();

    $this->actingAs($tenant->user)
        ->get(route('filament.admin.pages.tenant-invoice-history'))
        ->assertSuccessful()
        ->assertSeeText('Payment instructions will appear here once your organization updates its billing settings.')
        ->assertDontSeeText('Contact your building manager for payment instructions.');
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

it('renders invoice history copy in lithuanian for lithuanian tenants', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withUnpaidInvoices(1)
        ->create();

    $tenant->user->forceFill([
        'locale' => 'lt',
    ])->save();

    $this->actingAs($tenant->user->fresh())
        ->get(route('filament.admin.pages.tenant-invoice-history'))
        ->assertSuccessful()
        ->assertSeeText('Sąskaitų istorija')
        ->assertSeeText('Neapmokėtos')
        ->assertSeeText('Apmokėtos');
});

it('renders the invoice billing period with localized copy', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withUnpaidInvoices(1)
        ->create();

    $invoice = $tenant->invoices->firstOrFail();

    $tenant->user->forceFill([
        'locale' => 'lt',
    ])->save();

    $this->actingAs($tenant->user->fresh())
        ->get(route('filament.admin.pages.tenant-invoice-history'))
        ->assertSuccessful()
        ->assertSeeText($invoice->billing_period_start->format('Y-m-d').' iki '.$invoice->billing_period_end->format('Y-m-d'))
        ->assertDontSeeText($invoice->billing_period_start->format('Y-m-d').' to '.$invoice->billing_period_end->format('Y-m-d'));
});

it('refreshes translated invoice history copy when the shell locale changes', function () {
    $tenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->withUnpaidInvoices(1)
        ->create();

    $component = Livewire::actingAs($tenant->user)
        ->test(InvoiceHistory::class)
        ->assertSeeText(__('tenant.pages.invoices.page_heading', [], 'en'));

    $tenant->user->forceFill([
        'locale' => 'lt',
    ])->save();

    Auth::setUser($tenant->user->fresh());
    app()->setLocale('lt');

    $component
        ->dispatch('shell-locale-updated')
        ->assertSeeText(__('tenant.pages.invoices.page_heading', [], 'lt'))
        ->assertSeeText(__('tenant.status.unpaid', [], 'lt'));
});
