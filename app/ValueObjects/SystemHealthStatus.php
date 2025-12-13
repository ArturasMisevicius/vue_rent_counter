<?php

declare(strict_types=1);

namespace App\ValueObjects;

use Illuminate\Support\Collection;

enum HealthLevel: string
{
    case EXCELLENT = 'excellent';
    case GOOD = 'good';
    case WARNING = 'warning';
    case CRITICAL = 'critical';
    
    public function getColor(): string
    {
        return match($this) {
            self::EXCELLENT => 'success',
            self::GOOD => 'info',
            self::WARNING => 'warning',
            self::CRITICAL => 'danger',
        };
    }
    
    public function getLabel(): string
    {
        return match($this) {
            self::EXCELLENT => __('superadmin.health.excellent'),
            self::GOOD => __('superadmin.health.good'),
            self::WARNING => __('superadmin.health.warning'),
            self::CRITICAL => __('superadmin.health.critical'),
        };
    }
}

final readonly class SystemHealthStatus
{
    public function __construct(
        public HealthLevel $overall,
        public float $cpuUsage,
        public float $memoryUsage,
        public float $diskUsage,
        public int $activeTenants,
        public int $totalTenants,
        public int $totalUsers,
        public float $averageResponseTime,
        public Collection $alerts,
        public int $queueSize,
        public int $failedJobs,
        public float $databaseResponseTime,
    ) {}
    
    public function getCpuHealthLevel(): HealthLevel
    {
        return match(true) {
            $this->cpuUsage >= 90 => HealthLevel::CRITICAL,
            $this->cpuUsage >= 75 => HealthLevel::WARNING,
            $this->cpuUsage >= 50 => HealthLevel::GOOD,
            default => HealthLevel::EXCELLENT,
        };
    }
    
    public function getMemoryHealthLevel(): HealthLevel
    {
        return match(true) {
            $this->memoryUsage >= 90 => HealthLevel::CRITICAL,
            $this->memoryUsage >= 80 => HealthLevel::WARNING,
            $this->memoryUsage >= 60 => HealthLevel::GOOD,
            default => HealthLevel::EXCELLENT,
        };
    }
    
    public function getDiskHealthLevel(): HealthLevel
    {
        return match(true) {
            $this->diskUsage >= 95 => HealthLevel::CRITICAL,
            $this->diskUsage >= 85 => HealthLevel::WARNING,
            $this->diskUsage >= 70 => HealthLevel::GOOD,
            default => HealthLevel::EXCELLENT,
        };
    }
    
    public function getResponseTimeHealthLevel(): HealthLevel
    {
        return match(true) {
            $this->averageResponseTime >= 5000 => HealthLevel::CRITICAL, // 5 seconds
            $this->averageResponseTime >= 2000 => HealthLevel::WARNING,  // 2 seconds
            $this->averageResponseTime >= 1000 => HealthLevel::GOOD,     // 1 second
            default => HealthLevel::EXCELLENT,
        };
    }
    
    public function getQueueHealthLevel(): HealthLevel
    {
        return match(true) {
            $this->queueSize >= 1000 || $this->failedJobs >= 50 => HealthLevel::CRITICAL,
            $this->queueSize >= 500 || $this->failedJobs >= 20 => HealthLevel::WARNING,
            $this->queueSize >= 100 || $this->failedJobs >= 5 => HealthLevel::GOOD,
            default => HealthLevel::EXCELLENT,
        };
    }
    
    public function getTenantActivityPercentage(): float
    {
        if ($this->totalTenants <= 0) {
            return 0.0;
        }
        
        return ($this->activeTenants / $this->totalTenants) * 100;
    }
    
    public function getCriticalAlerts(): Collection
    {
        return $this->alerts->filter(fn($alert) => $alert['severity'] === 'critical');
    }
    
    public function getWarningAlerts(): Collection
    {
        return $this->alerts->filter(fn($alert) => $alert['severity'] === 'warning');
    }
    
    public function hasIssues(): bool
    {
        return $this->overall === HealthLevel::WARNING 
            || $this->overall === HealthLevel::CRITICAL
            || $this->alerts->isNotEmpty();
    }
    
    public function toArray(): array
    {
        return [
            'overall' => $this->overall->value,
            'overall_label' => $this->overall->getLabel(),
            'overall_color' => $this->overall->getColor(),
            'cpu_usage' => $this->cpuUsage,
            'cpu_health' => $this->getCpuHealthLevel()->value,
            'memory_usage' => $this->memoryUsage,
            'memory_health' => $this->getMemoryHealthLevel()->value,
            'disk_usage' => $this->diskUsage,
            'disk_health' => $this->getDiskHealthLevel()->value,
            'active_tenants' => $this->activeTenants,
            'total_tenants' => $this->totalTenants,
            'tenant_activity_percentage' => $this->getTenantActivityPercentage(),
            'total_users' => $this->totalUsers,
            'average_response_time' => $this->averageResponseTime,
            'response_time_health' => $this->getResponseTimeHealthLevel()->value,
            'queue_size' => $this->queueSize,
            'failed_jobs' => $this->failedJobs,
            'queue_health' => $this->getQueueHealthLevel()->value,
            'database_response_time' => $this->databaseResponseTime,
            'alerts_count' => $this->alerts->count(),
            'critical_alerts_count' => $this->getCriticalAlerts()->count(),
            'warning_alerts_count' => $this->getWarningAlerts()->count(),
            'has_issues' => $this->hasIssues(),
        ];
    }
}