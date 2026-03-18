<?php

declare(strict_types=1);

use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates an organization admin workspace helper with an active subscription', function (): void {
    [
        'organization' => $organization,
        'admin' => $admin,
        'subscription' => $subscription,
    ] = createOrgWithAdmin();

    expect($admin->organization_id)->toBe($organization->id)
        ->and($organization->owner_user_id)->toBe($admin->id)
        ->and($subscription->organization_id)->toBe($organization->id)
        ->and($subscription->isActiveLike())->toBeTrue();
});

it('creates a tenant assignment helper inside the admins organization', function (): void {
    [
        'admin' => $admin,
    ] = createOrgWithAdmin();

    [
        'building' => $building,
        'property' => $property,
        'tenant' => $tenant,
        'assignment' => $assignment,
    ] = createTenantInOrg($admin);

    expect($building->organization_id)->toBe($admin->organization_id)
        ->and($property->organization_id)->toBe($admin->organization_id)
        ->and($tenant->organization_id)->toBe($admin->organization_id)
        ->and($assignment->organization_id)->toBe($admin->organization_id)
        ->and($assignment->tenant_user_id)->toBe($tenant->id)
        ->and($assignment->property_id)->toBe($property->id);
});

it('signs in the requested role helper', function (UserRole $role): void {
    $user = signInAs($role);

    expect(auth()->id())->toBe($user->id)
        ->and($user->role)->toBe($role);
})->with([
    UserRole::SUPERADMIN,
    UserRole::ADMIN,
    UserRole::MANAGER,
    UserRole::TENANT,
]);
