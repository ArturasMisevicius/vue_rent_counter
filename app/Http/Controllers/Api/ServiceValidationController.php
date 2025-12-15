<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\ValidationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\BatchValidateReadingsRequest;
use App\Http\Requests\BulkUpdateValidationStatusRequest;
use App\Http\Requests\GetReadingsByStatusRequest;
use App\Http\Requests\ValidateEstimatedReadingRequest;
use App\Http\Requests\ValidateRateChangeRequest;
use App\Models\MeterReading;
use App\Models\ServiceConfiguration;
use App\Services\ServiceValidationEngine;
use App\Services\SystemHealthService;
use App\Repositories\MeterReadingRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

/**
 * API Controller for Service Validation operations.
 * 
 * Provides RESTful endpoints for meter reading validation, batch operations,
 * rate change validation, and validation status management.
 * 
 * Follows SOLID principles with dependency injection and single responsibility.
 * Implements Repository pattern for data access and Strategy pattern for validation.
 */
class ServiceValidationController extends Controller
{
    public function __construct(
        private readonly ServiceValidationEngine $validationEngine,
        private readonly SystemHealthService $healthService,
        private readonly MeterReadingRepository $meterReadingRepository
    ) {}

    /**
     * Validate a single meter reading.
     * 
     * @param MeterReading $reading The meter reading to validate
     * @return JsonResponse Validation result with metadata
     */
    public function validateReading(MeterReading $reading): JsonResponse
    {
        if (! auth()->user()?->can('view', $reading)) {
            abort(403, 'This action is unauthorized.');
        }
        
        // Apply rate limiting for validation operations
        $this->applyRateLimit('validation', auth()->id());

        $result = $this->validationEngine->validateMeterReading($reading);

        return $this->successResponse($result, [
            'reading_id' => $reading->id,
            'validated_at' => now()->toISOString(),
        ]);
    }

    /**
     * Batch validate multiple meter readings.
     * 
     * @param BatchValidateReadingsRequest $request Validated request with reading IDs and options
     * @return JsonResponse Batch validation results with performance metrics
     */
    public function batchValidateReadings(BatchValidateReadingsRequest $request): JsonResponse
    {
        $readingIds = $request->validated('reading_ids');
        $options = $request->validated('options', []);

        // Apply stricter rate limiting for batch operations
        $this->applyRateLimit('batch_validation', auth()->id(), count($readingIds));

        $readings = $this->meterReadingRepository->findManyWithRelations($readingIds);

        // Authorize all readings using policy
        $this->authorizeReadings($readings);

        $result = $this->validationEngine->batchValidateReadings($readings, $options);

        return $this->successResponse($result, [
            'requested_count' => count($readingIds),
            'processed_count' => $readings->count(),
            'validated_at' => now()->toISOString(),
        ]);
    }

    /**
     * Validate rate change restrictions for a service configuration.
     */
    public function validateRateChange(
        ServiceConfiguration $serviceConfiguration,
        ValidateRateChangeRequest $request
    ): JsonResponse {
        if (! auth()->user()?->can('update', $serviceConfiguration)) {
            abort(403, 'This action is unauthorized.');
        }

        $rateSchedule = $request->validated('rate_schedule');

        $result = $this->validationEngine->validateRateChangeRestrictions(
            $serviceConfiguration,
            $rateSchedule
        );

        return response()->json([
            'data' => $result,
            'meta' => [
                'service_configuration_id' => $serviceConfiguration->id,
                'validated_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Get readings filtered by validation status with optimized pagination.
     * 
     * @param GetReadingsByStatusRequest $request Validated filter parameters and pagination options
     * @return JsonResponse Paginated readings with metadata
     */
    public function getReadingsByStatus(GetReadingsByStatusRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        $status = ValidationStatus::from($validated['status']);
        $filters = $this->buildFilters($validated);
        
        // Use repository for optimized pagination
        $paginatedReadings = $this->meterReadingRepository->getByStatusPaginated(
            $status,
            $filters,
            $validated['per_page']
        );

        return $this->paginatedResponse($paginatedReadings, [
            'status_filter' => $status->value,
        ]);
    }

    /**
     * Bulk update validation status for multiple readings.
     */
    public function bulkUpdateValidationStatus(BulkUpdateValidationStatusRequest $request): JsonResponse
    {
        $readingIds = $request->validated('reading_ids');
        $newStatus = ValidationStatus::from($request->validated('new_status'));

        $readings = MeterReading::whereIn('id', $readingIds)->get();

        // Authorize all readings
        foreach ($readings as $reading) {
            if (! auth()->user()?->can('update', $reading)) {
                abort(403, 'This action is unauthorized.');
            }
        }

        $result = $this->validationEngine->bulkUpdateValidationStatus(
            $readings,
            $newStatus,
            auth()->id()
        );

        return response()->json([
            'data' => $result,
            'meta' => [
                'total_requested' => count($readingIds),
                'updated_by' => auth()->id(),
                'updated_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Validate an estimated reading against an actual reading.
     */
    public function validateEstimatedReading(
        MeterReading $reading,
        ValidateEstimatedReadingRequest $request
    ): JsonResponse {
        if (! auth()->user()?->can('view', $reading)) {
            abort(403, 'This action is unauthorized.');
        }

        $actualReadingId = $request->validated('actual_reading_id');
        $actualReading = $actualReadingId ? MeterReading::findOrFail($actualReadingId) : null;

        if ($actualReading) {
            if (! auth()->user()?->can('view', $actualReading)) {
                abort(403, 'This action is unauthorized.');
            }
        }

        $result = $this->validationEngine->validateEstimatedReading($reading, $actualReading);

        return response()->json([
            'data' => $result,
            'meta' => [
                'estimated_reading_id' => $reading->id,
                'actual_reading_id' => $actualReading?->id,
                'validated_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Get validation system health check with caching.
     * 
     * @return JsonResponse System health status and metrics
     */
    public function healthCheck(): JsonResponse
    {
        // Cache health check results for 30 seconds to prevent excessive checks
        $healthData = Cache::remember('system_health_check', 30, function () {
            return $this->healthService->performHealthCheck();
        });

        return response()->json($healthData);
    }

    /**
     * Apply rate limiting for API operations.
     * 
     * @param string $operation Operation type for rate limiting
     * @param mixed $identifier User or IP identifier
     * @param int $weight Operation weight (default: 1)
     * @throws \Illuminate\Http\Exceptions\ThrottleRequestsException
     */
    private function applyRateLimit(string $operation, mixed $identifier, int $weight = 1): void
    {
        $key = "rate_limit:{$operation}:{$identifier}";

        $defaultLimit = (int) config('service_validation.api.rate_limit', 60);

        $maxAttempts = match ($operation) {
            'batch_validation' => (int) config('service_validation.api.batch_rate_limit', 10),
            'rate_change' => (int) config('service_validation.api.rate_change_rate_limit', 20),
            default => $defaultLimit,
        };

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            throw new \Illuminate\Http\Exceptions\ThrottleRequestsException('Too Many Attempts.');
        }

        RateLimiter::hit($key, 60); // 1 minute window
    }

    /**
     * Authorize multiple readings using policy.
     * 
     * @param \Illuminate\Support\Collection $readings
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    private function authorizeReadings(\Illuminate\Support\Collection $readings): void
    {
        foreach ($readings as $reading) {
            if (! auth()->user()?->can('view', $reading)) {
                abort(403, 'This action is unauthorized.');
            }
        }
    }



    /**
     * Build filters array from validated request data.
     * 
     * @param array $validated
     * @return array
     */
    private function buildFilters(array $validated): array
    {
        $filters = array_intersect_key($validated, array_flip([
            'date_from', 'date_to', 'input_method', 'meter_ids'
        ]));
        
        $filters['tenant_id'] = auth()->user()->tenant_id;
        
        return $filters;
    }

    /**
     * Return standardized success response.
     * 
     * @param mixed $data Response data
     * @param array $meta Additional metadata
     * @return JsonResponse
     */
    private function successResponse(mixed $data, array $meta = []): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => $meta,
        ]);
    }

    /**
     * Return standardized paginated response.
     * 
     * @param \Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator
     * @param array $meta Additional metadata
     * @return JsonResponse
     */
    private function paginatedResponse(\Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator, array $meta = []): JsonResponse
    {
        return response()->json([
            'data' => $paginator->items(),
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
            'meta' => array_merge([
                'current_page' => $paginator->currentPage(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
            ], $meta),
        ]);
    }
}
