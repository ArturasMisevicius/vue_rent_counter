<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Enhanced;

use App\Actions\GenerateInvoiceAction;
use App\DTOs\InvoiceGenerationDTO;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Enums\InvoiceStatus;
use App\Services\Enhanced\BillingService;
use App\Services\Enhanced\ConsumptionCalculationService;
use App\Services\MeterReadingService;
use App\Services\ServiceResponse;
use App\Services\UniversalBillingCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Billing Service Unit Tests
 * 
 * Tests business logic in isolation with mocked dependencies.
 * Focuses on service behavior without database interactions.
 * 
 * @package Tests\Unit\Services\Enhanced
 */
final class BillingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BillingService $billingService;
    private Mockery\MockInterface $generateInvoiceAction;
    private Mockery\MockInterface $billingCalculator;
    private Mockery\MockInterface $meterReadingService;
    private Mockery\MockInterface $consumptionService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks for all dependencies
        $this->generateInvoiceAction = Mockery::mock(GenerateInvoiceAction::class);
        $this->billingCalculator = Mockery::mock(UniversalBillingCalculator::class);
        $this->meterReadingService = Mockery::mock(MeterReadingService::class);
        $this->consumptionService = Mockery::mock(ConsumptionCalculationService::class);

        // Create service with mocked dependencies
        $this->billingService = new BillingService(
            $this->generateInvoiceAction,
            $this->billingCalculator,
            $this->meterReadingService,
            $this->consumptionService
        );
    }

    /** @test */
    public function it_generates_invoice_successfully(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $invoice = Invoice::factory()->forTenantRenter($tenant)->make(['id' => 1]);
        
        $dto = new InvoiceGenerationDTO(
            tenantId: $tenant->tenant_id,
            tenantRenterId: $tenant->id,
            periodStart: Carbon::parse('2024-01-01'),
            periodEnd: Carbon::parse('2024-01-31'),
            dueDate: Carbon::parse('2024-02-14')
        );

        // Mock the action to return a successful invoice
        $this->generateInvoiceAction
            ->shouldReceive('execute')
            ->once()
            ->with($dto)
            ->andReturn($invoice);

        // Mock consumption calculation
        $this->consumptionService
            ->shouldReceive('calculatePropertyConsumption')
            ->once()
            ->andReturn(new ServiceResponse(true, []));

        // Act
        $result = $this->billingService->generateInvoice($dto);

        // Assert
        $this->assertTrue($result->success);
        $this->assertInstanceOf(Invoice::class, $result->data);
        $this->assertEquals('Invoice generated successfully', $result->message);
    }

    /** @test */
    public function it_handles_invoice_generation_failure(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        
        $dto = new InvoiceGenerationDTO(
            tenantId: $tenant->tenant_id,
            tenantRenterId: $tenant->id,
            periodStart: Carbon::parse('2024-01-01'),
            periodEnd: Carbon::parse('2024-01-31'),
            dueDate: Carbon::parse('2024-02-14')
        );

        // Mock the action to throw an exception
        $this->generateInvoiceAction
            ->shouldReceive('execute')
            ->once()
            ->with($dto)
            ->andThrow(new \RuntimeException('Database error'));

        // Act
        $result = $this->billingService->generateInvoice($dto);

        // Assert
        $this->assertFalse($result->success);
        $this->assertStringContainsString('Failed to generate invoice', $result->message);
    }

    /** @test */
    public function it_validates_billing_period(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        
        // Invalid period: start after end
        $dto = new InvoiceGenerationDTO(
            tenantId: $tenant->tenant_id,
            tenantRenterId: $tenant->id,
            periodStart: Carbon::parse('2024-01-31'),
            periodEnd: Carbon::parse('2024-01-01'),
            dueDate: Carbon::parse('2024-02-14')
        );

        // Act
        $result = $this->billingService->generateInvoice($dto);

        // Assert
        $this->assertFalse($result->success);
        $this->assertStringContainsString('Period start must be before period end', $result->message);
    }

    /** @test */
    public function it_prevents_duplicate_invoices(): void
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        
        // Create existing invoice for the same period
        Invoice::factory()->forTenantRenter($tenant)->create([
            'billing_period_start' => '2024-01-01',
            'billing_period_end' => '2024-01-31',
        ]);

        $dto = new InvoiceGenerationDTO(
            tenantId: $tenant->tenant_id,
            tenantRenterId: $tenant->id,
            periodStart: Carbon::parse('2024-01-01'),
            periodEnd: Carbon::parse('2024-01-31'),
            dueDate: Carbon::parse('2024-02-14')
        );

        // Act
        $result = $this->billingService->generateInvoice($dto);

        // Assert
        $this->assertFalse($result->success);
        $this->assertEquals('Invoice already exists for this period', $result->message);
    }

    /** @test */
    public function it_generates_bulk_invoices_with_proper_error_handling(): void
    {
        // Arrange
        $tenants = collect([
            Tenant::factory()->create(['id' => 1]),
            Tenant::factory()->create(['id' => 2]),
            Tenant::factory()->create(['id' => 3]),
        ]);

        $periodStart = Carbon::parse('2024-01-01');
        $periodEnd = Carbon::parse('2024-01-31');

        // Mock successful generation for first tenant
        $this->generateInvoiceAction
            ->shouldReceive('execute')
            ->once()
            ->andReturn(Invoice::factory()->make(['id' => 1]));

        // Mock consumption service for successful case
        $this->consumptionService
            ->shouldReceive('calculatePropertyConsumption')
            ->once()
            ->andReturn(new ServiceResponse(true, []));

        // Mock failure for second tenant (existing invoice)
        Invoice::factory()->forTenantRenter($tenants[1])->create([
            'billing_period_start' => '2024-01-01',
            'billing_period_end' => '2024-01-31',
        ]);

        // Mock exception for third tenant
        $this->generateInvoiceAction
            ->shouldReceive('execute')
            ->once()
            ->andThrow(new \RuntimeException('Service unavailable'));

        // Act
        $result = $this->billingService->generateBulkInvoices($tenants, $periodStart, $periodEnd);

        // Assert
        $this->assertTrue($result->success);
        $this->assertArrayHasKey('successful', $result->data);
        $this->assertArrayHasKey('failed', $result->data);
        $this->assertArrayHasKey('skipped', $result->data);
        $this->assertEquals(1, count($result->data['successful']));
        $this->assertEquals(1, count($result->data['skipped']));
        $this->assertEquals(1, count($result->data['failed']));
    }

    /** @test */
    public function it_finalizes_invoice_with_validation(): void
    {
        // Arrange
        $invoice = Invoice::factory()->create([
            'status' => 'draft',
            'total_amount' => 100.00,
        ]);

        // Act
        $result = $this->billingService->finalizeInvoice($invoice);

        // Assert
        $this->assertTrue($result->success);
        $this->assertEquals(InvoiceStatus::FINALIZED, $invoice->fresh()->status);
        $this->assertNotNull($invoice->fresh()->finalized_at);
    }

    /** @test */
    public function it_prevents_finalizing_non_draft_invoice(): void
    {
        // Arrange
        $invoice = Invoice::factory()->create([
            'status' => 'finalized',
            'total_amount' => 100.00,
        ]);

        // Act
        $result = $this->billingService->finalizeInvoice($invoice);

        // Assert
        $this->assertFalse($result->success);
        $this->assertEquals('Invoice is not in draft status', $result->message);
    }

    /** @test */
    public function it_prevents_finalizing_zero_amount_invoice(): void
    {
        // Arrange
        $invoice = Invoice::factory()->create([
            'status' => 'draft',
            'total_amount' => 0.00,
        ]);

        // Act
        $result = $this->billingService->finalizeInvoice($invoice);

        // Assert
        $this->assertFalse($result->success);
        $this->assertEquals('Cannot finalize invoice with zero or negative amount', $result->message);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
