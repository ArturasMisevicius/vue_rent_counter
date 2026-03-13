<?php

use App\ValueObjects\TenantMetrics;
use App\Enums\TenantStatus;
use Carbon\Carbon;

test('creates tenant metrics with all properties', function () {
    $lastActivity = Carbon::now()->subHours(2);
    
    $metrics = new TenantMetrics(
        totalUsers: 50,
        activeUsers: 35,
        storageUsedMB: 1024.5,
        storageQuotaMB: 2048.0,
        apiCallsToday: 750,
        apiCallsQuota: 1000,
        monthlyRevenue: 299.99,
        lastActivity: $lastActivity,
        status: TenantStatus::ACTIVE,
        totalProperties: 125,
        totalInvoices: 89,
        averageResponseTime: 850.5
    );
    
    expect($metrics->totalUsers)->toBe(50)
        ->and($metrics->activeUsers)->toBe(35)
        ->and($metrics->storageUsedMB)->toBe(1024.5)
        ->and($metrics->storageQuotaMB)->toBe(2048.0)
        ->and($metrics->apiCallsToday)->toBe(750)
        ->and($metrics->apiCallsQuota)->toBe(1000)
        ->and($metrics->monthlyRevenue)->toBe(299.99)
        ->and($metrics->lastActivity)->toEqual($lastActivity)
        ->and($metrics->status)->toBe(TenantStatus::ACTIVE)
        ->and($metrics->totalProperties)->toBe(125)
        ->and($metrics->totalInvoices)->toBe(89)
        ->and($metrics->averageResponseTime)->toBe(850.5);
});

test('calculates storage usage percentage correctly', function () {
    $metrics = new TenantMetrics(
        totalUsers: 10,
        activeUsers: 8,
        storageUsedMB: 512.0,
        storageQuotaMB: 1024.0,
        apiCallsToday: 100,
        apiCallsQuota: 1000,
        monthlyRevenue: 99.99,
        lastActivity: Carbon::now(),
        status: TenantStatus::ACTIVE,
        totalProperties: 50,
        totalInvoices: 25,
        averageResponseTime: 500.0
    );
    
    expect($metrics->getStorageUsagePercentage())->toBe(50.0);
});

test('calculates storage usage percentage with zero quota', function () {
    $metrics = new TenantMetrics(
        totalUsers: 10,
        activeUsers: 8,
        storageUsedMB: 512.0,
        storageQuotaMB: 0.0,
        apiCallsToday: 100,
        apiCallsQuota: 1000,
        monthlyRevenue: 99.99,
        lastActivity: Carbon::now(),
        status: TenantStatus::ACTIVE,
        totalProperties: 50,
        totalInvoices: 25,
        averageResponseTime: 500.0
    );
    
    expect($metrics->getStorageUsagePercentage())->toBe(0.0);
});

test('caps storage usage percentage at 100', function () {
    $metrics = new TenantMetrics(
        totalUsers: 10,
        activeUsers: 8,
        storageUsedMB: 2048.0,
        storageQuotaMB: 1024.0,
        apiCallsToday: 100,
        apiCallsQuota: 1000,
        monthlyRevenue: 99.99,
        lastActivity: Carbon::now(),
        status: TenantStatus::ACTIVE,
        totalProperties: 50,
        totalInvoices: 25,
        averageResponseTime: 500.0
    );
    
    expect($metrics->getStorageUsagePercentage())->toBe(100.0);
});

test('calculates API usage percentage correctly', function () {
    $metrics = new TenantMetrics(
        totalUsers: 10,
        activeUsers: 8,
        storageUsedMB: 512.0,
        storageQuotaMB: 1024.0,
        apiCallsToday: 750,
        apiCallsQuota: 1000,
        monthlyRevenue: 99.99,
        lastActivity: Carbon::now(),
        status: TenantStatus::ACTIVE,
        totalProperties: 50,
        totalInvoices: 25,
        averageResponseTime: 500.0
    );
    
    expect($metrics->getApiUsagePercentage())->toBe(75.0);
});

test('calculates API usage percentage with zero quota', function () {
    $metrics = new TenantMetrics(
        totalUsers: 10,
        activeUsers: 8,
        storageUsedMB: 512.0,
        storageQuotaMB: 1024.0,
        apiCallsToday: 750,
        apiCallsQuota: 0,
        monthlyRevenue: 99.99,
        lastActivity: Carbon::now(),
        status: TenantStatus::ACTIVE,
        totalProperties: 50,
        totalInvoices: 25,
        averageResponseTime: 500.0
    );
    
    expect($metrics->getApiUsagePercentage())->toBe(0.0);
});

test('caps API usage percentage at 100', function () {
    $metrics = new TenantMetrics(
        totalUsers: 10,
        activeUsers: 8,
        storageUsedMB: 512.0,
        storageQuotaMB: 1024.0,
        apiCallsToday: 1500,
        apiCallsQuota: 1000,
        monthlyRevenue: 99.99,
        lastActivity: Carbon::now(),
        status: TenantStatus::ACTIVE,
        totalProperties: 50,
        totalInvoices: 25,
        averageResponseTime: 500.0
    );
    
    expect($metrics->getApiUsagePercentage())->toBe(100.0);
});

test('calculates user utilization percentage correctly', function () {
    $metrics = new TenantMetrics(
        totalUsers: 20,
        activeUsers: 15,
        storageUsedMB: 512.0,
        storageQuotaMB: 1024.0,
        apiCallsToday: 500,
        apiCallsQuota: 1000,
        monthlyRevenue: 99.99,
        lastActivity: Carbon::now(),
        status: TenantStatus::ACTIVE,
        totalProperties: 50,
        totalInvoices: 25,
        averageResponseTime: 500.0
    );
    
    expect($metrics->getUserUtilizationPercentage())->toBe(75.0);
});

test('calculates user utilization percentage with zero users', function () {
    $metrics = new TenantMetrics(
        totalUsers: 0,
        activeUsers: 0,
        storageUsedMB: 512.0,
        storageQuotaMB: 1024.0,
        apiCallsToday: 500,
        apiCallsQuota: 1000,
        monthlyRevenue: 99.99,
        lastActivity: Carbon::now(),
        status: TenantStatus::ACTIVE,
        totalProperties: 50,
        totalInvoices: 25,
        averageResponseTime: 500.0
    );
    
    expect($metrics->getUserUtilizationPercentage())->toBe(0.0);
});

test('detects when storage is near limit', function () {
    $metrics = new TenantMetrics(
        totalUsers: 10,
        activeUsers: 8,
        storageUsedMB: 850.0, // 85% of 1000
        storageQuotaMB: 1000.0,
        apiCallsToday: 500,
        apiCallsQuota: 1000,
        monthlyRevenue: 99.99,
        lastActivity: Carbon::now(),
        status: TenantStatus::ACTIVE,
        totalProperties: 50,
        totalInvoices: 25,
        averageResponseTime: 500.0
    );
    
    expect($metrics->isStorageNearLimit())->toBeTrue();
});

test('detects when storage is not near limit', function () {
    $metrics = new TenantMetrics(
        totalUsers: 10,
        activeUsers: 8,
        storageUsedMB: 500.0, // 50% of 1000
        storageQuotaMB: 1000.0,
        apiCallsToday: 500,
        apiCallsQuota: 1000,
        monthlyRevenue: 99.99,
        lastActivity: Carbon::now(),
        status: TenantStatus::ACTIVE,
        totalProperties: 50,
        totalInvoices: 25,
        averageResponseTime: 500.0
    );
    
    expect($metrics->isStorageNearLimit())->toBeFalse();
});

test('detects when API usage is near limit', function () {
    $metrics = new TenantMetrics(
        totalUsers: 10,
        activeUsers: 8,
        storageUsedMB: 500.0,
        storageQuotaMB: 1000.0,
        apiCallsToday: 850, // 85% of 1000
        apiCallsQuota: 1000,
        monthlyRevenue: 99.99,
        lastActivity: Carbon::now(),
        status: TenantStatus::ACTIVE,
        totalProperties: 50,
        totalInvoices: 25,
        averageResponseTime: 500.0
    );
    
    expect($metrics->isApiNearLimit())->toBeTrue();
});

test('detects when API usage is not near limit', function () {
    $metrics = new TenantMetrics(
        totalUsers: 10,
        activeUsers: 8,
        storageUsedMB: 500.0,
        storageQuotaMB: 1000.0,
        apiCallsToday: 500, // 50% of 1000
        apiCallsQuota: 1000,
        monthlyRevenue: 99.99,
        lastActivity: Carbon::now(),
        status: TenantStatus::ACTIVE,
        totalProperties: 50,
        totalInvoices: 25,
        averageResponseTime: 500.0
    );
    
    expect($metrics->isApiNearLimit())->toBeFalse();
});

test('calculates days since last activity', function () {
    $lastActivity = Carbon::now()->subDays(3);
    
    $metrics = new TenantMetrics(
        totalUsers: 10,
        activeUsers: 8,
        storageUsedMB: 500.0,
        storageQuotaMB: 1000.0,
        apiCallsToday: 500,
        apiCallsQuota: 1000,
        monthlyRevenue: 99.99,
        lastActivity: $lastActivity,
        status: TenantStatus::ACTIVE,
        totalProperties: 50,
        totalInvoices: 25,
        averageResponseTime: 500.0
    );
    
    expect($metrics->getDaysSinceLastActivity())->toBe(3);
});

test('determines healthy tenant status', function () {
    $metrics = new TenantMetrics(
        totalUsers: 10,
        activeUsers: 8,
        storageUsedMB: 500.0, // 50% usage
        storageQuotaMB: 1000.0,
        apiCallsToday: 500, // 50% usage
        apiCallsQuota: 1000,
        monthlyRevenue: 99.99,
        lastActivity: Carbon::now()->subHours(2), // Recent activity
        status: TenantStatus::ACTIVE,
        totalProperties: 50,
        totalInvoices: 25,
        averageResponseTime: 1500.0 // Under 2 seconds
    );
    
    expect($metrics->isHealthy())->toBeTrue();
});

test('determines unhealthy tenant status due to inactive status', function () {
    $metrics = new TenantMetrics(
        totalUsers: 10,
        activeUsers: 8,
        storageUsedMB: 500.0,
        storageQuotaMB: 1000.0,
        apiCallsToday: 500,
        apiCallsQuota: 1000,
        monthlyRevenue: 99.99,
        lastActivity: Carbon::now()->subHours(2),
        status: TenantStatus::SUSPENDED, // Not active
        totalProperties: 50,
        totalInvoices: 25,
        averageResponseTime: 1500.0
    );
    
    expect($metrics->isHealthy())->toBeFalse();
});

test('determines unhealthy tenant status due to storage near limit', function () {
    $metrics = new TenantMetrics(
        totalUsers: 10,
        activeUsers: 8,
        storageUsedMB: 850.0, // 85% usage - near limit
        storageQuotaMB: 1000.0,
        apiCallsToday: 500,
        apiCallsQuota: 1000,
        monthlyRevenue: 99.99,
        lastActivity: Carbon::now()->subHours(2),
        status: TenantStatus::ACTIVE,
        totalProperties: 50,
        totalInvoices: 25,
        averageResponseTime: 1500.0
    );
    
    expect($metrics->isHealthy())->toBeFalse();
});

test('determines unhealthy tenant status due to slow response time', function () {
    $metrics = new TenantMetrics(
        totalUsers: 10,
        activeUsers: 8,
        storageUsedMB: 500.0,
        storageQuotaMB: 1000.0,
        apiCallsToday: 500,
        apiCallsQuota: 1000,
        monthlyRevenue: 99.99,
        lastActivity: Carbon::now()->subHours(2),
        status: TenantStatus::ACTIVE,
        totalProperties: 50,
        totalInvoices: 25,
        averageResponseTime: 3000.0 // Over 2 seconds
    );
    
    expect($metrics->isHealthy())->toBeFalse();
});

test('determines unhealthy tenant status due to old activity', function () {
    $metrics = new TenantMetrics(
        totalUsers: 10,
        activeUsers: 8,
        storageUsedMB: 500.0,
        storageQuotaMB: 1000.0,
        apiCallsToday: 500,
        apiCallsQuota: 1000,
        monthlyRevenue: 99.99,
        lastActivity: Carbon::now()->subDays(10), // Over 7 days
        status: TenantStatus::ACTIVE,
        totalProperties: 50,
        totalInvoices: 25,
        averageResponseTime: 1500.0
    );
    
    expect($metrics->isHealthy())->toBeFalse();
});

test('converts to array with all calculated values', function () {
    $lastActivity = Carbon::now()->subHours(2);
    
    $metrics = new TenantMetrics(
        totalUsers: 20,
        activeUsers: 15,
        storageUsedMB: 512.0,
        storageQuotaMB: 1024.0,
        apiCallsToday: 750,
        apiCallsQuota: 1000,
        monthlyRevenue: 299.99,
        lastActivity: $lastActivity,
        status: TenantStatus::ACTIVE,
        totalProperties: 125,
        totalInvoices: 89,
        averageResponseTime: 850.5
    );
    
    $array = $metrics->toArray();
    
    expect($array)->toBeArray()
        ->and($array['total_users'])->toBe(20)
        ->and($array['active_users'])->toBe(15)
        ->and($array['storage_used_mb'])->toBe(512.0)
        ->and($array['storage_quota_mb'])->toBe(1024.0)
        ->and($array['api_calls_today'])->toBe(750)
        ->and($array['api_calls_quota'])->toBe(1000)
        ->and($array['monthly_revenue'])->toBe(299.99)
        ->and($array['last_activity'])->toBe($lastActivity->toISOString())
        ->and($array['status'])->toBe('active')
        ->and($array['total_properties'])->toBe(125)
        ->and($array['total_invoices'])->toBe(89)
        ->and($array['average_response_time'])->toBe(850.5)
        ->and($array['storage_usage_percentage'])->toBe(50.0)
        ->and($array['api_usage_percentage'])->toBe(75.0)
        ->and($array['user_utilization_percentage'])->toBe(75.0)
        ->and($array['is_healthy'])->toBeTrue();
});

test('value object is immutable', function () {
    $metrics = new TenantMetrics(
        totalUsers: 10,
        activeUsers: 8,
        storageUsedMB: 500.0,
        storageQuotaMB: 1000.0,
        apiCallsToday: 500,
        apiCallsQuota: 1000,
        monthlyRevenue: 99.99,
        lastActivity: Carbon::now(),
        status: TenantStatus::ACTIVE,
        totalProperties: 50,
        totalInvoices: 25,
        averageResponseTime: 500.0
    );
    
    // Verify properties are readonly by checking reflection
    $reflection = new ReflectionClass($metrics);
    expect($reflection->isReadOnly())->toBeTrue();
    
    foreach ($reflection->getProperties() as $property) {
        expect($property->isReadOnly())->toBeTrue();
    }
});