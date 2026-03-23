<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Enums\SecurityViolationSeverity;
use App\Enums\SecurityViolationType;
use App\Events\SecurityViolationDetected;
use App\Models\SecurityViolation;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class SecurityMonitoringService
{
    private const ROOT_ALERT_CACHE_PREFIX = 'security_monitor:';

    private const STREAM_CHUNK_SIZE = 500;

    private const CRITICAL_WINDOW_MINUTES = 5;

    private const RATE_WINDOW_MINUTES = 10;

    private const RATE_THRESHOLD = 20;

    private const CRITICAL_ALERT_TTL_SECONDS = 3600;

    private const RATE_ALERT_TTL_SECONDS = 600;

    public function recordViolation(
        Request $request,
        SecurityViolationType $type,
        SecurityViolationSeverity $severity,
        string $summary,
        array $metadata = [],
    ): SecurityViolation {
        $user = $request->user();

        $payload = array_filter([
            'organization_id' => $user?->organization_id,
            'user_id' => $user?->id,
            'type' => $type,
            'severity' => $severity,
            'ip_address' => $request->ip(),
            'summary' => $summary,
            'metadata' => array_filter([
                ...$metadata,
                'url' => Arr::get($metadata, 'url', $request->fullUrl()),
                'user_agent' => Arr::get($metadata, 'user_agent', $request->userAgent()),
            ], static fn (mixed $value): bool => $value !== null && $value !== ''),
            'occurred_at' => now(),
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        /** @var SecurityViolation $violation */
        $violation = SecurityViolation::query()->create($payload);

        SecurityViolationDetected::dispatch($violation);

        return $violation;
    }

    public function monitorViolations(): void
    {
        $this->checkCriticalViolations();
        $this->checkViolationRates();
    }

    /**
     * @param  array{registered?: int, skipped?: int, errors?: array<int, mixed>}  $policyResults
     * @param  array{registered?: int, skipped?: int, errors?: array<int, mixed>}  $gateResults
     */
    public function recordPolicyRegistration(array $policyResults, array $gateResults): ?SecurityViolation
    {
        $policyErrors = count($policyResults['errors'] ?? []);
        $gateErrors = count($gateResults['errors'] ?? []);
        $totalErrors = $policyErrors + $gateErrors;

        Log::info('Policy registration completed', [
            'policies_registered' => (int) ($policyResults['registered'] ?? 0),
            'gates_registered' => (int) ($gateResults['registered'] ?? 0),
            'total_skipped' => (int) ($policyResults['skipped'] ?? 0) + (int) ($gateResults['skipped'] ?? 0),
            'timestamp' => now()->toISOString(),
        ]);

        if ($totalErrors === 0) {
            return null;
        }

        return $this->recordViolation(
            $this->systemRequest(),
            SecurityViolationType::AUTHORIZATION,
            SecurityViolationSeverity::HIGH,
            'Policy registration failures detected',
            [
                'policy_errors' => $policyErrors,
                'gate_errors' => $gateErrors,
                'total_errors' => $totalErrors,
                'source' => 'policy-registration',
            ],
        );
    }

    /**
     * @return array{violations: array<string, int>, timestamp: string}
     */
    public function getSecurityMetrics(): array
    {
        $violations = $this->emptyViolationMetrics();

        SecurityViolation::query()
            ->select(['id', 'type'])
            ->lazyById(self::STREAM_CHUNK_SIZE)
            ->each(function (SecurityViolation $violation) use (&$violations): void {
                $type = $violation->type;

                $typeKey = $type instanceof SecurityViolationType
                    ? $type->value
                    : (string) $type;

                if (! array_key_exists($typeKey, $violations)) {
                    return;
                }

                $violations[$typeKey]++;
            });

        return [
            'violations' => $violations,
            'timestamp' => now()->toISOString(),
        ];
    }

    private function checkCriticalViolations(): void
    {
        SecurityViolation::query()
            ->select([
                'id',
                'organization_id',
                'user_id',
                'type',
            ])
            ->ofSeverity(SecurityViolationSeverity::CRITICAL)
            ->unresolved()
            ->occurredSince(now()->subMinutes(self::CRITICAL_WINDOW_MINUTES))
            ->lazyById(self::STREAM_CHUNK_SIZE)
            ->each(function (SecurityViolation $violation): void {
                $this->triggerCriticalAlert($violation);
            });
    }

    private function checkViolationRates(): void
    {
        $violationCounts = [];

        SecurityViolation::query()
            ->select(['id', 'organization_id'])
            ->whereNotNull('organization_id')
            ->occurredSince(now()->subMinutes(self::RATE_WINDOW_MINUTES))
            ->lazyById(self::STREAM_CHUNK_SIZE)
            ->each(function (SecurityViolation $violation) use (&$violationCounts): void {
                if ($violation->organization_id === null) {
                    return;
                }

                $organizationId = (int) $violation->organization_id;

                $violationCounts[$organizationId] = ($violationCounts[$organizationId] ?? 0) + 1;
            });

        foreach ($violationCounts as $organizationId => $count) {
            $count = (int) $count;

            if ($count >= self::RATE_THRESHOLD) {
                $this->triggerRateAlert((int) $organizationId, $count);
            }
        }
    }

    private function triggerCriticalAlert(SecurityViolation $violation): void
    {
        $alertKey = self::ROOT_ALERT_CACHE_PREFIX.'critical:'.$violation->id;

        if (! Cache::add($alertKey, true, self::CRITICAL_ALERT_TTL_SECONDS)) {
            $this->refreshAlertSuppression($alertKey, self::CRITICAL_ALERT_TTL_SECONDS);

            return;
        }

        Log::critical('Critical security violation detected', [
            'violation_id' => $violation->id,
            'organization_id' => $violation->organization_id,
            'tenant_user_id' => $violation->user_id,
            'type' => $violation->type,
        ]);
    }

    private function triggerRateAlert(int $organizationId, int $violationCount): void
    {
        $alertKey = self::ROOT_ALERT_CACHE_PREFIX.'rate:'.$organizationId;

        if (! Cache::add($alertKey, true, self::RATE_ALERT_TTL_SECONDS)) {
            $this->refreshAlertSuppression($alertKey, self::RATE_ALERT_TTL_SECONDS);

            return;
        }

        Log::warning('High security violation rate detected', [
            'organization_id' => $organizationId,
            'violation_count' => $violationCount,
            'window_minutes' => self::RATE_WINDOW_MINUTES,
        ]);
    }

    private function refreshAlertSuppression(string $alertKey, int $ttlInSeconds): void
    {
        if (Cache::touch($alertKey, $ttlInSeconds)) {
            return;
        }

        Cache::put($alertKey, true, $ttlInSeconds);
    }

    private function systemRequest(): Request
    {
        /** @var Request|null $request */
        $request = request();

        if ($request instanceof Request) {
            return $request;
        }

        return Request::create('/__system/security-monitor', 'POST', server: [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'security-monitor',
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function emptyViolationMetrics(): array
    {
        $metrics = [];

        foreach (SecurityViolationType::cases() as $type) {
            $metrics[$type->value] = 0;
        }

        return $metrics;
    }
}
