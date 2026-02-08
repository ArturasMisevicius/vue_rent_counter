<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows success flash message only once on admin tenants index', function (): void {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    Subscription::factory()->active()->create([
        'user_id' => $admin->id,
    ]);

    $message = __('common.language_switched');

    $response = $this->actingAs($admin)
        ->withSession(['success' => $message])
        ->get(route('admin.tenants.index'));

    $response->assertOk();

    expect(substr_count($response->getContent(), $message))->toBe(1);
});

it('shows success flash message only once on admin tenants show', function (): void {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    Subscription::factory()->active()->create([
        'user_id' => $admin->id,
    ]);

    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $admin->tenant_id,
        'parent_user_id' => $admin->id,
    ]);

    $message = __('common.language_switched');

    $response = $this->actingAs($admin)
        ->withSession(['success' => $message])
        ->get(route('admin.tenants.show', $tenant));

    $response->assertOk();

    expect(substr_count($response->getContent(), $message))->toBe(1);
});

it('shows success flash message only once on admin tariffs index', function (): void {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    Subscription::factory()->active()->create([
        'user_id' => $admin->id,
    ]);

    $message = __('common.language_switched');

    $response = $this->actingAs($admin)
        ->withSession(['success' => $message])
        ->get(route('admin.tariffs.index'));

    $response->assertOk();

    expect(substr_count($response->getContent(), $message))->toBe(1);
});
