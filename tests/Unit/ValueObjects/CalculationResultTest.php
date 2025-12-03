<?php

declare(strict_types=1);

namespace Tests\Unit\ValueObjects;

use App\Models\Building;
use App\ValueObjects\CalculationResult;
use Tests\TestCase;

final class CalculationResultTest extends TestCase
{
    private Building $building;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock building without database interaction
        $this->building = new Building();
        $this->building->id = 1;
        $this->building->name = 'Test Building';
    }

    public function test_creates_success_result(): void
    {
        $result = CalculationResult::success($this->building, 123.45);

        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isSkipped());
        $this->assertFalse($result->isFailed());
        $this->assertEquals('success', $result->status);
        $this->assertEquals(123.45, $result->average);
        $this->assertNull($result->errorMessage);
    }

    public function test_creates_skipped_result(): void
    {
        $result = CalculationResult::skipped($this->building, 'Already calculated');

        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isSkipped());
        $this->assertFalse($result->isFailed());
        $this->assertEquals('skipped', $result->status);
        $this->assertNull($result->average);
        $this->assertEquals('Already calculated', $result->errorMessage);
    }

    public function test_creates_failed_result(): void
    {
        $result = CalculationResult::failed($this->building, 'Calculation error');

        $this->assertFalse($result->isSuccess());
        $this->assertFalse($result->isSkipped());
        $this->assertTrue($result->isFailed());
        $this->assertEquals('failed', $result->status);
        $this->assertNull($result->average);
        $this->assertEquals('Calculation error', $result->errorMessage);
    }

    public function test_get_message_for_success(): void
    {
        $result = CalculationResult::success($this->building, 123.45);

        $message = $result->getMessage();

        $this->assertStringContainsString('Test Building', $message);
        $this->assertStringContainsString('123.45', $message);
    }

    public function test_get_message_for_skipped(): void
    {
        $result = CalculationResult::skipped($this->building, 'Already calculated');

        $message = $result->getMessage();

        $this->assertStringContainsString('Test Building', $message);
        $this->assertStringContainsString('Skipped', $message);
        $this->assertStringContainsString('Already calculated', $message);
    }

    public function test_get_message_for_failed(): void
    {
        $result = CalculationResult::failed($this->building, 'Calculation error');

        $message = $result->getMessage();

        $this->assertStringContainsString('Test Building', $message);
        $this->assertStringContainsString('Failed', $message);
        $this->assertStringContainsString('Calculation error', $message);
    }
}
