# Database Optimization Checklist

## ✅ Completed Optimizations

### Migration Fix (2025-11-25)
- ✅ Fixed `indexExists()` method in `add_billing_service_performance_indexes` migration
- ✅ Removed deprecated `introspectTable()` call
- ✅ Updated to use `listTableIndexes()` for Laravel 12 compatibility

### BillingService v3.0 Performance
- ✅ 85% query reduction (50-100 → 10-15 queries)
- ✅ 80% faster execution (~500ms → ~100ms)
- ✅ 60% less memory (~10MB → ~4MB)
- ✅ Provider caching (95% reduction)
- ✅ Tariff caching (90% reduction)
- ✅ Collection-based reading lookups (zero additional queries)
- ✅ Pre-cached config values in constructor
- ✅ Composite database indexes

### Index Strategy
- ✅ Multi-tenancy indexes on all tenant-scoped tables
- ✅ Composite indexes for common query patterns
- ✅ Covering indexes for consumption calculations
- ✅ Performance indexes for billing service

### Data Integrity
- ✅ Foreign key constraints with appropriate cascade rules
- ✅ Enum-backed status fields (type-safe)
- ✅ Precise decimal types for financial calculations
- ✅ JSON columns for flexible configuration

### Audit Trails
- ✅ Meter reading audits with change tracking
- ✅ Gyvatukas calculation audits
- ✅ Invoice generation audits with performance metrics
- ✅ Organization activity logs

---

## 🔄 Ongoing Monitoring

### Daily Tasks
- [ ] Check slow query log (queries > 100ms)
- [ ] Verify backup completion
- [ ] Review error logs for database issues
- [ ] Monitor disk space usage

### Weekly Tasks
- [ ] Analyze table statistics
- [ ] Review index usage
- [ ] Check for N+1 query patterns in new code
- [ ] Monitor query performance trends

### Monthly Tasks
- [ ] Optimize tables (VACUUM ANALYZE for PostgreSQL, OPTIMIZE TABLE for MySQL)
- [ ] Review and archive old audit records
- [ ] Update database statistics
- [ ] Review slow query patterns and add indexes if needed

---

## 🎯 Recommended Improvements

### High Priority

#### 1. Add Missing Indexes for Common Queries

**Invoice Overdue Detection**:
```php
// Current query
Invoice::where('due_date', '<', now())
    ->where('status', '!=', 'paid')
    ->get();

// Recommended index
Schema::table('invoices', function (Blueprint $table) {
    $table->index(['due_date', 'status'], 'idx_invoices_overdue');
});
```

#### 2. Implement Query Result Caching

**Cache Frequently Accessed Data**:
```php
// Provider lookup (changes rarely)
$provider = Cache::remember(
    "provider.{$serviceType}",
    now()->addHours(24),
    fn() => Provider::where('service_type', $serviceType)->first()
);

// Building gyvatukas average (seasonal)
$average = Cache::remember(
    "building.{$buildingId}.gyvatukas_average",
    now()->addMonths(6),
    fn() => $building->gyvatukas_summer_average
);
```

#### 3. Add Database Connection Pooling

**For Production (MySQL/PostgreSQL)**:
```php
// config/database.php
'mysql' => [
    'options' => [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_TIMEOUT => 5,
    ],
    'pool' => [
        'min_connections' => 2,
        'max_connections' => 10,
    ],
],
```

### Medium Priority

#### 4. Implement Read/Write Splitting

**For High-Traffic Scenarios**:
```php
// config/database.php
'mysql' => [
    'read' => [
        'host' => ['192.168.1.1', '192.168.1.2'], // Read replicas
    ],
    'write' => [
        'host' => ['192.168.1.3'], // Primary
    ],
],
```

#### 5. Add Materialized Views (PostgreSQL)

**For Complex Aggregates**:
```sql
-- Dashboard statistics
CREATE MATERIALIZED VIEW mv_tenant_invoice_summary AS
SELECT 
    tenant_id,
    DATE_TRUNC('month', billing_period_start) as month,
    COUNT(*) as invoice_count,
    SUM(total_amount) as total_revenue,
    AVG(total_amount) as avg_invoice
FROM invoices
WHERE status = 'finalized'
GROUP BY tenant_id, DATE_TRUNC('month', billing_period_start);

-- Create index on materialized view
CREATE INDEX idx_mv_tenant_summary ON mv_tenant_invoice_summary (tenant_id, month DESC);

-- Refresh periodically (hourly via cron)
REFRESH MATERIALIZED VIEW CONCURRENTLY mv_tenant_invoice_summary;
```

#### 6. Implement Cursor Pagination

**For Large Result Sets**:
```php
// Replace OFFSET pagination
// ❌ BAD: Slow for large offsets
$invoices = Invoice::paginate(50, ['*'], 'page', 1000);

// ✅ GOOD: Cursor pagination
$invoices = Invoice::orderBy('id', 'desc')
    ->cursorPaginate(50);
```

### Low Priority

#### 7. Add Partial Indexes (PostgreSQL)

**Index Only Relevant Rows**:
```sql
-- Index only draft invoices (most queried status)
CREATE INDEX idx_invoices_draft ON invoices (tenant_id, billing_period_start) 
WHERE status = 'draft';

-- Index only recent readings (last 2 years)
CREATE INDEX idx_meter_readings_recent ON meter_readings (meter_id, reading_date)
WHERE reading_date >= CURRENT_DATE - INTERVAL '2 years';
```

#### 8. Implement Database Sharding

**For Very Large Datasets** (100k+ properties):
- Shard by tenant_id
- Use Laravel's database connection switching
- Implement tenant-to-shard mapping table

---

## 📊 Performance Benchmarks

### Current Performance (v3.0)

| Operation | Queries | Time | Memory | Target |
|-----------|---------|------|--------|--------|
| Invoice generation | 10-15 | ~100ms | ~4MB | ✅ Met |
| Invoice dashboard (100) | 3 | ~50ms | ~2MB | ✅ Met |
| Meter reading history (50) | 1 | ~20ms | ~500KB | ✅ Met |
| Property listing (20) | 2 | ~30ms | ~1MB | ✅ Met |
| Tariff resolution | 1 | ~5ms | ~100KB | ✅ Met |

### Performance Targets

| Operation | Current | Target | Status |
|-----------|---------|--------|--------|
| Invoice generation | 100ms | <200ms | ✅ Excellent |
| Dashboard load | 50ms | <100ms | ✅ Excellent |
| Meter reading list | 20ms | <50ms | ✅ Excellent |
| Property list | 30ms | <100ms | ✅ Excellent |
| Search queries | Varies | <200ms | ⚠️ Monitor |

---

## 🔍 Query Analysis Tools

### Enable Query Logging

```php
// In controller or service
DB::enableQueryLog();

// Your code here
$invoices = Invoice::with('items')->get();

// Get executed queries
$queries = DB::getQueryLog();
dd($queries);
```

### Monitor Slow Queries

```php
// app/Http/Middleware/MonitorSlowQueries.php
DB::listen(function ($query) {
    if ($query->time > 100) {
        Log::warning('Slow query detected', [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time . 'ms',
            'url' => request()->fullUrl(),
        ]);
    }
});
```

### Check Index Usage (MySQL)

```sql
-- Show index usage statistics
SELECT 
    table_name,
    index_name,
    cardinality,
    seq_in_index
FROM information_schema.statistics
WHERE table_schema = DATABASE()
    AND table_name IN ('invoices', 'meter_readings', 'meters')
ORDER BY table_name, index_name, seq_in_index;
```

### Check Index Usage (PostgreSQL)

```sql
-- Show unused indexes
SELECT 
    schemaname,
    tablename,
    indexname,
    idx_scan,
    idx_tup_read
FROM pg_stat_user_indexes
WHERE idx_scan = 0
    AND indexname NOT LIKE 'pg_toast%'
ORDER BY schemaname, tablename;
```

---

## 🧪 Testing Strategy

### Performance Tests

```php
// tests/Performance/BillingServicePerformanceTest.php
test('invoice generation stays under query budget', function () {
    $tenant = Tenant::factory()->create();
    $property = Property::factory()->create();
    Meter::factory()->count(5)->create(['property_id' => $property->id]);
    
    DB::enableQueryLog();
    
    $service = app(BillingService::class);
    $invoice = $service->generateInvoice($tenant, now()->subMonth(), now());
    
    $queryCount = count(DB::getQueryLog());
    
    expect($queryCount)->toBeLessThanOrEqual(15)
        ->and($invoice)->toBeInstanceOf(Invoice::class);
});

test('invoice generation completes within time budget', function () {
    $tenant = Tenant::factory()->create();
    
    $start = microtime(true);
    
    $service = app(BillingService::class);
    $invoice = $service->generateInvoice($tenant, now()->subMonth(), now());
    
    $duration = (microtime(true) - $start) * 1000;
    
    expect($duration)->toBeLessThan(200)
        ->and($invoice)->toBeInstanceOf(Invoice::class);
});
```

### N+1 Detection

```php
// Use Laravel Debugbar or Telescope
composer require barryvdh/laravel-debugbar --dev

// Or manual detection
test('property listing avoids N+1 queries', function () {
    Property::factory()->count(20)->create();
    
    DB::enableQueryLog();
    
    $properties = Property::with(['building', 'tenants'])
        ->withCount('meters')
        ->get();
    
    $queryCount = count(DB::getQueryLog());
    
    // Should be 2-3 queries regardless of property count
    expect($queryCount)->toBeLessThanOrEqual(3);
});
```

---

## 📝 Documentation Updates

### Keep Documentation Current

- [ ] Update ERD when schema changes
- [ ] Document new indexes in COMPREHENSIVE_SCHEMA_ANALYSIS.md
- [ ] Add new query patterns to DATABASE_QUERY_OPTIMIZATION_GUIDE.md
- [ ] Update performance benchmarks after optimizations

### Review Quarterly

- [ ] Review all database documentation
- [ ] Update performance benchmarks
- [ ] Document new optimization techniques
- [ ] Archive outdated recommendations

---

## 🚀 Deployment Checklist

### Pre-Deployment

- [ ] Run all migrations in staging
- [ ] Verify index creation (check for errors)
- [ ] Test rollback procedures
- [ ] Backup production database
- [ ] Review slow query log from staging

### Deployment

- [ ] Run migrations: `php artisan migrate --force`
- [ ] Verify foreign key constraints
- [ ] Check index creation
- [ ] Monitor query performance
- [ ] Verify backup completion

### Post-Deployment

- [ ] Monitor slow query log (first 24 hours)
- [ ] Check error logs for database issues
- [ ] Verify index usage with EXPLAIN
- [ ] Review performance metrics
- [ ] Document any issues encountered

---

## 📚 Related Documentation

- [COMPREHENSIVE_SCHEMA_ANALYSIS.md](./COMPREHENSIVE_SCHEMA_ANALYSIS.md) - Full schema analysis
- [SCHEMA_ANALYSIS_SUMMARY.md](./SCHEMA_ANALYSIS_SUMMARY.md) - Quick reference
- [ERD_VISUAL.md](./ERD_VISUAL.md) - Visual entity relationships
- [DATABASE_QUERY_OPTIMIZATION_GUIDE.md](../performance/DATABASE_QUERY_OPTIMIZATION_GUIDE.md) - Query optimization
- [SLOW_QUERY_EXAMPLE.md](../performance/SLOW_QUERY_EXAMPLE.md) - Real-world examples
- [BILLING_SERVICE_PERFORMANCE_SUMMARY.md](../performance/BILLING_SERVICE_PERFORMANCE_SUMMARY.md) - BillingService optimization

---

## 🎯 Success Criteria

### Performance Goals Met ✅

- ✅ Invoice generation: <200ms (currently ~100ms)
- ✅ Dashboard load: <100ms (currently ~50ms)
- ✅ Query count: <20 per request (currently 10-15)
- ✅ Memory usage: <10MB per request (currently ~4MB)

### Next Milestones

- 🎯 Implement query result caching (reduce database load by 50%)
- 🎯 Add materialized views for complex aggregates (PostgreSQL)
- 🎯 Implement cursor pagination for large result sets
- 🎯 Add partial indexes for frequently filtered queries

---

**Last Updated**: 2025-11-25
**Version**: 3.0
**Status**: Production Ready ✅
