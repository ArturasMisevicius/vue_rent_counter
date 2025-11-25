# Database Schema Analysis Summary

## Quick Reference

**Full Analysis**: See [COMPREHENSIVE_SCHEMA_ANALYSIS.md](./COMPREHENSIVE_SCHEMA_ANALYSIS.md)

---

## Key Metrics

- **Tables**: 20+ core tables
- **Indexes**: 50+ (composite and single-column)
- **Foreign Keys**: 30+ relationships
- **Enum Fields**: 100% backed by PHP enums
- **JSON Columns**: 10+ for flexible configuration

---

## Core Entities

```
organizations → users → buildings → properties → meters → meter_readings
                  ↓                      ↓
            subscriptions            tenants → invoices → invoice_items
                                        ↓
                                  property_tenant (pivot)
```

---

## Performance Highlights

### BillingService v3.0

| Metric | Before | After | Improvement |
|--------|--------|-------|-------------|
| Queries | 50-100 | 10-15 | **85% reduction** |
| Time | ~500ms | ~100ms | **80% faster** |
| Memory | ~10MB | ~4MB | **60% less** |

### Optimization Techniques

1. ✅ Eager loading with ±7 day buffer
2. ✅ Provider/tariff caching (in-memory)
3. ✅ Collection-based reading lookups
4. ✅ Composite database indexes
5. ✅ Selective column loading

---

## Critical Indexes

### Multi-Tenancy
- `users.tenant_id` (INDEX)
- `buildings.tenant_id` (INDEX)
- `properties.tenant_id` (INDEX)
- `meters.tenant_id` (INDEX)
- `meter_readings.tenant_id` (INDEX)
- `invoices.tenant_id` (INDEX)

### Performance Critical
- `meter_readings_meter_date_zone_index` (meter_id, reading_date, zone)
- `idx_invoices_tenant_period` (tenant_id, billing_period_start, status)
- `meters_property_type_index` (property_id, type)
- `providers_service_type_index` (service_type)
- `tariffs_provider_active_index` (provider_id, active_from, active_until)

---

## Common Query Patterns

### 1. Invoice Dashboard
```php
Invoice::with(['tenant', 'items'])
    ->where('tenant_id', $tenantId)
    ->whereBetween('billing_period_start', [$start, $end])
    ->orderBy('billing_period_start', 'desc')
    ->get();
```
**Performance**: 3 queries, 50ms, 2MB

### 2. Meter Reading History
```php
MeterReading::where('meter_id', $meterId)
    ->orderBy('reading_date', 'desc')
    ->get();
```
**Performance**: 1 query, 20ms, 500KB

### 3. Property Listing
```php
Property::with(['building', 'tenants'])
    ->withCount('meters')
    ->where('tenant_id', $tenantId)
    ->get();
```
**Performance**: 2 queries, 30ms, 1MB

---

## Data Types

### Decimal Precision
- Meter readings: `DECIMAL(10,2)` - Max 99,999,999.99
- Invoice amounts: `DECIMAL(10,2)` - Max €99,999,999.99
- Unit prices: `DECIMAL(10,4)` - Precise rates (€0.0001)
- Area: `DECIMAL(8,2)` - Max 999,999.99 m²

### Enums (Type-Safe)
- `users.role`: superadmin, admin, manager, tenant
- `properties.type`: apartment, house
- `meters.type`: electricity, water_cold, water_hot, heating
- `invoices.status`: draft, finalized, paid
- `subscriptions.status`: active, expired, suspended, cancelled

### JSON Columns
- `tariffs.configuration` - Tariff rules (flat/time-of-use)
- `invoice_items.meter_reading_snapshot` - Historical data
- `providers.contact_info` - Flexible contact data
- `organizations.settings` - Org-specific config

---

## Relationships

### One-to-Many
- Building → Properties
- Property → Meters
- Meter → MeterReadings
- Tenant → Invoices
- Invoice → InvoiceItems
- Provider → Tariffs

### Many-to-Many
- Property ↔ Tenant (via `property_tenant` pivot)
  - Tracks historical assignments
  - Includes `assigned_at`, `vacated_at` timestamps

### Hierarchical
- User → User (parent_user_id)
  - Admin creates managers/tenants
  - Supports multi-level hierarchy

---

## Global Scopes

**TenantScope** (automatic multi-tenancy):
- Applied to: Building, Property, Tenant, Meter, MeterReading, Invoice
- Adds: `WHERE tenant_id = session('tenant_id')`
- Bypass: `Model::withoutGlobalScope(TenantScope::class)`

---

## Observers

| Model | Events | Purpose |
|-------|--------|---------|
| MeterReading | updating, updated | Audit trail + invoice recalculation |
| Invoice | creating, updating | Auto-set tenant_id + immutability protection |
| Tenant | creating | Auto-generate unique slug |
| Faq | saved, deleted | Cache invalidation |

---

## Foreign Key Cascade Rules

### CASCADE (Delete children)
- meters → meter_readings
- invoices → invoice_items
- providers → tariffs

### SET NULL (Preserve records)
- buildings → properties
- properties → tenants
- users → meter_readings (entered_by)

### RESTRICT (Prevent deletion)
- tenants → invoices (cannot delete tenant with invoices)

---

## SQLite WAL Mode

**Configuration**:
```php
PRAGMA journal_mode=WAL;
PRAGMA synchronous=NORMAL;
PRAGMA foreign_keys=ON;
PRAGMA temp_store=MEMORY;
```

**Benefits**:
- 5-10x faster writes
- Concurrent reads during writes
- Better multi-user performance

---

## Testing Strategy

### Deterministic Seeders
```bash
php artisan test:setup --fresh
```

**Creates**:
- 3 providers (Ignitis, VV, VE)
- 5 buildings with gyvatukas data
- 20 properties (apartments + houses)
- 15 tenants with lease dates
- 60 meters (3 per property avg)
- 300+ readings (6 months history)
- 50 invoices with items

### Factory-Based Tests
```php
$property = Property::factory()
    ->for(Building::factory())
    ->has(Meter::factory()->electricity()->count(1))
    ->create();
```

### Performance Tests
```php
test('invoice generation stays under query budget', function () {
    DB::enableQueryLog();
    $invoice = $service->generateInvoice($tenant, $start, $end);
    expect(count(DB::getQueryLog()))->toBeLessThanOrEqual(15);
});
```

---

## Production Recommendations

### Monitoring
- Log queries > 100ms
- Monitor index usage monthly
- Review slow query log daily

### Maintenance
- **Daily**: Check backups, review error logs
- **Weekly**: Analyze table statistics, check disk space
- **Monthly**: Optimize tables, archive old audits

### Backup
- Spatie Backup 10.x configured
- Nightly backups to S3
- Includes SQLite + WAL files
- 30-day retention

---

## Security

### SQL Injection Prevention
✅ Always use parameter binding
✅ Never concatenate user input

### Multi-Tenancy Isolation
✅ Global TenantScope on all models
✅ Policy-based authorization
✅ Session-based tenant_id

### Sensitive Data
✅ PII redaction in logs
✅ Password hashing (bcrypt)
✅ No sensitive data in JSON snapshots

---

## Migration Best Practices

### Idempotent Migrations
```php
if (!$this->indexExists('table', 'index_name')) {
    $table->index(['col1', 'col2'], 'index_name');
}
```

### Rollback Safety
Always provide `down()` method for reversibility

### Data Migration
Separate data migrations from schema changes

---

## Quick Commands

```bash
# Run migrations
php artisan migrate

# Fresh database with test data
php artisan migrate:fresh --seed
php artisan test:setup --fresh

# Run tests
php artisan test --filter=BillingServiceTest

# Backup database
php artisan backup:run

# Monitor slow queries
php artisan pail
```

---

## Related Documentation

- [COMPREHENSIVE_SCHEMA_ANALYSIS.md](./COMPREHENSIVE_SCHEMA_ANALYSIS.md) - Full analysis
- [DATABASE_QUERY_OPTIMIZATION_GUIDE.md](../performance/DATABASE_QUERY_OPTIMIZATION_GUIDE.md) - Query optimization
- [SLOW_QUERY_EXAMPLE.md](../performance/SLOW_QUERY_EXAMPLE.md) - Real-world examples
- [BILLING_SERVICE_PERFORMANCE_SUMMARY.md](../performance/BILLING_SERVICE_PERFORMANCE_SUMMARY.md) - BillingService optimization
