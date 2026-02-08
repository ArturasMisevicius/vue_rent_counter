<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Property;
use App\Models\Tenant;
use App\Services\InvoicePdfService;
use Barryvdh\DomPDF\PDF as DomPDF;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * InvoicePdfService Feature Tests (Phase 8)
 *
 * Tests PDF generation for invoices including:
 * - PDF object creation
 * - PDF content verification (critical strings)
 * - Filename generation
 * - Invoice data integrity in PDF
 *
 * @group services
 * @group pdf
 * @group phase-8
 */
class InvoicePdfServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InvoicePdfService $pdfService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdfService = app(InvoicePdfService::class);
    }

    // ========================================
    // PDF GENERATION TESTS
    // ========================================

    /** @test */
    public function it_generates_pdf_for_invoice(): void
    {
        // Arrange: Create invoice with items
        $property = Property::factory()->create(['tenant_id' => 1]);
        $tenantRecord = Tenant::factory()->create([
            'tenant_id' => 1,
            'property_id' => $property->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $tenantRecord->id,
            'invoice_number' => 'INV-2024-001',
            'billing_period_start' => '2024-01-01',
            'billing_period_end' => '2024-01-31',
            'total_amount' => 150.00,
            'status' => InvoiceStatus::FINALIZED,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Electricity Consumption',
            'quantity' => 100.00,
            'unit' => 'kWh',
            'unit_price' => 0.15,
            'total' => 15.00,
        ]);

        // Act: Generate PDF
        $pdf = $this->pdfService->generate($invoice);

        // Assert: PDF object is returned
        $this->assertInstanceOf(DomPDF::class, $pdf);
    }

    /** @test */
    public function view_contains_invoice_number(): void
    {
        // Arrange
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'invoice_number' => 'INV-2024-TEST-123',
            'total_amount' => 250.00,
            'status' => InvoiceStatus::DRAFT,
        ]);

        // Act: Render view HTML
        $view = view('pdf.invoice', ['invoice' => $invoice]);
        $html = $view->render();

        // Assert: HTML contains invoice number
        $this->assertStringContainsString('INV-2024-TEST-123', $html);
    }

    /** @test */
    public function view_contains_total_amount(): void
    {
        // Arrange
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'total_amount' => 357.89,
            'status' => InvoiceStatus::FINALIZED,
        ]);

        // Act: Render view HTML
        $view = view('pdf.invoice', ['invoice' => $invoice]);
        $html = $view->render();

        // Assert: HTML contains formatted total amount
        $this->assertStringContainsString('357.89', $html);
    }

    /** @test */
    public function view_contains_invoice_items(): void
    {
        // Arrange: Create invoice with specific items
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'total_amount' => 45.00,
            'status' => InvoiceStatus::FINALIZED,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Water Supply Service',
            'quantity' => 15.00,
            'unit' => 'm³',
            'unit_price' => 2.00,
            'total' => 30.00,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Sewage Service',
            'quantity' => 15.00,
            'unit' => 'm³',
            'unit_price' => 1.00,
            'total' => 15.00,
        ]);

        // Act: Render view HTML
        $view = view('pdf.invoice', ['invoice' => $invoice]);
        $html = $view->render();

        // Assert: HTML contains item descriptions
        $this->assertStringContainsString('Water Supply Service', $html);
        $this->assertStringContainsString('Sewage Service', $html);
    }

    /** @test */
    public function view_contains_tenant_information(): void
    {
        // Arrange: Create invoice with tenant details
        $property = Property::factory()->create([
            'tenant_id' => 1,
            'unit_number' => '5B',
            'address' => '123 Main Street',
        ]);

        $tenantRecord = Tenant::factory()->create([
            'tenant_id' => 1,
            'property_id' => $property->id,
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
        ]);

        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => $tenantRecord->id,
            'total_amount' => 100.00,
            'status' => InvoiceStatus::FINALIZED,
        ]);

        // Act: Render view HTML
        $view = view('pdf.invoice', ['invoice' => $invoice]);
        $html = $view->render();

        // Assert: HTML contains tenant information
        $this->assertStringContainsString('Jane Smith', $html);
        $this->assertStringContainsString('jane.smith@example.com', $html);
    }

    /** @test */
    public function view_contains_billing_period(): void
    {
        // Arrange
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'billing_period_start' => '2024-03-01',
            'billing_period_end' => '2024-03-31',
            'total_amount' => 200.00,
            'status' => InvoiceStatus::FINALIZED,
        ]);

        // Act: Render view HTML
        $view = view('pdf.invoice', ['invoice' => $invoice]);
        $html = $view->render();

        // Assert: HTML contains billing period dates
        $this->assertStringContainsString('2024-03-01', $html);
        $this->assertStringContainsString('2024-03-31', $html);
    }

    /** @test */
    public function view_contains_status_badge(): void
    {
        // Arrange
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'total_amount' => 75.00,
            'status' => InvoiceStatus::PAID,
        ]);

        // Act: Render view HTML
        $view = view('pdf.invoice', ['invoice' => $invoice]);
        $html = $view->render();

        // Assert: HTML contains status (enum values are lowercase)
        $this->assertStringContainsString('paid', $html);
    }

    // ========================================
    // FILENAME GENERATION TESTS
    // ========================================

    /** @test */
    public function it_generates_filename_with_invoice_number(): void
    {
        // Arrange
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'invoice_number' => 'INV-2024-042',
            'total_amount' => 100.00,
        ]);

        // Act: Generate filename using reflection to access protected method
        $reflection = new \ReflectionClass($this->pdfService);
        $method = $reflection->getMethod('generateFilename');
        $method->setAccessible(true);
        $filename = $method->invoke($this->pdfService, $invoice);

        // Assert: Filename contains invoice number
        $this->assertStringContainsString('INV-2024-042', $filename);
        $this->assertStringEndsWith('.pdf', $filename);
    }

    /** @test */
    public function it_generates_filename_with_sanitized_characters(): void
    {
        // Arrange: Invoice number with special characters
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'invoice_number' => 'INV/2024#TEST*001',
            'total_amount' => 100.00,
        ]);

        // Act: Generate filename
        $reflection = new \ReflectionClass($this->pdfService);
        $method = $reflection->getMethod('generateFilename');
        $method->setAccessible(true);
        $filename = $method->invoke($this->pdfService, $invoice);

        // Assert: Special characters are replaced with underscores
        $this->assertStringNotContainsString('/', $filename);
        $this->assertStringNotContainsString('#', $filename);
        $this->assertStringNotContainsString('*', $filename);
        $this->assertStringEndsWith('.pdf', $filename);
    }

    /** @test */
    public function it_generates_fallback_filename_when_no_invoice_number(): void
    {
        // Arrange: Invoice without invoice_number
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'invoice_number' => null,
            'total_amount' => 100.00,
        ]);

        // Act: Generate filename
        $reflection = new \ReflectionClass($this->pdfService);
        $method = $reflection->getMethod('generateFilename');
        $method->setAccessible(true);
        $filename = $method->invoke($this->pdfService, $invoice);

        // Assert: Uses fallback format with invoice ID
        $this->assertStringContainsString('INV-' . $invoice->id, $filename);
        $this->assertStringEndsWith('.pdf', $filename);
    }

    // ========================================
    // EDGE CASES
    // ========================================

    /** @test */
    public function it_handles_invoice_with_no_items(): void
    {
        // Arrange: Invoice with no items
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'total_amount' => 0.00,
            'status' => InvoiceStatus::DRAFT,
        ]);

        // Act: Generate PDF
        $pdf = $this->pdfService->generate($invoice);

        // Assert: PDF is generated without errors
        $this->assertInstanceOf(DomPDF::class, $pdf);
        $this->assertNotEmpty($pdf->output());
    }

    /** @test */
    public function it_handles_invoice_with_minimal_data(): void
    {
        // Arrange: Invoice with minimal required data
        $invoice = Invoice::factory()->create([
            'tenant_id' => 1,
            'tenant_renter_id' => null, // No tenant renter
            'billing_period_start' => '2024-01-01',
            'billing_period_end' => '2024-01-31',
            'total_amount' => 50.00,
            'status' => InvoiceStatus::DRAFT,
        ]);

        // Act: Generate PDF
        $pdf = $this->pdfService->generate($invoice);

        // Assert: PDF is generated successfully
        $this->assertInstanceOf(DomPDF::class, $pdf);
        $this->assertNotEmpty($pdf->output());
    }
}
