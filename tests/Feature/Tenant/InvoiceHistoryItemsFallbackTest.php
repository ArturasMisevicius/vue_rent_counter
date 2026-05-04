<?php

namespace Tests\Feature\Tenant;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TenantPortalFactory;
use Tests\TestCase;

class InvoiceHistoryItemsFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_history_shows_all_persisted_invoice_items_when_snapshot_arrays_are_empty(): void
    {
        $tenant = TenantPortalFactory::new()
            ->withAssignedProperty()
            ->create();

        $invoice = Invoice::factory()
            ->for($tenant->organization)
            ->for($tenant->property)
            ->for($tenant->user, 'tenant')
            ->create([
                'invoice_number' => 'INV-ITEMS-001',
                'status' => InvoiceStatus::FINALIZED,
                'items' => [],
                'snapshot_data' => [],
                'total_amount' => '184.50',
            ]);

        InvoiceItem::factory()->for($invoice)->create([
            'description' => 'Water base charge',
            'quantity' => '1.00',
            'unit' => 'month',
            'unit_price' => '84.5000',
            'total' => '84.50',
        ]);

        InvoiceItem::factory()->for($invoice)->create([
            'description' => 'Heating usage',
            'quantity' => '2.00',
            'unit' => 'month',
            'unit_price' => '50.0000',
            'total' => '100.00',
        ]);

        $this->actingAs($tenant->user)
            ->get(route('filament.admin.pages.tenant-invoice-history'))
            ->assertSuccessful()
            ->assertSeeText('INV-ITEMS-001')
            ->assertSeeText('Water base charge')
            ->assertSeeText('Heating usage')
            ->assertSeeText("84,50\u{00A0}€")
            ->assertSeeText("100,00\u{00A0}€");
    }
}
