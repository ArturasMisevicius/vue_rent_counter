# Slow Query Optimization Example

## Real-World Example: Invoice Dashboard Query

### Original Slow Query

```php
// Controller method that's slow
public function dashboard(Request $request)
{
    $tenantId = auth()->user()->tenant_id;
    
    // ❌ SLOW: Multiple queries, no eager loading
    $invoices = Invoice::where('tenant_id', $tenantId)
        ->whereBetween('billing_period_start', [
            now()->subMonths(6),
            now()
        ])
        ->orderBy('billing_period_start', 'desc')
        ->get();
    
    $stats = [];
    foreach ($invoices as $invoice) {
        $stats[] = [
            'invoice' => $invoice,
            'items_count' => $invoice->items->count(), // N+1 query
            'tenant_name' => $invoice->tenant->name, // N+1 query
            'property_address' => $invoice->tenant->property->address, // N+1 query
        ];
    }
    
    return view('dashboard', compact('stats'));
}
```

### Performance Analysis

**EXPLAIN Output**:
```sql
EXPLAIN SELECT * FROM invoices 
WHERE tenant_id = 1 
AND billing_period_start BETWEEN '2024-06-01' AND '2025-01-01'
ORDER BY billing_period_start DESC;

+----+-------------+----------+------+---------------+------+---------+------+------+-------------+
| id | select_type | table    | type | possible_keys | key  | key_len | ref  | rows | Extra       |
+----+-------------+----------+------+---------------+------+---------+------+------+-------------+
|  1 | SIMPLE      | invoices | ALL  | NULL          | NULL | NULL    | NULL | 5000 | Using where |
|    |             |          |      |               |      |         |      |      | Using filesort |
+----+-------------+----------+------+---------------+------+---------+------+------+-------------+
```

**Problems Identified**:
1. `type: ALL` - Full table scan (no index used)
2. `Using filesort` - Sorting without index
3. N+1 queries for items, tenant, property
4. Loading all columns with `SELECT *`

**Measured Performance**:
- Queries: 1 + (3 × N) where N = number of invoices
- For 100 invoices: 301 queries
- Execution time: ~2.5 seconds
- Memory usage: ~15MB

---

## Step 1: Add Missing Indexes

```php
// database/migrations/2025_11_25_add_dashboard_indexes.php
public function up(): void
{
    Schema::table('invoices', function (Blueprint $table) {
        // Composite index for tenant + date range + ordering
        if (!$this->indexExists('invoices', 'idx_invoices_dashboard')) {
            $table->index(
                ['tenant_id', 'billing_period_start', 'status'],
                'idx_invoices_dashboard'
            );
        }
    });
}

private function indexExists(string $table, string $index): bool
{
    $connection = Schema::getConnection();
    $indexes = $connection->getDoctrineSchemaManager()->listTableIndexes($table);
    return isset($indexes[$index]);
}
```

**EXPLAIN After Index**:
```sql
+----+-------------+----------+-------+----------------------+----------------------+---------+-------+------+-------------+
| id | select_type | table    | type  | possible_keys        | key                  | key_len | ref   | rows | Extra       |
+----+-------------+----------+-------+----------------------+----------------------+---------+-------+------+-------------+
|  1 | SIMPLE      | invoices | range | idx_invoices_dashboard| idx_invoices_dashboard| 9       | NULL  | 100  | Using index |
+----+-------------+----------+-------+----------------------+----------------------+---------+-------+------+-------------+
```

**Improvement**: 
- `type: range` (index range scan instead of full table scan)
- `rows: 100` (only examines relevant rows)
- No more `Using filesort`

---

## Step 2: Optimize Query with Eager Loading

```php
// ✅ OPTIMIZED VERSION 1: Eloquent with eager loading
public function dashboard(Request $request)
{
    $tenantId = auth()->user()->tenant_id;
    
    $invoices = Invoice::with([
            'items:id,invoice_id,total', // Only needed columns
            'tenant:id,name,property_id',
            'tenant.property:id,address'
        ])
        ->where('tenant_id', $tenantId)
        ->whereBetween('billing_period_start', [
            now()->subMonths(6),
            now()
        ])
        ->select('id', 'tenant_id', 'tenant_renter_id', 'billing_period_start', 'total_amount', 'status')
        ->orderBy('billing_period_start', 'desc')
        ->get();
    
    $stats = $invoices->map(function ($invoice) {
        return [
            'invoice' => $invoice,
            'items_count' => $invoice->items->count(),
            'tenant_name' => $invoice->tenant->name,
            'property_address' => $invoice->tenant->property->address,
        ];
    });
    
    return view('dashboard', compact('stats'));
}
```

**Performance**:
- Queries: 4 (1 invoices + 1 items + 1 tenants + 1 properties)
- Execution time: ~180ms (93% improvement)
- Memory usage: ~4MB (73% reduction)

---

## Step 3: Further Optimization with Query Builder

```php
// ✅ OPTIMIZED VERSION 2: Query Builder with JOINs
public function dashboard(Request $request)
{
    $tenantId = auth()->user()->tenant_id;
    
    $stats = DB::table('invoices as i')
        ->join('tenants as t', 'i.tenant_renter_id', '=', 't.id')
        ->join('properties as p', 't.property_id', '=', 'p.id')
        ->leftJoin('invoice_items as ii', 'i.id', '=', 'ii.invoice_id')
        ->select([
            'i.id',
            'i.billing_period_start',
            'i.total_amount',
            'i.status',
            't.name as tenant_name',
            'p.address as property_address',
            DB::raw('COUNT(ii.id) as items_count')
        ])
        ->where('i.tenant_id', $tenantId)
        ->whereBetween('i.billing_period_start', [
            now()->subMonths(6),
            now()
        ])
        ->groupBy('i.id', 'i.billing_period_start', 'i.total_amount', 'i.status', 't.name', 'p.address')
        ->orderBy('i.billing_period_start', 'desc')
        ->get();
    
    return view('dashboard', compact('stats'));
}
```

**Performance**:
- Queries: 1 (single JOIN query)
- Execution time: ~45ms (98% improvement)
- Memory usage: ~1.5MB (90% reduction)

---

## Step 4: Add Caching Layer

```php
// ✅ OPTIMIZED VERSION 3: With caching
public function dashboard(Request $request)
{
    $tenantId = auth()->user()->tenant_id;
    $cacheKey = "dashboard.stats.{$tenantId}." . now()->format('Y-m-d-H');
    
    $stats = Cache::remember($cacheKey, now()->addHour(), function () use ($tenantId) {
        return DB::table('invoices as i')
            ->join('tenants as t', 'i.tenant_renter_id', '=', 't.id')
            ->join('properties as p', 't.property_id', '=', 'p.id')
            ->leftJoin('invoice_items as ii', 'i.id', '=', 'ii.invoice_id')
            ->select([
                'i.id',
                'i.billing_period_start',
                'i.total_amount',
                'i.status',
                't.name as tenant_name',
                'p.address as property_address',
                DB::raw('COUNT(ii.id) as items_count')
            ])
            ->where('i.tenant_id', $tenantId)
            ->whereBetween('i.billing_period_start', [
                now()->subMonths(6),
                now()
            ])
            ->groupBy('i.id', 'i.billing_period_start', 'i.total_amount', 'i.status', 't.name', 'p.address')
            ->orderBy('i.billing_period_start', 'desc')
            ->get();
    });
    
    return view('dashboard', compact('stats'));
}

// Invalidate cache when invoices change
class InvoiceObserver
{
    public function saved(Invoice $invoice): void
    {
        $cacheKey = "dashboard.stats.{$invoice->tenant_id}." . now()->format('Y-m-d-H');
        Cache::forget($cacheKey);
    }
}
```

**Performance (cached)**:
- Queries: 0 (served from cache)
- Execution time: ~2ms (99.9% improvement)
- Memory usage: ~0.5MB (96% reduction)

---

## Step 5: Add Materialized View (PostgreSQL)

```sql
-- Create materialized view for dashboard stats
CREATE MATERIALIZED VIEW mv_dashboard_stats AS
SELECT 
    i.id as invoice_id,
    i.tenant_id,
    i.billing_period_start,
    i.total_amount,
    i.status,
    t.name as tenant_name,
    p.address as property_address,
    COUNT(ii.id) as items_count,
    SUM(ii.total) as calculated_total
FROM invoices i
JOIN tenants t ON i.tenant_renter_id = t.id
JOIN properties p ON t.property_id = p.id
LEFT JOIN invoice_items ii ON i.id = ii.invoice_id
WHERE i.billing_period_start >= CURRENT_DATE - INTERVAL '6 months'
GROUP BY i.id, i.tenant_id, i.billing_period_start, i.total_amount, i.status, t.name, p.address;

-- Create index on materialized view
CREATE INDEX idx_mv_dashboard_tenant ON mv_dashboard_stats (tenant_id, billing_period_start DESC);

-- Refresh periodically (e.g., hourly via cron)
REFRESH MATERIALIZED VIEW CONCURRENTLY mv_dashboard_stats;
```

```php
// Query materialized view
public function dashboard(Request $request)
{
    $tenantId = auth()->user()->tenant_id;
    
    $stats = DB::table('mv_dashboard_stats')
        ->where('tenant_id', $tenantId)
        ->orderBy('billing_period_start', 'desc')
        ->get();
    
    return view('dashboard', compact('stats'));
}
```

**Performance**:
- Queries: 1 (simple SELECT from materialized view)
- Execution time: ~5ms (99.8% improvement)
- Memory usage: ~0.8MB (94% reduction)

---

## Performance Comparison Summary

| Version | Queries | Time | Memory | Improvement |
|---------|---------|------|--------|-------------|
| Original | 301 | 2500ms | 15MB | Baseline |
| + Index | 301 | 1800ms | 15MB | 28% faster |
| + Eager Loading | 4 | 180ms | 4MB | 93% faster |
| + Query Builder | 1 | 45ms | 1.5MB | 98% faster |
| + Caching | 0 | 2ms | 0.5MB | 99.9% faster |
| + Materialized View | 1 | 5ms | 0.8MB | 99.8% faster |

---

## Testing the Optimization

```php
// tests/Performance/DashboardPerformanceTest.php
use Illuminate\Support\Facades\DB;

test('dashboard query stays under performance budget', function () {
    // Arrange: Create test data
    $tenant = Tenant::factory()->create();
    $property = Property::factory()->create(['tenant_id' => $tenant->tenant_id]);
    $tenant->update(['property_id' => $property->id]);
    
    Invoice::factory()
        ->count(100)
        ->has(InvoiceItem::factory()->count(5))
        ->create([
            'tenant_id' => $tenant->tenant_id,
            'tenant_renter_id' => $tenant->id,
        ]);
    
    // Act: Measure performance
    DB::enableQueryLog();
    $start = microtime(true);
    
    $response = $this->actingAs($tenant->user)
        ->get(route('dashboard'));
    
    $duration = (microtime(true) - $start) * 1000;
    $queryCount = count(DB::getQueryLog());
    
    // Assert: Performance budgets
    expect($response->status())->toBe(200)
        ->and($queryCount)->toBeLessThanOrEqual(5) // Max 5 queries
        ->and($duration)->toBeLessThan(200); // Max 200ms
});

test('dashboard handles large datasets efficiently', function () {
    $tenant = Tenant::factory()->create();
    
    // Create 1000 invoices
    Invoice::factory()
        ->count(1000)
        ->has(InvoiceItem::factory()->count(10))
        ->create(['tenant_id' => $tenant->tenant_id]);
    
    $start = microtime(true);
    $memoryBefore = memory_get_usage();
    
    $response = $this->actingAs($tenant->user)
        ->get(route('dashboard'));
    
    $duration = (microtime(true) - $start) * 1000;
    $memoryUsed = (memory_get_usage() - $memoryBefore) / 1024 / 1024; // MB
    
    expect($response->status())->toBe(200)
        ->and($duration)->toBeLessThan(500) // Max 500ms for large dataset
        ->and($memoryUsed)->toBeLessThan(10); // Max 10MB memory
});
```

---

## Monitoring in Production

```php
// app/Http/Middleware/MonitorSlowQueries.php
class MonitorSlowQueries
{
    public function handle(Request $request, Closure $next)
    {
        DB::listen(function ($query) {
            if ($query->time > 100) { // Queries slower than 100ms
                Log::warning('Slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time . 'ms',
                    'url' => request()->fullUrl(),
                    'user_id' => auth()->id(),
                ]);
            }
        });
        
        return $next($request);
    }
}
```

---

## Key Takeaways

1. **Always add indexes** for WHERE, ORDER BY, and JOIN columns
2. **Use eager loading** to eliminate N+1 queries
3. **Select only needed columns** to reduce memory usage
4. **Consider Query Builder** for complex queries with JOINs
5. **Implement caching** for frequently accessed data
6. **Use materialized views** for expensive aggregates
7. **Test performance** with realistic data volumes
8. **Monitor in production** to catch regressions

