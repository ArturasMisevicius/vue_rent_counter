<?php

use App\Enums\UserRole;
use App\Models\User;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates the foundation auth domain schema', function () {
    expect(Schema::hasTable('organizations'))->toBeTrue();
    expect(Schema::hasTable('subscriptions'))->toBeTrue();
    expect(Schema::hasTable('organization_invitations'))->toBeTrue();

    expect(Schema::hasColumns('users', [
        'role',
        'status',
        'locale',
        'organization_id',
        'last_login_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('organizations', [
        'name',
        'slug',
        'status',
        'owner_user_id',
    ]))->toBeTrue();

    expect(Schema::hasColumns('subscriptions', [
        'organization_id',
        'plan',
        'status',
        'starts_at',
        'expires_at',
        'is_trial',
    ]))->toBeTrue();

    expect(Schema::hasColumns('organization_invitations', [
        'organization_id',
        'inviter_user_id',
        'email',
        'role',
        'full_name',
        'token',
        'expires_at',
        'accepted_at',
    ]))->toBeTrue();
});

it('exposes role helpers for shared auth routing', function () {
    $superadmin = User::factory()->superadmin()->make();
    $admin = User::factory()->admin()->make(['organization_id' => null]);
    $manager = User::factory()->manager()->make();
    $tenant = User::factory()->tenant()->make();
    $panel = Panel::make()->id('admin');

    expect($superadmin->isSuperadmin())->toBeTrue()
        ->and($admin->isAdminLike())->toBeTrue()
        ->and($manager->isAdminLike())->toBeTrue()
        ->and($tenant->isAdminLike())->toBeFalse()
        ->and($superadmin->canAccessPanel($panel))->toBeTrue()
        ->and($admin->canAccessPanel($panel))->toBeTrue()
        ->and($manager->canAccessPanel($panel))->toBeTrue()
        ->and($tenant->canAccessPanel($panel))->toBeFalse();
});
