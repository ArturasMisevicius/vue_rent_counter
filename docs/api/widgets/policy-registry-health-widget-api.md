# PolicyRegistryHealthWidget API Reference

## Class Definition

```php
namespace App\Filament\Widgets;

final class PolicyRegistryHealthWidget extends StatsOverviewWidget
```

## Properties

### Instance Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `$pollingInterval` | `?string` | `'30s'` | Auto-refresh interval for real-time updates |
| `$isLazy` | `bool` | `false` | Whether to defer widget loading |

### Static Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `$sort` | `?int` | `100` | Widget display order in dashboard |

## Methods

### Public Methods

#### `canView(): bool`

Determines widget visibility based on user authorization.

**Returns:**
- `bool` - True if user is authenticated and has super_admin role

**Authorization:**
- Requires authenticated user
- Requires `super_admin` role

**Example:**
```php
if (PolicyRegistryHealthWidget::canView()) {
    // Widget is visible to current user
}
```

#### `getStats(): array<Stat>`

Retrieves and formats health statistics for display.

**Returns:**
- `array<Stat>` - Array of Filament Stat objects

**Throws:**
- Catches all `\Throwable` exceptions and logs them

**Stats Returned:**
1. Health Status (success/warning/danger)
2. Total Policies Count
3. Total Gates Count  
4. Cache Hit Rate Percentage
5. Average Registration Time
6. 24-Hour Error Rate

### Private Methods

#### `getHealthData(): ?array`

Retrieves health data from monitoring service with cache fallback.

**Returns:**
- `array|null` - Health data structure or null if unavailable

**Data Structure:**
```php
[
    'healthy' => bool,
    'metrics' => [
        'total_policies' => int,
        'total_gates' => int,
        'cache_hit_rate' => float,
        'average_registration_time' => float,
        'error_rate_24h' => float,
    ],
    'issues' => [
        'critical' => array,
        'warnings' => array,
    ],
]
```

#### `isValidHealthData(?array $data): bool`

Validates health data structure before processing.

**Parameters:**
- `$data` (`array|null`) - Health data to validate

**Returns:**
- `bool` - True if data structure is valid

**Validation Checks:**
- Data is not null
- Contains required keys: `healthy`, `metrics`, `issues`
- Correct data types for each key

#### `buildHealthStats(array $healthData): array<Stat>`

Constructs stat objects from validated health data.

**Parameters:**
- `$healthData` (`array`) - Validated health data structure

**Returns:**
- `array<Stat>` - Array of formatted stat objects

#### `buildHealthStatusStat(bool $healthy, array $issues): Stat`

Creates the main health status indicator stat.

**Parameters:**
- `$healthy` (`bool`) - Overall system health status
- `$issues` (`array`) - Array of critical and warning issues

**Returns:**
- `Stat` - Health status stat with appropriate color and description

**Color Logic:**
- `danger` - Unhealthy or critical issues present
- `warning` - Healthy but warnings present
- `success` - Healthy with no issues

#### `buildPoliciesStat(array $metrics): Stat`

Creates policy count stat.

**Parameters:**
- `$metrics` (`array`) - Metrics data containing policy count

**Returns:**
- `Stat` - Policy count stat with primary color

#### `buildGatesStat(array $metrics): Stat`

Creates gate count stat.

**Parameters:**
- `$metrics` (`array`) - Metrics data containing gate count

**Returns:**
- `Stat` - Gate count stat with primary color

#### `buildCacheHitRateStat(array $metrics): Stat`

Creates cache hit rate performance stat.

**Parameters:**
- `$metrics` (`array`) - Metrics data containing cache hit rate

**Returns:**
- `Stat` - Cache hit rate stat with performance-based color

**Color Thresholds:**
- `success` - ≥90% hit rate
- `warning` - 80-89% hit rate  
- `danger` - <80% hit rate

#### `buildPerformanceStat(array $metrics): Stat`

Creates average registration time performance stat.

**Parameters:**
- `$metrics` (`array`) - Metrics data containing registration time

**Returns:**
- `Stat` - Performance stat with time-based color

**Color Thresholds:**
- `success` - ≤50ms
- `warning` - 51-100ms
- `danger` - >100ms

#### `buildErrorRateStat(array $metrics): Stat`

Creates 24-hour error rate stat.

**Parameters:**
- `$metrics` (`array`) - Metrics data containing error rate

**Returns:**
- `Stat` - Error rate stat with threshold-based color

**Color Thresholds:**
- `success` - ≤1% error rate
- `warning` - 1-5% error rate
- `danger` - >5% error rate

#### `getErrorStats(): array<Stat>`

Creates fallback error stat when health data is unavailable.

**Returns:**
- `array<Stat>` - Single error stat indicating data unavailability

#### `formatDuration(float $milliseconds): string`

Formats millisecond duration to human-readable string.

**Parameters:**
- `$milliseconds` (`float`) - Duration in milliseconds

**Returns:**
- `string` - Formatted duration string

**Format Logic:**
- `< 1ms` for values < 1
- `XXXms` for values < 1000
- `X.XXs` for values ≥ 1000

#### `formatPercentage(float $decimal): string`

Converts decimal to percentage string.

**Parameters:**
- `$decimal` (`float`) - Decimal value (0.0-1.0)

**Returns:**
- `string` - Formatted percentage with one decimal place

## Dependencies

### Constructor Injection

```php
public function __construct(
    private readonly PolicyRegistryMonitoringService $monitoringService
) {
    parent::__construct();
}
```

**Required Services:**
- `PolicyRegistryMonitoringService` - Provides health data and metrics

## Error Handling

### Exception Handling

All exceptions in `getStats()` are caught and handled gracefully:

```php
try {
    // Health data processing
} catch (\Throwable $e) {
    Log::error('PolicyRegistryHealthWidget: Failed to load health data', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    
    return $this->getErrorStats();
}
```

### Fallback Behavior

1. **Service Unavailable**: Returns error stats
2. **Invalid Data**: Returns error stats  
3. **Missing Metrics**: Uses default values (0)
4. **Cache Miss**: Falls back to fresh health check

## Localization Keys

### Required Translation Keys

All labels use Laravel's localization system:

```php
// Health Status
__('app.widgets.policy_registry.health_status')
__('app.status.healthy')
__('app.status.unhealthy')

// Counts
__('app.widgets.policy_registry.total_policies')
__('app.widgets.policy_registry.total_gates')

// Performance
__('app.widgets.policy_registry.cache_hit_rate')
__('app.widgets.policy_registry.avg_registration_time')
__('app.widgets.policy_registry.error_rate')

// Descriptions
__('app.widgets.policy_registry.registered_policies')
__('app.widgets.policy_registry.registered_gates')
__('app.widgets.policy_registry.cache_performance')
__('app.widgets.policy_registry.performance_metric')
__('app.widgets.policy_registry.last_24h')

// Status Messages
__('app.widgets.policy_registry.all_systems_operational')
__('app.widgets.policy_registry.critical_issues', ['count' => $count])
__('app.widgets.policy_registry.warnings', ['count' => $count])
__('app.widgets.policy_registry.configuration_issues')
__('app.widgets.policy_registry.data_unavailable')
```

## Usage Examples

### Basic Dashboard Integration

```php
// In Filament Dashboard
class Dashboard extends BasePage
{
    protected function getWidgets(): array
    {
        return [
            PolicyRegistryHealthWidget::class,
        ];
    }
}
```

### Custom Widget Configuration

```php
// Custom widget with different polling
class CustomPolicyWidget extends PolicyRegistryHealthWidget
{
    protected ?string $pollingInterval = '60s';
    protected static ?int $sort = 50;
}
```

### Conditional Widget Display

```php
// Show widget based on additional conditions
class ConditionalPolicyWidget extends PolicyRegistryHealthWidget
{
    public static function canView(): bool
    {
        return parent::canView() && config('app.debug');
    }
}
```

## Testing

### Mock Service Response

```php
$healthCheckData = [
    'healthy' => true,
    'metrics' => [
        'total_policies' => 15,
        'total_gates' => 8,
        'cache_hit_rate' => 0.95,
        'average_registration_time' => 45.0,
        'error_rate_24h' => 0.005,
    ],
    'issues' => [
        'critical' => [],
        'warnings' => [],
    ],
];

$mockService->shouldReceive('getLastHealthCheck')
    ->andReturn($healthCheckData);
```

### Test Stat Properties

```php
// Helper methods for testing
private function getStatColor(Stat $stat): string
{
    $reflection = new \ReflectionClass($stat);
    $property = $reflection->getProperty('color');
    $property->setAccessible(true);
    return $property->getValue($stat);
}

private function getStatValue(Stat $stat): string
{
    $reflection = new \ReflectionClass($stat);
    $property = $reflection->getProperty('value');
    $property->setAccessible(true);
    return $property->getValue($stat);
}
```

## Performance Characteristics

- **Memory Usage**: Minimal, processes data in single pass
- **Database Queries**: None (relies on monitoring service cache)
- **Cache Strategy**: Prioritizes cached data, falls back to fresh data
- **Update Frequency**: 30-second polling interval
- **Error Recovery**: Graceful degradation with error stats