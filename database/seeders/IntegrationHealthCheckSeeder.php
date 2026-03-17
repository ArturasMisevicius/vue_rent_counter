<?php

namespace Database\Seeders;

use App\Enums\IntegrationHealthStatus;
use App\Models\IntegrationHealthCheck;
use Illuminate\Database\Seeder;

class IntegrationHealthCheckSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'database', 'label' => 'Database'],
            ['name' => 'queue', 'label' => 'Queue'],
            ['name' => 'mail', 'label' => 'Mail'],
        ] as $check) {
            IntegrationHealthCheck::query()->updateOrCreate(
                ['name' => $check['name']],
                [
                    'label' => $check['label'],
                    'status' => IntegrationHealthStatus::HEALTHY,
                    'summary' => 'Seeded platform dependency baseline.',
                    'checked_at' => now(),
                    'metadata' => [],
                ],
            );
        }
    }
}
