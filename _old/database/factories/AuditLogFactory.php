<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'user_id' => User::factory(),
            'auditable_type' => User::class,
            'auditable_id' => function (): int {
                return User::query()->value('id') ?? User::factory()->create()->id;
            },
            'event' => fake()->randomElement(['created', 'updated', 'deleted']),
            'old_values' => [
                'status' => 'draft',
            ],
            'new_values' => [
                'status' => 'active',
            ],
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'notes' => fake()->sentence(),
        ];
    }

    public function forTenantId(int $tenantId): static
    {
        return $this->state(fn () => [
            'tenant_id' => $tenantId,
        ]);
    }
}
