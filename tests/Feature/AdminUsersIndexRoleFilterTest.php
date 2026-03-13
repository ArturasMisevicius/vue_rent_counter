<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;

test('admin users page shows only tenant and manager roles', function (): void {
    $tenantId = 1;

    $admin = User::factory()->create([
        'tenant_id' => $tenantId,
        'role' => UserRole::ADMIN,
    ]);

    Subscription::factory()->active()->create([
        'user_id' => $admin->id,
    ]);

    $property = Property::factory()->create([
        'tenant_id' => $tenantId,
    ]);

    $manager = User::factory()->create([
        'tenant_id' => $tenantId,
        'role' => UserRole::MANAGER,
        'name' => 'Visible Manager',
    ]);

    $tenant = User::factory()->create([
        'tenant_id' => $tenantId,
        'role' => UserRole::TENANT,
        'property_id' => $property->id,
        'parent_user_id' => $admin->id,
        'name' => 'Visible Tenant',
    ]);

    User::factory()->create([
        'tenant_id' => $tenantId,
        'role' => UserRole::ADMIN,
        'name' => 'Hidden Admin',
    ]);

    User::factory()->superadmin()->create([
        'name' => 'Hidden Superadmin',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.users.index'));

    $response->assertOk()
        ->assertSee($manager->name)
        ->assertSee($tenant->name)
        ->assertDontSee('Hidden Admin')
        ->assertDontSee('Hidden Superadmin');
});
