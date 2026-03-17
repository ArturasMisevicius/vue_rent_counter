<?php

namespace Database\Factories;

use App\Models\MeterReading;
use App\Models\MeterReadingAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeterReadingAudit>
 */
class MeterReadingAuditFactory extends Factory
{
    public function definition(): array
    {
        $oldValue = fake()->randomFloat(3, 100, 5000);

        return [
            'meter_reading_id' => MeterReading::factory(),
            'changed_by_user_id' => User::factory(),
            'old_value' => $oldValue,
            'new_value' => $oldValue + fake()->randomFloat(3, 1, 100),
            'change_reason' => fake()->sentence(),
        ];
    }
}
