<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Enums\SecuritySeverity;
use App\Enums\ThreatClassification;
use App\Models\SecurityViolation;
use App\Notifications\SecurityAlertNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Psr\Log\LoggerInterface;

/**
 * Security Monitoring Service
 * 
 * Provides real-time security monitoring, threat detection,
 * and automated incident response capabilities.
 */
final class SecurityMonitoringService
{
    private const ALERT_CACHE_PREFIX = 'security_alert:';
    private const THRESHOLD_CACHE_PREFIX = 'security_threshold:';
    
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly SecurityAnalyticsMcpService $analyticsService
    ) {}

    /**
     * Monitor security violations and trigger alerts.
     */
    public function monitorViolations(): void
    {
        $this->checkCriticalViolations();
        $this->checkViolationRates();
        $this->checkAnomalousPatterns();
        $this->checkComplianceThresholds();
    }

    /**
     * Check for critical security violations requiring immediate attention.
     */
    private function checkCriticalViolations(): void
    {
        $criticalViolations = SecurityViolation::where('severity_level', SecuritySeverity::CRITICAL)
            ->where('threat_classification', ThreatClassification::MALICIOUS)
            ->whereNull('resolved_at')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->get();

        foreach ($criticalViolations as $violation) {
            $this->triggerCriticalAlert($violation);
        }
    }

    /**
     * Check violation rates for potential attacks.
     */
    private function checkViolationRates(): void
    {
        $tenants = SecurityViolation::select('tenant_id')
            ->distinct()
            ->whereNotNull('tenant_id')
            ->pluck('tenant_id');

        foreach ($tenants as $tenantId) {
            $recentViolations = SecurityViolation::where('tenant_id', $tenantId)
                ->where('created_at', '>=', now()->subMinutes(10))
                ->count();

            if ($recentViolations >= 20) { // 20+ violations in 10 minutes
                $this->triggerRateAlert($tenantId, $recentViolations);
            }
        }
    }

    /**
     * Check for anomalous security patterns.
     */
    private function checkAnomalousPatterns(): void
    {
        try {
            $anomalies = $this->analyticsService->detectAnomalies([
                'window' => '1h',
                'sensitivity' => 'high',
            ]);

            foreach ($anomalies as $anomaly) {
                if ($anomaly['risk_score'] >= 0.8) {
                    $this->triggerAnomalyAlert($anomaly);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Anomaly detection failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check compliance thresholds.
     */
    private function checkComplianceThresholds(): void
    {
        $violationsByType = SecurityViolation::selectRaw('violation_type, COUNT(*) as count')
            ->where('created_at', '>=', now()->subHours(24))
            ->groupBy('violation_type')
            ->pluck('count', 'violation_type');

        foreach ($violationsByType as $type => $count) {
            $threshold = $this->getComplianceThreshold($type);
            
            if ($count > $threshold) {
                $this->triggerComplianceAlert($type, $count, $threshold);
            }
        }
    }

    /**
     * Trigger critical security alert.
     */
    private function triggerCriticalAlert(SecurityViolation $violation): void
    {
        $alertKey = self::ALERT_CACHE_PREFIX . 'critical:' . $violation->id;
        
        if (Cache::has($alertKey)) {
            return; // Already alerted
        }

        Cache::put($alertKey, true, 3600); // Don't repeat for 1 hour

        $this->logger->critical('Critical security violation detected', [
            'violation_id' => $violation->id,
            'tenant_id' => $violation->tenant_id,
            'type' => $violation->violation_type,
            'severity' => $violation->severity_level->value,
            'classification' => $violation->threat_classification->value,
            'blocked_uri' => $this->sanitizeForLog($violation->blocked_uri),
            'document_uri' => $this->sanitizeForLog($violation->document_uri),
        ]);

        // Send notifications to security team
        $this->sendSecurityNotification('critical_violation', [
            'violation' => $violation,
            'message' => 'Critical security violation requires immediate attention',
        ]);

        // Auto-block if configured
        if (config('security.analytics.anomaly_detection.auto_block_threshold', 10) <= 1) {
            $this->autoBlockThreat($violation);
        }
    }

    /**
     * Trigger rate-based alert.
     */
    private function triggerRateAlert(int $tenantId, int $violationCount): void
    {
        $alertKey = self::ALERT_CACHE_PREFIX . 'rate:' . $tenantId;
        
        if (Cache::has($alertKey)) {
            return; // Already alerted
        }

        Cache::put($alertKey, true, 600); // Don't repeat for 10 minutes

        $this->logger->warning('High violation rate detected', [
            'tenant_id' => $tenantId,
            'violation_count' => $violationCount,
            'time_window' => '10 minutes',
        ]);

        $this->sendSecurityNotification('high_violation_rate', [
            'tenant_id' => $tenantId,
            'count' => $violationCount,
            'message' => "High violation rate: {$violationCount} violations in 10 minutes",
        ]);
    }

    /**
     * Trigger anomaly alert.
     */
    private function triggerAnomalyAlert(array $anomaly): void
    {
        $alertKey = self::ALERT_CACHE_PREFIX . 'anomaly:' . md5(json_encode($anomaly));
        
        if (Cache::has($alertKey)) {
            return; // Already alerted
        }

        Cache::put($alertKey, true, 1800); // Don't repeat for 30 minutes

        $this->logger->warning('Security anomaly detected', [
            'anomaly_type' => $anomaly['type'] ?? 'unknown',
            'risk_score' => $anomaly['risk_score'] ?? 0,
            'description' => $anomaly['description'] ?? 'No description',
        ]);

        $this->sendSecurityNotification('security_anomaly', [
            'anomaly' => $anomaly,
            'message' => 'Security anomaly detected with high risk score',
        ]);
    }

    /**
     * Trigger compliance alert.
     */
    private function triggerComplianceAlert(string $type, int $count, int $threshold): void
    {
        $alertKey = self::ALERT_CACHE_PREFIX . 'compliance:' . $type;
        
        if (Cache::has($alertKey)) {
            return; // Already alerted
        }

        Cache::put($alertKey, true, 3600); // Don't repeat for 1 hour

        $this->logger->warning('Compliance threshold exceeded', [
            'violation_type' => $type,
            'count' => $count,
            'threshold' => $threshold,
            'time_window' => '24 hours',
        ]);

        $this->sendSecurityNotification('compliance_threshold', [
            'type' => $type,
            'count' => $count,
            'threshold' => $threshold,
            'message' => "Compliance threshold exceeded for {$type}: {$count}/{$threshold}",
        ]);
    }

    /**
     * Auto-block threat based on violation.
     */
    private function autoBlockThreat(SecurityViolation $violation): void
    {
        // Extract IP from metadata if available
        $metadata = $violation->metadata;
        $ipHash = $metadata['ip_hash'] ?? null;

        if (!$ipHash) {
            return;
        }

        // Add to blocked IPs cache
        $blockKey = 'blocked_ip:' . $ipHash;
        Cache::put($blockKey, true, 3600); // Block for 1 hour

        $this->logger->info('Auto-blocked threat', [
            'violation_id' => $violation->id,
            'ip_hash' => $ipHash,
            'block_duration' => '1 hour',
        ]);
    }

    /**
     * Send security notification.
     */
    private function sendSecurityNotification(string $type, array $data): void
    {
        try {
            $recipients = $this->getSecurityNotificationRecipients();
            
            Notification::send($recipients, new SecurityAlertNotification($type, $data));
        } catch (\Exception $e) {
            $this->logger->error('Failed to send security notification', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get security notification recipients.
     */
    private function getSecurityNotificationRecipients(): array
    {
        // Return list of users who should receive security alerts
        return \App\Models\User::whereHas('permissions', function ($query) {
            $query->where('name', 'receive-security-alerts');
        })->get()->toArray();
    }

    /**
     * Get compliance threshold for violation type.
     */
    private function getComplianceThreshold(string $type): int
    {
        $thresholds = [
            'csp' => 100,
            'xss' => 10,
            'clickjacking' => 5,
            'mime_sniffing' => 50,
        ];

        return $thresholds[$type] ?? 20;
    }

    /**
     * Sanitize data for logging.
     */
    private function sanitizeForLog(?string $data): ?string
    {
        if (!$data) {
            return null;
        }

        // Remove potential sensitive information
        $data = preg_replace('/[?&]token=[^&]*/', '?token=***', $data);
        $data = preg_replace('/[?&]key=[^&]*/', '?key=***', $data);
        $data = preg_replace('/[?&]password=[^&]*/', '?password=***', $data);

        return substr($data, 0, 255);
    }

    /**
     * Get security monitoring statistics.
     */
    public function getMonitoringStats(): array
    {
        return [
            'total_violations_24h' => SecurityViolation::where('created_at', '>=', now()->subHours(24))->count(),
            'critical_violations_24h' => SecurityViolation::where('severity_level', SecuritySeverity::CRITICAL)
                ->where('created_at', '>=', now()->subHours(24))->count(),
            'unresolved_violations' => SecurityViolation::whereNull('resolved_at')->count(),
            'malicious_violations_24h' => SecurityViolation::where('threat_classification', ThreatClassification::MALICIOUS)
                ->where('created_at', '>=', now()->subHours(24))->count(),
            'alerts_sent_24h' => Cache::get('security_alerts_sent_24h', 0),
            'monitoring_status' => 'active',
            'last_check' => now()->toISOString(),
        ];
    }
}