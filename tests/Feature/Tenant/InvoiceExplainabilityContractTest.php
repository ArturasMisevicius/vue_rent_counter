<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Billing\InvoicePdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TenantPortalFactory;

uses(RefreshDatabase::class);

it('renders the same explainable invoice breakdown across tenant, admin, and pdf surfaces', function (): void {
    $fixture = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->create();

    $admin = User::factory()->admin()->create([
        'organization_id' => $fixture->organization->id,
    ]);

    $lineItems = [
        [
            'description' => 'Water usage',
            'quantity' => '12.000',
            'unit' => 'm3',
            'unit_price' => '4.2750',
            'total' => '51.30',
        ],
        [
            'description' => 'Shared heating',
            'quantity' => '1.000',
            'unit' => 'month',
            'unit_price' => '94.0000',
            'total' => '94.00',
        ],
    ];

    $invoice = Invoice::factory()
        ->for($fixture->organization)
        ->for($fixture->property)
        ->for($fixture->user, 'tenant')
        ->create([
            'invoice_number' => 'INV-EXPLAIN-001',
            'status' => InvoiceStatus::PARTIALLY_PAID,
            'total_amount' => '145.30',
            'amount_paid' => '20.00',
            'paid_amount' => '20.00',
            'due_date' => now()->addDays(7)->toDateString(),
            'items' => $lineItems,
            'snapshot_data' => $lineItems,
        ]);

    $this->actingAs($fixture->user)
        ->get(route('filament.admin.pages.tenant-invoice-history'))
        ->assertSuccessful()
        ->assertSeeText('Water usage')
        ->assertSeeText('Shared heating')
        ->assertSeeText("145,30\u{00A0}€")
        ->assertSeeText("20,00\u{00A0}€")
        ->assertSeeText("125,30\u{00A0}€")
        ->assertSeeText('Paid so far')
        ->assertSeeText('Balance Due');

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.invoices.view', $invoice))
        ->assertSuccessful()
        ->assertSeeText('Water usage')
        ->assertSeeText('Shared heating')
        ->assertSeeText("145,30\u{00A0}€")
        ->assertSeeText("20,00\u{00A0}€")
        ->assertSeeText("125,30\u{00A0}€");

    $response = app(InvoicePdfService::class)->streamDownload($invoice->fresh());

    ob_start();
    $response->sendContent();
    $pdf = ob_get_clean();

    expect($pdf)
        ->toBeString()
        ->toStartWith('%PDF');
});
