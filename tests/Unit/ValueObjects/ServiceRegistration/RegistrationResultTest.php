<?php

declare(strict_types=1);

namespace Tests\Unit\ValueObjects\ServiceRegistration;

use App\ValueObjects\ServiceRegistration\RegistrationResult;
use Tests\TestCase;

final class RegistrationResultTest extends TestCase
{
    public function test_creates_successful_result(): void
    {
        $result = RegistrationResult::success(5, 100.5);
        
        $this->assertEquals(5, $result->registered);
        $this->assertEquals(0, $result->skipped);
        $this->assertEquals([], $result->errors);
        $this->assertEquals(100.5, $result->durationMs);
        $this->assertTrue($result->isSuccessful());
        $this->assertFalse($result->hasErrors());
    }

    public function test_creates_result_with_errors(): void
    {
        $errors = ['Model1' => 'error1', 'Model2' => 'error2'];
        $result = RegistrationResult::withErrors(3, 2, $errors, 200.0);
        
        $this->assertEquals(3, $result->registered);
        $this->assertEquals(2, $result->skipped);
        $this->assertEquals($errors, $result->errors);
        $this->assertEquals(200.0, $result->durationMs);
        $this->assertFalse($result->isSuccessful());
        $this->assertTrue($result->hasErrors());
    }

    public function test_calculates_totals_correctly(): void
    {
        $result = new RegistrationResult(5, 3, ['error1', 'error2'], 150.0);
        
        $this->assertEquals(8, $result->getTotalProcessed());
        $this->assertEquals(2, $result->getErrorCount());
    }

    public function test_converts_to_array_with_calculated_fields(): void
    {
        $result = new RegistrationResult(8, 2, ['error1'], 100.0);
        $array = $result->toArray();
        
        $this->assertEquals([
            'registered' => 8,
            'skipped' => 2,
            'errors' => ['error1'],
            'duration_ms' => 100.0,
            'total_processed' => 10,
            'error_count' => 1,
            'success_rate' => 80.0,
        ], $array);
    }

    public function test_handles_zero_total_processed(): void
    {
        $result = new RegistrationResult(0, 0, [], 50.0);
        $array = $result->toArray();
        
        $this->assertEquals(0, $array['success_rate']);
    }
}