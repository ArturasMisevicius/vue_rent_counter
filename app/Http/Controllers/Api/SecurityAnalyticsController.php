<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SecurityAnalyticsRequest;
use App\Models\SecurityViolation;
use App\Services\Security\SecurityAnalyticsMcpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Security Analytics API Controller
 * 
 * Provides REST API endpoints for security analytics data,
 * violation tracking, and real-time monitoring integration.
 */
final class SecurityAnalyticsController extends Controller
{
    public function __construct(
        private readonly SecurityAnalyticsMcpService $analyticsService
    ) {}

    /**
     * Get security violations with filtering and pagination.
     */
    public function violations(SecurityAnalyticsRequest $request): JsonResponse
    {
        // Authorize access to violations
        $this->authorize('viewAny', SecurityViolation::class);

        $query = SecurityViolation::query()
            ->select([
                'id', 'violation_type', 'policy_directive', 'severity_level',
                'threat_classification', 'resolved_at', 'created_at', 'tenant_id'
            ]) // Limit selected fields to prevent data exposure
            ->with(['tenant:id,name']) // Only load necessary tenant fields
            ->orderBy('created_at', 'desc');

        // Apply tenant scoping for non-superadmin users
        if (!$request->user()->isSuperAdmin()) {
            $query->where('tenant_id', $request->user()->tenant_id);
        }

        // Apply validated filters
        $validated = $request->validatedWithCasting();

        if (isset($validated['violation_type'])) {
            $query->ofType($validated['violation_type']);
        }

        if (isset($validated['severity_level'])) {
            $query->withSeverity($validated['severity_level']);
        }

        if (isset($validated['start_date'])) {
            $query->where('created_at', '>=', $validated['start_date']);
        }

        if (isset($validated['end_date'])) {
            $query->where('created_at', '<=', $validated['end_date']);
        }

        if ($validated['unresolved_only'] ?? false) {
            $query->unresolved();
        }

        if ($validated['resolved_only'] ?? false) {
            $query->whereNotNull('resolved_at');
        }

        // Apply sorting with validation
        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortDirection = $validated['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        $violations = $query->paginate(
            min($validated['per_page'] ?? 25, 100) // Cap at 100 items per page
        );

        // Transform data to remove sensitive information
        $transformedData = $violations->getCollection()->map(function ($violation) {
            return [
                'id' => $violation->id,
                'type' => $violation->violation_type,
                'directive' => $violation->policy_directive,
                'severity' => $violation->severity_level->value,
                'classification' => $violation->threat_classification->value,
                'resolved' => $violation->isResolved(),
                'created_at' => $violation->created_at->toISOString(),
                'tenant' => $violation->tenant ? [
                    'id' => $violation->tenant->id,
                    'name' => $violation->tenant->name,
                ] : null,
            ];
        });

        return response()->json([
            'data' => $transformedData,
            'meta' => [
                'total' => $violations->total(),
                'per_page' => $violations->perPage(),
                'current_page' => $violations->currentPage(),
                'last_page' => $violations->lastPage(),
                'from' => $violations->firstItem(),
                'to' => $violations->lastItem(),
            ],
        ]);
    }

    /**
     * Get security metrics and analytics.
     */
    public function metrics(SecurityAnalyticsRequest $request): JsonResponse
    {
        $filters = $request->validated();
        
        // Get metrics from MCP service
        $mcpMetrics = $this->analyticsService->analyzeSecurityMetrics($filters);

        // Get local database metrics
        $localMetrics = $this->getLocalMetrics($filters);

        return response()->json([
            'data' => [
                'mcp_analytics' => $mcpMetrics,
                'local_metrics' => $localMetrics,
                'generated_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Get real-time dashboard data.
     */
    public function dashboard(): JsonResponse
    {
        $dashboardData = [
            'summary' => $this->getDashboardSummary(),
            'recent_violations' => $this->getRecentViolations(),
            'severity_distribution' => $this->getSeverityDistribution(),
            'violation_trends' => $this->getViolationTrends(),
            'top_violation_types' => $this->getTopViolationTypes(),
        ];

        return response()->json([
            'data' => $dashboardData,
            'last_updated' => now()->toISOString(),
        ]);
    }

    /**
     * Report a CSP violation with enhanced security validation.
     */
    public function reportViolation(\App\Http\Requests\CspViolationRequest $request): JsonResponse
    {
        try {
            $violation = $this->analyticsService->processCspViolationFromRequest($request);

            if (!$violation) {
                // Log failed violation processing for security monitoring
                Log::warning('CSP violation processing failed', [
                    'ip_hash' => hash('sha256', $request->ip() . config('app.key')),
                    'user_agent_hash' => hash('sha256', $request->userAgent() . config('app.key')),
                    'timestamp' => now()->toISOString(),
                ]);

                return response()->json([
                    'error' => 'Unable to process violation report',
                ], 422);
            }

            // Return minimal response to prevent information disclosure
            return response()->json([
                'status' => 'processed',
                'timestamp' => now()->toISOString(),
            ], 201);

        } catch (\Exception $e) {
            // Log error without exposing details
            Log::error('CSP violation processing error', [
                'error_type' => get_class($e),
                'ip_hash' => hash('sha256', $request->ip() . config('app.key')),
                'timestamp' => now()->toISOString(),
            ]);

            return response()->json([
                'error' => 'Processing error occurred',
            ], 500);
        }
    }

    /**
     * Get anomaly detection results.
     */
    public function anomalies(SecurityAnalyticsRequest $request): JsonResponse
    {
        $parameters = $request->validated();
        
        $anomalies = $this->analyticsService->detectAnomalies($parameters);

        return response()->json([
            'data' => $anomalies,
            'detection_parameters' => $parameters,
            'detected_at' => now()->toISOString(),
        ]);
    }

    /**
     * Generate security report.
     */
    public function report(SecurityAnalyticsRequest $request): JsonResponse
    {
        $config = $request->validated();
        
        $report = $this->analyticsService->generateSecurityReport($config);

        return response()->json([
            'data' => $report,
            'report_config' => $config,
            'generated_at' => now()->toISOString(),
        ]);
    }

    /**
     * Get dashboard summary statistics.
     */
    private function getDashboardSummary(): array
    {
        $totalViolations = SecurityViolation::count();
        $recentViolations = SecurityViolation::recent(24)->count();
        $unresolvedViolations = SecurityViolation::unresolved()->count();
        $criticalViolations = SecurityViolation::withSeverity(\App\Enums\SecuritySeverity::CRITICAL)
            ->unresolved()
            ->count();

        return [
            'total_violations' => $totalViolations,
            'recent_violations_24h' => $recentViolations,
            'unresolved_violations' => $unresolvedViolations,
            'critical_unresolved' => $criticalViolations,
            'resolution_rate' => $totalViolations > 0 
                ? round((($totalViolations - $unresolvedViolations) / $totalViolations) * 100, 2)
                : 0,
        ];
    }

    /**
     * Get recent violations for dashboard.
     */
    private function getRecentViolations(): array
    {
        return SecurityViolation::recent(1)
            ->with(['tenant'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Get severity distribution.
     */
    private function getSeverityDistribution(): array
    {
        return SecurityViolation::selectRaw('severity_level, COUNT(*) as count')
            ->groupBy('severity_level')
            ->pluck('count', 'severity_level')
            ->toArray();
    }

    /**
     * Get violation trends over time.
     */
    private function getViolationTrends(): array
    {
        return SecurityViolation::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * Get top violation types.
     */
    private function getTopViolationTypes(): array
    {
        return SecurityViolation::selectRaw('violation_type, COUNT(*) as count')
            ->groupBy('violation_type')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->pluck('count', 'violation_type')
            ->toArray();
    }

    /**
     * Get local metrics from database.
     */
    private function getLocalMetrics(array $filters): array
    {
        $query = SecurityViolation::query();

        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        return [
            'total_violations' => $query->count(),
            'by_severity' => $query->selectRaw('severity_level, COUNT(*) as count')
                ->groupBy('severity_level')
                ->pluck('count', 'severity_level')
                ->toArray(),
            'by_type' => $query->selectRaw('violation_type, COUNT(*) as count')
                ->groupBy('violation_type')
                ->pluck('count', 'violation_type')
                ->toArray(),
        ];
    }
}