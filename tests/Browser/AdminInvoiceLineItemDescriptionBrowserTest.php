<?php

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Browser\Configuration;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    (new Configuration)
        ->inChrome()
        ->timeout(15_000);
});

it('lets admins save detailed manual service descriptions from the browser', function (): void {
    $workspace = createOrgWithAdmin();
    $workspace['admin']->forceFill([
        'onboarding_tour_completed_at' => now(),
    ])->save();
    $workspace['admin']->refresh();

    $tenantWorkspace = createTenantInOrg($workspace['admin']);
    $initialDescription = 'Short manual service note';
    $detailedDescription = str_repeat(
        'Repair works, garbage removal, internet, pool fee and sauna fee details. ',
        8,
    );

    $invoice = Invoice::factory()
        ->for($workspace['organization'])
        ->for($tenantWorkspace['property'])
        ->for($tenantWorkspace['tenant'], 'tenant')
        ->create([
            'invoice_number' => 'INV-BROWSER-0001',
            'status' => InvoiceStatus::DRAFT,
            'total_amount' => '125.00',
            'amount_paid' => '0.00',
            'paid_amount' => '0.00',
            'finalized_at' => null,
            'items' => [[
                'description' => $initialDescription,
                'amount' => '125.00',
                'total' => '125.00',
            ]],
            'notes' => 'Browser draft invoice fixture',
        ]);

    $editPath = route('filament.admin.resources.invoices.edit', ['record' => $invoice], false);
    $viewPath = route('filament.admin.resources.invoices.view', ['record' => $invoice], false);

    $page = visit($editPath)
        ->assertPathIs('/login')
        ->type('#email', $workspace['admin']->email)
        ->type('#password', 'password')
        ->press(__('auth.login_button'))
        ->wait()
        ->assertPathIs($editPath)
        ->assertSee('Edit record')
        ->assertSee(__('admin.invoices.helpers.line_item_description'))
        ->assertNoJavaScriptErrors();

    $descriptionSelector = 'textarea[id$=".description"]';

    $page
        ->assertAttribute($descriptionSelector, 'maxlength', '4000')
        ->type($descriptionSelector, $detailedDescription)
        ->press('Save changes')
        ->wait()
        ->assertPathIs($viewPath)
        ->assertSee($detailedDescription)
        ->assertNoJavaScriptErrors();

    $invoice->refresh();

    expect($invoice->items[0]['description'] ?? null)->toBe($detailedDescription)
        ->and($invoice->invoiceItems()->sole()->description)->toBe($detailedDescription);
});
