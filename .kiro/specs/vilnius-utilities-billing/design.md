# Design Document

## Overview

The Vilnius Utilities Billing System is a monolithic Laravel 11/12 application using SQLite as the primary database. The architecture follows the "Majestic Monolith" pattern, consolidating all business logic, data access, and presentation layers within a single deployable unit. The system eliminates the complexity of separate frontend/backend codebases by using server-side rendering (Blade templates) enhanced with Alpine.js for reactive UI components.

The core domain revolves around three Lithuanian utility providers (Ignitis for electricity, Vilniaus Vandenys for water, Vilniaus Energija for heating) and their unique billing rules, particularly the seasonal "gyvatukas" (circulation fee) calculation that differs between heating and non-heating seasons.

Key architectural decisions:
- **SQLite with WAL mode**: Enables concurrent reads during writes, suitable for multi-user web applications
- **No JavaScript build step**: Alpine.js loaded via CDN, eliminating webpack/vite complexity
- **Single-database multi-tenancy**: Global scopes enforce data isolation between property management companies
- **Snapshot-based billing**: Invoice generation copies current prices to prevent retroactive recalculation

## Architecture

### Layered Architecture

```
┌─────────────────────────────────────────────────┐
│         Presentation Layer (Blade + Alpine)      │
│  - Blade Components (x-card, x-meter-form)      │
│  - Alpine.js for client-side reactivity         │
└─────────────────────────────────────────────────┘
                      ↓
┌─────────────────────────────────────────────────┐
│           Application Layer (Controllers)        │
│  - Single Action Controllers preferred          │
│  - Form Requests for validation                 │
│  - Resource Controllers for CRUD                │
└─────────────────────────────────────────────────┘
                      ↓
┌─────────────────────────────────────────────────┐
│          Domain Layer (Services + Models)        │
│  - BillingService (invoice generation)          │
│  - GyvatukasCalculator (circulation logic)      │
│  - TariffResolver (time-based rate selection)   │
│  - Eloquent Models with business logic          │
└─────────────────────────────────────────────────┘
                      ↓
┌─────────────────────────────────────────────────┐
│        Data Layer (Eloquent ORM + SQLite)        │
│  - Migrations with foreign keys                 │
│  - Global Scopes for multi-tenancy              │
│  - Observers for audit trails                   │
└─────────────────────────────────────────────────┘
```

### Multi-Tenancy Strategy

The system uses a **single database, shared schema** approach with automatic tenant scoping:

1. **Session-based tenant identification**: Upon authentication, `tenant_id` is stored in session
2. **Global Scopes**: Applied to all tenant-aware models (Property, Meter, Invoice, Tenant)
3. **Middleware enforcement**: `EnsureTenantContext` middleware validates tenant_id on every request
4. **Automatic query filtering**: All Eloquent queries automatically include `WHERE tenant_id = ?`

### Security Model

Three-tier role-based access control (RBAC):

- **Admin**: Full system access, tariff configuration, tenant management
- **Manager**: Meter reading entry, invoice generation, tenant viewing
- **Tenant**: Read-only access to own invoices and consumption history

Authorization implemented via Laravel Policies with `@can` directives in Blade templates.

## Components and Interfaces

### Core Domain Models

#### 1. Tenant (Арендатор)
```php
class Tenant extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'property_id', 'lease_start', 'lease_end'];
    
    public function property(): BelongsTo;
    public function invoices(): HasMany;
    public function meterReadings(): HasManyThrough;
}
```

#### 2. Property (Недвижимость)
```php
class Property extends Model
{
    protected $fillable = ['address', 'type', 'area_sqm', 'tenant_id', 'building_id'];
    protected $casts = ['type' => PropertyType::class]; // enum: apartment, house
    
    public function tenants(): HasMany;
    public function meters(): HasMany;
    public function building(): BelongsTo;
}
```

#### 3. Meter (Счетчик)
```php
class Meter extends Model
{
    protected $fillable = ['serial_number', 'type', 'property_id', 'installation_date', 'supports_zones'];
    protected $casts = [
        'type' => MeterType::class, // enum: electricity, water_cold, water_hot, heating
        'supports_zones' => 'boolean'
    ];
    
    public function readings(): HasMany;
    public function property(): BelongsTo;
}
```

#### 4. MeterReading (Показание счетчика)
```php
class MeterReading extends Model
{
    protected $fillable = ['meter_id', 'reading_date', 'value', 'zone', 'entered_by'];
    protected $casts = ['reading_date' => 'datetime', 'value' => 'decimal:2'];
    
    public function meter(): BelongsTo;
    public function enteredBy(): BelongsTo; // User who entered
    public function auditTrail(): HasMany; // MeterReadingAudit
}
```

#### 5. Provider (Поставщик)
```php
class Provider extends Model
{
    protected $fillable = ['name', 'service_type', 'contact_info'];
    protected $casts = ['service_type' => ServiceType::class]; // electricity, water, heating
    
    public function tariffs(): HasMany;
}
```

#### 6. Tariff (Тариф)
```php
class Tariff extends Model
{
    protected $fillable = ['provider_id', 'name', 'configuration', 'active_from', 'active_until'];
    protected $casts = [
        'configuration' => 'array', // JSON structure
        'active_from' => 'datetime',
        'active_until' => 'datetime'
    ];
    
    public function provider(): BelongsTo;
    public function isActiveOn(Carbon $date): bool;
}
```

**Tariff Configuration JSON Schema:**
```json
{
    "type": "time_of_use",
    "currency": "EUR",
    "zones": [
        {"id": "day", "start": "07:00", "end": "23:00", "rate": 0.18},
        {"id": "night", "start": "23:00", "end": "07:00", "rate": 0.10}
    ],
    "weekend_logic": "apply_night_rate",
    "fixed_fee": 0.85
}
```

#### 7. Invoice (Счет)
```php
class Invoice extends Model
{
    protected $fillable = ['tenant_id', 'billing_period_start', 'billing_period_end', 'total_amount', 'status', 'finalized_at'];
    protected $casts = [
        'billing_period_start' => 'date',
        'billing_period_end' => 'date',
        'total_amount' => 'decimal:2',
        'status' => InvoiceStatus::class, // draft, finalized, paid
        'finalized_at' => 'datetime'
    ];
    
    public function tenant(): BelongsTo;
    public function items(): HasMany; // InvoiceItem
    public function finalize(): void; // Makes invoice immutable
}
```

#### 8. InvoiceItem (Позиция счета)
```php
class InvoiceItem extends Model
{
    protected $fillable = ['invoice_id', 'description', 'quantity', 'unit_price', 'total', 'meter_reading_snapshot'];
    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:4',
        'total' => 'decimal:2',
        'meter_reading_snapshot' => 'array'
    ];
    
    public function invoice(): BelongsTo;
}
```

#### 9. Building (Здание)
```php
class Building extends Model
{
    protected $fillable = ['address', 'total_apartments', 'gyvatukas_summer_average', 'gyvatukas_last_calculated'];
    protected $casts = [
        'gyvatukas_summer_average' => 'decimal:2',
        'gyvatukas_last_calculated' => 'date'
    ];
    
    public function properties(): HasMany;
    public function calculateSummerAverage(Carbon $startDate, Carbon $endDate): float;
}
```

#### 10. MeterReadingAudit (Аудит изменений)
```php
class MeterReadingAudit extends Model
{
    protected $fillable = ['meter_reading_id', 'changed_by_user_id', 'old_value', 'new_value', 'change_reason'];
    protected $casts = ['old_value' => 'decimal:2', 'new_value' => 'decimal:2'];
    
    public function meterReading(): BelongsTo;
    public function changedBy(): BelongsTo; // User
}
```

### Service Layer

#### BillingService
Orchestrates invoice generation with snapshotting:

```php
class BillingService
{
    public function generateInvoice(Tenant $tenant, Carbon $periodStart, Carbon $periodEnd): Invoice
    {
        // 1. Collect all meter readings for period
        // 2. Resolve applicable tariffs (snapshot rates)
        // 3. Calculate consumption per utility type
        // 4. Apply GyvatukasCalculator if heating/hot water
        // 5. Create Invoice with InvoiceItems
        // 6. Return draft invoice
    }
    
    public function finalizeInvoice(Invoice $invoice): void
    {
        // Mark as immutable, set finalized_at timestamp
    }
}
```

#### GyvatukasCalculator
Implements seasonal circulation fee logic:

```php
class GyvatukasCalculator
{
    public function calculate(Building $building, Carbon $billingMonth): float
    {
        if ($this->isHeatingSeason($billingMonth)) {
            return $this->calculateWinterGyvatukas($building);
        }
        return $this->calculateSummerGyvatukas($building, $billingMonth);
    }
    
    private function isHeatingSeason(Carbon $date): bool
    {
        return $date->month >= 10 || $date->month <= 4; // Oct-Apr
    }
    
    private function calculateSummerGyvatukas(Building $building, Carbon $month): float
    {
        // Q_circ = Q_total - (V_water × c × ΔT)
        // Fetch building total energy and hot water consumption
    }
    
    private function calculateWinterGyvatukas(Building $building): float
    {
        // Use stored summer average from building->gyvatukas_summer_average
    }
}
```

#### TariffResolver
Selects correct tariff based on date and calculates time-of-use rates:

```php
class TariffResolver
{
    public function resolve(Provider $provider, Carbon $date): Tariff
    {
        return $provider->tariffs()
            ->where('active_from', '<=', $date)
            ->where(fn($q) => $q->whereNull('active_until')->orWhere('active_until', '>=', $date))
            ->firstOrFail();
    }
    
    public function calculateCost(Tariff $tariff, float $consumption, ?Carbon $timestamp = null): float
    {
        $config = $tariff->configuration;
        
        if ($config['type'] === 'flat') {
            return $consumption * $config['rate'];
        }
        
        if ($config['type'] === 'time_of_use') {
            // Determine which zone applies (day/night/weekend)
            $zone = $this->determineZone($config['zones'], $timestamp);
            return $consumption * $zone['rate'];
        }
    }
}
```

### Presentation Layer

#### Blade Components

**x-meter-reading-form**: Reactive form for entering readings
```blade
<div x-data="meterReadingForm()">
    <select x-model="meterId" @change="loadPreviousReading()">
        @foreach($meters as $meter)
            <option value="{{ $meter->id }}">{{ $meter->serial_number }}</option>
        @endforeach
    </select>
    
    <input type="number" 
           x-model="currentReading" 
           @input="validateReading()"
           :class="{'border-red-500': error}">
    
    <span x-show="error" x-text="errorMessage" class="text-red-500"></span>
    
    <div x-show="previousReading">
        Previous: <span x-text="previousReading"></span>
        Consumption: <span x-text="consumption"></span>
    </div>
</div>

<script>
function meterReadingForm() {
    return {
        meterId: null,
        currentReading: 0,
        previousReading: null,
        error: false,
        errorMessage: '',
        
        async loadPreviousReading() {
            const response = await fetch(`/api/meters/${this.meterId}/last-reading`);
            this.previousReading = await response.json();
        },
        
        validateReading() {
            if (this.currentReading < this.previousReading) {
                this.error = true;
                this.errorMessage = 'Reading cannot be lower than previous';
            } else {
                this.error = false;
            }
        },
        
        get consumption() {
            return this.currentReading - this.previousReading;
        }
    }
}
</script>
```

**x-invoice-summary**: Displays itemized invoice breakdown
```blade
<div class="invoice-summary">
    <h3>Invoice #{{ $invoice->id }}</h3>
    <p>Period: {{ $invoice->billing_period_start->format('Y-m-d') }} - {{ $invoice->billing_period_end->format('Y-m-d') }}</p>
    
    <table>
        <thead>
            <tr>
                <th>Service</th>
                <th>Consumption</th>
                <th>Rate</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td>{{ $item->quantity }} {{ $item->unit }}</td>
                <td>€{{ $item->unit_price }}</td>
                <td>€{{ $item->total }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3"><strong>Total</strong></td>
                <td><strong>€{{ $invoice->total_amount }}</strong></td>
            </tr>
        </tfoot>
    </table>
</div>
```

## Data Models

### Database Schema

#### Core Tables

**tenants**
- id (primary key)
- tenant_id (for multi-tenancy isolation)
- name
- email
- phone
- property_id (foreign key)
- lease_start (date)
- lease_end (date)
- created_at, updated_at

**properties**
- id (primary key)
- tenant_id (for multi-tenancy)
- address (text)
- type (enum: apartment, house)
- area_sqm (decimal)
- building_id (foreign key, nullable)
- created_at, updated_at

**buildings**
- id (primary key)
- tenant_id (for multi-tenancy)
- address (text)
- total_apartments (integer)
- gyvatukas_summer_average (decimal, nullable)
- gyvatukas_last_calculated (date, nullable)
- created_at, updated_at

**meters**
- id (primary key)
- tenant_id (for multi-tenancy)
- serial_number (unique)
- type (enum: electricity, water_cold, water_hot, heating)
- property_id (foreign key)
- installation_date (date)
- supports_zones (boolean, default false)
- created_at, updated_at

**meter_readings**
- id (primary key)
- tenant_id (for multi-tenancy)
- meter_id (foreign key)
- reading_date (datetime)
- value (decimal)
- zone (string, nullable - for day/night electricity)
- entered_by (foreign key to users)
- created_at, updated_at

**meter_reading_audits**
- id (primary key)
- meter_reading_id (foreign key)
- changed_by_user_id (foreign key to users)
- old_value (decimal)
- new_value (decimal)
- change_reason (text)
- created_at

**providers**
- id (primary key)
- name (string)
- service_type (enum: electricity, water, heating)
- contact_info (json, nullable)
- created_at, updated_at

**tariffs**
- id (primary key)
- provider_id (foreign key)
- name (string)
- configuration (json)
- active_from (datetime)
- active_until (datetime, nullable)
- created_at, updated_at

**invoices**
- id (primary key)
- tenant_id (for multi-tenancy + foreign key to tenants)
- billing_period_start (date)
- billing_period_end (date)
- total_amount (decimal)
- status (enum: draft, finalized, paid)
- finalized_at (datetime, nullable)
- created_at, updated_at

**invoice_items**
- id (primary key)
- invoice_id (foreign key)
- description (string)
- quantity (decimal)
- unit (string - kWh, m³, etc.)
- unit_price (decimal)
- total (decimal)
- meter_reading_snapshot (json - stores reading IDs and values used)
- created_at, updated_at

**users**
- id (primary key)
- tenant_id (for multi-tenancy)
- name
- email (unique)
- password (hashed)
- role (enum: admin, manager, tenant)
- created_at, updated_at

### Relationships

```
Building 1---* Property
Property 1---* Meter
Property 1---* Tenant
Meter 1---* MeterReading
MeterReading 1---* MeterReadingAudit
Tenant 1---* Invoice
Invoice 1---* InvoiceItem
Provider 1---* Tariff
User 1---* MeterReading (entered_by)
User 1---* MeterReadingAudit (changed_by)
```

### Indexes

Critical indexes for performance:
- `meter_readings(meter_id, reading_date)` - for fetching latest reading
- `invoices(tenant_id, billing_period_start)` - for tenant invoice history
- `tariffs(provider_id, active_from, active_until)` - for tariff resolution
- `meter_readings(tenant_id)` - for multi-tenancy filtering
- `properties(tenant_id)` - for multi-tenancy filtering

### Foreign Key Constraints

All foreign keys configured with appropriate cascade rules:
- `ON DELETE CASCADE`: meter_readings → meters (if meter deleted, readings go too)
- `ON DELETE RESTRICT`: invoices → tenants (cannot delete tenant with invoices)
- `ON DELETE CASCADE`: invoice_items → invoices (if invoice deleted, items go too)
- `ON DELETE SET NULL`: meter_readings → users (if user deleted, preserve readings)


## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Meter reading monotonicity
*For any* meter and any new reading submission, if the new reading value is less than the most recent reading value for that meter, then the submission should be rejected with a validation error.
**Validates: Requirements 1.2**

### Property 2: Meter reading temporal validity
*For any* meter reading submission, if the reading_date is in the future (after the current timestamp), then the submission should be rejected with a validation error.
**Validates: Requirements 1.3**

### Property 3: Meter reading audit trail completeness
*For any* successfully stored meter reading, the record should contain a non-null entered_by user ID and a created_at timestamp.
**Validates: Requirements 1.4**

### Property 4: Multi-zone meter reading acceptance
*For any* meter where supports_zones is true, the system should accept and store readings with different zone identifiers (e.g., "day", "night") for the same reading_date.
**Validates: Requirements 1.5**

### Property 5: Tariff configuration JSON round-trip
*For any* valid tariff configuration object, storing it as JSON in the database and then retrieving it should produce an equivalent configuration structure.
**Validates: Requirements 2.1**

### Property 6: Time-of-use zone validation
*For any* tariff configuration with type "time_of_use", if the time zones overlap or do not cover all 24 hours, then the tariff creation should be rejected with a validation error.
**Validates: Requirements 2.2**

### Property 7: Tariff temporal selection
*For any* provider and billing date, the system should select the tariff where active_from ≤ billing_date AND (active_until IS NULL OR active_until ≥ billing_date), and if multiple tariffs match, select the most recent one.
**Validates: Requirements 2.3, 2.4**

### Property 8: Weekend tariff rate application
*For any* tariff with weekend_logic defined and any consumption on Saturday or Sunday, the calculated cost should use the weekend rate specified in the configuration.
**Validates: Requirements 2.5**

### Property 9: Water bill component calculation
*For any* water consumption value V (in m³), the calculated water bill should equal (V × 0.97) + (V × 1.23) + 0.85, representing supply, sewage, and fixed fee respectively.
**Validates: Requirements 3.1, 3.2**

### Property 10: Property type tariff differentiation
*For any* two properties with identical consumption but different types (apartment vs. house), if house-specific tariffs exist, the calculated bills should differ according to the property-type-specific rates.
**Validates: Requirements 3.3**

### Property 11: Invoice immutability after finalization
*For any* invoice, once finalized_at is set to a non-null timestamp, any attempt to modify invoice_items or total_amount should be rejected, and the snapshotted tariff rates should remain unchanged even if the tariff table is modified.
**Validates: Requirements 5.1, 5.2, 5.3, 5.4, 5.5**

### Property 12: Summer gyvatukas calculation formula
*For any* building and billing period in non-heating season (May-September), the calculated circulation energy Q_circ should equal Q_total - (V_water × c × ΔT), where Q_total is total building energy, V_water is hot water consumption, c is specific heat capacity, and ΔT is temperature difference.
**Validates: Requirements 4.1, 4.3**

### Property 13: Winter gyvatukas norm application
*For any* building and billing period in heating season (October-April), the calculated gyvatukas cost should equal the building's stored gyvatukas_summer_average multiplied by the current heating rate, without recalculating from consumption data.
**Validates: Requirements 4.2**

### Property 14: Circulation cost distribution
*For any* building with N apartments and total circulation cost C, if distribution is equal, each apartment should be charged C/N; if distribution is by area, apartment i with area A_i should be charged C × (A_i / Σ A_j).
**Validates: Requirements 4.5**

### Property 15: Tenant data isolation
*For any* authenticated user with tenant_id T, all database queries for tenant-scoped models (Property, Meter, Invoice, Tenant) should automatically include WHERE tenant_id = T, and attempts to access records with different tenant_id values should return empty results.
**Validates: Requirements 7.1, 7.2, 7.3, 7.5**

### Property 16: Tenant account initialization
*For any* newly created tenant account, the system should initialize isolated data structures such that queries for that tenant_id return empty result sets until data is explicitly created for that tenant.
**Validates: Requirements 7.4**

### Property 17: Meter reading modification audit
*For any* meter reading modification, an audit record should be created in meter_reading_audit containing the original value, new value, change reason, changed_by_user_id, and created_at timestamp.
**Validates: Requirements 8.1, 8.2**

### Property 18: Draft invoice recalculation on reading correction
*For any* meter reading correction where the reading is used in a draft invoice (status != 'finalized'), the invoice total_amount should be recalculated to reflect the corrected consumption.
**Validates: Requirements 8.3**

### Property 19: Foreign key constraint enforcement
*For any* delete operation on a record with foreign key dependencies, if the foreign key is configured with ON DELETE RESTRICT, the operation should be rejected with a constraint violation error; if configured with ON DELETE CASCADE, dependent records should be automatically deleted.
**Validates: Requirements 9.4, 9.5**

### Property 20: Client-side charge preview calculation
*For any* meter reading input with consumption C and applicable tariff rate R, the client-side preview calculation should produce C × R, matching the server-side calculation result.
**Validates: Requirements 10.3**

### Property 21: Role-based resource access control
*For any* user with role R attempting to access resource X, the system should grant access if and only if a policy or gate exists that returns true for authorize(R, X).
**Validates: Requirements 11.1**

### Property 22: Tenant role data access restriction
*For any* user with role 'tenant' and tenant_id T, all queries for invoices and meters should return only records where the associated tenant has tenant_id = T.
**Validates: Requirements 11.4**

### Property 23: Backup retention policy enforcement
*For any* backup file with creation timestamp older than the configured retention period, the automated cleanup process should delete the file.
**Validates: Requirements 12.4**

### Property 24: Invoice itemization completeness
*For any* generated invoice, the invoice_items collection should include at least one item for each utility type (electricity, water, heating, gyvatukas) where consumption occurred during the billing period, and each item should contain both quantity and unit_price.
**Validates: Requirements 6.2, 6.4**

### Property 25: Consumption history chronological ordering
*For any* tenant's consumption history query, the returned meter readings should be ordered by reading_date in ascending chronological order.
**Validates: Requirements 6.3**

### Property 26: Multi-property filtering
*For any* tenant with multiple properties, when filtering by property_id P, the system should return only invoices and meters associated with property P.
**Validates: Requirements 6.5**

## Error Handling

### Validation Errors

The system uses Laravel Form Requests for input validation with clear error messages:

**Meter Reading Validation:**
- `reading.required`: "Meter reading is required"
- `reading.numeric`: "Reading must be a number"
- `reading.min_value`: "Reading cannot be lower than previous reading ({previous})"
- `reading_date.before_or_equal:today`: "Reading date cannot be in the future"

**Tariff Configuration Validation:**
- `configuration.json`: "Tariff configuration must be valid JSON"
- `configuration.zones.overlap`: "Time zones cannot overlap"
- `configuration.zones.coverage`: "Time zones must cover all 24 hours"

**Invoice Finalization Errors:**
- `invoice.already_finalized`: "Invoice is already finalized and cannot be modified"
- `invoice.missing_readings`: "Cannot finalize invoice: missing meter readings for period"

### Database Errors

**Foreign Key Violations:**
```php
try {
    $tenant->delete();
} catch (QueryException $e) {
    if ($e->getCode() === '23000') {
        throw new CannotDeleteTenantException(
            "Cannot delete tenant with existing invoices"
        );
    }
}
```

**Concurrent Modification:**
SQLite in WAL mode handles concurrent reads/writes automatically, but for critical operations (invoice finalization), use optimistic locking:

```php
$invoice = Invoice::where('id', $id)
    ->where('status', 'draft')
    ->lockForUpdate()
    ->firstOrFail();
    
$invoice->finalize();
```

### Business Logic Errors

**Gyvatukas Calculation Errors:**
- Missing summer average during heating season: Log warning and use default norm
- Negative circulation energy: Indicates data error, reject calculation and alert admin

**Tariff Resolution Errors:**
- No active tariff for date: Throw `NoActiveTariffException` with provider and date details
- Multiple overlapping tariffs: Log error and select most recent, alert admin

### Multi-Tenancy Errors

**Tenant Context Missing:**
If session lacks tenant_id, redirect to login with error message. All tenant-scoped routes protected by `EnsureTenantContext` middleware.

**Cross-Tenant Access Attempt:**
Log security event with user ID, attempted resource, and timestamp. Return 404 (not 403) to avoid information disclosure.

## Testing Strategy

The system employs a dual testing approach combining unit tests for specific scenarios and property-based tests for universal correctness guarantees.

### Property-Based Testing

**Framework:** We will use **Pest PHP** with the **pest-plugin-faker** for property-based testing in PHP.

**Configuration:** Each property-based test will run a minimum of 100 iterations to ensure statistical confidence in correctness across diverse inputs.

**Tagging Convention:** Each property-based test must include a comment explicitly referencing the design document property:
```php
// Feature: vilnius-utilities-billing, Property 1: Meter reading monotonicity
test('meter readings must be monotonically increasing', function () {
    // Generate random meter with previous reading
    $meter = Meter::factory()->create();
    $previousReading = MeterReading::factory()->for($meter)->create(['value' => 1000]);
    
    // Property: any reading lower than previous should be rejected
    $invalidReading = fake()->numberBetween(0, 999);
    
    expect(fn() => MeterReading::create([
        'meter_id' => $meter->id,
        'value' => $invalidReading,
        'reading_date' => now()
    ]))->toThrow(ValidationException::class);
})->repeat(100);
```

**Property Test Coverage:**
- Property 1-4: Meter reading validation and storage
- Property 5-8: Tariff configuration and selection
- Property 9-10: Water billing calculations
- Property 11: Invoice immutability
- Property 12-14: Gyvatukas calculations
- Property 15-16: Multi-tenancy isolation
- Property 17-18: Audit trail
- Property 19: Foreign key constraints
- Property 20: Calculation consistency
- Property 21-22: Authorization
- Property 23: Backup retention
- Property 24-26: Invoice and consumption display

### Unit Testing

Unit tests complement property tests by verifying specific examples, edge cases, and integration points:

**Meter Reading Tests:**
- Test storing valid reading with all fields
- Test rejection of future-dated reading
- Test multi-zone reading storage for electricity meters
- Edge case: Zero consumption reading

**Tariff Resolution Tests:**
- Test selecting correct tariff for specific date
- Test handling tariff transition at midnight
- Edge case: Tariff active_until on exact billing date

**Gyvatukas Calculation Tests:**
- Test summer calculation with known values
- Test winter calculation using stored average
- Edge case: First winter season (no summer average yet)
- Edge case: Building with single apartment

**Invoice Generation Tests:**
- Test complete invoice generation for all utility types
- Test invoice finalization prevents modifications
- Test snapshotted prices remain after tariff change
- Edge case: Invoice with zero consumption (fixed fees only)

**Multi-Tenancy Tests:**
- Test global scope filters queries correctly
- Test cross-tenant access returns empty results
- Test tenant creation initializes isolated data

**Authorization Tests:**
- Test admin can access all resources
- Test manager can create invoices but not modify tariffs
- Test tenant can only view own data

### Integration Testing

Integration tests verify end-to-end workflows:

**Monthly Billing Workflow:**
1. Create tenants with properties and meters
2. Enter meter readings for billing period
3. Generate invoices
4. Verify invoice totals match expected calculations
5. Finalize invoices
6. Verify invoices are immutable

**Audit Trail Workflow:**
1. Create meter reading
2. Modify reading with reason
3. Verify audit record created
4. Display reading history
5. Verify all modifications shown

**Multi-Tenancy Workflow:**
1. Create two tenant accounts
2. Create data for each tenant
3. Authenticate as tenant 1
4. Verify cannot access tenant 2 data
5. Switch to tenant 2
6. Verify cannot access tenant 1 data

### Test Data Generation

Use Laravel factories with realistic Lithuanian data:

```php
// MeterFactory.php
public function definition(): array
{
    return [
        'serial_number' => fake()->unique()->numerify('LT-####-####'),
        'type' => fake()->randomElement(['electricity', 'water_cold', 'water_hot', 'heating']),
        'installation_date' => fake()->dateTimeBetween('-5 years', 'now'),
        'supports_zones' => fn(array $attributes) => 
            $attributes['type'] === 'electricity' ? fake()->boolean(30) : false,
    ];
}

// TariffFactory.php
public function ignitis(): static
{
    return $this->state(fn (array $attributes) => [
        'provider_id' => Provider::where('name', 'Ignitis')->first()->id,
        'configuration' => [
            'type' => 'time_of_use',
            'currency' => 'EUR',
            'zones' => [
                ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => 0.18],
                ['id' => 'night', 'start' => '23:00', 'end' => '07:00', 'rate' => 0.10],
            ],
            'weekend_logic' => 'apply_night_rate',
        ],
    ]);
}
```

### Continuous Testing

**Pre-commit Hooks:**
- Run unit tests on changed files
- Run property tests for affected properties
- Check code style (Laravel Pint)

**CI Pipeline:**
- Run full test suite on pull requests
- Generate code coverage report (minimum 80% coverage)
- Run static analysis (PHPStan level 8)
- Check for N+1 queries in integration tests

**Scheduled Tests:**
- Run extended property tests (1000 iterations) nightly
- Run performance benchmarks weekly
- Test backup/restore procedures monthly

