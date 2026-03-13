<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\MeterReading;
use App\Services\ServiceValidationEngine;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Security-focused tests for ServiceValidationEngine
 */
class ServiceValidationEngineSecurityTest extends TestCase
{
    private ServiceValidationEngine $validationEngine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validationEngine = app(ServiceValidationEngine::class);
    }

    #[Test]
    public function it_rejects_empty_collections_in_batch_validation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Readings collection cannot be empty');

        $this->validationEngine->batchValidateReadings(collect([]));
    }

    #[Test]
    public function it_rejects_invalid_models_in_batch_validation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('All items in readings collection must be MeterReading instances');

        $invalidCollection = collect([
            MeterReading::factory()->make(),
            (object) ['invalid' => 'model'],
            MeterReading::factory()->make(),
        ]);

        $this->validationEngine->batchValidateReadings($invalidCollection);
    }

    #[Test]
    public function it_enforces_batch_size_limits(): void
    {
        // Mock config to set a low batch size limit for testing
        config(['service_validation.performance.batch_validation_size' => 2]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Batch size (3) exceeds maximum allowed size (2)');

        $oversizedCollection = collect([
            MeterReading::factory()->make(),
            MeterReading::factory()->make(),
            MeterReading::factory()->make(),
        ]);

        $this->validationEngine->batchValidateReadings($oversizedCollection);
    }

    #[Test]
    public function it_accepts_valid_collections_within_limits(): void
    {
        config(['service_validation.performance.batch_validation_size' => 5]);

        $validCollection = collect([
            MeterReading::factory()->make(),
            MeterReading::factory()->make(),
        ]);

        $result = $this->validationEngine->batchValidateReadings($validCollection);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_readings', $result);
        $this->assertEquals(2, $result['total_readings']);
    }
}