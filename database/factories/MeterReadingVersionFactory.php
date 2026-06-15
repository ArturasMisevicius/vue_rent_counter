<?php

namespace Database\Factories;

use App\Enums\MeterReadingStatus;
use App\Models\MeterReading;
use App\Models\MeterReadingVersion;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeterReadingVersion>
 */
class MeterReadingVersionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'meter_reading_id' => MeterReading::factory(),
            'organization_id' => Organization::factory(),
            'invoice_id' => null,
            'billing_period_id' => null,
            'changed_by_user_id' => User::factory(),
            'version' => 1,
            'event' => 'submitted',
            'previous_value' => fake()->randomFloat(3, 1, 5000),
            'current_value' => fake()->randomFloat(3, 5001, 10000),
            'consumption' => fake()->randomFloat(3, 1, 1000),
            'status' => MeterReadingStatus::SUBMITTED,
            'reading_date' => now()->toDateString(),
            'reason' => fake()->sentence(),
            'snapshot' => [],
        ];
    }
}
