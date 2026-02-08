<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Audit\UniversalServiceAuditReporter;
use App\Services\Audit\AuditAlertSystem;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Audit Report API Controller
 * 
 * Provides REST API endpoints for audit reporting and alert management.
 */
final class AuditReportController extends Controller
{
    public function __construct(
        private readonly UniversalServiceAuditReporter $auditReporter,
        private readonly AuditAlertSystem $alertSystem,
    ) {}

    /**
     * Generate comprehensive audit report.
     */
    public function generateReport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'nullable|integer|exists:teams,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'service_types' => 'nullable|array',
            'service_types.*' => 'string|in:electricity,water,heating,gas',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('validation.failed'),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $tenantId = $request->input('tenant_id');
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;
            $serviceTypes = $request->input('service_types', []);

            // Authorization check
            if ($tenantId && !$this->canAccessTenant($tenantId)) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.unauthorized'),
                ], 403);
            }

            $report = $this->auditReporter->generateReport(
                tenantId: $tenantId,
                startDate: $startDate,
                endDate: $endDate,
                serviceTypes: $serviceTypes,
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_changes' => $report->summary->totalChanges,
                        'user_changes' => $report->summary->userChanges,
                        'system_changes' => $report->summary->systemChanges,
                        'changes_per_day' => $report->summary->getChangesPerDay(),
                        'event_breakdown' => $report->summary->eventBreakdown,
                        'model_breakdown' => $report->summary->modelBreakdown,
                        'period_start' => $report->summary->periodStart->toISOString(),
                        'period_end' => $report->summary->periodEnd->toISOString(),
                    ],
                    'compliance' => [
                        'overall_score' => $report->complianceStatus->overallScore,
                        'overall_status' => $report->complianceStatus->getOverallStatus(),
                        'audit_trail_completeness' => $report->complianceStatus->auditTrailCompleteness,
                        'data_retention_compliance' => $report->complianceStatus->dataRetentionCompliance,
                        'regulatory_compliance' => $report->complianceStatus->regulatoryCompliance,
                        'security_compliance' => $report->complianceStatus->securityCompliance,
                        'data_quality_compliance' => $report->complianceStatus->dataQualityCompliance,
                        'recommendations' => $report->complianceStatus->recommendations,
                    ],
                    'performance' => [
                        'overall_score' => $report->performanceMetrics->getOverallScore(),
                        'performance_grade' => $report->performanceMetrics->getPerformanceGrade(),
                        'billing_performance' => $report->performanceMetrics->getBillingPerformanceScore(),
                        'system_response' => $report->performanceMetrics->getSystemResponseScore(),
                        'data_quality' => $report->performanceMetrics->getDataQualityScore(),
                        'operational_efficiency' => $report->performanceMetrics->getOperationalEfficiencyScore(),
                        'error_rate' => $report->performanceMetrics->getErrorRateScore(),
                        'metrics' => $report->performanceMetrics->metrics,
                    ],
                    'anomalies' => [
                        'total_count' => count($report->anomalies),
                        'critical_count' => count($report->getCriticalAnomalies()),
                        'high_count' => count($report->getHighSeverityAnomalies()),
                        'medium_count' => count($report->getMediumSeverityAnomalies()),
                        'low_count' => count($report->getLowSeverityAnomalies()),
                        'anomalies' => $report->anomalies,
                    ],
                    'configuration_changes' => $report->configurationChanges,
                    'generated_at' => $report->generatedAt->toISOString(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('audit.report_generation_failed'),
                'error' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get audit summary for dashboard.
     */
    public function getSummary(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'nullable|integer|exists:teams,id',
            'period' => 'nullable|string|in:24h,7d,30d,90d',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('validation.failed'),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $tenantId = $request->input('tenant_id');
            $period = $request->input('period', '30d');

            // Authorization check
            if ($tenantId && !$this->canAccessTenant($tenantId)) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.unauthorized'),
                ], 403);
            }

            $startDate = match ($period) {
                '24h' => now()->subHours(24),
                '7d' => now()->subDays(7),
                '30d' => now()->subDays(30),
                '90d' => now()->subDays(90),
                default => now()->subDays(30),
            };

            $report = $this->auditReporter->generateReport(
                tenantId: $tenantId,
                startDate: $startDate,
                endDate: now(),
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'total_changes' => $report->summary->totalChanges,
                    'compliance_score' => $report->complianceStatus->overallScore,
                    'performance_grade' => $report->performanceMetrics->getPerformanceGrade(),
                    'critical_anomalies' => count($report->getCriticalAnomalies()),
                    'period' => $period,
                    'generated_at' => $report->generatedAt->toISOString(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('audit.summary_generation_failed'),
                'error' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get anomaly details.
     */
    public function getAnomalies(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'nullable|integer|exists:teams,id',
            'severity' => 'nullable|string|in:critical,high,medium,low',
            'type' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('validation.failed'),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $tenantId = $request->input('tenant_id');
            $severity = $request->input('severity');
            $type = $request->input('type');
            $limit = $request->input('limit', 50);

            // Authorization check
            if ($tenantId && !$this->canAccessTenant($tenantId)) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.unauthorized'),
                ], 403);
            }

            $report = $this->auditReporter->generateReport(
                tenantId: $tenantId,
                startDate: now()->subDays(7), // Last 7 days for anomalies
                endDate: now(),
            );

            $anomalies = $report->anomalies;

            // Filter by severity
            if ($severity) {
                $anomalies = array_filter($anomalies, fn($a) => $a['severity'] === $severity);
            }

            // Filter by type
            if ($type) {
                $anomalies = array_filter($anomalies, fn($a) => $a['type'] === $type);
            }

            // Limit results
            $anomalies = array_slice($anomalies, 0, $limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'anomalies' => array_values($anomalies),
                    'total_count' => count($report->anomalies),
                    'filtered_count' => count($anomalies),
                    'filters' => [
                        'severity' => $severity,
                        'type' => $type,
                        'limit' => $limit,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('audit.anomalies_retrieval_failed'),
                'error' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Process alerts for a tenant.
     */
    public function processAlerts(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|integer|exists:teams,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('validation.failed'),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $tenantId = $request->input('tenant_id');

            // Authorization check
            if (!$this->canAccessTenant($tenantId)) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.unauthorized'),
                ], 403);
            }

            $this->alertSystem->processAlerts($tenantId);

            return response()->json([
                'success' => true,
                'message' => __('audit.alerts_processed'),
                'data' => [
                    'tenant_id' => $tenantId,
                    'processed_at' => now()->toISOString(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('audit.alert_processing_failed'),
                'error' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get alert configuration for a tenant.
     */
    public function getAlertConfiguration(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|integer|exists:teams,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('validation.failed'),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $tenantId = $request->input('tenant_id');

            // Authorization check
            if (!$this->canAccessTenant($tenantId)) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.unauthorized'),
                ], 403);
            }

            $config = $this->alertSystem->getAlertConfiguration($tenantId);

            return response()->json([
                'success' => true,
                'data' => $config,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('audit.config_retrieval_failed'),
                'error' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Update alert configuration for a tenant.
     */
    public function updateAlertConfiguration(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|integer|exists:teams,id',
            'critical_anomaly_threshold' => 'nullable|integer|min:0',
            'compliance_threshold' => 'nullable|numeric|min:0|max:100',
            'performance_threshold' => 'nullable|numeric|min:0|max:100',
            'change_frequency_threshold' => 'nullable|integer|min:1',
            'alert_cooldown_hours' => 'nullable|integer|min:1|max:24',
            'notification_channels' => 'nullable|array',
            'notification_channels.*' => 'string|in:mail,database,slack',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('validation.failed'),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $tenantId = $request->input('tenant_id');

            // Authorization check
            if (!$this->canAccessTenant($tenantId)) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.unauthorized'),
                ], 403);
            }

            $config = $request->only([
                'critical_anomaly_threshold',
                'compliance_threshold',
                'performance_threshold',
                'change_frequency_threshold',
                'alert_cooldown_hours',
                'notification_channels',
            ]);

            // Remove null values
            $config = array_filter($config, fn($value) => $value !== null);

            $this->alertSystem->updateAlertConfiguration($tenantId, $config);

            return response()->json([
                'success' => true,
                'message' => __('audit.config_updated'),
                'data' => [
                    'tenant_id' => $tenantId,
                    'updated_config' => $config,
                    'updated_at' => now()->toISOString(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('audit.config_update_failed'),
                'error' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Export audit report in various formats.
     */
    public function exportReport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_id' => 'nullable|integer|exists:teams,id',
            'format' => 'required|string|in:json,csv,pdf',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'include_anomalies' => 'nullable|boolean',
            'include_performance' => 'nullable|boolean',
            'include_compliance' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('validation.failed'),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $tenantId = $request->input('tenant_id');
            $format = $request->input('format');
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

            // Authorization check
            if ($tenantId && !$this->canAccessTenant($tenantId)) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.unauthorized'),
                ], 403);
            }

            $report = $this->auditReporter->generateReport(
                tenantId: $tenantId,
                startDate: $startDate,
                endDate: $endDate,
            );

            // For now, return JSON format (CSV and PDF export can be implemented later)
            $exportData = [
                'report_metadata' => [
                    'tenant_id' => $tenantId,
                    'format' => $format,
                    'generated_at' => $report->generatedAt->toISOString(),
                    'period' => [
                        'start' => $report->summary->periodStart->toISOString(),
                        'end' => $report->summary->periodEnd->toISOString(),
                    ],
                ],
                'summary' => $report->summary,
            ];

            if ($request->input('include_compliance', true)) {
                $exportData['compliance'] = $report->complianceStatus;
            }

            if ($request->input('include_performance', true)) {
                $exportData['performance'] = $report->performanceMetrics;
            }

            if ($request->input('include_anomalies', true)) {
                $exportData['anomalies'] = $report->anomalies;
            }

            return response()->json([
                'success' => true,
                'data' => $exportData,
                'download_url' => null, // Could implement file generation and return URL
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('audit.export_failed'),
                'error' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Check if the current user can access the specified tenant.
     */
    private function canAccessTenant(int $tenantId): bool
    {
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }

        // SuperAdmin can access all tenants
        if ($user->hasRole('superadmin')) {
            return true;
        }

        // Check if user belongs to the tenant
        return $user->teams()->where('teams.id', $tenantId)->exists();
    }
}