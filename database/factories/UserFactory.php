<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'property_id' => null,
            'parent_user_id' => null,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => UserRole::MANAGER,
            'is_active' => true,
            'organization_name' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create a superadmin user.
     */
    public function superadmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => null,
            'property_id' => null,
            'parent_user_id' => null,
            'role' => UserRole::SUPERADMIN,
            'organization_name' => null,
        ]);
    }

    /**
     * Create an admin user with organization.
     */
    public function admin(?int $tenantId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenantId ?? fake()->unique()->numberBetween(1, 1000),
            'property_id' => null,
            'parent_user_id' => null,
            'role' => UserRole::ADMIN,
            'organization_name' => fake()->company(),
        ]);
    }

    /**
     * Create a manager user (legacy role, similar to admin).
     */
    public function manager(?int $tenantId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenantId ?? 1,
            'property_id' => null,
            'parent_user_id' => null,
            'role' => UserRole::MANAGER,
            'organization_name' => null,
        ]);
    }

    /**
     * Create a tenant user with property assignment.
     */
    public function tenant(?int $tenantId = null, ?int $propertyId = null, ?int $parentUserId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenantId ?? 1,
            'property_id' => $propertyId,
            'parent_user_id' => $parentUserId,
            'role' => UserRole::TENANT,
            'organization_name' => null,
        ]);
    }

    /**
     * Mark the user as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Ensure an Organization exists for tenant-scoped users.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (User $user): void {
            if ($user->tenant_id === null || $user->role === UserRole::SUPERADMIN) {
                return;
            }

            if (Organization::whereKey($user->tenant_id)->exists()) {
                return;
            }

            Organization::factory()->create([
                'id' => $user->tenant_id,
                'name' => $user->organization_name ?: "Test Organization {$user->tenant_id}",
                'email' => $user->email,
                'domain' => null,
                'created_by' => $user->id,
                'is_active' => true,
                'suspended_at' => null,
                'suspension_reason' => null,
            ]);
        });
    }
}
