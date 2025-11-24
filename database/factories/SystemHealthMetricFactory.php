<?php

namespace Database\Factories;

use App\Models\SystemHealthMetric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SystemHealthMetric>
 */
class SystemHealthMetricFactory extends Factory
{
    protected $model = SystemHealthMetric::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $metricType = $this->faker->randomElement(['database', 'backup', 'queue', 'storage', 'cache']);
        
        return [
            'metric_type' => $metricType,
            'metric_name' => $this->getMetricName($metricType),
            'value' => $this->getMetricValue($metricType),
            'status' => $this->faker->randomElement(['healthy', 'warning', 'danger']),
            'checked_at' => now(),
        ];
    }

    /**
     * Get a metric name based on type.
     */
    private function getMetricName(string $type): string
    {
        return match ($type) {
            'database' => $this->faker->randomElement(['connection_status', 'active_connections', 'slow_queries']),
            'backup' => $this->faker->randomElement(['last_backup', 'backup_size', 'backup_status']),
            'queue' => $this->faker->randomElement(['pending_jobs', 'failed_jobs', 'processing_time']),
            'storage' => $this->faker->randomElement(['disk_usage', 'database_size', 'log_size']),
            'cache' => $this->faker->randomElement(['connection_status', 'hit_rate', 'memory_usage']),
            default => 'unknown',
        };
    }

    /**
     * Get a metric value based on type.
     */
    private function getMetricValue(string $type): array
    {
        return match ($type) {
            'database' => [
                'connections' => $this->faker->numberBetween(1, 100),
                'slow_queries' => $this->faker->numberBetween(0, 10),
            ],
            'backup' => [
                'last_backup' => now()->subHours($this->faker->numberBetween(1, 24))->toIso8601String(),
                'size_mb' => $this->faker->numberBetween(10, 1000),
            ],
            'queue' => [
                'pending' => $this->faker->numberBetween(0, 100),
                'failed' => $this->faker->numberBetween(0, 10),
            ],
            'storage' => [
                'used_gb' => $this->faker->numberBetween(1, 100),
                'total_gb' => 500,
            ],
            'cache' => [
                'hit_rate' => $this->faker->randomFloat(2, 0.5, 1.0),
                'memory_mb' => $this->faker->numberBetween(10, 500),
            ],
            default => [],
        };
    }

    /**
     * Indicate that the metric is healthy.
     */
    public function healthy(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'healthy',
        ]);
    }

    /**
     * Indicate that the metric has a warning.
     */
    public function warning(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'warning',
        ]);
    }

    /**
     * Indicate that the metric is in danger.
     */
    public function danger(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'danger',
        ]);
    }

    /**
     * Set the metric type to database.
     */
    public function database(): static
    {
        return $this->state(fn (array $attributes) => [
            'metric_type' => 'database',
            'metric_name' => 'connection_status',
            'value' => [
                'connections' => $this->faker->numberBetween(1, 100),
                'slow_queries' => $this->faker->numberBetween(0, 10),
            ],
        ]);
    }

    /**
     * Set the metric type to backup.
     */
    public function backup(): static
    {
        return $this->state(fn (array $attributes) => [
            'metric_type' => 'backup',
            'metric_name' => 'last_backup',
            'value' => [
                'last_backup' => now()->subHours($this->faker->numberBetween(1, 24))->toIso8601String(),
                'size_mb' => $this->faker->numberBetween(10, 1000),
            ],
        ]);
    }

    /**
     * Set the metric type to queue.
     */
    public function queue(): static
    {
        return $this->state(fn (array $attributes) => [
            'metric_type' => 'queue',
            'metric_name' => 'pending_jobs',
            'value' => [
                'pending' => $this->faker->numberBetween(0, 100),
                'failed' => $this->faker->numberBetween(0, 10),
            ],
        ]);
    }

    /**
     * Set the metric type to storage.
     */
    public function storage(): static
    {
        return $this->state(fn (array $attributes) => [
            'metric_type' => 'storage',
            'metric_name' => 'disk_usage',
            'value' => [
                'used_gb' => $this->faker->numberBetween(1, 100),
                'total_gb' => 500,
            ],
        ]);
    }

    /**
     * Set the metric type to cache.
     */
    public function cache(): static
    {
        return $this->state(fn (array $attributes) => [
            'metric_type' => 'cache',
            'metric_name' => 'connection_status',
            'value' => [
                'hit_rate' => $this->faker->randomFloat(2, 0.5, 1.0),
                'memory_mb' => $this->faker->numberBetween(10, 500),
            ],
        ]);
    }
}
