# BillingService API Reference

**Version**: 2.0.0  
**Last Updated**: 2024-11-25  
**Status**: Production Ready ✅

## Overview

The `BillingService` provides invoice generation and finalization capabilities for the Vilnius Utilities Billing Platform. It orchestrates tariff resolution, meter reading collection, gyvatukas calculations, and invoice item creation with full tariff and reading snapshotting.

## Class Reference

### Namespace

```php
App\Services\BillingService
```

### Extends

```php
App\Services\BaseService
```

### Dependencies

```php
use App\Services\TariffResolver;
use App\Services\GyvatukasCalculator;
```

### Constructor

```php
public function __construct(
    private readonly TariffResolver $tariffResolver,
    private readonly GyvatukasCalculator $gyvatukasCalculator
)
```

**Parameters**: None (dependencies injected)

**Example**:
```php
$billingService = app(BillingService::class);
// or
$billingService = new BillingService(
    app(TariffResolver::class),
    app(GyvatukasCalculator::class)
);
```

---

## Public Methods

### generateInvoice()

Generate a draft invoice for a tenant for a specific billing period.

```php
public function generateInvoice(
    Tenant $tenant,
    Carbon $periodStart,
    Carbon $periodEnd
): Invoice
```

**Parameters**:
- `$tenant` (Tenant) - The tenant to bill
- `$periodStart` (Carbon) - Start of billing period
- `$periodEnd` (Carbon) - End of billing period

**Returns**: `Invoice` - The generated draft invoice with items

**Throws**:
- `BillingException` - If invoice generation fails
- `MissingMeterReadingException` - If required meter readings are missing

**Requirements**: 3.1, 3.2, 3.3, 5.1, 5.2

**Example**:
```php
$billingService = app(BillingService::class);
$tenant = Tenant::find(1);

$invoice = $billingService->generateInvoice(
    $tenant,
    Carbon::parse('2024-11-01'),
    Carbon::parse('2024-11-30')
);

echo "Invoice #{$invoice->id}\n";
echo "Total: €{$invoice->total_amount}\n";
echo "Items: {$invoice->items->count()}\n";
```

**Process**:
1. Validates tenant has property
2. Eager loads meters with readings (±7 day buffer)
3. Creates draft Invoice
4. Generates items for each meter
5. Adds gyvatukas items (if applicable)
6. Calculates total amount
7. Returns invoice with items

**Performance**:
- Constant 3 queries regardless of meter count
- Execution time: ~50-250ms depending on meter count
- Memory usage: ~3-15MB depending on meter count

**Error Handling**:
```php
try {
    $invoice = $billingService->generateInvoice($tenant, $start, $end);
} catch (BillingException $e) {
    // Handle billing errors (no property, no meters, no provider)
    Log::error('Billing failed', ['error' => $e->getMessage()]);
} catch (MissingMeterReadingException $e) {
    // Handle missing readings
    Log::warning('Missing readings', ['error' => $e->getMessage()]);
}
```

---

### finalizeInvoice()

Finalize an invoice, making it immutable.

```php
public function finalizeInvoice(Invoice $invoice): Invoice
```

**Parameters**:
- `$invoice` (Invoice) - The invoice to finalize

**Returns**: `Invoice` - The finalized invoice

**Throws**:
- `InvoiceAlreadyFinalizedException` - If invoice is already finalized or paid

**Requirements**: 5.5

**Example**:
```php
$invoice = Invoice::find(1);

try {
    $finalizedInvoice = $billingService->finalizeInvoice($invoice);
    echo "Finalized at: {$finalizedInvoice->finalized_at}\n";
} catch (InvoiceAlreadyFinalizedException $e) {
    echo "Invoice already finalized\n";
}
```

**Validation**:
- Checks if invoice is already finalized
- Checks if invoice is already paid
- Throws exception if either condition is true

**Side Effects**:
- Sets `finalized_at` timestamp
- Changes status to `FINALIZED`
- Makes invoice immutable (no further edits allowed)
- Logs finalization event

---

## Private Methods

### generateInvoiceItemsForMeter()

Generate invoice items for a specific meter.

```php
private function generateInvoiceItemsForMeter(
    Meter $meter,
    BillingPeriod $period,
    Property $property
): Collection
```

**Parameters**:
- `$meter` (Meter) - The meter to generate items for
- `$period` (BillingPeriod) - The billing period
- `$property` (Property) - The property (for tariff selection)

**Returns**: `Collection<int, array>` - Collection of invoice item data arrays

**Throws**:
- `MissingMeterReadingException` - If required readings are missing

**Handles**:
- Multi-zone meters (day/night electricity)
- Single-zone meters (water, heating)
- Fixed fees for water meters

---

### createInvoiceItemForZone()

Create an invoice item for a specific meter and zone.

```php
private function createInvoiceItemForZone(
    Meter $meter,
    ?string $zone,
    BillingPeriod $period,
    Property $property
): ?array
```

**Parameters**:
- `$meter` (Meter) - The meter
- `$zone` (string|null) - The tariff zone (e.g., 'day', 'night')
- `$period` (BillingPeriod) - The billing period
- `$property` (Property) - The property

**Returns**: `array|null` - Invoice item data array or null if no consumption

**Throws**:
- `MissingMeterReadingException` - If required readings are missing

**Item Structure**:
```php
[
    'description' => 'Electricity (day)',
    'quantity' => 150.50,
    'unit' => 'kWh',
    'unit_price' => 0.18,
    'total' => 27.09,
    'meter_reading_snapshot' => [
        'meter_id' => 1,
        'meter_serial' => 'ABC123',
        'start_reading_id' => 10,
        'start_value' => 1000.0,
        'start_date' => '2024-11-01',
        'end_reading_id' => 11,
        'end_value' => 1150.5,
        'end_date' => '2024-11-30',
        'zone' => 'day',
        'tariff_id' => 5,
        'tariff_name' => 'Ignitis Day/Night',
        'tariff_configuration' => [...]
    ]
]
```

---

### calculateWaterTotal()

Calculate water bill total including supply, sewage, and fixed fee.

```php
private function calculateWaterTotal(
    float $consumption,
    Property $property
): float
```

**Parameters**:
- `$consumption` (float) - Water consumption in m³
- `$property` (Property) - The property (for type-specific rates)

**Returns**: `float` - Total cost

**Requirements**: 3.1, 3.2

**Formula**:
```
Total = (Consumption × Supply Rate) + (Consumption × Sewage Rate)
```

**Configuration**:
```php
// config/billing.php
'water_tariffs' => [
    'default_supply_rate' => 0.97,  // €/m³
    'default_sewage_rate' => 1.23,  // €/m³
],
```

**Example**:
```php
// 10 m³ consumption
$total = $this->calculateWaterTotal(10.0, $property);
// Returns: (10 × 0.97) + (10 × 1.23) = 22.00
```

---

### createWaterFixedFeeItem()

Create a fixed fee invoice item for water meters.

```php
private function createWaterFixedFeeItem(Meter $meter): array
```

**Parameters**:
- `$meter` (Meter) - The water meter

**Returns**: `array` - Invoice item data array

**Requirements**: 3.2

**Configuration**:
```php
// config/billing.php
'water_tariffs' => [
    'default_fixed_fee' => 0.85,  // €/month
],
```

**Item Structure**:
```php
[
    'description' => 'Water Cold - Fixed Fee',
    'quantity' => 1.00,
    'unit' => 'month',
    'unit_price' => 0.85,
    'total' => 0.85,
    'meter_reading_snapshot' => [
        'meter_id' => 2,
        'meter_serial' => 'WATER123',
        'fee_type' => 'fixed_monthly',
    ]
]
```

---

### generateGyvatukasItems()

Generate gyvatukas (circulation fee) invoice items.

```php
private function generateGyvatukasItems(
    Property $property,
    BillingPeriod $period
): Collection
```

**Parameters**:
- `$property` (Property) - The property
- `$period` (BillingPeriod) - The billing period

**Returns**: `Collection<int, array>` - Collection of gyvatukas invoice item data

**Requirements**: 4.1, 4.2, 4.3

**Error Handling**:
- Graceful degradation if calculation fails
- Logs error but continues invoice generation
- Returns empty collection on failure

**Example**:
```php
[
    [
        'description' => 'Gyvatukas (Hot Water Circulation)',
        'quantity' => 1.00,
        'unit' => 'month',
        'unit_price' => 150.50,
        'total' => 150.50,
        'meter_reading_snapshot' => [
            'building_id' => 1,
            'calculation_type' => 'gyvatukas',
            'calculation_date' => '2024-11-01',
        ]
    ]
]
```

---

### getReadingAtOrBefore()

Get meter reading at or before a specific date.

```php
private function getReadingAtOrBefore(
    Meter $meter,
    ?string $zone,
    Carbon $date
): ?MeterReading
```

**Parameters**:
- `$meter` (Meter) - The meter
- `$zone` (string|null) - The zone
- `$date` (Carbon) - The date

**Returns**: `MeterReading|null` - The reading or null

**Query**:
```sql
SELECT * FROM meter_readings
WHERE meter_id = ?
  AND (zone = ? OR zone IS NULL)
  AND reading_date <= ?
ORDER BY reading_date DESC
LIMIT 1
```

---

### getReadingAtOrAfter()

Get meter reading at or after a specific date.

```php
private function getReadingAtOrAfter(
    Meter $meter,
    ?string $zone,
    Carbon $date
): ?MeterReading
```

**Parameters**:
- `$meter` (Meter) - The meter
- `$zone` (string|null) - The zone
- `$date` (Carbon) - The date

**Returns**: `MeterReading|null` - The reading or null

**Query**:
```sql
SELECT * FROM meter_readings
WHERE meter_id = ?
  AND (zone = ? OR zone IS NULL)
  AND reading_date >= ?
ORDER BY reading_date ASC
LIMIT 1
```

---

### getZonesForMeter()

Get zones for a multi-zone meter within a billing period.

```php
private function getZonesForMeter(
    Meter $meter,
    BillingPeriod $period
): array
```

**Parameters**:
- `$meter` (Meter) - The meter
- `$period` (BillingPeriod) - The billing period

**Returns**: `array` - Array of zone identifiers

**Example**:
```php
// Returns: ['day', 'night']
$zones = $this->getZonesForMeter($electricityMeter, $period);
```

---

### getProviderForMeterType()

Get provider for a specific meter type.

```php
private function getProviderForMeterType(MeterType $meterType): Provider
```

**Parameters**:
- `$meterType` (MeterType) - The meter type

**Returns**: `Provider` - The provider

**Throws**:
- `BillingException` - If provider not found

**Mapping**:
```php
MeterType::ELECTRICITY => ServiceType::ELECTRICITY
MeterType::WATER_COLD  => ServiceType::WATER
MeterType::WATER_HOT   => ServiceType::WATER
MeterType::HEATING     => ServiceType::HEATING
```

---

### calculateUnitPrice()

Calculate unit price for a meter based on tariff.

```php
private function calculateUnitPrice(
    Meter $meter,
    Tariff $tariff,
    float $consumption,
    Carbon $timestamp,
    Property $property
): float
```

**Parameters**:
- `$meter` (Meter) - The meter
- `$tariff` (Tariff) - The tariff
- `$consumption` (float) - The consumption amount
- `$timestamp` (Carbon) - The timestamp for time-of-use calculation
- `$property` (Property) - The property

**Returns**: `float` - The unit price

**Special Cases**:
- Water meters: Returns supply + sewage rate
- Other meters: Uses TariffResolver for time-of-use calculation

---

### getItemDescription()

Get item description for a meter.

```php
private function getItemDescription(
    Meter $meter,
    ?string $zone
): string
```

**Parameters**:
- `$meter` (Meter) - The meter
- `$zone` (string|null) - The zone

**Returns**: `string` - The description

**Examples**:
```php
'Electricity'
'Electricity (day)'
'Electricity (night)'
'Water Cold'
'Heating'
```

---

### getUnit()

Get unit for a meter type.

```php
private function getUnit(MeterType $meterType): string
```

**Parameters**:
- `$meterType` (MeterType) - The meter type

**Returns**: `string` - The unit

**Mapping**:
```php
MeterType::ELECTRICITY => 'kWh'
MeterType::HEATING     => 'kWh'
MeterType::WATER_COLD  => 'm³'
MeterType::WATER_HOT   => 'm³'
```

---

## Exception Reference

### BillingException

**Namespace**: `App\Exceptions\BillingException`

**Thrown When**:
- Tenant has no associated property
- Property has no meters
- No provider found for meter type

**Example**:
```php
throw new BillingException("Tenant {$tenant->id} has no associated property");
```

---

### MissingMeterReadingException

**Namespace**: `App\Exceptions\MissingMeterReadingException`

**Thrown When**:
- No start reading found
- No end reading found
- Specific zone reading missing

**Constructor**:
```php
public function __construct(
    int $meterId,
    Carbon $date,
    ?string $zone = null
)
```

**Example**:
```php
throw new MissingMeterReadingException($meter->id, $period->start, 'day');
```

---

### InvoiceAlreadyFinalizedException

**Namespace**: `App\Exceptions\InvoiceAlreadyFinalizedException`

**Thrown When**:
- Invoice is already finalized
- Invoice is already paid

**Constructor**:
```php
public function __construct(int $invoiceId)
```

**Example**:
```php
throw new InvoiceAlreadyFinalizedException($invoice->id);
```

---

## Configuration Reference

### Water Tariffs

**File**: `config/billing.php`

```php
'water_tariffs' => [
    'default_supply_rate' => 0.97,  // €/m³
    'default_sewage_rate' => 1.23,  // €/m³
    'default_fixed_fee' => 0.85,    // €/month
],
```

### Invoice Settings

**File**: `config/billing.php`

```php
'invoice' => [
    'default_due_days' => 14,  // Days after period end
],
```

---

## Integration Examples

### Controller Integration

```php
use App\Services\BillingService;
use App\Http\Requests\GenerateInvoiceRequest;

class InvoiceController extends Controller
{
    public function __construct(
        private BillingService $billingService
    ) {}
    
    public function generate(GenerateInvoiceRequest $request)
    {
        $tenant = Tenant::findOrFail($request->tenant_id);
        
        try {
            $invoice = $this->billingService->generateInvoice(
                $tenant,
                Carbon::parse($request->period_start),
                Carbon::parse($request->period_end)
            );
            
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('success', 'Invoice generated successfully');
                
        } catch (BillingException $e) {
            return back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }
    
    public function finalize(Invoice $invoice)
    {
        try {
            $this->billingService->finalizeInvoice($invoice);
            
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('success', 'Invoice finalized successfully');
                
        } catch (InvoiceAlreadyFinalizedException $e) {
            return back()
                ->withErrors(['error' => 'Invoice already finalized']);
        }
    }
}
```

### Command Integration

```php
use App\Services\BillingService;
use Illuminate\Console\Command;

class GenerateMonthlyInvoicesCommand extends Command
{
    protected $signature = 'invoices:generate-monthly {--month=}';
    
    public function __construct(
        private BillingService $billingService
    ) {
        parent::__construct();
    }
    
    public function handle()
    {
        $month = $this->option('month') ?? now()->subMonth()->format('Y-m');
        $date = Carbon::parse($month);
        
        $periodStart = $date->copy()->startOfMonth();
        $periodEnd = $date->copy()->endOfMonth();
        
        $tenants = Tenant::with('property.meters.readings')->get();
        $bar = $this->output->createProgressBar($tenants->count());
        
        $generated = 0;
        $failed = 0;
        
        foreach ($tenants as $tenant) {
            try {
                $invoice = $this->billingService->generateInvoice(
                    $tenant,
                    $periodStart,
                    $periodEnd
                );
                
                $generated++;
                
            } catch (\Exception $e) {
                $this->error("Failed for tenant {$tenant->id}: {$e->getMessage()}");
                $failed++;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        
        $this->info("Generated: {$generated}");
        $this->info("Failed: {$failed}");
    }
}
```

### Job Integration

```php
use App\Services\BillingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class GenerateInvoiceJob implements ShouldQueue
{
    use Queueable;
    
    public function __construct(
        private int $tenantId,
        private string $periodStart,
        private string $periodEnd
    ) {}
    
    public function handle(BillingService $billingService)
    {
        $tenant = Tenant::findOrFail($this->tenantId);
        
        try {
            $invoice = $billingService->generateInvoice(
                $tenant,
                Carbon::parse($this->periodStart),
                Carbon::parse($this->periodEnd)
            );
            
            Log::info("Invoice generated", [
                'invoice_id' => $invoice->id,
                'tenant_id' => $tenant->id,
            ]);
            
        } catch (\Exception $e) {
            Log::error("Invoice generation failed", [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
            
            throw $e; // Re-throw for job retry
        }
    }
}
```

---

## Related Documentation

- [BillingService v2.0 Implementation Guide](../implementation/BILLING_SERVICE_V2_IMPLEMENTATION.md)
- [Service Layer Architecture](../architecture/SERVICE_LAYER_ARCHITECTURE.md)
- [TariffResolver API](./TARIFF_RESOLVER_API.md)
- [GyvatukasCalculator API](GYVATUKAS_CALCULATOR_API.md)
- [Value Objects Guide](../architecture/VALUE_OBJECTS_GUIDE.md)

---

**Document Version**: 1.0.0  
**Last Updated**: 2024-11-25  
**Status**: Production Ready ✅
