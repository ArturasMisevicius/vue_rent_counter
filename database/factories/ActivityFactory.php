<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        return [
            'tenant_id' => User::factory(),
            'log_name' => fake()->randomElement(['system', 'auth', 'billing', 'profile']),
            'description' => fake()->sentence(),
            'subject_type' => Organization::class,
            'subject_id' => function (): int {
                return Organization::query()->value('id') ?? Organization::factory()->create()->id;
            },
            'causer_type' => User::class,
            'causer_id' => User::factory(),
            'properties' => [
                'old' => [],
                'attributes' => [],
            ],
            'event' => fake()->randomElement(['created', 'updated', 'deleted']),
            'batch_uuid' => fake()->optional()->uuid(),
        ];
    }

    public function forCauser(User $user): static
    {
        return $this->state(fn () => [
            'causer_type' => User::class,
            'causer_id' => $user->id,
            'tenant_id' => $user->id,
        ]);
    }
}
