<?php

declare(strict_types=1);

namespace Tests\Unit\Filament\Widgets;

use App\Filament\Widgets\PolicyRegistryHealthWidget;
use App\Services\PolicyRegistryMonitoringService;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

final class PolicyRegistryHealthWidgetTest extends TestCase
{
    use RefreshDatabase;

    private PolicyRegistryMonitoringService $mockMonitoringService;
    private PolicyRegistryHealthWidget $widget;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockMonitoringService = Mockery::mock(PolicyRegistryMonitoringService::class);
        $this->app->instance(PolicyRegistryMonitoringService::class, $this->mockMonitoringService);
        
        $this->widget = new PolicyRegistryHealthWidget();
    }

    public function test_widget_displays_healthy_status_with_valid_metrics(): void
    {
        // Arrange
        $healthCheckData = [
            'healthy' => true,
            'metrics' => [
                'total_policies' => 15,
                'total_gates' => 8,
                'cache_hit_rate' => 0.95,
                'average_registration_time' => 45.0,
                'error_rate_24h' => 0.005,
            ],
            'issues' => [
                'critical' => [],
                'warnings' => [],
            ],
        ];

        $this->mockMonitoringService
            ->shouldReceive('getLastHealthCheck')
            ->once()
            ->andReturn($healthCheckData);

        // Act
        $stats = $this->widget->getStats();

        // Assert
        $this->assertCount(6, $stats);
        $this->assertContainsOnlyInstancesOf(Stat::class, $stats);
        
        // Check health status stat
        $healthStat = $stats[0];
        $this->assertEquals('success', $this->getStatColor($healthStat));
        
        // Check metrics are properly formatted
        $this->assertStringContains('95.0%', $this->getStatValue($stats[3])); // Cache hit rate
        $this->assertStringContains('45ms', $this->getStatValue($stats[4])); // Performance
        $this->assertStringContains('0.5%', $this->getStatValue($stats[5])); // Error rate
    }

    public function test_widget_displays_unhealthy_status_with_critical_issues(): void
    {
        // Arrange
        $healthCheckData = [
            'healthy' => false,
            'metrics' => [
                'total_policies' => 10,
                'total_gates' => 5,
                'cache_hit_rate' => 0.75,
                'average_registration_time' => 150.0,
                'error_rate_24h' => 0.08,
            ],
            'issues' => [
                'critical' => ['Policy validation failed', 'Registry unavailable'],
                'warnings' => [],
            ],
        ];

        $this->mockMonitoringService
            ->shouldReceive('getLastHealthCheck')
            ->once()
            ->andReturn($healthCheckData);

        // Act
        $stats = $this->widget->getStats();

        // Assert
        $healthStat = $stats[0];
        $this->assertEquals('danger', $this->getStatColor($healthStat));
        
        // Check performance colors based on thresholds
        $this->assertEquals('danger', $this->getStatColor($stats[3])); // Cache hit rate < 0.8
        $this->assertEquals('danger', $this->getStatColor($stats[4])); // Performance > 100ms
        $this->assertEquals('danger', $this->getStatColor($stats[5])); // Error rate > 5%
    }

    public function test_widget_falls_back_to_health_check_when_no_cached_data(): void
    {
        // Arrange
        $healthCheckData = [
            'healthy' => true,
            'metrics' => [
                'total_policies' => 12,
                'total_gates' => 6,
                'cache_hit_rate' => 0.85,
                'average_registration_time' => 75.0,
                'error_rate_24h' => 0.02,
            ],
            'issues' => ['critical' => [], 'warnings' => []],
        ];

        $this->mockMonitoringService
            ->shouldReceive('getLastHealthCheck')
            ->once()
            ->andReturn(null);
            
        $this->mockMonitoringService
            ->shouldReceive('healthCheck')
            ->once()
            ->andReturn($healthCheckData);

        // Act
        $stats = $this->widget->getStats();

        // Assert
        $this->assertCount(6, $stats);
        $this->assertEquals('warning', $this->getStatColor($stats[3])); // Cache hit rate 0.85 (warning)
        $this->assertEquals('warning', $this->getStatColor($stats[4])); // Performance 75ms (warning)
        $this->assertEquals('warning', $this->getStatColor($stats[5])); // Error rate 2% (warning)
    }

    public function test_widget_handles_service_exception_gracefully(): void
    {
        // Arrange
        Log::shouldReceive('error')
            ->once()
            ->with('PolicyRegistryHealthWidget: Failed to load health data', Mockery::type('array'));

        $this->mockMonitoringService
            ->shouldReceive('getLastHealthCheck')
            ->once()
            ->andThrow(new \RuntimeException('Service unavailable'));

        // Act
        $stats = $this->widget->getStats();

        // Assert
        $this->assertCount(1, $stats);
        $healthStat = $stats[0];
        $this->assertEquals('danger', $this->getStatColor($healthStat));
    }

    public function test_widget_handles_invalid_health_check_data(): void
    {
        // Arrange
        $this->mockMonitoringService
            ->shouldReceive('getLastHealthCheck')
            ->once()
            ->andReturn(['invalid' => 'data']); // Missing required keys

        // Act
        $stats = $this->widget->getStats();

        // Assert
        $this->assertCount(1, $stats);
        $healthStat = $stats[0];
        $this->assertEquals('danger', $this->getStatColor($healthStat));
    }

    public function test_widget_handles_missing_metrics_gracefully(): void
    {
        // Arrange
        $healthCheckData = [
            'healthy' => true,
            'metrics' => [], // Empty metrics
            'issues' => ['critical' => [], 'warnings' => []],
        ];

        $this->mockMonitoringService
            ->shouldReceive('getLastHealthCheck')
            ->once()
            ->andReturn($healthCheckData);

        // Act
        $stats = $this->widget->getStats();

        // Assert
        $this->assertCount(6, $stats);
        
        // Check default values are used
        $this->assertEquals('0', $this->getStatValue($stats[1])); // Policies count
        $this->assertEquals('0', $this->getStatValue($stats[2])); // Gates count
        $this->assertStringContains('0.0%', $this->getStatValue($stats[3])); // Cache hit rate
    }

    public function test_format_duration_handles_various_time_ranges(): void
    {
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->widget);
        $method = $reflection->getMethod('formatDuration');
        $method->setAccessible(true);

        // Test various durations
        $this->assertEquals('< 1ms', $method->invoke($this->widget, 0.5));
        $this->assertEquals('50ms', $method->invoke($this->widget, 50.0));
        $this->assertEquals('999ms', $method->invoke($this->widget, 999.0));
        $this->assertEquals('1.50s', $method->invoke($this->widget, 1500.0));
    }

    public function test_format_percentage_formats_correctly(): void
    {
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->widget);
        $method = $reflection->getMethod('formatPercentage');
        $method->setAccessible(true);

        // Test various percentages
        $this->assertEquals('95.0%', $method->invoke($this->widget, 0.95));
        $this->assertEquals('0.5%', $method->invoke($this->widget, 0.005));
        $this->assertEquals('100.0%', $method->invoke($this->widget, 1.0));
    }

    public function test_can_view_returns_true_for_super_admin(): void
    {
        // Arrange
        $user = \App\Models\User::factory()->create();
        $user->assignRole('super_admin');
        $this->actingAs($user);

        // Act & Assert
        $this->assertTrue(PolicyRegistryHealthWidget::canView());
    }

    public function test_can_view_returns_false_for_non_super_admin(): void
    {
        // Arrange
        $user = \App\Models\User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);

        // Act & Assert
        $this->assertFalse(PolicyRegistryHealthWidget::canView());
    }

    public function test_can_view_returns_false_for_unauthenticated_user(): void
    {
        // Act & Assert
        $this->assertFalse(PolicyRegistryHealthWidget::canView());
    }

    public function test_widget_properties_are_configured_correctly(): void
    {
        // Assert
        $this->assertEquals('30s', $this->widget->pollingInterval);
        $this->assertFalse($this->widget->isLazy);
        $this->assertEquals(100, $this->widget->sort);
    }

    /**
     * Helper method to extract color from Stat object
     */
    private function getStatColor(Stat $stat): string
    {
        $reflection = new \ReflectionClass($stat);
        $property = $reflection->getProperty('color');
        $property->setAccessible(true);
        
        return $property->getValue($stat);
    }

    /**
     * Helper method to extract value from Stat object
     */
    private function getStatValue(Stat $stat): string
    {
        $reflection = new \ReflectionClass($stat);
        $property = $reflection->getProperty('value');
        $property->setAccessible(true);
        
        return $property->getValue($stat);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}