<?php

namespace Tests\Feature\Tenant;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\Billing\InvoicePdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\TenantPortalFactory;
use Tests\TestCase;

class InvoicePdfLocalizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: string, 2: string, 3: string, 4: string}>
     */
    public static function localeProvider(): array
    {
        return [
            'english' => ['en', 'Invoice Number', 'Amount Paid', 'Outstanding', 'Water usage'],
            'lithuanian' => ['lt', 'Sąskaitos numeris', 'Apmokėta suma', 'Neapmokėta', 'Vandens mokestis'],
            'russian' => ['ru', 'Номер счета', 'Оплачено', 'Не оплачен', 'Отопление'],
            'spanish' => ['es', 'Número de factura', 'Importe pagado', 'Pendiente', 'Consumo de agua'],
        ];
    }

    #[DataProvider('localeProvider')]
    public function test_invoice_pdf_markup_is_localized_for_supported_tenant_locales(
        string $locale,
        string $invoiceNumberLabel,
        string $amountPaidLabel,
        string $outstandingLabel,
        string $lineItemDescription,
    ): void {
        $fixture = TenantPortalFactory::new()
            ->withAssignedProperty()
            ->create();

        $fixture->user->forceFill([
            'locale' => $locale,
        ])->save();

        app()->setLocale($locale);

        $invoice = Invoice::factory()
            ->for($fixture->organization)
            ->for($fixture->property)
            ->for($fixture->user->fresh(), 'tenant')
            ->create([
                'invoice_number' => 'INV-LOCALE-001',
                'status' => InvoiceStatus::FINALIZED,
                'total_amount' => '145.30',
                'amount_paid' => '20.00',
                'paid_amount' => '20.00',
                'items' => [[
                    'description' => $lineItemDescription,
                    'quantity' => '3.000',
                    'unit' => 'm3',
                    'unit_price' => '48.4333',
                    'total' => '145.30',
                ]],
                'snapshot_data' => [[
                    'description' => $lineItemDescription,
                    'quantity' => '3.000',
                    'unit' => 'm3',
                    'unit_price' => '48.4333',
                    'total' => '145.30',
                ]],
            ]);

        $markup = app(InvoicePdfService::class)->renderMarkup($invoice->fresh());

        $this->assertStringContainsString($invoiceNumberLabel, $markup);
        $this->assertStringContainsString($amountPaidLabel, $markup);
        $this->assertStringContainsString($outstandingLabel, $markup);
        $this->assertStringContainsString($lineItemDescription, $markup);
        $this->assertStringContainsString('INV-LOCALE-001', $markup);
    }

    #[DataProvider('localeProvider')]
    public function test_invoice_pdf_streams_a_valid_document_for_supported_tenant_locales(
        string $locale,
        string $invoiceNumberLabel,
        string $amountPaidLabel,
        string $outstandingLabel,
        string $lineItemDescription,
    ): void {
        $fixture = TenantPortalFactory::new()
            ->withAssignedProperty()
            ->create();

        $fixture->user->forceFill([
            'locale' => $locale,
        ])->save();

        app()->setLocale($locale);

        $invoice = Invoice::factory()
            ->for($fixture->organization)
            ->for($fixture->property)
            ->for($fixture->user->fresh(), 'tenant')
            ->create([
                'invoice_number' => 'INV-PDF-001',
                'status' => InvoiceStatus::FINALIZED,
                'total_amount' => '145.30',
                'amount_paid' => '20.00',
                'paid_amount' => '20.00',
                'items' => [[
                    'description' => $lineItemDescription,
                    'quantity' => '3.000',
                    'unit' => 'm3',
                    'unit_price' => '48.4333',
                    'total' => '145.30',
                ]],
                'snapshot_data' => [[
                    'description' => $lineItemDescription,
                    'quantity' => '3.000',
                    'unit' => 'm3',
                    'unit_price' => '48.4333',
                    'total' => '145.30',
                ]],
            ]);

        $pdf = app(InvoicePdfService::class)->renderPdf($invoice->fresh());

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertGreaterThan(5000, strlen($pdf));
    }
}
