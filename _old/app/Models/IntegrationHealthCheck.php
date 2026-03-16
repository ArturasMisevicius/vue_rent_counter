<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\IntegrationStatus;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Integration health check model for tracking external service health.
 * 
 * @property int $id
 * @property string $service_name
 * @property string $endpoint
 * @property IntegrationStatus $status
 * @property int|null $response_time_ms
 * @property string|null $error_message
 * @property \Carbon\Carbon $checked_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @package App\Models
 * @author Laravel Development Team
 * @since 1.0.0
 */
final class IntegrationHealthCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_name',
        'endpoint',
        'status',
        'response_time_ms',
        'error_message',
        'checked_at',
    ];

    protected $casts = [
        'status' => IntegrationStatus::class,
        'response_time_ms' => 'integer',
        'checked_at' => 'datetime',
    ];

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'service_name';
    }

    /**
     * Scope query to a specific service.
     */
    public function scopeForService($query, string $serviceName)
    {
        return $query->where('service_name', $serviceName);
    }

    /**
     * Scope query to recent checks.
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('checked_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope query to healthy checks.
     */
    public function scopeHealthy($query)
    {
        return $query->where('status', IntegrationStatus::HEALTHY);
    }

    /**
     * Scope query to unhealthy checks.
     */
    public function scopeUnhealthy($query)
    {
        return $query->whereIn('status', [
            IntegrationStatus::UNHEALTHY,
            IntegrationStatus::CIRCUIT_OPEN,
        ]);
    }

    /**
     * Get the average response time for a service.
     */
    public static function getAverageResponseTime(string $serviceName, int $hours = 24): float
    {
        return static::forService($serviceName)
            ->recent($hours)
            ->whereNotNull('response_time_ms')
            ->avg('response_time_ms') ?? 0.0;
    }

    /**
     * Get uptime percentage for a service.
     */
    public static function getUptimePercentage(string $serviceName, int $hours = 24): float
    {
        $total = static::forService($serviceName)->recent($hours)->count();
        
        if ($total === 0) {
            return 0.0;
        }

        $healthy = static::forService($serviceName)
            ->recent($hours)
            ->whereIn('status', [IntegrationStatus::HEALTHY, IntegrationStatus::DEGRADED])
            ->count();

        return round(($healthy / $total) * 100, 2);
    }

    /**
     * Get health trend for a service.
     * 
     * @return array<string, mixed>
     */
    public static function getHealthTrend(string $serviceName, int $hours = 24): array
    {
        $checks = static::forService($serviceName)
            ->recent($hours)
            ->orderBy('checked_at')
            ->get(['status', 'response_time_ms', 'checked_at']);

        $trend = [
            'improving' => false,
            'degrading' => false,
            'stable' => true,
            'recent_status_changes' => 0,
            'response_time_trend' => 'stable',
        ];

        if ($checks->count() < 2) {
            return $trend;
        }

        // Analyze status changes
        $statusChanges = 0;
        $previousStatus = null;

        foreach ($checks as $check) {
            if ($previousStatus && $previousStatus !== $check->status) {
                $statusChanges++;
            }
            $previousStatus = $check->status;
        }

        $trend['recent_status_changes'] = $statusChanges;
        $trend['stable'] = $statusChanges <= 1;

        // Analyze response time trend
        $responseTimes = $checks->whereNotNull('response_time_ms')->pluck('response_time_ms')->toArray();
        
        if (count($responseTimes) >= 3) {
            $firstHalf = array_slice($responseTimes, 0, intval(count($responseTimes) / 2));
            $secondHalf = array_slice($responseTimes, intval(count($responseTimes) / 2));

            $firstAvg = array_sum($firstHalf) / count($firstHalf);
            $secondAvg = array_sum($secondHalf) / count($secondHalf);

            $percentChange = (($secondAvg - $firstAvg) / $firstAvg) * 100;

            if ($percentChange > 20) {
                $trend['response_time_trend'] = 'degrading';
                $trend['degrading'] = true;
                $trend['stable'] = false;
            } elseif ($percentChange < -20) {
                $trend['response_time_trend'] = 'improving';
                $trend['improving'] = true;
                $trend['stable'] = false;
            }
        }

        return $trend;
    }
}