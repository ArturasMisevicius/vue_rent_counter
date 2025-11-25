<?php

declare(strict_types=1);

namespace Tests\Unit\ValueObjects;

use App\ValueObjects\SummerPeriod;
use Carbon\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

final class SummerPeriodTest extends TestCase
{
    public function test_creates_summer_period_with_correct_dates(): void
    {
        $period = new SummerPeriod(2023);

        $this->assertEquals(2023, $period->year);
        $this->assertEquals('2023-05-01', $period->startDate->toDateString());
        $this->assertEquals('2023-09-30', $period->endDate->toDateString());
    }

    public function test_creates_period_for_previous_year(): void
    {
        Carbon::setTestNow('2024-01-15');

        $period = SummerPeriod::forPreviousYear();

        $this->assertEquals(2023, $period->year);
        $this->assertEquals('2023-05-01', $period->startDate->toDateString());
        $this->assertEquals('2023-09-30', $period->endDate->toDateString());

        Carbon::setTestNow();
    }

    public function test_creates_period_for_current_year(): void
    {
        Carbon::setTestNow('2024-01-15');

        $period = SummerPeriod::forCurrentYear();

        $this->assertEquals(2024, $period->year);
        $this->assertEquals('2024-05-01', $period->startDate->toDateString());
        $this->assertEquals('2024-09-30', $period->endDate->toDateString());

        Carbon::setTestNow();
    }

    public function test_description_returns_formatted_string(): void
    {
        $period = new SummerPeriod(2023);

        $description = $period->description();

        $this->assertEquals('2023-05-01 to 2023-09-30', $description);
    }

    public function test_throws_exception_for_year_too_old(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Year must be between');

        new SummerPeriod(2019);
    }

    public function test_throws_exception_for_future_year(): void
    {
        Carbon::setTestNow('2024-01-15');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Year must be between');

        new SummerPeriod(2025);

        Carbon::setTestNow();
    }

    public function test_uses_configuration_for_months(): void
    {
        config(['gyvatukas.summer_start_month' => 6]);
        config(['gyvatukas.summer_end_month' => 8]);

        $period = new SummerPeriod(2023);

        $this->assertEquals('2023-06-01', $period->startDate->toDateString());
        $this->assertEquals('2023-08-31', $period->endDate->toDateString());
    }
}
