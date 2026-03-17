<?php

namespace Database\Seeders;

use App\Enums\IntegrationHealthStatus;
use App\Models\IntegrationHealthCheck;
use Illuminate\Database\Seeder;

class IntegrationHealthCheckSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['key' => 'database', 'label' => 'Database'],
            ['key' => 'queue', 'label' => 'Queue'],
            ['key' => 'mail', 'label' => 'Mail'],
        ])->each(function (array $check): void {
            IntegrationHealthCheck::query()->updateOrCreate(
                ['key' => $check['key']],
                [
                    'label' => $check['label'],
                    'status' => IntegrationHealthStatus::HEALTHY,
                    'checked_at' => now(),
                    'response_time_ms' => 100,
                    'summary' => $check['label'].' is responding normally.',
                    'details' => ['seeded' => true],
                ],
            );
        });
    }
}
