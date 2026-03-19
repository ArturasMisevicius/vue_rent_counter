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

    private function checkCriticalViolations(): void
    {
        $criticalViolations = SecurityViolation::query()
            ->where('severity', SecurityViolationSeverity::CRITICAL)
            ->whereNull('resolved_at')
            ->where('occurred_at', '>=', now()->subMinutes(5))
            ->get();

        foreach ($criticalViolations as $violation) {
            $this->triggerCriticalAlert($violation);
        }
    }

    private function checkViolationRates(): void
    {
        $organizationIds = SecurityViolation::query()
            ->select('organization_id')
            ->distinct()
            ->whereNotNull('organization_id')
            ->pluck('organization_id');

        foreach ($organizationIds as $organizationId) {
            $count = SecurityViolation::query()
                ->where('organization_id', $organizationId)
                ->where('occurred_at', '>=', now()->subMinutes(10))
                ->count();

            if ($count >= 20) {
                $this->triggerRateAlert((int) $organizationId, $count);
            }
        }
    }

    private function triggerCriticalAlert(SecurityViolation $violation): void
    {
        $alertKey = self::ROOT_ALERT_CACHE_PREFIX.'critical:'.$violation->id;

        if (Cache::has($alertKey)) {
            return;
        }

        Cache::put($alertKey, true, 3600);

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

        if (Cache::has($alertKey)) {
            return;
        }

        Cache::put($alertKey, true, 600);

        Log::warning('High security violation rate detected', [
            'organization_id' => $organizationId,
            'violation_count' => $violationCount,
            'window_minutes' => 10,
        ]);
    }
}

