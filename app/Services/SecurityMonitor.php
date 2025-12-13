<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Security Monitoring Service
 * 
 * Centralized security event tracking and alerting system.
 * Monitors security-sensitive operations and provides real-time
 * threat detection and response capabilities.
 * 
 * FEATURES:
 * - Real-time security event tracking
 * - Automated threat detection
 * - Configurable alerting thresholds
 * - Security metrics collection
 * - Incident response automation
 * 
 * @package App\Services
 */
class SecurityMonitor
{
    private const CACHE_PREFIX = 'security_monitor:';
    private const METRICS_TTL = 86400; // 24 hours

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Track a security event with automatic threat assessment.
     */
    public function trackSecurityEvent(
        string $event, 
        array $context = [], 
        string $severity = 'medium'
    ): void {
        $enrichedContext = $this->enrichContext($context, $severity);
        
        // Log the security event
        $this->logger->warning("Security event: {$event}", $enrichedContext);
        
        // Update security metrics
        $this->updateSecurityMetrics($event, $severity);
        
        // Check if event requires immediate alerting
        if ($this->isHighRiskEvent($event, $enrichedContext)) {
            $this->triggerSecurityAlert($event, $enrichedContext);
        }
        
        // Check for attack patterns
        $this->detectAttackPatterns($event, $enrichedContext);
    }

    /**
     * Get security metrics for dashboard display.
     */
    public function getSecurityMetrics(int $hours = 24): array
    {
        $metrics = [];
        
        // Get event counts by type
        $eventTypes = [
            'sql_injection_attempt',
            'mass_assignment_attack',
            'authorization_bypass_attempt',
            'rate_limit_exceeded',
            'path_traversal_attempt',
            'xss_attempt',
            'csrf_token_mismatch',
            'suspicious_activity',
        ];
        
        foreach ($eventTypes as $eventType) {
            $metrics['events'][$eventType] = $this->getEventCount($eventType, $hours);
        }
        
        // Get hourly trends
        $metrics['trends'] = $this->getHourlyTrends($hours);
        
        // Get top threat sources
        $metrics['threat_sources'] = $this->getTopThreatSources($hours);
        
        // Get security score
        $metrics['security_score'] = $this->calculateSecurityScore($metrics);
        
        return $metrics;
    }

    /**
     * Check if an IP address should be blocked.
     */
    public function shouldBlockIP(string $ipAddress): bool
    {
        $ipHash = hash('sha256', $ipAddress . config('app.key'));
        $key = self::CACHE_PREFIX . "blocked_ips:{$ipHash}";
        
        return Cache::has($key);
    }

    /**
     * Block an IP address for suspicious activity.
     */
    public function blockIP(string $ipAddress, int $duration = 3600, string $reason = 'Suspicious activity'): void
    {
        $ipHash = hash('sha256', $ipAddress . config('app.key'));
        $key = self::CACHE_PREFIX . "blocked_ips:{$ipHash}";
        
        Cache::put($key, [
            'blocked_at' => now()->toISOString(),
            'reason' => $reason,
            'duration' => $duration,
        ], $duration);
        
        $this->logger->warning('IP address blocked', [
            'ip_hash' => $ipHash,
            'reason' => $reason,
            'duration' => $duration,
        ]);
    }

    /**
     * Get security alerts that need attention.
     */
    public function getActiveAlerts(): array
    {
        $alertsKey = self::CACHE_PREFIX . 'active_alerts';
        return Cache::get($alertsKey, []);
    }

    /**
     * Clear a security alert.
     */
    public function clearAlert(string $alertId): void
    {
        $alertsKey = self::CACHE_PREFIX . 'active_alerts';
        $alerts = Cache::get($alertsKey, []);
        
        unset($alerts[$alertId]);
        
        Cache::put($alertsKey, $alerts, self::METRICS_TTL);
    }

    /**
     * Enrich context with additional security information.
     */
    private function enrichContext(array $context, string $severity): array
    {
        return array_merge($context, [
            'timestamp' => now()->toISOString(),
            'severity' => $severity,
            'user_id' => auth()?->id(),
            'tenant_id' => auth()?->user()?->tenant_id,
            'ip_hash' => request()?->ip() ? hash('sha256', request()->ip() . config('app.key')) : null,
            'user_agent_hash' => request()?->userAgent() ? hash('sha256', request()->userAgent()) : null,
            'session_id' => session()?->getId(),
            'request_id' => request()?->header('X-Request-ID') ?? uniqid(),
        ]);
    }

    /**
     * Check if an event is considered high risk.
     */
    private function isHighRiskEvent(string $event, array $context): bool
    {
        $highRiskEvents = [
            'sql_injection_attempt',
            'mass_assignment_attack',
            'authorization_bypass_attempt',
            'path_traversal_attempt',
            'privilege_escalation_attempt',
        ];
        
        return in_array($event, $highRiskEvents) || 
               ($context['severity'] ?? 'medium') === 'critical';
    }

    /**
     * Trigger security alert for high-risk events.
     */
    private function triggerSecurityAlert(string $event, array $context): void
    {
        $alertId = uniqid('alert_');
        $alert = [
            'id' => $alertId,
            'event' => $event,
            'severity' => $context['severity'],
            'timestamp' => $context['timestamp'],
            'user_id' => $context['user_id'],
            'ip_hash' => $context['ip_hash'],
            'context' => $context,
        ];
        
        // Store alert
        $alertsKey = self::CACHE_PREFIX . 'active_alerts';
        $alerts = Cache::get($alertsKey, []);
        $alerts[$alertId] = $alert;
        Cache::put($alertsKey, $alerts, self::METRICS_TTL);
        
        // Log critical alert
        $this->logger->critical("Security alert triggered: {$event}", [
            'alert_id' => $alertId,
            'context' => $context,
        ]);
        
        // TODO: Integrate with external alerting systems (Slack, PagerDuty, etc.)
    }

    /**
     * Update security metrics counters.
     */
    private function updateSecurityMetrics(string $event, string $severity): void
    {
        // Increment event counter
        $eventKey = self::CACHE_PREFIX . "events:{$event}:count";
        Cache::increment($eventKey);
        
        // Increment severity counter
        $severityKey = self::CACHE_PREFIX . "severity:{$severity}:count";
        Cache::increment($severityKey);
        
        // Increment total counter
        $totalKey = self::CACHE_PREFIX . 'events:total:count';
        Cache::increment($totalKey);
        
        // Store hourly metrics for trending
        $hour = now()->format('Y-m-d-H');
        $hourlyKey = self::CACHE_PREFIX . "events:{$event}:hourly:{$hour}";
        Cache::increment($hourlyKey);
        
        // Set TTL on new keys
        if (!Cache::has($eventKey . ':ttl')) {
            Cache::put($eventKey . ':ttl', true, self::METRICS_TTL);
            Cache::expire($eventKey, self::METRICS_TTL);
        }
    }

    /**
     * Detect attack patterns based on event frequency and timing.
     */
    private function detectAttackPatterns(string $event, array $context): void
    {
        $ipHash = $context['ip_hash'] ?? null;
        $userId = $context['user_id'] ?? null;
        
        if (!$ipHash && !$userId) {
            return;
        }
        
        // Check for rapid-fire attacks from same source
        $identifier = $userId ? "user:{$userId}" : "ip:{$ipHash}";
        $recentEventsKey = self::CACHE_PREFIX . "recent_events:{$identifier}";
        
        $recentEvents = Cache::get($recentEventsKey, []);
        $recentEvents[] = [
            'event' => $event,
            'timestamp' => now()->timestamp,
        ];
        
        // Keep only events from last 5 minutes
        $cutoff = now()->subMinutes(5)->timestamp;
        $recentEvents = array_filter($recentEvents, fn($e) => $e['timestamp'] > $cutoff);
        
        Cache::put($recentEventsKey, $recentEvents, 300); // 5 minutes
        
        // Check for attack patterns
        if (count($recentEvents) >= 10) {
            $this->trackSecurityEvent('rapid_fire_attack_detected', [
                'source_identifier' => $identifier,
                'event_count' => count($recentEvents),
                'time_window' => '5_minutes',
            ], 'critical');
            
            // Auto-block if from IP
            if ($ipHash && !$userId) {
                $this->blockIP(
                    request()->ip(), 
                    3600, 
                    'Rapid-fire security events detected'
                );
            }
        }
    }

    /**
     * Get event count for a specific type and time period.
     */
    private function getEventCount(string $eventType, int $hours): int
    {
        $count = 0;
        $now = now();
        
        for ($i = 0; $i < $hours; $i++) {
            $hour = $now->subHours($i)->format('Y-m-d-H');
            $key = self::CACHE_PREFIX . "events:{$eventType}:hourly:{$hour}";
            $count += Cache::get($key, 0);
        }
        
        return $count;
    }

    /**
     * Get hourly security event trends.
     */
    private function getHourlyTrends(int $hours): array
    {
        $trends = [];
        $now = now();
        
        for ($i = $hours - 1; $i >= 0; $i--) {
            $hour = $now->copy()->subHours($i)->format('Y-m-d-H');
            $totalKey = self::CACHE_PREFIX . "events:total:hourly:{$hour}";
            
            $trends[] = [
                'hour' => $hour,
                'count' => Cache::get($totalKey, 0),
            ];
        }
        
        return $trends;
    }

    /**
     * Get top threat sources by event count.
     */
    private function getTopThreatSources(int $hours): array
    {
        // This would require more sophisticated tracking
        // For now, return placeholder data
        return [
            ['source' => 'automated_scanners', 'count' => 45],
            ['source' => 'suspicious_ips', 'count' => 23],
            ['source' => 'failed_logins', 'count' => 12],
        ];
    }

    /**
     * Calculate overall security score based on recent activity.
     */
    private function calculateSecurityScore(array $metrics): int
    {
        $totalEvents = array_sum($metrics['events']);
        
        // Base score of 100, subtract points for security events
        $score = 100;
        
        // Deduct points based on event severity and frequency
        $score -= min(50, $totalEvents * 2); // Max 50 point deduction
        
        // Bonus points for no high-severity events
        $highSeverityEvents = $metrics['events']['sql_injection_attempt'] ?? 0;
        $highSeverityEvents += $metrics['events']['mass_assignment_attack'] ?? 0;
        
        if ($highSeverityEvents === 0) {
            $score += 10;
        }
        
        return max(0, min(100, $score));
    }
}