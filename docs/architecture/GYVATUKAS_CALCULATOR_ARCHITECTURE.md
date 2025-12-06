# GyvatukasCalculator Architecture (ARCHIVED)

> **Status**: ARCHIVED as of 2025-12-05  
> **Purpose**: Architectural documentation for the complex seasonal calculation system

## System Overview

The GyvatukasCalculator implements a sophisticated thermodynamic calculation engine for Lithuanian hot water circulation fees (gyvatukas). The system automatically adjusts calculations based on seasonal patterns and distributes costs among properties using configurable methods.

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                        BillingService                            │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  calculateBuildingInvoice(Building, Carbon)                │ │
│  └────────────────────────────────────────────────────────────┘ │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│                    GyvatukasCalculator                           │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  calculate(Building, Carbon): float                        │ │
│  │    ├─ isHeatingSeason(Carbon): bool                        │ │
│  │    ├─ calculateSummerGyvatukas(Building, Carbon): float    │ │
│  │    └─ calculateWinterGyvatukas(Building): float            │ │
│  │                                                              │ │
│  │  distributeCirculationCost(Building, float, string): array │ │
│  │    ├─ Equal Distribution (C/N)                             │ │
│  │    └─ Area-Based Distribution (C × A_i / Σ A_j)           │ │
│  └────────────────────────────────────────────────────────────┘ │
└───────────────────────────┬─────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Data Layer                                  │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  Building                                                   │ │
│  │    ├─ gyvatukas_summer_average: decimal                    │ │
│  │    └─ properties: HasMany<Property>                        │ │
│  │                                                              │ │
│  │  Property                                                   │ │
│  │    ├─ area_sqm: decimal                                    │ │
│  │    └─ meters: HasMany<Meter>                               │ │
│  │                                                              │ │
│  │  Meter                                                      │ │
│  │    ├─ type: MeterType (HEATING, WATER_HOT)                │ │
│  │    └─ readings: HasMany<MeterReading>                      │ │
│  │                                                              │ │
│  │  MeterReading                                               │ │
│  │    ├─ reading_date: date                                   │ │
│  │    └─ value: decimal                                       │ │
│  └────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

## Component Relationships

### Service Dependencies

```
GyvatukasCalculator
├── Depends On
│   ├── App\Models\Building
│   ├── App\Models\Property
│   ├── App\Models\Meter
│   ├── App\Models\MeterReading
│   ├── App\Enums\MeterType
│   ├── Carbon\Carbon
│   └── config/gyvatukas.php
│
└── Used By
    ├── App\Services\BillingService
    ├── App\Console\Commands\CalculateSummerAveragesCommand
    └── App\Http\Controllers\InvoiceController
```

## Data Flow

### Summer Calculation Flow

```
┌─────────────┐
│   Request   │
│  (Building, │
│    Month)   │
└──────┬──────┘
       │
       ▼
┌─────────────────────────────────────────┐
│  1. Check if Heating Season             │
│     month >= 10 || month <= 4           │
└──────┬──────────────────────────────────┘
       │ No (Summer: May-Sep)
       ▼
┌─────────────────────────────────────────┐
│  2. Check Calculation Cache             │
│     Key: {building_id}_{Y-m}            │
└──────┬──────────────────────────────────┘
       │ Cache Miss
       ▼
┌─────────────────────────────────────────┐
│  3. Get Building Heating Energy         │
│     - Eager load properties.meters      │
│     - Filter: MeterType::HEATING        │
│     - Calculate: last - first reading   │
│     Result: Q_total (kWh)               │
└──────┬──────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────┐
│  4. Get Building Hot Water Volume       │
│     - Eager load properties.meters      │
│     - Filter: MeterType::WATER_HOT      │
│     - Calculate: last - first reading   │
│     Result: V_water (m³)                │
└──────┬──────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────┐
│  5. Apply Thermodynamic Formula         │
│     Q_circ = Q_total - (V_water × c × ΔT)│
│     where:                               │
│       c = 1.163 kWh/m³·°C               │
│       ΔT = 45.0°C                       │
└──────┬──────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────┐
│  6. Validate Result                     │
│     if Q_circ < 0:                      │
│       Log warning                        │
│       Return 0.0                         │
│     else:                                │
│       Round to 2 decimals                │
└──────┬──────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────┐
│  7. Cache Result                        │
│     Store in calculationCache           │
└──────┬──────────────────────────────────┘
       │
       ▼
┌─────────────┐
│   Return    │
│  Q_circ     │
│   (kWh)     │
└─────────────┘
```

### Winter Calculation Flow

```
┌─────────────┐
│   Request   │
│  (Building, │
│    Month)   │
└──────┬──────┘
       │
       ▼
┌─────────────────────────────────────────┐
│  1. Check if Heating Season             │
│     month >= 10 || month <= 4           │
└──────┬──────────────────────────────────┘
       │ Yes (Winter: Oct-Apr)
       ▼
┌─────────────────────────────────────────┐
│  2. Retrieve Summer Average             │
│     $building->gyvatukas_summer_average │
└──────┬──────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────┐
│  3. Validate Summer Average             │
│     if null or <= 0:                    │
│       Log warning                        │
│       Return 0.0                         │
│     else:                                │
│       Return summer_average              │
└──────┬──────────────────────────────────┘
       │
       ▼
┌─────────────┐
│   Return    │
│  Summer Avg │
│   (kWh)     │
└─────────────┘
```

### Distribution Flow

```
┌─────────────────┐
│  Total Cost (€) │
│    Building     │
│     Method      │
└────────┬────────┘
         │
         ▼
┌─────────────────────────────────────────┐
│  1. Load Building Properties            │
│     $building->properties               │
└──────┬──────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────┐
│  2. Check Distribution Method           │
└──────┬──────────────────────────────────┘
         │
         ├─────────────────┬───────────────┐
         │                 │               │
         ▼                 ▼               ▼
    ┌─────────┐      ┌─────────┐    ┌──────────┐
    │  Equal  │      │  Area   │    │ Invalid  │
    └────┬────┘      └────┬────┘    └────┬─────┘
         │                │              │
         │                │              └──────┐
         ▼                ▼                     │
    ┌─────────────┐  ┌──────────────┐         │
    │  C / N      │  │ C × (A_i/ΣA) │         │
    └──────┬──────┘  └──────┬───────┘         │
           │                │                  │
           │                ▼                  │
           │         ┌──────────────┐          │
           │         │ Validate     │          │
           │         │ Total Area   │          │
           │         └──────┬───────┘          │
           │                │                  │
           │                │ if <= 0          │
           │                └──────────────────┤
           │                                   │
           └───────────────┬───────────────────┘
                           │
                           ▼
                  ┌─────────────────┐
                  │  Round to 2     │
                  │  Decimals       │
                  └────────┬────────┘
                           │
                           ▼
                  ┌─────────────────┐
                  │  Return Array   │
                  │  [prop_id => €] │
                  └─────────────────┘
```

## Caching Strategy

### Two-Level Cache Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    GyvatukasCalculator                       │
│                                                               │
│  ┌─────────────────────────────────────────────────────┐   │
│  │              Calculation Cache                       │   │
│  │  Key: {building_id}_{Y-m}                           │   │
│  │  Value: Final circulation energy (kWh)              │   │
│  │  Purpose: Avoid recalculating same building+month   │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                               │
│  ┌─────────────────────────────────────────────────────┐   │
│  │              Consumption Cache                       │   │
│  │  Key: heating_{building_id}_{start}_{end}          │   │
│  │       water_{building_id}_{start}_{end}            │   │
│  │  Value: Intermediate meter consumption values       │   │
│  │  Purpose: Reuse meter reading calculations          │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

### Cache Lifecycle

```
┌──────────────┐
│  Calculate   │
│   Request    │
└──────┬───────┘
       │
       ▼
┌──────────────────────┐
│ Check Calculation    │
│ Cache                │
└──────┬───────────────┘
       │
       ├─ Hit ──────────────────────┐
       │                             │
       └─ Miss                       │
          │                          │
          ▼                          │
   ┌──────────────────┐             │
   │ Check Consumption│             │
   │ Cache            │             │
   └──────┬───────────┘             │
          │                          │
          ├─ Hit ─────────┐         │
          │                │         │
          └─ Miss          │         │
             │             │         │
             ▼             │         │
      ┌──────────────┐    │         │
      │ Query DB     │    │         │
      └──────┬───────┘    │         │
             │             │         │
             ▼             │         │
      ┌──────────────┐    │         │
      │ Store in     │    │         │
      │ Consumption  │    │         │
      │ Cache        │    │         │
      └──────┬───────┘    │         │
             │             │         │
             └─────────────┤         │
                           │         │
                           ▼         │
                    ┌──────────────┐ │
                    │ Calculate    │ │
                    │ Result       │ │
                    └──────┬───────┘ │
                           │         │
                           ▼         │
                    ┌──────────────┐ │
                    │ Store in     │ │
                    │ Calculation  │ │
                    │ Cache        │ │
                    └──────┬───────┘ │
                           │         │
                           └─────────┤
                                     │
                                     ▼
                              ┌──────────────┐
                              │ Return Result│
                              └──────────────┘
```

## Query Optimization

### N+1 Prevention Strategy

**Problem**: Without optimization
```
1 query: SELECT * FROM buildings WHERE id = ?
N queries: SELECT * FROM properties WHERE building_id = ?
M queries: SELECT * FROM meters WHERE property_id = ?
P queries: SELECT * FROM meter_readings WHERE meter_id = ?

Total: 1 + N + M + P queries (O(n²))
```

**Solution**: Eager loading
```php
$building->load([
    'properties.meters' => function ($query) {
        $query->where('type', MeterType::HEATING)
              ->select('id', 'property_id', 'type');
    },
    'properties.meters.readings' => function ($query) use ($periodStart, $periodEnd) {
        $query->whereBetween('reading_date', [$periodStart, $periodEnd])
              ->orderBy('reading_date')
              ->select('id', 'meter_id', 'reading_date', 'value');
    }
]);

Total: 2 queries (O(1))
```

### Query Execution Plan

```
Query 1: Load Properties with Meters
┌─────────────────────────────────────────────────────────┐
│ SELECT properties.*, meters.*                           │
│ FROM properties                                          │
│ LEFT JOIN meters ON meters.property_id = properties.id  │
│ WHERE properties.building_id = ?                        │
│   AND meters.type = 'HEATING'                           │
└─────────────────────────────────────────────────────────┘

Query 2: Load Meter Readings
┌─────────────────────────────────────────────────────────┐
│ SELECT *                                                 │
│ FROM meter_readings                                      │
│ WHERE meter_id IN (?, ?, ?, ...)                       │
│   AND reading_date BETWEEN ? AND ?                      │
│ ORDER BY reading_date                                    │
└─────────────────────────────────────────────────────────┘
```

## Configuration Architecture

### Configuration Hierarchy

```
┌─────────────────────────────────────────────────────────┐
│                  config/gyvatukas.php                    │
│                                                           │
│  ┌─────────────────────────────────────────────────┐   │
│  │  Physical Constants                              │   │
│  │  - water_specific_heat: 1.163 kWh/m³·°C        │   │
│  │  - temperature_delta: 45.0°C                    │   │
│  └─────────────────────────────────────────────────┘   │
│                                                           │
│  ┌─────────────────────────────────────────────────┐   │
│  │  Seasonal Thresholds                             │   │
│  │  - heating_season_start_month: 10 (October)     │   │
│  │  - heating_season_end_month: 4 (April)          │   │
│  └─────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────┐
│              GyvatukasCalculator Constructor             │
│                                                           │
│  $this->waterSpecificHeat = config('gyvatukas.water_...')│
│  $this->temperatureDelta = config('gyvatukas.temp...')  │
│  $this->heatingSeasonStartMonth = config('gyvatukas...') │
│  $this->heatingSeasonEndMonth = config('gyvatukas...')  │
└─────────────────────────────────────────────────────────┘
```

## Error Handling Architecture

### Error Flow

```
┌─────────────────────────────────────────────────────────┐
│                   Calculation Error                      │
└───────────────────────┬─────────────────────────────────┘
                        │
        ┌───────────────┼───────────────┐
        │               │               │
        ▼               ▼               ▼
┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│  Negative    │ │   Missing    │ │   Invalid    │
│ Circulation  │ │   Summer     │ │ Distribution │
│   Energy     │ │   Average    │ │    Method    │
└──────┬───────┘ └──────┬───────┘ └──────┬───────┘
       │                │                │
       ▼                ▼                ▼
┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│ Log::warning │ │ Log::warning │ │  Log::error  │
└──────┬───────┘ └──────┬───────┘ └──────┬───────┘
       │                │                │
       ▼                ▼                ▼
┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│  Return 0.0  │ │  Return 0.0  │ │  Fallback to │
│              │ │              │ │    Equal     │
└──────────────┘ └──────────────┘ └──────────────┘
```

### Logging Strategy

```
┌─────────────────────────────────────────────────────────┐
│                    Log Levels                            │
│                                                           │
│  WARNING: Data quality issues, recoverable errors        │
│  ├─ Negative circulation energy                         │
│  ├─ Missing summer average                              │
│  ├─ No properties found                                 │
│  └─ Zero/negative total area                            │
│                                                           │
│  ERROR: Configuration or logic errors                    │
│  └─ Invalid distribution method                         │
└─────────────────────────────────────────────────────────┘
```

## Integration Points

### Service Integration

```
┌─────────────────────────────────────────────────────────┐
│                    BillingService                        │
│  ┌─────────────────────────────────────────────────┐   │
│  │  public function __construct(                    │   │
│  │      private GyvatukasCalculator $calculator    │   │
│  │  ) {}                                            │   │
│  └─────────────────────────────────────────────────┘   │
└───────────────────────┬─────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────┐
│              GyvatukasCalculator                         │
│  ┌─────────────────────────────────────────────────┐   │
│  │  calculate(Building, Carbon): float             │   │
│  │  distributeCirculationCost(...): array          │   │
│  └─────────────────────────────────────────────────┘   │
└───────────────────────┬─────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────┐
│                   Eloquent Models                        │
│  ┌─────────────────────────────────────────────────┐   │
│  │  Building → Property → Meter → MeterReading     │   │
│  └─────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────┘
```

## Performance Characteristics

### Time Complexity

| Operation | Without Optimization | With Optimization |
|-----------|---------------------|-------------------|
| Single Building | O(n²) | O(1) |
| Batch Processing | O(n³) | O(n) |
| Cache Hit | N/A | O(1) |

### Space Complexity

| Component | Memory Usage |
|-----------|-------------|
| Calculation Cache | ~100 bytes per building-month |
| Consumption Cache | ~200 bytes per period |
| Eager Loaded Data | ~1KB per building |

### Benchmark Results

```
Operation: Calculate Summer Gyvatukas
├─ Without Optimization: 50-100ms
├─ With Eager Loading: 10-15ms
└─ With Cache Hit: <1ms

Operation: Distribute Cost (10 properties)
├─ Equal Method: <1ms
└─ Area Method: <1ms

Operation: Batch Processing (100 buildings)
├─ Without Optimization: 5-10 seconds
├─ With Optimization: 1-2 seconds
└─ With Cache: 0.5-1 second
```

## Security Considerations

### Input Validation

```
┌─────────────────────────────────────────────────────────┐
│                  Input Validation                        │
│                                                           │
│  Building Model                                          │
│  ├─ Validated by Eloquent                               │
│  └─ Tenant-scoped by BelongsToTenant                    │
│                                                           │
│  Carbon Date                                             │
│  ├─ Type-hinted as Carbon                               │
│  └─ Validated by Carbon parsing                         │
│                                                           │
│  Distribution Method                                     │
│  ├─ String validation                                    │
│  └─ Fallback to 'equal' if invalid                      │
└─────────────────────────────────────────────────────────┘
```

### Authorization

```
┌─────────────────────────────────────────────────────────┐
│              Authorization Flow                          │
│                                                           │
│  Request → Policy → GyvatukasCalculator                 │
│                                                           │
│  Policies:                                               │
│  ├─ BuildingPolicy::view()                              │
│  ├─ BuildingPolicy::update()                            │
│  └─ InvoicePolicy::create()                             │
└─────────────────────────────────────────────────────────┘
```

## Scalability Considerations

### Horizontal Scaling

```
┌─────────────────────────────────────────────────────────┐
│                  Load Balancer                           │
└───────────────────────┬─────────────────────────────────┘
                        │
        ┌───────────────┼───────────────┐
        │               │               │
        ▼               ▼               ▼
┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│  App Server  │ │  App Server  │ │  App Server  │
│      1       │ │      2       │ │      3       │
└──────┬───────┘ └──────┬───────┘ └──────┬───────┘
       │                │                │
       └────────────────┼────────────────┘
                        │
                        ▼
                ┌──────────────┐
                │   Database   │
                └──────────────┘

Note: In-memory cache is per-instance
Consider Redis for shared caching
```

### Vertical Scaling

```
Memory Requirements:
├─ Base: 10MB
├─ Per 1000 buildings: +50MB (with cache)
└─ Recommended: 512MB minimum

CPU Requirements:
├─ Single calculation: <10ms
├─ Batch processing: CPU-bound
└─ Recommended: 2+ cores for batch operations
```

## Related Documentation

- [GyvatukasCalculator Service Documentation](../services/GYVATUKAS_CALCULATOR_ARCHIVED.md)
- [GyvatukasCalculator API Reference](../api/GYVATUKAS_CALCULATOR_API.md)
- [BillingService Architecture](BILLING_SERVICE_ARCHITECTURE.md)
- [Multi-Tenancy Architecture](MULTI_TENANT_ARCHITECTURE.md)
- [Performance Optimization Guide](../performance/GYVATUKAS_CALCULATOR_OPTIMIZATION.md)

---

**Document Version**: 1.0  
**Last Updated**: 2025-12-05  
**Status**: ARCHIVED  
**Maintained By**: Development Team
