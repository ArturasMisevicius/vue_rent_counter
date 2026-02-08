# PolicyRegistryHealthWidget Documentation

## Overview

The `PolicyRegistryHealthWidget` is a Filament v4 StatsOverviewWidget that provides real-time monitoring of the policy registry system health. It displays comprehensive metrics about policy registration performance, cache efficiency, error rates, and overall system status.

## Key Features

- **Real-time Health Monitoring**: Displays system health status with color-coded indicators
- **Performance Metrics**: Tracks policy registration times and cache hit rates
- **Error Rate Monitoring**: Shows 24-hour error rates with threshold-based alerts
- **Policy & Gate Counts**: Displays total registered policies and gates
- **Automatic Polling**: Updates every 30 seconds for real-time monitoring
- **Graceful Error Handling**: Falls back to error state when monitoring service is unavailable

## Architecture

### Class Structure

```php
final class PolicyRegistryHealthWidget extends BaseWidget
{
    protected ?string $pollingInterval = '30s';
    protected bool $isLazy = false;
    protected static ?int $sort = 100;
}
```

### Key Changes in v4

The widget properties `$pollingInterval` and `$isLazy` were changed from `static` to instance properties to align with Filament v4 conventions. This change allows for more flexible widget configuration and better integration with the Filament lifecycle.

**Before (v3 pattern):**
```php
protected static ?string $pollingInterval = '30s';
protected static bool $isLazy = false;
```

**After (v4 pattern):**
```php
protected ?string $pollingInterval = '30s';
protected bool $isLazy = false;
```

## Dependencies

- **PolicyRegistryMonitoringService**: Provides health data and metrics
- **Filament StatsOverviewWidget**: Base widget functionality
- **Laravel Localization**: Multi-language support for widget labels

## Authorization

The widget is restricted to super administrators only:

```php
public static function canView(): bool
{
    return auth()->check() && auth()->user()->hasRole('super_admin');
}
```

## Health Metrics

### 1. Health Status
- **Green (Success)**: All systems operational
- **Yellow (Warning)**: Non-critical issues detected
- **Red (Danger)**: Critical issues or system unhealthy

### 2. Policy Count
- Displays total number of registered policies
- Icon: Shield check
- Color: Primary

### 3. Gate Count
- Displays total number of registered gates
- Icon: Key
- Color: Primary

### 4. Cache Hit Rate
- **Green**: ≥90% hit rate
- **Yellow**: 80-89% hit rate
- **Red**: <80% hit rate

### 5. Performance Metric
- Average registration time
- **Green**: ≤50ms
- **Yellow**: 51-100ms
- **Red**: >100ms

### 6. Error Rate (24h)
- **Green**: ≤1% error rate
- **Yellow**: 1-5% error rate
- **Red**: >5% error rate

## Data Flow

```mermaid
graph TD
    A[Widget Render] --> B[getStats()]
    B --> C[getHealthData()]
    C --> D{Cached Data?}
    D -->|Yes| E[Return Cached]
    D -->|No| F[Fresh Health Check]
    E --> G[Validate Data]
    F --> G
    G --> H{Valid?}
    H -->|Yes| I[Build Health Stats]
    H -->|No| J[Return Error Stats]
    I --> K[Format & Display]
    J --> K
```

## Error Handling

The widget implements comprehensive error handling:

1. **Service Exceptions**: Caught and logged with full stack trace
2. **Invalid Data**: Validated before processing
3. **Missing Metrics**: Default values provided for missing data
4. **Fallback State**: Error stats displayed when health data unavailable

## Localization

All text is localized using Laravel's translation system:

```php
// Russian translations in lang/ru/app.php
'widgets' => [
    'policy_registry' => [
        'health_status' => 'Состояние системы политик',
        'total_policies' => 'Всего политик',
        'total_gates' => 'Всего шлюзов',
        // ... more translations
    ],
],
```

## Usage Examples

### Basic Integration

```php
// In Filament Dashboard
protected function getWidgets(): array
{
    return [
        PolicyRegistryHealthWidget::class,
    ];
}
```

### Custom Polling Interval

```php
// Override in subclass if needed
protected ?string $pollingInterval = '60s'; // Poll every minute
```

## Testing

The widget includes comprehensive unit tests covering:

- Health status display with various metrics
- Error handling and fallback states
- Data validation and formatting
- Authorization checks
- Performance thresholds

### Test Examples

```php
public function test_widget_displays_healthy_status_with_valid_metrics(): void
{
    $healthCheckData = [
        'healthy' => true,
        'metrics' => [
            'total_policies' => 15,
            'cache_hit_rate' => 0.95,
            'average_registration_time' => 45.0,
        ],
        'issues' => ['critical' => [], 'warnings' => []],
    ];

    $this->mockMonitoringService
        ->shouldReceive('getLastHealthCheck')
        ->andReturn($healthCheckData);

    $stats = $this->widget->getStats();
    
    $this->assertCount(6, $stats);
    $this->assertEquals('success', $this->getStatColor($stats[0]));
}
```

## Performance Considerations

1. **Caching Strategy**: Prioritizes cached data over fresh health checks
2. **Polling Interval**: 30-second updates balance freshness with performance
3. **Lazy Loading**: Disabled (`isLazy = false`) for immediate visibility
4. **Error Logging**: Comprehensive but not excessive to avoid log spam

## Troubleshooting

### Common Issues

1. **Widget Not Visible**
   - Check user has `super_admin` role
   - Verify widget is registered in dashboard

2. **Data Not Loading**
   - Check PolicyRegistryMonitoringService is properly registered
   - Verify cache is functioning correctly
   - Check application logs for service errors

3. **Incorrect Metrics**
   - Validate health data structure in monitoring service
   - Check translation keys exist for current locale
   - Verify metric calculation logic

### Debug Information

Enable debug logging to troubleshoot issues:

```php
Log::debug('PolicyRegistryHealthWidget: Health data', [
    'data' => $healthData,
    'valid' => $this->isValidHealthData($healthData),
]);
```

## Related Documentation

- [PolicyRegistryMonitoringService](../services/policy-registry-monitoring-service.md)
- [Filament Widget Development](../development/widget-development.md)
- [Service Registration Architecture](../../architecture/service-registration-architecture.md)
- [Multi-tenant Dashboard Widgets](../dashboard/multi-tenant-widgets.md)

## Changelog

### v1.1.0 (2025-01-06)
- **BREAKING**: Changed `$pollingInterval` and `$isLazy` from static to instance properties for Filament v4 compatibility
- Enhanced documentation with comprehensive parameter descriptions
- Improved error handling and validation
- Added performance considerations and troubleshooting guide

### v1.0.0 (2024-12-15)
- Initial implementation with health status monitoring
- Basic policy and gate count display
- Cache hit rate and performance metrics
- Error rate monitoring with 24-hour window