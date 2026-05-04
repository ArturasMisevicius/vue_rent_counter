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

    public function test_invoice_history_falls_back_to_persisted_items_when_snapshot_contains_metadata_only(): void
    {
        $tenant = TenantPortalFactory::new()
            ->withAssignedProperty()
            ->create();

        $invoice = Invoice::factory()
            ->for($tenant->organization)
            ->for($tenant->property)
            ->for($tenant->user, 'tenant')
            ->create([
                'invoice_number' => 'INV-METADATA-001',
                'status' => InvoiceStatus::FINALIZED,
                'items' => [],
                'snapshot_data' => ['seed' => 'legacy_operations_foundation'],
                'total_amount' => '142.75',
            ]);

        InvoiceItem::factory()->for($invoice)->create([
            'description' => 'Electricity charge',
            'quantity' => '1.00',
            'unit' => 'month',
            'unit_price' => '82.3500',
            'total' => '82.35',
        ]);

        InvoiceItem::factory()->for($invoice)->create([
            'description' => 'Water charge',
            'quantity' => '1.00',
            'unit' => 'month',
            'unit_price' => '60.4000',
            'total' => '60.40',
        ]);

        $this->actingAs($tenant->user)
            ->get(route('filament.admin.pages.tenant-invoice-history'))
            ->assertSuccessful()
            ->assertSeeText('INV-METADATA-001')
            ->assertSeeText('Electricity charge')
            ->assertSeeText('Water charge')
            ->assertDontSeeText('No invoices match the selected filter.');
    }

    public function test_invoice_history_localizes_seeded_line_item_descriptions_and_units(): void
    {
        $tenant = TenantPortalFactory::new()
            ->withAssignedProperty()
            ->create();

        $tenant->user->forceFill([
            'locale' => 'lt',
        ])->save();

        $invoice = Invoice::factory()
            ->for($tenant->organization)
            ->for($tenant->property)
            ->for($tenant->user->fresh(), 'tenant')
            ->create([
                'invoice_number' => 'INV-LT-LINES-001',
                'status' => InvoiceStatus::FINALIZED,
                'items' => [],
                'snapshot_data' => [],
                'total_amount' => '22.50',
            ]);

        InvoiceItem::factory()->for($invoice)->create([
            'description' => 'Shared services fee',
            'quantity' => '1.00',
            'unit' => 'month',
            'unit_price' => '22.5000',
            'total' => '22.50',
        ]);

        $this->actingAs($tenant->user->fresh())
            ->get(route('filament.admin.pages.tenant-invoice-history'))
            ->assertSuccessful()
            ->assertSeeText('Bendrų paslaugų mokestis')
            ->assertSeeText('1.000 mėn.')
            ->assertDontSeeText('Shared services fee')
            ->assertDontSeeText('1.000 month');
    }
}
