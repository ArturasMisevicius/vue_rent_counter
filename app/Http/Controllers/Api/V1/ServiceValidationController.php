<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ValidateMeterReadingRequest;
use App\Http\Requests\Api\V1\BatchValidateReadingsRequest;
use App\Http\Requests\Api\V1\ValidateRateChangeRequest;
use App\Models\MeterReading;
use App\Models\ServiceConfiguration;
use App\Services\ServiceValidationEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * API Controller for service validation operations.
 * 
 * Provides RESTful endpoints for validating meter readings, rate changes,
 * and batch operations with proper authorization and error handling.
 */
class ServiceValidationController extends Controller
{
    public function __construct(
        private readonly ServiceValidationEngine $validationEngine
    ) {}

    /**
     * Validate a single meter reading.
     * 
     * @param ValidateMeterReadingRequest $request
     * @param MeterReading $reading
     * @return JsonResponse
     */
    public function validateMeterReading(
        ValidateMeterReadingRequest $request,
        MeterReading $reading
    ): JsonResponse {
        $user = $request->user();

        if (! $user?->can('view', $reading)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => __('validation.unauthorized_operation'),
                ],
            ], 403);
        }

        // V1 API restriction: A user may validate only readings they entered.
        if (filled($reading->entered_by) && ((int) $reading->entered_by !== (int) $user->id)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => __('validation.unauthorized_operation'),
                ],
            ], 403);
        }
        
        try {
            $serviceConfigId = $request->validated()['service_configuration_id'] ?? null;
            $serviceConfig = $serviceConfigId 
                ? ServiceConfiguration::find($serviceConfigId)
                : null;

            $result = $this->validationEngine->validateMeterReading($reading, $serviceConfig);
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'meta' => [
                    'reading_id' => $reading->id,
                    'meter_id' => $reading->meter_id,
                    'validated_at' => now()->toISOString(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_FAILED',
                    'message' => __('validation.system_error'),
                    'details' => config('app.debug') ? $e->getMessage() : null,
                ],
            ], 422);
        }
    }

    /**
     * Batch validate multiple meter readings.
     * 
     * @param BatchValidateReadingsRequest $request
     * @return JsonResponse
     */
    public function batchValidateReadings(BatchValidateReadingsRequest $request): JsonResponse
    {
        try {
            $readingIds = $request->validated()['reading_ids'];
            $options = $request->validated()['validation_options'] ?? [];

            // Load readings with authorization check
            $readings = MeterReading::whereIn('id', $readingIds)->get();
            
            // Verify all readings exist
            if ($readings->count() !== count($readingIds)) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => __('validation.some_readings_not_found'),
                    ],
                ], 404);
            }

            // Authorization check for each reading
            foreach ($readings as $reading) {
                Gate::authorize('view', $reading);
            }

            $result = $this->validationEngine->batchValidateReadings($readings, $options);
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'meta' => [
                    'batch_size' => $readings->count(),
                    'validated_at' => now()->toISOString(),
                ],
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => __('validation.unauthorized_batch_operation'),
                ],
            ], 403);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'BATCH_VALIDATION_FAILED',
                    'message' => __('validation.batch_system_error'),
                    'details' => config('app.debug') ? $e->getMessage() : null,
                ],
            ], 422);
        }
    }

    /**
     * Validate rate change restrictions.
     * 
     * @param ValidateRateChangeRequest $request
     * @param ServiceConfiguration $serviceConfiguration
     * @return JsonResponse
     */
    public function validateRateChange(
        ValidateRateChangeRequest $request,
        ServiceConfiguration $serviceConfiguration
    ): JsonResponse {
        Gate::authorize('update', $serviceConfiguration);
        
        try {
            $newRateSchedule = $request->validated()['new_rate_schedule'];
            
            $result = $this->validationEngine->validateRateChangeRestrictions(
                $serviceConfiguration,
                $newRateSchedule
            );
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'meta' => [
                    'service_configuration_id' => $serviceConfiguration->id,
                    'validated_at' => now()->toISOString(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'RATE_VALIDATION_FAILED',
                    'message' => __('validation.rate_change_system_error'),
                    'details' => config('app.debug') ? $e->getMessage() : null,
                ],
            ], 422);
        }
    }

    /**
     * Get validation rules for a service configuration.
     * 
     * @param ServiceConfiguration $serviceConfiguration
     * @return JsonResponse
     */
    public function getValidationRules(ServiceConfiguration $serviceConfiguration): JsonResponse
    {
        Gate::authorize('view', $serviceConfiguration);
        
        try {
            $utilityService = $serviceConfiguration->utilityService;
            
            return response()->json([
                'success' => true,
                'data' => [
                    'service_configuration_id' => $serviceConfiguration->id,
                    'utility_service' => [
                        'id' => $utilityService->id,
                        'name' => $utilityService->name,
                        'unit_of_measurement' => $utilityService->unit_of_measurement,
                    ],
                    'validation_rules' => $serviceConfiguration->getMergedConfiguration(),
                    'effective_from' => $serviceConfiguration->effective_from?->toISOString(),
                    'last_updated' => $serviceConfiguration->updated_at->toISOString(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'RULES_RETRIEVAL_FAILED',
                    'message' => __('validation.rules_system_error'),
                    'details' => config('app.debug') ? $e->getMessage() : null,
                ],
            ], 500);
        }
    }

    /**
     * Health check for validation system.
     * 
     * @return JsonResponse
     */
    public function healthCheck(): JsonResponse
    {
        try {
            // Test basic validation functionality
            $testReading = new MeterReading([
                'value' => 100,
                'reading_date' => now(),
                'input_method' => \App\Enums\InputMethod::MANUAL,
                'validation_status' => \App\Enums\ValidationStatus::PENDING,
            ]);

            // Create a minimal test context
            $testResult = $this->validationEngine->validateMeterReading($testReading);
            
            $isHealthy = is_array($testResult) && isset($testResult['is_valid']);

            return response()->json([
                'success' => true,
                'data' => [
                    'status' => $isHealthy ? 'healthy' : 'unhealthy',
                    'validators' => [
                        'consumption' => 'active',
                        'seasonal' => 'active',
                        'data_quality' => 'active',
                        'business_rules' => 'active',
                        'input_method' => 'active',
                        'rate_change' => 'active',
                    ],
                    'cache_status' => [
                        'validation_rules' => 'healthy',
                        'historical_data' => 'healthy',
                        'hit_rate' => 0.85, // Placeholder - implement actual cache metrics
                    ],
                    'performance_metrics' => [
                        'average_validation_time_ms' => 42, // Placeholder
                        'success_rate' => 0.98, // Placeholder
                        'error_rate' => 0.02, // Placeholder
                    ],
                    'system_info' => [
                        'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 1),
                        'active_validations' => 0, // Placeholder
                        'queue_size' => 0, // Placeholder
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => [
                    'status' => 'unhealthy',
                    'error' => config('app.debug') ? $e->getMessage() : 'System error',
                ],
            ], 500);
        }
    }

    /**
     * Get validation metrics for monitoring.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getMetrics(Request $request): JsonResponse
    {
        try {
            $period = $request->query('period', 'last_24_hours');
            
            // Placeholder implementation - replace with actual metrics collection
            $metrics = [
                'period' => $period,
                'total_validations' => 1250,
                'success_rate' => 0.982,
                'average_response_time_ms' => 45,
                'cache_hit_rate' => 0.87,
                'error_breakdown' => [
                    'consumption_limit_exceeded' => 12,
                    'invalid_date' => 5,
                    'unauthorized' => 3,
                ],
                'performance_trends' => [
                    'response_time_trend' => 'stable',
                    'error_rate_trend' => 'decreasing',
                    'cache_efficiency_trend' => 'improving',
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $metrics,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'METRICS_RETRIEVAL_FAILED',
                    'message' => __('validation.metrics_system_error'),
                    'details' => config('app.debug') ? $e->getMessage() : null,
                ],
            ], 500);
        }
    }

    /**
     * Validate estimated readings with true-up calculations.
     * 
     * @param Request $request
     * @param MeterReading $estimatedReading
     * @return JsonResponse
     */
    public function validateEstimatedReading(
        Request $request,
        MeterReading $estimatedReading
    ): JsonResponse {
        Gate::authorize('view', $estimatedReading);
        
        try {
            $actualReadingId = $request->query('actual_reading_id');
            $actualReading = $actualReadingId 
                ? MeterReading::find($actualReadingId)
                : null;

            if ($actualReading) {
                Gate::authorize('view', $actualReading);
            }

            $result = $this->validationEngine->validateEstimatedReading(
                $estimatedReading,
                $actualReading
            );
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'meta' => [
                    'estimated_reading_id' => $estimatedReading->id,
                    'actual_reading_id' => $actualReading?->id,
                    'validated_at' => now()->toISOString(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ESTIMATED_VALIDATION_FAILED',
                    'message' => __('validation.estimated_reading_system_error'),
                    'details' => config('app.debug') ? $e->getMessage() : null,
                ],
            ], 422);
        }
    }
}
