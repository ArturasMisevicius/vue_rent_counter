<?php

declare(strict_types=1);

use App\Enums\DistributionMethod;
use App\Enums\MeterType;
use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Models\Invoice;
use App\Models\InvoiceGenerationAudit;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\ServiceConfiguration;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UtilityService;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenantId = 1101;
    $this->periodStart = Carbon::parse('2025-01-01');
    $this->periodEnd = Carbon::parse('2025-01-31');

    session(['tenant_id' => $this->tenantId]);

    $this->property = Property::factory()->create([
        'tenant_id' => $this->tenantId,
    ]);

    $this->tenant = Tenant::factory()->create([
        'tenant_id' => $this->tenantId,
        'property_id' => $this->property->id,
    ]);

    $this->admin = User::factory()->admin($this->tenantId)->create();

    $utilityService = UtilityService::factory()->create([
        'tenant_id' => $this->tenantId,
        'default_pricing_model' => PricingModel::CONSUMPTION_BASED,
        'service_type_bridge' => ServiceType::ELECTRICITY,
        'unit_of_measurement' => 'kWh',
        'is_active' => true,
    ]);

    $serviceConfiguration = ServiceConfiguration::factory()
        ->withPricingModel(PricingModel::CONSUMPTION_BASED)
        ->create([
            'tenant_id' => $this->tenantId,
            'property_id' => $this->property->id,
            'utility_service_id' => $utilityService->id,
            'pricing_model' => PricingModel::CONSUMPTION_BASED,
            'distribution_method' => DistributionMethod::EQUAL,
            'rate_schedule' => ['rate_per_unit' => 0.20],
            'effective_from' => $this->periodStart->copy()->subMonth(),
            'effective_until' => null,
            'is_active' => true,
        ]);

    $meter = Meter::factory()->create([
        'tenant_id' => $this->tenantId,
        'property_id' => $this->property->id,
        'service_configuration_id' => $serviceConfiguration->id,
        'type' => MeterType::ELECTRICITY,
        'supports_zones' => false,
    ]);

    MeterReading::factory()->create([
        'tenant_id' => $this->tenantId,
        'meter_id' => $meter->id,
        'reading_date' => $this->periodStart,
        'value' => 100,
        'entered_by' => $this->admin->id,
    ]);

    MeterReading::factory()->create([
        'tenant_id' => $this->tenantId,
        'meter_id' => $meter->id,
        'reading_date' => $this->periodEnd,
        'value' => 150,
        'entered_by' => $this->admin->id,
    ]);
});

test('billing service reuses existing draft invoice for same tenant and billing period', function (): void {
    $this->actingAs($this->admin);
    $service = app(BillingService::class);

    $firstInvoice = $service->generateInvoice($this->tenant, $this->periodStart, $this->periodEnd);
    $secondInvoice = $service->generateInvoice($this->tenant, $this->periodStart, $this->periodEnd);

    expect($secondInvoice->id)->toBe($firstInvoice->id)
        ->and(
            Invoice::query()
                ->where('tenant_id', $this->tenantId)
                ->where('tenant_renter_id', $this->tenant->id)
                ->whereDate('billing_period_start', $this->periodStart->toDateString())
                ->whereDate('billing_period_end', $this->periodEnd->toDateString())
                ->count()
        )->toBe(1);
});

test('billing service records invoice generation audit entries', function (): void {
    $this->actingAs($this->admin);
    $invoice = app(BillingService::class)->generateInvoice($this->tenant, $this->periodStart, $this->periodEnd);

    /** @var InvoiceGenerationAudit|null $audit */
    $audit = InvoiceGenerationAudit::query()
        ->where('invoice_id', $invoice->id)
        ->latest('id')
        ->first();

    expect($audit)->not->toBeNull()
        ->and($audit->user_id)->toBe($this->admin->id)
        ->and($audit->tenant_id)->toBe($this->tenantId)
        ->and($audit->period_start->toDateString())->toBe($this->periodStart->toDateString())
        ->and($audit->period_end->toDateString())->toBe($this->periodEnd->toDateString())
        ->and($audit->items_count)->toBeGreaterThan(0)
        ->and((float) $audit->total_amount)->toBe((float) $invoice->total_amount)
        ->and($audit->metadata['was_reused'] ?? null)->toBeFalse();
});
