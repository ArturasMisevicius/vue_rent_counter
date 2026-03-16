<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Carbon\Carbon;
use App\Enums\TenantStatus;

final readonly class TenantMetrics
{
    public function __construct(
        public int $totalUsers,
        public int $activeUsers,
        public float $storageUsedMB,
        public float $storageQuotaMB,
        public int $apiCallsToday,
        public int $apiCallsQuota,
        public float $monthlyRevenue,
        public Carbon $lastActivity,
        public TenantStatus $status,
        public int $totalProperties,
        public int $totalInvoices,
        public float $averageResponseTime,
    ) {}
    
    public function getStorageUsagePercentage(): float
    {
        if ($this->storageQuotaMB <= 0) {
            return 0.0;
        }
        
        return min(100.0, ($this->storageUsedMB / $this->storageQuotaMB) * 100);
    }
    
    public function getApiUsagePercentage(): float
    {
        if ($this->apiCallsQuota <= 0) {
            return 0.0;
        }
        
        return min(100.0, ($this->apiCallsToday / $this->apiCallsQuota) * 100);
    }
    
    public function getUserUtilizationPercentage(): float
    {
        if ($this->totalUsers <= 0) {
            return 0.0;
        }
        
        return ($this->activeUsers / $this->totalUsers) * 100;
    }
    
    public function isStorageNearLimit(): bool
    {
        return $this->getStorageUsagePercentage() >= 80.0;
    }
    
    public function isApiNearLimit(): bool
    {
        return $this->getApiUsagePercentage() >= 80.0;
    }
    
    public function getDaysSinceLastActivity(): int
    {
        return (int) now()->diffInDays($this->lastActivity, true);
    }
    
    public function isHealthy(): bool
    {
        return $this->status === TenantStatus::ACTIVE
            && !$this->isStorageNearLimit()
            && !$this->isApiNearLimit()
            && $this->getDaysSinceLastActivity() <= 7
            && $this->averageResponseTime < 2000; // 2 seconds
    }
    
    public function toArray(): array
    {
        return [
            'total_users' => $this->totalUsers,
            'active_users' => $this->activeUsers,
            'storage_used_mb' => $this->storageUsedMB,
            'storage_quota_mb' => $this->storageQuotaMB,
            'api_calls_today' => $this->apiCallsToday,
            'api_calls_quota' => $this->apiCallsQuota,
            'monthly_revenue' => $this->monthlyRevenue,
            'last_activity' => $this->lastActivity->toISOString(),
            'status' => $this->status->value,
            'total_properties' => $this->totalProperties,
            'total_invoices' => $this->totalInvoices,
            'average_response_time' => $this->averageResponseTime,
            'storage_usage_percentage' => $this->getStorageUsagePercentage(),
            'api_usage_percentage' => $this->getApiUsagePercentage(),
            'user_utilization_percentage' => $this->getUserUtilizationPercentage(),
            'is_healthy' => $this->isHealthy(),
        ];
    }
}
