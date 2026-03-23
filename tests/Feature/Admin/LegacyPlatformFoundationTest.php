<?php

use App\Models\Organization;
use App\Models\PlatformOrganizationInvitation;
use App\Models\SystemConfiguration;
use App\Models\SystemTenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates the legacy platform support tables and models', function () {
    expect(Schema::hasTable('system_tenants'))->toBeTrue()
        ->and(Schema::hasTable('system_configurations'))->toBeTrue()
        ->and(Schema::hasTable('platform_notification_recipients'))->toBeFalse()
        ->and(Schema::hasTable('platform_organization_invitations'))->toBeTrue()
        ->and(Schema::hasColumns('users', ['system_tenant_id', 'is_super_admin']))->toBeTrue()
        ->and(Schema::hasColumns('organizations', ['system_tenant_id']))->toBeTrue()
        ->and(class_exists(SystemTenant::class))->toBeTrue()
        ->and(class_exists(SystemConfiguration::class))->toBeTrue()
        ->and(class_exists('App\\Models\\PlatformNotificationRecipient', false))->toBeFalse()
        ->and(class_exists(PlatformOrganizationInvitation::class))->toBeTrue();
});

it('links legacy platform support through organizations and users', function () {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create();

    $systemTenant = SystemTenant::factory()
        ->for($superadmin, 'createdByAdmin')
        ->create();

    $organization->forceFill([
        'system_tenant_id' => $systemTenant->id,
    ])->save();

    $superadmin->forceFill([
        'system_tenant_id' => $systemTenant->id,
        'is_super_admin' => true,
    ])->save();

    $configuration = SystemConfiguration::factory()
        ->for($superadmin, 'updatedByAdmin')
        ->create();

    $invitation = PlatformOrganizationInvitation::factory()
        ->for($superadmin, 'inviter')
        ->create();

    expect($organization->fresh()->systemTenant?->is($systemTenant))->toBeTrue()
        ->and($systemTenant->fresh()->organizations->contains($organization))->toBeTrue()
        ->and($superadmin->fresh()->systemTenant?->is($systemTenant))->toBeTrue()
        ->and($superadmin->is_super_admin)->toBeTrue()
        ->and($configuration->fresh()->updatedByAdmin?->is($superadmin))->toBeTrue()
        ->and($invitation->fresh()->inviter?->is($superadmin))->toBeTrue()
        ->and($systemTenant->fresh()->createdByAdmin?->is($superadmin))->toBeTrue();
});
