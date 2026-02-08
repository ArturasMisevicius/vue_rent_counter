<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Property;
use App\Models\SystemTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Enhanced User Factory with Multi-Tenant Support
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactoryEnhanced extends Factory
{
    protected $model = User::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => UserRole::TENANT,
            'is_active' => true,
            'is_super_admin' => false,
            'remember_token' => Str::random(10),
            'last_login_at' => fake()->optional(0.7)->dateTimeBetween('-30 days'),
        ];
    }

    /**
     * Create a superadmin user.
     */
    public function superadmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::SUPERADMIN,
            'is_super_admin' => true,
            'tenant_id' => null,
            'property_id' => null,
            'parent_user_id' => null,
            'system_tenant_id' => SystemTenant::factory(),
        ]);
    }

    /**
     * Create an admin user with tenant.
     */
    public function admin(?int $tenantId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::ADMIN,
            'tenant_id' => $tenantId ?? fake()->numberBetween(1, 100),
            'property_id' => null,
            'parent_user_id' => null,
            'organization_name' => fake()->company(),
        ]);
    }

    /**
     * Create a manager user with tenant.
     */
    public function manager(?int $tenantId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::MANAGER,
            'tenant_id' => $tenantId ?? fake()->numberBetween(1, 100),
            'property_id' => null,
            'parent_user_id' => null,
        ]);
    }

    /**
     * Create a tenant user with property and parent.
     */
    public function tenant(?int $tenantId = null, ?int $propertyId = null, ?int $parentUserId = null): static
    {
        return $this->state(function (array $attributes) use ($tenantId, $propertyId, $parentUserId) {
            $actualTenantId = $tenantId ?? fake()->numberBetween(1, 100);
            
            return [
                'role' => UserRole::TENANT,
                'tenant_id' => $actualTenantId,
                'property_id' => $propertyId ?? Property::factory()->create(['tenant_id' => $actualTenantId])->id,
                'parent_user_id' => $parentUserId ?? User::factory()->admin($actualTenantId)->create()->id,
            ];
        });
    }

    /**
     * Create an inactive user.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a suspended user.
     */
    public function suspended(?string $reason = null): static
    {
        return $this->state(fn (array $attributes) => [
            'suspended_at' => fake()->dateTimeBetween('-30 days'),
            'suspension_reason' => $reason ?? fake()->sentence(),
        ]);
    }

    /**
     * Create an unverified user.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create a user with API tokens.
     */
    public function withApiTokens(int $count = 1): static
    {
        return $this->afterCreating(function (User $user) use ($count) {
            for ($i = 0; $i < $count; $i++) {
                $user->createApiToken("token-{$i}");
            }
        });
    }

    /**
     * Create a user with specific tenant relationships.
     */
    public function forTenant(int $tenantId): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Create a user with hierarchical relationships.
     */
    public function withHierarchy(): static
    {
        return $this->afterCreating(function (User $user) {
            if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
                // Create child tenant users
                User::factory()
                    ->count(fake()->numberBetween(1, 5))
                    ->tenant($user->tenant_id, null, $user->id)
                    ->create();
            }
        });
    }
}