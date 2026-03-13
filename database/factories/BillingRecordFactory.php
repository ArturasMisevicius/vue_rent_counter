<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BillingRecord;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BillingRecord>
 */
final class BillingRecordFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = BillingRecord::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        return [
            'invoice_id' => Invoice::factory(),
            'tenant_id' => Tenant::factory(),
            'property_id' => Property::factory(),
            'service_type' => $this->faker->randomElement(['electricity', 'gas', 'water', 'heating']),
            'consumption' => $this->faker->randomFloat(2, 10, 500),
            'rate' => $this->faker->randomFloat(2, 0.1, 2.0),
            'amount' => function (array $attributes) {
                return $attributes['consumption'] * $attributes['rate'];
            },
            'billing_period_start' => $startDate,
            'billing_period_end' => $endDate,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Create a billing record for electricity service.
     */
    public function electricity(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_type' => 'electricity',
            'consumption' => $this->faker->randomFloat(2, 50, 300),
            'rate' => $this->faker->randomFloat(2, 0.4, 0.6),
        ]);
    }

    /**
     * Create a billing record for gas service.
     */
    public function gas(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_type' => 'gas',
            'consumption' => $this->faker->randomFloat(2, 20, 150),
            'rate' => $this->faker->randomFloat(2, 0.8, 1.2),
        ]);
    }

    /**
     * Create a billing record for water service.
     */
    public function water(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_type' => 'water',
            'consumption' => $this->faker->randomFloat(2, 5, 50),
            'rate' => $this->faker->randomFloat(2, 1.5, 2.5),
        ]);
    }

    /**
     * Create a billing record for heating service.
     */
    public function heating(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_type' => 'heating',
            'consumption' => $this->faker->randomFloat(2, 100, 800),
            'rate' => $this->faker->randomFloat(2, 0.3, 0.8),
        ]);
    }

    /**
     * Create a billing record for a specific period.
     */
    public function forPeriod(Carbon $start, Carbon $end): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_period_start' => $start,
            'billing_period_end' => $end,
        ]);
    }

    /**
     * Create a billing record with specific consumption and rate.
     */
    public function withConsumption(float $consumption, float $rate): static
    {
        return $this->state(fn (array $attributes) => [
            'consumption' => $consumption,
            'rate' => $rate,
            'amount' => $consumption * $rate,
        ]);
    }
}