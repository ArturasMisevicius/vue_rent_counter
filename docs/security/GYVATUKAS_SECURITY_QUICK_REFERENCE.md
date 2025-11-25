# GyvatukasCalculator Security Quick Reference

**Version**: 2.0.0 (Secure)  
**Last Updated**: 2024-11-25

## Quick Start

### 1. Basic Usage

```php
use App\Http\Requests\CalculateGyvatukasRequest;
use App\Services\GyvatukasCalculatorSecure;

public function calculate(CalculateGyvatukasRequest $request)
{
    $building = $request->getBuilding();
    $month = $request->getBillingMonth();
    
    $calculator = app(GyvatukasCalculatorSecure::class);
    $result = $calculator->calculate($building, $month);
    
    return response()->json(['circulation_energy' => $result]);
}
```

### 2. Distribution

```php
$method = $request->getDistributionMethod(); // 'equal' or 'area'
$distribution = $calculator->distributeCirculationCost($building, $totalCost, $method);

// Returns: ['property_id' => cost, ...]
```

### 3. Error Handling

```php
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

try {
    $result = $calculator->calculate($building, $month);
} catch (AuthorizationException $e) {
    return response()->json(['error' => 'Unauthorized'], 403);
} catch (ThrottleRequestsException $e) {
    return response()->json([
        'error' => 'Rate limit exceeded',
        'retry_after' => $e->getHeaders()['Retry-After'] ?? 60
    ], 429);
} catch (\InvalidArgumentException $e) {
    return response()->json(['error' => $e->getMessage()], 422);
}
```

## Authorization

### Who Can Calculate?

| Role | Can Calculate? | Restrictions |
|------|----------------|--------------|
| Superadmin | ✅ Yes | Any building |
| Admin | ✅ Yes | Same tenant only |
| Manager | ✅ Yes | Same tenant only |
| Tenant | ❌ No | View-only |

### Check Authorization

```php
// In controller
$this->authorize('calculate', [GyvatukasCalculatorSecure::class, $building]);

// In code
if (Gate::allows('calculate', [GyvatukasCalculatorSecure::class, $building])) {
    // Authorized
}
```

## Rate Limits

### Default Limits

- **Per-user**: 10 calculations/minute
- **Per-tenant**: 100 calculations/minute

### Adjust Limits

```env
# .env
GYVATUKAS_RATE_LIMIT_USER=20
GYVATUKAS_RATE_LIMIT_TENANT=200
```

### Clear Rate Limits

```php
use Illuminate\Support\Facades\RateLimiter;

// Clear user limit
RateLimiter::clear('gyvatukas:user:' . $userId);

// Clear tenant limit
RateLimiter::clear('gyvatukas:tenant:' . $tenantId);
```

## Audit Trail

### Query Audits

```php
use App\Models\GyvatukasCalculationAudit;

// Recent calculations for building
$audits = GyvatukasCalculationAudit::forBuilding($buildingId)
    ->latest()
    ->take(10)
    ->get();

// Calculations for specific month
$audits = GyvatukasCalculationAudit::forMonth('2024-06')
    ->with('calculatedBy', 'building')
    ->get();

// Check for warnings
$hasWarnings = $audit->hasNegativeEnergyWarning();
$hasMissing = $audit->hasMissingSummerAverageWarning();

// Performance metrics
$duration = $audit->getCalculationDuration(); // milliseconds
$queries = $audit->getQueryCount();
```

## Validation Rules

### Building

- Must exist
- Must be active
- Must have at least one property
- Must belong to user's tenant (unless superadmin)

### Billing Month

- Must be valid date (Y-m-d format)
- Cannot be in future
- Cannot be before 2020-01-01

### Distribution Method

- Must be 'equal' or 'area'
- Defaults to 'equal' if not provided

## Configuration

### Security Settings

```php
// config/gyvatukas.php

'rate_limit' => [
    'per_user' => 10,
    'per_tenant' => 100,
    'window' => 60, // seconds
],

'audit' => [
    'enabled' => true,
    'retention_days' => 365,
],

'logging' => [
    'hash_building_ids' => true,
    'log_performance' => true,
],

'validation' => [
    'min_year' => 2020,
    'require_properties' => true,
],
```

### Environment Variables

```env
# Rate Limiting
GYVATUKAS_RATE_LIMIT_USER=10
GYVATUKAS_RATE_LIMIT_TENANT=100

# Audit
GYVATUKAS_AUDIT_ENABLED=true
GYVATUKAS_AUDIT_RETENTION_DAYS=365

# Logging
GYVATUKAS_HASH_BUILDING_IDS=true
GYVATUKAS_LOG_PERFORMANCE=true

# Validation
GYVATUKAS_MIN_YEAR=2020
GYVATUKAS_REQUIRE_PROPERTIES=true
```

## Testing

### Run Security Tests

```bash
# All security tests
php artisan test tests/Security/GyvatukasCalculatorSecurityTest.php

# Specific groups
php artisan test --filter="Authorization"
php artisan test --filter="Rate Limiting"
php artisan test --filter="Audit Trail"
```

### Test with Different Roles

```php
// Superadmin
$superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
$this->actingAs($superadmin);

// Admin
$admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
$this->actingAs($admin);

// Manager
$manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => 1]);
$this->actingAs($manager);

// Tenant (should fail)
$tenant = User::factory()->create(['role' => UserRole::TENANT]);
$this->actingAs($tenant);
```

## Monitoring

### Key Metrics

```sql
-- Authorization failures (last hour)
SELECT COUNT(*) FROM logs 
WHERE message LIKE '%Unauthorized gyvatukas%' 
AND created_at > NOW() - INTERVAL 1 HOUR;

-- Rate limit hits (last hour)
SELECT COUNT(*) FROM logs 
WHERE message LIKE '%rate limit exceeded%' 
AND created_at > NOW() - INTERVAL 1 HOUR;

-- Average performance (last 24 hours)
SELECT 
    AVG(JSON_EXTRACT(calculation_metadata, '$.duration_ms')) as avg_duration,
    AVG(JSON_EXTRACT(calculation_metadata, '$.query_count')) as avg_queries,
    COUNT(*) as total_calculations
FROM gyvatukas_calculation_audits
WHERE created_at > NOW() - INTERVAL 1 DAY;

-- Error rate (last 24 hours)
SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN JSON_EXTRACT(calculation_metadata, '$.negative_energy_warning') = true THEN 1 ELSE 0 END) as warnings,
    (SUM(CASE WHEN JSON_EXTRACT(calculation_metadata, '$.negative_energy_warning') = true THEN 1 ELSE 0 END) / COUNT(*) * 100) as error_rate
FROM gyvatukas_calculation_audits
WHERE created_at > NOW() - INTERVAL 1 DAY;
```

### Alert Thresholds

| Metric | Warning | Critical |
|--------|---------|----------|
| Auth failures | >10/hour | >50/hour |
| Rate limit hits | >100/hour | >500/hour |
| Avg duration | >500ms | >2s |
| Avg queries | >10 | >20 |
| Error rate | >1% | >5% |

## Troubleshooting

### Authorization Failed

**Error**: `AuthorizationException`

**Causes**:
- User not authenticated
- User role is Tenant
- Building belongs to different tenant

**Fix**:
```php
// Check user role
if ($user->role === UserRole::TENANT) {
    // Tenants cannot calculate
}

// Check tenant match
if ($building->tenant_id !== $user->tenant_id) {
    // Cross-tenant access
}
```

### Rate Limit Exceeded

**Error**: `ThrottleRequestsException`

**Causes**:
- Too many calculations
- Batch processing without delays

**Fix**:
```php
// Add delays
foreach ($buildings as $building) {
    $calculator->calculate($building, $month);
    sleep(1); // 1 second delay
}

// Or increase limits
// In .env: GYVATUKAS_RATE_LIMIT_USER=20
```

### Invalid Input

**Error**: `InvalidArgumentException`

**Causes**:
- Building has no properties
- Future billing month
- Old billing month

**Fix**:
```php
// Validate before calling
if ($building->properties()->count() === 0) {
    throw new \InvalidArgumentException('Building has no properties');
}

if ($billingMonth->isFuture()) {
    throw new \InvalidArgumentException('Billing month cannot be in future');
}
```

## Best Practices

### ✅ DO

```php
// Use FormRequest
public function calculate(CalculateGyvatukasRequest $request)

// Handle exceptions
try {
    $result = $calculator->calculate($building, $month);
} catch (ThrottleRequestsException $e) {
    // Handle rate limit
}

// Clear cache after meter updates
$calculator->clearBuildingCache($buildingId);

// Query audit trail
$audits = GyvatukasCalculationAudit::forBuilding($buildingId)->get();
```

### ❌ DON'T

```php
// Don't bypass validation
$building = Building::find($request->building_id); // Use FormRequest instead

// Don't ignore rate limits
for ($i = 0; $i < 1000; $i++) {
    $calculator->calculate($building, $month); // Will hit rate limit
}

// Don't use original service
$calculator = new GyvatukasCalculator(); // Use GyvatukasCalculatorSecure

// Don't log raw building IDs
Log::info('Calculation', ['building_id' => $building->id]); // Use hash
```

## Migration Checklist

- [ ] Run migration: `php artisan migrate`
- [ ] Register policy in `AuthServiceProvider`
- [ ] Update service binding in `AppServiceProvider`
- [ ] Replace service calls with secure version
- [ ] Add FormRequest validation to controllers
- [ ] Update error handling
- [ ] Add monitoring queries
- [ ] Run security tests
- [ ] Update documentation
- [ ] Deploy to staging
- [ ] Monitor for 1 week
- [ ] Deploy to production

## Resources

- [Security Audit Report](./GYVATUKAS_CALCULATOR_SECURITY_AUDIT.md)
- [Implementation Guide](./GYVATUKAS_SECURITY_IMPLEMENTATION.md)
- [Summary](./SECURITY_HARDENING_SUMMARY.md)
- [API Documentation](../api/GYVATUKAS_CALCULATOR_API.md)

## Support

For security issues or questions:
- Review documentation above
- Check audit trail for calculation history
- Monitor metrics for anomalies
- Contact security team for critical issues

---

**Quick Reference Version**: 1.0.0  
**Last Updated**: 2024-11-25  
**Status**: ✅ PRODUCTION READY
