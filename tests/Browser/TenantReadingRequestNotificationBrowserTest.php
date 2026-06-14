<?php

use App\Filament\Actions\Admin\Invoices\OpenReadingInvoiceCycleAction;
use App\Models\Invoice;
use App\Models\Meter;
use App\Notifications\Billing\InvoiceReadyForTenantNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Browser\Configuration;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    (new Configuration)
        ->inChrome()
        ->timeout(15_000);
});

it('lets tenants open current invoice reading requests from the browser', function (): void {
    $workspace = createOrgWithAdmin();
    $workspace['admin']->forceFill([
        'onboarding_tour_completed_at' => now(),
    ])->save();

    $tenantWorkspace = createTenantInOrg($workspace['admin']);
    $tenantWorkspace['tenant']->forceFill([
        'onboarding_tour_completed_at' => now(),
    ])->save();

    $meter = Meter::factory()
        ->for($workspace['organization'])
        ->for($tenantWorkspace['property'])
        ->create([
            'name' => 'Main electricity meter',
            'identifier' => 'MTR-BROWSER-READING',
        ]);

    app(OpenReadingInvoiceCycleAction::class)->handle($workspace['organization'], [
        'billing_period_start' => now()->subMonthNoOverflow()->startOfMonth()->toDateString(),
        'billing_period_end' => now()->subMonthNoOverflow()->endOfMonth()->toDateString(),
        'due_date' => now()->subMonthNoOverflow()->endOfMonth()->addDays(14)->toDateString(),
    ], $workspace['admin']);

    $this->actingAs($tenantWorkspace['tenant']);

    visit(route('tenant.home', [], false))
        ->assertPathIs(route('filament.admin.pages.dashboard', [], false))
        ->assertSee($tenantWorkspace['tenant']->name)
        ->assertSee(__('tenant.pages.home.current_invoice'))
        ->assertSee(__('tenant.actions.submit_readings'))
        ->click('[data-tenant-current-invoice="true"]')
        ->wait()
        ->assertPathIs(route('filament.admin.pages.tenant-submit-meter-reading', [], false))
        ->assertSee(__('tenant.pages.readings.title'))
        ->assertSee(__('tenant.pages.readings.invoice_request_heading', [
            'number' => 'INV-',
        ]))
        ->assertSee('MTR-BROWSER-READING')
        ->assertSee(__('tenant.pages.readings.previous_reading_column'))
        ->assertSee(__('tenant.pages.readings.current_reading_column'))
        ->assertSee(__('tenant.pages.readings.consumption_column'))
        ->type("#reading_{$meter->id}_value", '125.000')
        ->press(__('tenant.pages.readings.submit_all'))
        ->wait()
        ->assertSee(__('tenant.pages.readings.submitted_review_status'))
        ->assertSee('125.000')
        ->assertNoJavaScriptErrors();
});

it('lets tenants open finalized invoice notifications from the browser', function (): void {
    $workspace = createOrgWithAdmin();
    $workspace['admin']->forceFill([
        'onboarding_tour_completed_at' => now(),
    ])->save();

    $tenantWorkspace = createTenantInOrg($workspace['admin']);
    $tenantWorkspace['tenant']->forceFill([
        'onboarding_tour_completed_at' => now(),
    ])->save();

    $invoice = Invoice::factory()
        ->for($workspace['organization'])
        ->for($tenantWorkspace['property'])
        ->for($tenantWorkspace['tenant'], 'tenant')
        ->create([
            'invoice_number' => 'INV-BROWSER-READY',
            'total_amount' => 125.50,
        ]);

    $tenantWorkspace['tenant']->notify(new InvoiceReadyForTenantNotification($invoice));

    $invoiceHistoryPath = route('filament.admin.pages.tenant-invoice-history', [], false);
    $this->actingAs($tenantWorkspace['tenant']);

    visit(route('tenant.home', [], false))
        ->assertPathIs(route('filament.admin.pages.dashboard', [], false))
        ->assertSee($tenantWorkspace['tenant']->name)
        ->click('[data-shell-notifications-slot="desktop"] [data-shell-notifications="center"] > button')
        ->assertSee(__('admin.invoices.invoice_ready.database_title'))
        ->press(__('admin.invoices.invoice_ready.database_title'))
        ->wait()
        ->assertPathIs($invoiceHistoryPath)
        ->assertSee(__('tenant.pages.invoices.page_heading'))
        ->assertSee('INV-BROWSER-READY')
        ->assertNoJavaScriptErrors();
});
