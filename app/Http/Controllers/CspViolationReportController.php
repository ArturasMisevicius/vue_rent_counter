<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\SecurityViolationSeverity;
use App\Enums\SecurityViolationType;
use App\Http\Requests\Security\CspViolationRequest;
use App\Services\Security\SecurityMonitor;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

final class CspViolationReportController extends Controller
{
    public function __invoke(
        CspViolationRequest $request,
        SecurityMonitor $securityMonitor,
    ): Response {
        $reportData = $request->reportData();
        $effectiveDirective = $reportData['effective_directive'] ?? $reportData['violated_directive'] ?? 'unknown-directive';
        $blockedUri = $reportData['blocked_uri'] ?? 'inline-resource';

        $securityMonitor->recordViolation(
            $request,
            SecurityViolationType::DATA_ACCESS,
            $this->severityForDirective($effectiveDirective),
            Str::limit(
                sprintf('CSP violation: %s blocked %s', $effectiveDirective, $blockedUri),
                255,
            ),
            [
                ...$reportData,
                'source' => 'csp-report',
            ],
        );

        return response()->noContent(202);
    }

    private function severityForDirective(string $directive): SecurityViolationSeverity
    {
        return str_contains($directive, 'script')
            ? SecurityViolationSeverity::HIGH
            : SecurityViolationSeverity::MEDIUM;
    }
}
