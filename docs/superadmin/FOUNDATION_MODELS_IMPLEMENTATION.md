# Superadmin Dashboard Foundation Models Implementation

## Overview

This document describes the implementation of the foundation data models for the Superadmin Dashboard Enhancement feature. This implementation covers task 1 from the specification.

## Implemented Components

### 1. Database Migrations

#### System Health Metrics Table
- **File**: `database/migrations/2025_12_03_000001_create_system_health_metrics_table.php`
- **Purpose**: Store system health monitoring data for database, backup, queue, storage, and cache metrics
- **Fields**:
  - `metric_type`: Type of metric (database, backup, queue, storage, cache)
  - `metric_name`: Specific metric name
  - `value`: JSON field storing metric data
  - `status`: Health status (healthy, warning, danger)
  - `checked_at`: Timestamp of when the metric was checked
- **Indexes**:
  - Composite index on `metric_type` and `checked_at`
  - Index on `status`

### 2. Models

#### SystemHealthMetric Model
- **File**: `app/Models/SystemHealthMetric.php`
- **Methods**:
  - `isHealthy()`: Check if metric is healthy
  - `isWarning()`: Check if metric has warning status
  - `isDanger()`: Check if metric is in danger status
  - `getStatusColor()`: Get color for status indicator (green/yellow/red)
  - `getStatusIcon()`: Get Heroicon for status display
- **Scopes**:
  - `latestByType()`: Get latest metrics by type
  - `withinTimeRange()`: Get metrics within time range
  - `unhealthy()`: Get metrics with warning or danger status

#### Organization Model Enhancements
- **File**: `app/Models/Organization.php`
- **New Methods**:
  - `daysUntilExpiry()`: Calculate days until subscription expires (returns negative if expired)
- **Existing Methods Verified**:
  - `isSuspended()`: Check if organization is suspended ✓
  - `suspend(string $reason)`: Suspend organization with reason ✓
  - `reactivate()`: Reactivate suspended organization ✓

#### Subscription Model Enhancements
- **File**: `app/Models/Subscription.php`
- **New Methods**:
  - `isSuspended()`: Check if subscription is suspended
  - `renew(Carbon $newExpiryDate)`: Renew subscription with new expiry date
  - `suspend()`: Suspend the subscription
  - `activate()`: Activate the subscription
- **Existing Methods Verified**:
  - `daysUntilExpiry()`: Calculate days until expiry ✓

#### OrganizationInvitation Model Enhancements
- **File**: `app/Models/OrganizationInvitation.php`
- **New Methods**:
  - `isPending()`: Check if invitation is pending (not accepted and not expired)
  - `cancel()`: Cancel the invitation (deletes it)
  - `resend()`: Resend invitation with new token and expiry date
- **Existing Methods Verified**:
  - `isExpired()`: Check if invitation expired ✓
  - `isAccepted()`: Check if invitation accepted ✓
  - `accept()`: Accept the invitation ✓

### 3. Factories

#### SystemHealthMetricFactory
- **File**: `database/factories/SystemHealthMetricFactory.php`
- **Features**:
  - Generates realistic metric data based on type
  - State methods for status: `healthy()`, `warning()`, `danger()`
  - State methods for types: `database()`, `backup()`, `queue()`, `storage()`, `cache()`
  - Intelligent value generation based on metric type

### 4. Tests

#### Unit Tests
- **File**: `tests/Unit/SuperadminFoundationModelsTest.php`
- **Coverage**:
  - Organization `daysUntilExpiry()` method
  - Organization suspend/reactivate workflow
  - Subscription renewal functionality
  - Subscription suspend/activate workflow
  - Subscription `isSuspended()` method
  - OrganizationInvitation `isPending()` method
  - OrganizationInvitation resend functionality
  - SystemHealthMetric status checking methods
  - SystemHealthMetric status color mapping
  - SystemHealthMetric scopes

**Test Results**: All 10 tests passing with 33 assertions

## Database Schema

### system_health_metrics Table

```sql
CREATE TABLE system_health_metrics (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    metric_type VARCHAR(255) NOT NULL,
    metric_name VARCHAR(255) NOT NULL,
    value JSON NOT NULL,
    status VARCHAR(255) NOT NULL,
    checked_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX health_metrics_type_checked_index (metric_type, checked_at),
    INDEX health_metrics_status_index (status)
);
```

## Usage Examples

### Organization Management

```php
// Check days until expiry
$org = Organization::find(1);
$daysLeft = $org->daysUntilExpiry(); // Returns integer (negative if expired)

// Suspend organization
$org->suspend('Payment overdue');

// Check if suspended
if ($org->isSuspended()) {
    // Handle suspended state
}

// Reactivate organization
$org->reactivate();
```

### Subscription Management

```php
// Renew subscription
$subscription = Subscription::find(1);
$subscription->renew(now()->addYear());

// Suspend subscription
$subscription->suspend();

// Check if suspended
if ($subscription->isSuspended()) {
    // Handle suspended state
}

// Activate subscription
$subscription->activate();

// Check days until expiry
$daysLeft = $subscription->daysUntilExpiry();
```

### Organization Invitations

```php
// Check if invitation is pending
$invitation = OrganizationInvitation::find(1);
if ($invitation->isPending()) {
    // Can be accepted
}

// Resend invitation
$invitation->resend(); // Generates new token and extends expiry

// Cancel invitation
$invitation->cancel(); // Deletes the invitation
```

### System Health Metrics

```php
// Create health metric
$metric = SystemHealthMetric::create([
    'metric_type' => 'database',
    'metric_name' => 'connection_status',
    'value' => ['connections' => 50, 'slow_queries' => 2],
    'status' => 'healthy',
    'checked_at' => now(),
]);

// Check status
if ($metric->isHealthy()) {
    $color = $metric->getStatusColor(); // 'green'
    $icon = $metric->getStatusIcon(); // 'heroicon-o-check-circle'
}

// Query metrics
$latestDatabase = SystemHealthMetric::latestByType('database')->first();
$unhealthyMetrics = SystemHealthMetric::unhealthy()->get();
```

## Requirements Validation

This implementation satisfies the following requirements from the specification:

- **Requirement 2.1**: Organization management with suspension capabilities ✓
- **Requirement 3.1**: Subscription management with renewal and status control ✓
- **Requirement 13.1**: Organization invitation system with token management ✓
- **Requirement 6.1-6.5**: System health monitoring infrastructure ✓

## Next Steps

The foundation models are now in place. The next tasks in the implementation plan are:

1. **Task 2**: Create dashboard widgets (SubscriptionStatsWidget, OrganizationStatsWidget, SystemHealthWidget, etc.)
2. **Task 3**: Create SuperadminDashboard page
3. **Task 4**: Enhance OrganizationResource with new fields and actions

## Testing

To run the foundation models tests:

```bash
php artisan test --filter=SuperadminFoundationModelsTest
```

All tests should pass with 10 tests and 33 assertions.

## Migration

To apply the new migration:

```bash
php artisan migrate
```

Or to run only the system health metrics migration:

```bash
php artisan migrate --path=database/migrations/2025_12_03_000001_create_system_health_metrics_table.php
```
