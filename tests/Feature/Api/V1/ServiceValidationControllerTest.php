<?php

declare(strict_types=1);

use App\Models\MeterReading;
use App\Models\ServiceConfiguration;
use App\Models\User;
use App\Enums\InputMethod;
use App\Enums\ValidationStatus;

describe('ServiceValidationController API', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'sanctum');
    });

    describe('validateMeterReading', function () {
        it('validates a meter reading successfully', function () {
            $reading = MeterReading::factory()->create([
                'value' => 150,
                'input_method' => InputMethod::MANUAL,
                'validation_status' => ValidationStatus::PENDING,
            ]);

            $response = $this->postJson("/api/v1/validation/meter-reading/{$reading->id}");

            $response->assertOk()
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'is_valid',
                        'errors',
                        'warnings',
                        'metadata',
                    ],
                    'meta' => [
                        'reading_id',
                        'meter_id',
                        'validated_at',
                    ],
                ]);

            expect($response->json('success'))->toBeTrue();
        });

        it('returns validation errors for invalid readings', function () {
            $reading = MeterReading::factory()->create([
                'value' => 99999, // Exceeds maximum
                'input_method' => InputMethod::MANUAL,
                'validation_status' => ValidationStatus::PENDING,
            ]);

            $response = $this->postJson("/api/v1/validation/meter-reading/{$reading->id}");

            $response->assertOk();
            
            $data = $response->json('data');
            expect($data['is_valid'])->toBeFalse();
            expect($data['errors'])->not->toBeEmpty();
        });

        it('requires authorization to validate readings', function () {
            $otherUser = User::factory()->create();
            $reading = MeterReading::factory()->create(['entered_by' => $otherUser->id]);

            $response = $this->postJson("/api/v1/validation/meter-reading/{$reading->id}");

            $response->assertForbidden();
        });

        it('accepts service configuration override', function () {
            $reading = MeterReading::factory()->create();
            $serviceConfig = ServiceConfiguration::factory()->create();

            $response = $this->postJson("/api/v1/validation/meter-reading/{$reading->id}", [
                'service_configuration_id' => $serviceConfig->id,
                'validation_options' => [
                    'strict_mode' => true,
                    'include_recommendations' => true,
                ],
            ]);

            $response->assertOk();
            expect($response->json('success'))->toBeTrue();
        });
    });

    describe('batchValidateReadings', function () {
        it('validates multiple readings in batch', function () {
            $readings = MeterReading::factory()->count(5)->create([
                'entered_by' => $this->user->id,
            ]);

            $response = $this->postJson('/api/v1/validation/batch/meter-readings', [
                'reading_ids' => $readings->pluck('id')->toArray(),
                'validation_options' => [
                    'parallel_processing' => true,
                    'include_performance_metrics' => true,
                ],
            ]);

            $response->assertOk()
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'total_readings',
                        'valid_readings',
                        'invalid_readings',
                        'results',
                        'performance_metrics',
                    ],
                ]);

            $data = $response->json('data');
            expect($data['total_readings'])->toBe(5);
            expect($data['results'])->toHaveCount(5);
        });

        it('validates batch size limits', function () {
            $maxBatchSize = config('service_validation.performance.max_batch_size', 500);
            $readingIds = range(1, $maxBatchSize + 1);

            $response = $this->postJson('/api/v1/validation/batch/meter-readings', [
                'reading_ids' => $readingIds,
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['reading_ids']);
        });

        it('rejects empty batch requests', function () {
            $response = $this->postJson('/api/v1/validation/batch/meter-readings', [
                'reading_ids' => [],
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['reading_ids']);
        });

        it('handles non-existent reading IDs', function () {
            $response = $this->postJson('/api/v1/validation/batch/meter-readings', [
                'reading_ids' => [99999, 99998],
            ]);

            $response->assertNotFound();
        });
    });

    describe('validateRateChange', function () {
        it('validates rate change restrictions', function () {
            $serviceConfig = ServiceConfiguration::factory()->create();

            $response = $this->postJson("/api/v1/validation/rate-change/{$serviceConfig->id}", [
                'new_rate_schedule' => [
                    'rate_per_unit' => 0.15,
                    'monthly_rate' => 25.00,
                    'effective_from' => now()->addDays(7)->toDateString(),
                ],
            ]);

            $response->assertOk()
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'is_valid',
                        'errors',
                        'warnings',
                    ],
                ]);
        });

        it('requires authorization for rate change validation', function () {
            $otherUser = User::factory()->create();
            $serviceConfig = ServiceConfiguration::factory()->create(['tenant_id' => $otherUser->id]);

            $response = $this->postJson("/api/v1/validation/rate-change/{$serviceConfig->id}", [
                'new_rate_schedule' => ['rate_per_unit' => 0.15],
            ]);

            $response->assertForbidden();
        });
    });

    describe('getValidationRules', function () {
        it('returns validation rules for service configuration', function () {
            $serviceConfig = ServiceConfiguration::factory()->create();

            $response = $this->getJson("/api/v1/validation/rules/{$serviceConfig->id}");

            $response->assertOk()
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'service_configuration_id',
                        'utility_service',
                        'validation_rules',
                        'effective_from',
                        'last_updated',
                    ],
                ]);
        });
    });

    describe('healthCheck', function () {
        it('returns system health status', function () {
            $response = $this->getJson('/api/v1/validation/health');

            $response->assertOk()
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'status',
                        'validators',
                        'cache_status',
                        'performance_metrics',
                        'system_info',
                    ],
                ]);

            expect($response->json('data.status'))->toBeIn(['healthy', 'unhealthy']);
        });
    });

    describe('getMetrics', function () {
        it('returns validation metrics', function () {
            $response = $this->getJson('/api/v1/validation/metrics');

            $response->assertOk()
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'period',
                        'total_validations',
                        'success_rate',
                        'average_response_time_ms',
                        'cache_hit_rate',
                        'error_breakdown',
                        'performance_trends',
                    ],
                ]);
        });

        it('accepts period parameter', function () {
            $response = $this->getJson('/api/v1/validation/metrics?period=last_week');

            $response->assertOk();
            expect($response->json('data.period'))->toBe('last_week');
        });
    });

    describe('validateEstimatedReading', function () {
        it('validates estimated readings with true-up calculations', function () {
            $estimatedReading = MeterReading::factory()->create([
                'input_method' => InputMethod::ESTIMATED,
                'value' => 150,
            ]);

            $actualReading = MeterReading::factory()->create([
                'input_method' => InputMethod::MANUAL,
                'value' => 155,
                'meter_id' => $estimatedReading->meter_id,
            ]);

            $response = $this->postJson(
                "/api/v1/validation/estimated-reading/{$estimatedReading->id}",
                [],
                ['actual_reading_id' => $actualReading->id]
            );

            $response->assertOk()
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'is_valid',
                        'true_up_amount',
                        'adjustment_required',
                        'errors',
                        'warnings',
                        'metadata',
                    ],
                ]);
        });

        it('validates estimated readings without actual reading', function () {
            $estimatedReading = MeterReading::factory()->create([
                'input_method' => InputMethod::ESTIMATED,
            ]);

            $response = $this->postJson("/api/v1/validation/estimated-reading/{$estimatedReading->id}");

            $response->assertOk();
            
            $data = $response->json('data');
            expect($data['true_up_amount'])->toBeNull();
            expect($data['adjustment_required'])->toBeFalse();
        });
    });
});