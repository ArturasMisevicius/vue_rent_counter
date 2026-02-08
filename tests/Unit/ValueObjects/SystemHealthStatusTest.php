<?php

use App\ValueObjects\SystemHealthStatus;
use App\ValueObjects\HealthLevel;
use Illuminate\Support\Collection;

test('creates system health status with all properties', function () {
    $alerts = collect([
        ['severity' => 'warning', 'message' => 'High CPU usage'],
        ['severity' => 'critical', 'message' => 'Disk space low'],
    ]);
    
    $status = new SystemHealthStatus(
        overall: HealthLevel::WARNING,
        cpuUsage: 75.5,
        memoryUsage: 60.2,
        diskUsage: 85.0,
        activeTenants: 45,
        totalTenants: 50,
        totalUsers: 1250,
        averageResponseTime: 1500.0,
        alerts: $alerts,
        queueSize: 150,
        failedJobs: 5,
        databaseResponseTime: 250.5
    );
    
    expect($status->overall)->toBe(HealthLevel::WARNING)
        ->and($status->cpuUsage)->toBe(75.5)
        ->and($status->memoryUsage)->toBe(60.2)
        ->and($status->diskUsage)->toBe(85.0)
        ->and($status->activeTenants)->toBe(45)
        ->and($status->totalTenants)->toBe(50)
        ->and($status->totalUsers)->toBe(1250)
        ->and($status->averageResponseTime)->toBe(1500.0)
        ->and($status->alerts)->toEqual($alerts)
        ->and($status->queueSize)->toBe(150)
        ->and($status->failedJobs)->toBe(5)
        ->and($status->databaseResponseTime)->toBe(250.5);
});

test('HealthLevel enum has correct values', function () {
    expect(HealthLevel::EXCELLENT->value)->toBe('excellent')
        ->and(HealthLevel::GOOD->value)->toBe('good')
        ->and(HealthLevel::WARNING->value)->toBe('warning')
        ->and(HealthLevel::CRITICAL->value)->toBe('critical');
});

test('HealthLevel enum provides colors', function () {
    expect(HealthLevel::EXCELLENT->getColor())->toBe('success')
        ->and(HealthLevel::GOOD->getColor())->toBe('info')
        ->and(HealthLevel::WARNING->getColor())->toBe('warning')
        ->and(HealthLevel::CRITICAL->getColor())->toBe('danger');
});

test('HealthLevel enum provides labels', function () {
    expect(HealthLevel::EXCELLENT->getLabel())->toBeString()
        ->and(HealthLevel::GOOD->getLabel())->toBeString()
        ->and(HealthLevel::WARNING->getLabel())->toBeString()
        ->and(HealthLevel::CRITICAL->getLabel())->toBeString();
});

test('calculates CPU health level correctly', function () {
    $status = new SystemHealthStatus(
        overall: HealthLevel::GOOD,
        cpuUsage: 95.0, // Critical level
        memoryUsage: 50.0,
        diskUsage: 50.0,
        activeTenants: 10,
        totalTenants: 20,
        totalUsers: 100,
        averageResponseTime: 1000.0,
        alerts: collect(),
        queueSize: 10,
        failedJobs: 0,
        databaseResponseTime: 100.0
    );
    
    expect($status->getCpuHealthLevel())->toBe(HealthLevel::CRITICAL);
});

test('calculates CPU health levels for all thresholds', function () {
    // Excellent (< 50%)
    $status = new SystemHealthStatus(
        overall: HealthLevel::EXCELLENT,
        cpuUsage: 30.0,
        memoryUsage: 50.0,
        diskUsage: 50.0,
        activeTenants: 10,
        totalTenants: 20,
        totalUsers: 100,
        averageResponseTime: 1000.0,
        alerts: collect(),
        queueSize: 10,
        failedJobs: 0,
        databaseResponseTime: 100.0
    );
    expect($status->getCpuHealthLevel())->toBe(HealthLevel::EXCELLENT);
    
    // Good (50-74%)
    $status = new SystemHealthStatus(
        overall: HealthLevel::GOOD,
        cpuUsage: 65.0,
        memoryUsage: 50.0,
        diskUsage: 50.0,
        activeTenants: 10,
        totalTenants: 20,
        totalUsers: 100,
        averageResponseTime: 1000.0,
        alerts: collect(),
        queueSize: 10,
        failedJobs: 0,
        databaseResponseTime: 100.0
    );
    expect($status->getCpuHealthLevel())->toBe(HealthLevel::GOOD);
    
    // Warning (75-89%)
    $status = new SystemHealthStatus(
        overall: HealthLevel::WARNING,
        cpuUsage: 80.0,
        memoryUsage: 50.0,
        diskUsage: 50.0,
        activeTenants: 10,
        totalTenants: 20,
        totalUsers: 100,
        averageResponseTime: 1000.0,
        alerts: collect(),
        queueSize: 10,
        failedJobs: 0,
        databaseResponseTime: 100.0
    );
    expect($status->getCpuHealthLevel())->toBe(HealthLevel::WARNING);
    
    // Critical (>= 90%)
    $status = new SystemHealthStatus(
        overall: HealthLevel::CRITICAL,
        cpuUsage: 95.0,
        memoryUsage: 50.0,
        diskUsage: 50.0,
        activeTenants: 10,
        totalTenants: 20,
        totalUsers: 100,
        averageResponseTime: 1000.0,
        alerts: collect(),
        queueSize: 10,
        failedJobs: 0,
        databaseResponseTime: 100.0
    );
    expect($status->getCpuHealthLevel())->toBe(HealthLevel::CRITICAL);
});

test('calculates memory health levels for all thresholds', function () {
    // Excellent (< 60%)
    $status = new SystemHealthStatus(
        overall: HealthLevel::EXCELLENT,
        cpuUsage: 30.0,
        memoryUsage: 40.0,
        diskUsage: 50.0,
        activeTenants: 10,
        totalTenants: 20,
        totalUsers: 100,
        averageResponseTime: 1000.0,
        alerts: collect(),
        queueSize: 10,
        failedJobs: 0,
        databaseResponseTime: 100.0
    );
    expect($status->getMemoryHealthLevel())->toBe(HealthLevel::EXCELLENT);
    
    // Good (60-79%)
    $status = new SystemHealthStatus(
        overall: HealthLevel::GOOD,
        cpuUsage: 30.0,
        memoryUsage: 70.0,
        diskUsage: 50.0,
        activeTenants: 10,
        totalTenants: 20,
        totalUsers: 100,
        averageResponseTime: 1000.0,
        alerts: collect(),
        queueSize: 10,
        failedJobs: 0,
        databaseResponseTime: 100.0
    );
    expect($status->getMemoryHealthLevel())->toBe(HealthLevel::GOOD);
    
    // Warning (80-89%)
    $status = new SystemHealthStatus(
        overall: HealthLevel::WARNING,
        cpuUsage: 30.0,
        memoryUsage: 85.0,
        diskUsage: 50.0,
        activeTenants: 10,
        totalTenants: 20,
        totalUsers: 100,
        averageResponseTime: 1000.0,
        alerts: collect(),
        queueSize: 10,
        failedJobs: 0,
        databaseResponseTime: 100.0
    );
    expect($status->getMemoryHealthLevel())->toBe(HealthLevel::WARNING);
    
    // Critical (>= 90%)
    $status = new SystemHealthStatus(
        overall: HealthLevel::CRITICAL,
        cpuUsage: 30.0,
        memoryUsage: 95.0,
        diskUsage: 50.0,
        activeTenants: 10,
        totalTenants: 20,
        totalUsers: 100,
        averageResponseTime: 1000.0,
        alerts: collect(),
        queueSize: 10,
        failedJobs: 0,
        databaseResponseTime: 100.0
    );
    expect($status->getMemoryHealthLevel())->toBe(HealthLevel::CRITICAL);
});

test('calculates disk health levels for all thresholds', function () {
    // Excellent (< 70%)
    $status = new SystemHealthStatus(
        overall: HealthLevel::EXCELLENT,
        cpuUsage: 30.0,
        memoryUsage: 40.0,
        diskUsage: 50.0,
        activeTenants: 10,
        totalTenants: 20,
        totalUsers: 100,
        averageResponseTime: 1000.0,
        alerts: collect(),
        queueSize: 10,
        failedJobs: 0,
        databaseResponseTime: 100.0
    );
    expect($status->getDiskHealthLevel())->toBe(HealthLevel::EXCELLENT);
    
    // Good (70-84%)
    $status = new SystemHealthStatus(
        overall: HealthLevel::GOOD,
        cpuUsage: 30.0,
        memoryUsage: 40.0,
        diskUsage: 75.0,
        activeTenants: 10,
        totalTenants: 20,
        totalUsers: 100,
        averageResponseTime: 1000.0,
        alerts: collect(),
        queueSize: 10,
        failedJobs: 0,
        databaseResponseTime: 100.0
    );
    expect($status->getDiskHealthLevel())->toBe(HealthLevel::GOOD);
    
    // Warning (85-94%)
    $status = new SystemHealthStatus(
        overall: HealthLevel::WARNING,
        cpuUsage: 30.0,
        memoryUsage: 40.0,
        diskUsage: 90.0,
        activeTenants: 10,
        totalTenants: 20,
        totalUsers: 100,
        averageResponseTime: 1000.0,
        alerts: collect(),
        queueSize: 10,
        failedJobs: 0,
        databaseResponseTime: 100.0
    );
    expect($status->getDiskHealthLevel())->toBe(HealthLevel::WARNING);
    
    // Critical (>= 95%)
    $status = new SystemHealthStatus(
        overall: HealthLevel::CRITICAL,
        cpuUsage: 30.0,
        memoryUsage: 40.0,
        diskUsage: 98.0,
        activeTenants: 10,
        totalTenants: 20,
        totalUsers: 100,
        averageResponseTime: 1000.0,
        alerts: collect(),
        queueSize: 10,
        failedJobs: 0,
        databaseResponseTime: 100.0
    );
    expect($status->getDiskHealthLevel())->toBe(HealthLevel::CRITICAL);
});

test('calculates response time health levels for all thresholds', function () {
    // Excellent (< 1000ms)
    $status = new SystemHealthStatus(
        overall: HealthLevel::EXCELLENT,
        cpuUsage: 30.0,
        memoryUsage: 40.0,
        diskUsage: 50.0,
        activeTenants: 10,
        totalTenants: 20,
        totalUsers: 100,
        averageResponseTime: 500.0,
        alerts: collect(),
        queueSize: 10,
        failedJobs: 0,
        databaseResponseTime: 100.0
    );
    expect($status->getResponseTimeHealthLevel())->toBe(HealthLevel::EXCELLENT);
    
    // Good (1000-1999ms)
    $status = new SystemHealthStatus(
        overall: HealthLevel::GOOD,
        cpuUsage: 30.0,
        memoryUsage: 40.0,
        diskUsage: 50.0,
        activeTenants: 10,
        totalTenants: 20,
        totalUsers: 100,
        averageResponseTime: 1500.0,
        alerts: collect(),
        queueSize: 10,
        failedJobs: 0,
        databaseResponseTime: 100.0
    );
    expect($status->getResponseTimeHealthLevel())->toBe(HealthLevel::GOOD);
    
    // Warning (2000-4999ms)
    $status = new SystemHealthStatus(
        overall: HealthLevel::WARNING,
        cpuUsage: 30.0,
        memoryUsage: 40.0,
        diskUsage: 50.0,
        activeTenants: 10,
        totalTenants: 20,
        totalUsers: 100,
        averageResponseTime: 3000.0,
        alerts: collect(),
        queueSize: 10,
        failedJobs: 0,
        databaseResponseTime: 100.0
    );
    expect($status->getResponseTimeHealthLevel())->toBe(HealthLevel::WARNING);
    
    // Critical (>= 5000ms)
    $status = new SystemHealthStatus(
        overall: HealthLevel::CRITICAL,
        cpuUsage: 30.0,
        memoryUsage: 40.0,
        diskUsage: 50.0,
        activeTenants: 10,
        totalTenants: 20,
        totalUsers: 100,
        averageResponseTime: 6000.0,
        alerts: collect(),
        queueSize: 10,
        failedJobs: 0,
        databaseResponseTime: 100.0
    );
    expect($status->getResponseTimeHealthLevel())->toBe(HealthLevel::CRITICAL);
});

test('calculates queue health levels for all thresholds', function () {
    // Excellent (< 100 queue, < 5 failed)
    $status = new SystemHealthStatus(
        overall: HealthLevel::EXCELLENT,
        cpuUsage: 30.0,
        memoryUsage: 40.0,
        diskUsage: 50.0,
        activeTenants: 10,
        totalTenants: 20,
        totalUsers: 100,
        averageResponseTime: 500.0,
        alerts: collect(),
        queueSize: 50,
        failedJobs: 2,
        databaseResponseTime: 100.0
    );
    expect($status->getQueueHealthLevel())->toBe(HealthLevel::EXCELLENT);
    
    // Good (100-499 queue, 5-19 failed)
    $status = new SystemHealthStatus(
        overall: HealthLevel::GOOD,
        cpuUsage: 30.0,
        memoryUsage: 40.0,
        diskUsage: 50.0,
        activeTenants: 10,
        totalTenants: 20,
        totalUsers: 100,
        averageResponseTime: 500.0,
        alerts: collect(),
        queueSize: 200,
        failedJobs: 10,
        databaseResponseTime: 100.0
    );
    expect($status->getQueueHealthLevel())->toBe(HealthLevel::GOOD);
    
    // Warning (500-999 queue, 20-49 failed)
    $status = new SystemHealthStatus(
        overall: HealthLevel::WARNING,
        cpuUsage: 30.0,
        memoryUsage: 40.0,
        diskUsage: 50.0,
        activeTenants: 10,
        totalTenants: 20,
        totalUsers: 100,
        averageResponseTime: 500.0,
        alerts: collect(),
        queueSize: 750,
        failedJobs: 30,
        databaseResponseTime: 100.0
    );
    expect($status->getQueueHealthLevel())->toBe(HealthLevel::WARNING);
    
    // Critical (>= 1000 queue, >= 50 failed)
    $status = new SystemHealthStatus(
        overall: HealthLevel::CRITICAL,
        cpuUsage: 30.0,
        memoryUsage: 40.0,
        diskUsage: 50.0,
        activeTenants: 10,
        totalTenants: 20,
        totalUsers: 100,
        averageResponseTime: 500.0,
        alerts: collect(),
        queueSize: 1200,
        failedJobs: 75,
        databaseResponseTime: 100.0
    );
    expect($status->getQueueHealthLevel())->toBe(HealthLevel::CRITICAL);
});

test('calculates tenant activity percentage correctly', function () {
    $status = new SystemHealthStatus(
        overall: HealthLevel::GOOD,
        cpuUsage: 50.0,
        memoryUsage: 60.0,
        diskUsage: 70.0,
        activeTenants: 30,
        totalTenants: 40,
        totalUsers: 1000,
        averageResponseTime: 1000.0,
        alerts: collect(),
        queueSize: 50,
        failedJobs: 2,
        databaseResponseTime: 100.0
    );
    
    expect($status->getTenantActivityPercentage())->toBe(75.0);
});

test('calculates tenant activity percentage with zero tenants', function () {
    $status = new SystemHealthStatus(
        overall: HealthLevel::GOOD,
        cpuUsage: 50.0,
        memoryUsage: 60.0,
        diskUsage: 70.0,
        activeTenants: 0,
        totalTenants: 0,
        totalUsers: 0,
        averageResponseTime: 1000.0,
        alerts: collect(),
        queueSize: 50,
        failedJobs: 2,
        databaseResponseTime: 100.0
    );
    
    expect($status->getTenantActivityPercentage())->toBe(0.0);
});

test('filters critical alerts correctly', function () {
    $alerts = collect([
        ['severity' => 'info', 'message' => 'System started'],
        ['severity' => 'warning', 'message' => 'High CPU usage'],
        ['severity' => 'critical', 'message' => 'Disk space low'],
        ['severity' => 'critical', 'message' => 'Database connection failed'],
        ['severity' => 'warning', 'message' => 'Memory usage high'],
    ]);
    
    $status = new SystemHealthStatus(
        overall: HealthLevel::CRITICAL,
        cpuUsage: 50.0,
        memoryUsage: 60.0,
        diskUsage: 70.0,
        activeTenants: 10,
        totalTenants: 20,
        totalUsers: 100,
        averageResponseTime: 1000.0,
        alerts: $alerts,
        queueSize: 50,
        failedJobs: 2,
        databaseResponseTime: 100.0
    );
    
    $criticalAlerts = $status->getCriticalAlerts();
    
    expect($criticalAlerts)->toHaveCount(2)
        ->and($criticalAlerts->pluck('message')->toArray())->toContain('Disk space low')
        ->and($criticalAlerts->pluck('message')->toArray())->toContain('Database connection failed');
});

test('filters warning alerts correctly', function () {
    $alerts = collect([
        ['severity' => 'info', 'message' => 'System started'],
        ['severity' => 'warning', 'message' => 'High CPU usage'],
        ['severity' => 'critical', 'message' => 'Disk space low'],
        ['severity' => 'warning', 'message' => 'Memory usage high'],
    ]);
    
    $status = new SystemHealthStatus(
        overall: HealthLevel::WARNING,
        cpuUsage: 50.0,
        memoryUsage: 60.0,
        diskUsage: 70.0,
        activeTenants: 10,
        totalTenants: 20,
        totalUsers: 100,
        averageResponseTime: 1000.0,
        alerts: $alerts,
        queueSize: 50,
        failedJobs: 2,
        databaseResponseTime: 100.0
    );
    
    $warningAlerts = $status->getWarningAlerts();
    
    expect($warningAlerts)->toHaveCount(2)
        ->and($warningAlerts->pluck('message')->toArray())->toContain('High CPU usage')
        ->and($warningAlerts->pluck('message')->toArray())->toContain('Memory usage high');
});

test('detects when system has issues', function () {
    $alerts = collect([
        ['severity' => 'warning', 'message' => 'High CPU usage'],
    ]);
    
    // Has issues due to warning status
    $status = new SystemHealthStatus(
        overall: HealthLevel::WARNING,
        cpuUsage: 50.0,
        memoryUsage: 60.0,
        diskUsage: 70.0,
        activeTenants: 10,
        totalTenants: 20,
        totalUsers: 100,
        averageResponseTime: 1000.0,
        alerts: collect(),
        queueSize: 50,
        failedJobs: 2,
        databaseResponseTime: 100.0
    );
    expect($status->hasIssues())->toBeTrue();
    
    // Has issues due to critical status
    $status = new SystemHealthStatus(
        overall: HealthLevel::CRITICAL,
        cpuUsage: 50.0,
        memoryUsage: 60.0,
        diskUsage: 70.0,
        activeTenants: 10,
        totalTenants: 20,
        totalUsers: 100,
        averageResponseTime: 1000.0,
        alerts: collect(),
        queueSize: 50,
        failedJobs: 2,
        databaseResponseTime: 100.0
    );
    expect($status->hasIssues())->toBeTrue();
    
    // Has issues due to alerts
    $status = new SystemHealthStatus(
        overall: HealthLevel::GOOD,
        cpuUsage: 50.0,
        memoryUsage: 60.0,
        diskUsage: 70.0,
        activeTenants: 10,
        totalTenants: 20,
        totalUsers: 100,
        averageResponseTime: 1000.0,
        alerts: $alerts,
        queueSize: 50,
        failedJobs: 2,
        databaseResponseTime: 100.0
    );
    expect($status->hasIssues())->toBeTrue();
});

test('detects when system has no issues', function () {
    $status = new SystemHealthStatus(
        overall: HealthLevel::EXCELLENT,
        cpuUsage: 30.0,
        memoryUsage: 40.0,
        diskUsage: 50.0,
        activeTenants: 18,
        totalTenants: 20,
        totalUsers: 500,
        averageResponseTime: 500.0,
        alerts: collect(),
        queueSize: 25,
        failedJobs: 0,
        databaseResponseTime: 50.0
    );
    
    expect($status->hasIssues())->toBeFalse();
});

test('converts to array with all calculated values', function () {
    $alerts = collect([
        ['severity' => 'warning', 'message' => 'High CPU usage'],
        ['severity' => 'critical', 'message' => 'Disk space low'],
    ]);
    
    $status = new SystemHealthStatus(
        overall: HealthLevel::WARNING,
        cpuUsage: 80.0,
        memoryUsage: 85.0,
        diskUsage: 90.0,
        activeTenants: 30,
        totalTenants: 40,
        totalUsers: 1200,
        averageResponseTime: 3000.0,
        alerts: $alerts,
        queueSize: 750,
        failedJobs: 25,
        databaseResponseTime: 300.0
    );
    
    $array = $status->toArray();
    
    expect($array)->toBeArray()
        ->and($array['overall'])->toBe('warning')
        ->and($array['overall_label'])->toBeString()
        ->and($array['overall_color'])->toBe('warning')
        ->and($array['cpu_usage'])->toBe(80.0)
        ->and($array['cpu_health'])->toBe('warning')
        ->and($array['memory_usage'])->toBe(85.0)
        ->and($array['memory_health'])->toBe('warning')
        ->and($array['disk_usage'])->toBe(90.0)
        ->and($array['disk_health'])->toBe('warning')
        ->and($array['active_tenants'])->toBe(30)
        ->and($array['total_tenants'])->toBe(40)
        ->and($array['tenant_activity_percentage'])->toBe(75.0)
        ->and($array['total_users'])->toBe(1200)
        ->and($array['average_response_time'])->toBe(3000.0)
        ->and($array['response_time_health'])->toBe('warning')
        ->and($array['queue_size'])->toBe(750)
        ->and($array['failed_jobs'])->toBe(25)
        ->and($array['queue_health'])->toBe('warning')
        ->and($array['database_response_time'])->toBe(300.0)
        ->and($array['alerts_count'])->toBe(2)
        ->and($array['critical_alerts_count'])->toBe(1)
        ->and($array['warning_alerts_count'])->toBe(1)
        ->and($array['has_issues'])->toBeTrue();
});

test('value object is immutable', function () {
    $status = new SystemHealthStatus(
        overall: HealthLevel::GOOD,
        cpuUsage: 50.0,
        memoryUsage: 60.0,
        diskUsage: 70.0,
        activeTenants: 10,
        totalTenants: 20,
        totalUsers: 100,
        averageResponseTime: 1000.0,
        alerts: collect(),
        queueSize: 50,
        failedJobs: 2,
        databaseResponseTime: 100.0
    );
    
    // Verify properties are readonly by checking reflection
    $reflection = new ReflectionClass($status);
    expect($reflection->isReadOnly())->toBeTrue();
    
    foreach ($reflection->getProperties() as $property) {
        expect($property->isReadOnly())->toBeTrue();
    }
});