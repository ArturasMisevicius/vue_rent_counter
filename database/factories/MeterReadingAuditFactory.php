<?php

namespace Database\Factories;

use App\Models\MeterReading;
use App\Models\MeterReadingAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\MeterReadingAudit>
 */
class MeterReadingAuditFactory extends Factory
{
    protected $model = MeterReadingAudit::class;

    public function definition(): array
    {
        $oldValue = fake()->randomFloat(2, 100, 5000);
        $delta = fake()->randomFloat(2, 1, 100);

        return [
            'meter_reading_id' => MeterReading::factory(),
            'changed_by_user_id' => User::factory(),
            'old_value' => $oldValue,
            'new_value' => $oldValue + $delta,
            'change_reason' => fake()->sentence(),
        ];
    }
}
