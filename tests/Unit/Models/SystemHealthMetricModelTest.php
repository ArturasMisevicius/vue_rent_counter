<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\SystemHealthMetric;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemHealthMetricModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function is_healthy_returns_true_for_healthy_status(): void
    {
        $metric = SystemHealthMetric::factory()->create(['status' => 'healthy']);

        $this->assertTrue($metric->isHealthy());
    }

    /** @test */
    public function is_healthy_returns_false_for_non_healthy_status(): void
    {
        $warningMetric = SystemHealthMetric::factory()->create(['status' => 'warning']);
        $dangerMetric = SystemHealthMetric::factory()->create(['status' => 'danger']);

        $this->assertFalse($warningMetric->isHealthy());
        $this->assertFalse($dangerMetric->isHealthy());
    }

    /** @test */
    public function is_warning_returns_true_for_warning_status(): void
    {
        $metric = SystemHealthMetric::factory()->create(['status' => 'warning']);

        $this->assertTrue($metric->isWarning());
    }

    /** @test */
    public function is_danger_returns_true_for_danger_status(): void
    {
        $metric = SystemHealthMetric::factory()->create(['status' => 'danger']);

        $this->assertTrue($metric->isDanger());
    }

    /** @test */
    public function get_status_color_returns_correct_colors(): void
    {
        $healthyMetric = SystemHealthMetric::factory()->create(['status' => 'healthy']);
        $warningMetric = SystemHealthMetric::factory()->create(['status' => 'warning']);
        $dangerMetric = SystemHealthMetric::factory()->create(['status' => 'danger']);
        $unknownMetric = SystemHealthMetric::factory()->create(['status' => 'unknown']);

        $this->assertEquals('green', $healthyMetric->getStatusColor());
        $this->assertEquals('yellow', $warningMetric->getStatusColor());
        $this->assertEquals('red', $dangerMetric->getStatusColor());
        $this->assertEquals('gray', $unknownMetric->getStatusColor());
    }

    /** @test */
    public function get_status_icon_returns_correct_icons(): void
    {
        $healthyMetric = SystemHealthMetric::factory()->create(['status' => 'healthy']);
        $warningMetric = SystemHealthMetric::factory()->create(['status' => 'warning']);
        $dangerMetric = SystemHealthMetric::factory()->create(['status' => 'danger']);
        $unknownMetric = SystemHealthMetric::factory()->create(['status' => 'unknown']);

        $this->assertEquals('heroicon-o-check-circle', $healthyMetric->getStatusIcon());
        $this->assertEquals('heroicon-o-exclamation-triangle', $warningMetric->getStatusIcon());
        $this->assertEquals('heroicon-o-x-circle', $dangerMetric->getStatusIcon());
        $this->assertEquals('heroicon-o-question-mark-circle', $unknownMetric->getStatusIcon());
    }

    /** @test */
    public function latest_by_type_scope_returns_most_recent_metric(): void
    {
        // Create older metric
        SystemHealthMetric::factory()->create([
            'metric_type' => 'database',
            'checked_at' => now()->subHours(2),
        ]);

        // Create newer metric
        $latestMetric = SystemHealthMetric::factory()->create([
            'metric_type' => 'database',
            'checked_at' => now()->subHours(1),
        ]);

        // Create metric of different type
        SystemHealthMetric::factory()->create([
            'metric_type' => 'storage',
            'checked_at' => now(),
        ]);

        $result = SystemHealthMetric::latestByType('database')->first();

        $this->assertEquals($latestMetric->id, $result->id);
    }

    /** @test */
    public function within_time_range_scope_filters_by_date_range(): void
    {
        $from = now()->subDays(2);
        $to = now()->subDays(1);

        // Create metric within range
        $withinRange = SystemHealthMetric::factory()->create([
            'checked_at' => now()->subDays(1)->subHours(12),
        ]);

        // Create metric outside range (too old)
        SystemHealthMetric::factory()->create([
            'checked_at' => now()->subDays(3),
        ]);

        // Create metric outside range (too new)
        SystemHealthMetric::factory()->create([
            'checked_at' => now(),
        ]);

        $results = SystemHealthMetric::withinTimeRange($from, $to)->get();

        $this->assertCount(1, $results);
        $this->assertEquals($withinRange->id, $results->first()->id);
    }

    /** @test */
    public function unhealthy_scope_returns_warning_and_danger_metrics(): void
    {
        $healthyMetric = SystemHealthMetric::factory()->create(['status' => 'healthy']);
        $warningMetric = SystemHealthMetric::factory()->create(['status' => 'warning']);
        $dangerMetric = SystemHealthMetric::factory()->create(['status' => 'danger']);

        $unhealthyMetrics = SystemHealthMetric::unhealthy()->get();

        $this->assertCount(2, $unhealthyMetrics);
        $this->assertTrue($unhealthyMetrics->contains($warningMetric));
        $this->assertTrue($unhealthyMetrics->contains($dangerMetric));
        $this->assertFalse($unhealthyMetrics->contains($healthyMetric));
    }
}