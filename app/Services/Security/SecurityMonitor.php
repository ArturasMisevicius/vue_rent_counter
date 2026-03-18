<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Enums\SecurityViolationSeverity;
use App\Enums\SecurityViolationType;
use App\Events\SecurityViolationDetected;
use App\Models\SecurityViolation;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

final class SecurityMonitor
{
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
}
