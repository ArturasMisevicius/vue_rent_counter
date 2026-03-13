# Filament Widget Development Guide

Complete guide to building performant dashboard widgets in Filament v4.3+ for Laravel applications. Learn widget types, optimization techniques, and best practices for multi-tenant environments.

## Overview

Filament widgets provide powerful dashboard components for displaying key metrics, recent data, and interactive charts. This guide covers widget development patterns, performance optimization, and integration with Laravel's multi-tenant architecture.

## Widget Types

### Stats Overview Widgets

Display key performance indicators and metrics:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class PropertyStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Properties', $this->getTotalProperties())
                ->description('Active properties in portfolio')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('success'),
                
            Stat::make('Monthly Revenue', $this->getMonthlyRevenue())
                ->description('Current month earnings')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('primary'),
                
            Stat::make('Pending Invoices', $this->getPendingInvoices())
                ->description('Awaiting payment')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),
        ];
    }
    
    private function getTotalProperties(): int
    {
        return cache()->remember(
            'stats.properties.total',
            300,
            fn () => Property::count()
        );
    }
}
```

### Table Widgets

Show recent records or filtered data:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;

final class RecentInvoicesWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Invoice::query()
                    ->with(['property', 'tenant'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('number')
                    ->label('Invoice #')
                    ->searchable(),
                    
                TextColumn::make('property.address')
                    ->label('Property')
                    ->limit(30),
                    
                TextColumn::make('total')
                    ->money('EUR')
                    ->sortable(),
                    
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'sent' => 'warning',
                        'paid' => 'success',
                        'overdue' => 'danger',
                    }),
            ])
            ->actions([
                Action::make('view')
                    ->url(fn (Invoice $record): string => 
                        InvoiceResource::getUrl('view', ['record' => $record])
                    ),
            ]);
    }
}
```

### Chart Widgets

Display trends and analytics:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

final class RevenueChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Monthly Revenue';
    protected static string $color = 'info';
    
    protected function getData(): array
    {
        $data = cache()->remember(
            'chart.revenue.monthly',
            3600,
            fn () => Trend::model(Invoice::class)
                ->between(
                    start: now()->subMonths(12),
                    end: now(),
                )
                ->perMonth()
                ->sum('total')
        );
        
        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                    'backgroundColor' => '#3b82f6',
                    'borderColor' => '#1d4ed8',
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => $value->date),
        ];
    }
    
    protected function getType(): string
    {
        return 'line';
    }
}
```

## Performance Optimization

### Caching Strategies

Cache expensive queries with appropriate TTL:

```php
protected function getStats(): array
{
    $cacheKey = "widget.stats.{$this->getTenantId()}";
    
    return cache()->remember($cacheKey, 300, function () {
        return [
            'properties' => Property::count(),
            'revenue' => Invoice::sum('total'),
            'pending' => Invoice::where('status', 'pending')->count(),
        ];
    });
}
```

### Query Optimization

Prevent N+1 queries with eager loading:

```php
public function table(Table $table): Table
{
    return $table
        ->query(
            MeterReading::query()
                ->with(['meter.property', 'meter.building'])
                ->select(['id', 'meter_id', 'reading', 'created_at'])
                ->latest()
        );
}
```

### Polling Configuration

Use polling sparingly for real-time updates:

```php
protected static ?string $pollingInterval = '30s';

// Conditional polling
protected function getPollingInterval(): ?string
{
    return auth()->user()->wants_live_updates ? '30s' : null;
}
```

## Multi-Tenant Considerations

### Tenant Scoping

Ensure widgets respect tenant boundaries:

```php
protected function getStats(): array
{
    $tenantId = Filament::getTenant()->id;
    
    return [
        Stat::make('Properties', 
            Property::where('tenant_id', $tenantId)->count()
        ),
    ];
}
```

### Cache Key Scoping

Include tenant ID in cache keys:

```php
private function getCacheKey(string $suffix): string
{
    $tenantId = Filament::getTenant()->id;
    return "widget.{$tenantId}.{$suffix}";
}
```

## Authorization

### Widget Visibility

Control widget access with policies:

```php
public static function canView(): bool
{
    return auth()->user()->can('view_dashboard_metrics');
}
```

### Conditional Display

Show widgets based on user roles:

```php
protected static bool $isLazy = false;

public static function canView(): bool
{
    return auth()->user()->hasRole(['admin', 'manager']);
}
```

## Testing Widgets

### Unit Tests

Test widget data and calculations:

```php
it('calculates property stats correctly', function () {
    Property::factory()->count(5)->create();
    
    $widget = new PropertyStatsWidget();
    $stats = $widget->getStats();
    
    expect($stats[0]->getValue())->toBe('5');
});
```

### Feature Tests

Test widget rendering and permissions:

```php
it('displays property stats widget for managers', function () {
    $user = User::factory()->create();
    $user->assignRole('manager');
    
    actingAs($user)
        ->get('/admin')
        ->assertSeeLivewire(PropertyStatsWidget::class);
});
```

## Best Practices

### Performance Guidelines

- Cache expensive queries with tenant-scoped keys
- Use `->select()` to limit columns in table widgets
- Implement lazy loading for heavy content
- Set reasonable polling intervals (30s minimum)
- Eager load relationships to prevent N+1 queries

### UX Guidelines

- Provide meaningful empty states
- Use appropriate colors for different metrics
- Include helpful descriptions and icons
- Keep widget heights consistent
- Add drill-down actions where relevant

### Security Guidelines

- Always scope data to current tenant
- Implement proper authorization checks
- Validate user permissions for sensitive metrics
- Log access to financial/sensitive widgets
- Use policies for fine-grained access control

## Common Patterns

### Metric Comparison Widget

```php
Stat::make('This Month', $this->getCurrentMonth())
    ->description($this->getMonthComparison())
    ->descriptionIcon($this->getComparisonIcon())
    ->color($this->getComparisonColor());

private function getMonthComparison(): string
{
    $current = $this->getCurrentMonth();
    $previous = $this->getPreviousMonth();
    $change = $current - $previous;
    $percentage = $previous > 0 ? ($change / $previous) * 100 : 0;
    
    return sprintf('%+.1f%% from last month', $percentage);
}
```

### Interactive Chart Widget

```php
protected function getFilters(): ?array
{
    return [
        'today' => 'Today',
        'week' => 'Last week',
        'month' => 'Last month',
        'year' => 'This year',
    ];
}

protected function getData(): array
{
    $activeFilter = $this->filter;
    
    return match ($activeFilter) {
        'today' => $this->getTodayData(),
        'week' => $this->getWeekData(),
        'month' => $this->getMonthData(),
        'year' => $this->getYearData(),
        default => $this->getMonthData(),
    };
}
```

## Troubleshooting

### Common Issues

**Widget Not Displaying:**
- Check `canView()` method returns true
- Verify widget is registered in dashboard
- Ensure proper namespace and class name

**Performance Issues:**
- Add caching to expensive queries
- Reduce polling frequency
- Optimize database queries with indexes
- Use `->select()` to limit columns

**Authorization Errors:**
- Implement proper `canView()` checks
- Verify user has required permissions
- Check tenant scoping is correct

### Debug Techniques

```php
// Add debug info to widget
protected function getStats(): array
{
    if (app()->environment('local')) {
        logger('Widget data', [
            'tenant_id' => Filament::getTenant()->id,
            'user_id' => auth()->id(),
            'cache_key' => $this->getCacheKey('stats'),
        ]);
    }
    
    return $this->getCachedStats();
}
```

## Related Documentation

- [Filament Dashboard Widgets](filament-dashboard-widgets.md) - Widget configuration patterns
- [Filament Performance](filament-performance.md) - Performance optimization techniques
- [Multi-Tenant Architecture](../architecture/multi-tenant-patterns.md) - Tenant scoping patterns
- [Testing Standards](../testing/testing-standards.md) - Widget testing approaches