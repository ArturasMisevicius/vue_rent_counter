<?php

namespace Database\Factories;

use App\Filament\Support\Admin\ManagerPermissions\ManagerPermissionCatalog;
use App\Models\ManagerPermission;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ManagerPermission>
 */
class ManagerPermissionFactory extends Factory
{
    public function definition(): array
    {
        $organization = Organization::factory();

        return [
            'id' => Str::ulid()->toBase32(),
            'organization_id' => $organization,
            'user_id' => User::factory()->manager()->for($organization),
            'resource' => fake()->randomElement(ManagerPermissionCatalog::resources()),
            'can_create' => fake()->boolean(),
            'can_edit' => fake()->boolean(),
            'can_delete' => fake()->boolean(),
        ];
    }
}
