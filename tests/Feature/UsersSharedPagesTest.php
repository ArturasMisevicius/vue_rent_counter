<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;

it('renders admin user create page in shared component style', function (): void {
    $tenant = Tenant::factory()->create();

    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenant->id,
    ]);

    Subscription::factory()->active()->create([
        'user_id' => $admin->id,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.users.create'));

    $response->assertOk();
    $response->assertViewIs('pages.users.create');
    $response->assertSee(__('users.headings.create'));
    $response->assertSee(__('users.actions.create'));
    $response->assertSee(__('users.actions.back'));
});

it('renders admin user show page in shared component style', function (): void {
    $tenant = Tenant::factory()->create();

    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenant->id,
    ]);

    Subscription::factory()->active()->create([
        'user_id' => $admin->id,
    ]);

    $user = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant->id,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.users.show', $user));

    $response->assertOk();
    $response->assertViewIs('pages.users.show');
    $response->assertSee(__('users.headings.show'));
    $response->assertSee(__('users.headings.information'));
    $response->assertSee(__('users.actions.back'));
    $response->assertSee($user->name);
});
