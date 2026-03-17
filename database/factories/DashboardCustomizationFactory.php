<?php

namespace Database\Factories;

use App\Models\DashboardCustomization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DashboardCustomization>
 */
class DashboardCustomizationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'widget_configuration' => [
                ['widget' => 'stats', 'enabled' => true],
            ],
            'layout_configuration' => [
                'columns' => 2,
            ],
            'refresh_intervals' => [
                'stats' => 60,
            ],
        ];
    }
}
