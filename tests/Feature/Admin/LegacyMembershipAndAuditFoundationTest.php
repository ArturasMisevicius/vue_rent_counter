<?php

use App\Models\Organization;
use App\Models\OrganizationActivityLog;
use App\Models\OrganizationUser;
use App\Models\SuperAdminAuditLog;
use App\Models\SystemTenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates the remaining legacy membership and audit support tables and models', function () {
    expect(Schema::hasTable('organization_activity_logs'))->toBeTrue()
        ->and(Schema::hasTable('organization_user'))->toBeTrue()
        ->and(Schema::hasTable('super_admin_audit_logs'))->toBeTrue()
        ->and(class_exists(OrganizationActivityLog::class))->toBeTrue()
        ->and(class_exists(OrganizationUser::class))->toBeTrue()
        ->and(class_exists(SuperAdminAuditLog::class))->toBeTrue();
});

it('links the remaining legacy membership and audit support through current organizations and users', function () {
    $systemAdmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create();
    $member = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $systemTenant = SystemTenant::factory()
        ->for($systemAdmin, 'createdByAdmin')
        ->create();

    $systemAdmin->forceFill([
        'system_tenant_id' => $systemTenant->id,
        'is_super_admin' => true,
    ])->save();

    $organization->forceFill([
        'system_tenant_id' => $systemTenant->id,
    ])->save();

    $membership = OrganizationUser::query()->create([
        'organization_id' => $organization->id,
        'user_id' => $member->id,
        'role' => 'manager',
        'permissions' => ['reports.view'],
        'joined_at' => now()->subMonth(),
        'left_at' => null,
        'is_active' => true,
        'invited_by' => $systemAdmin->id,
    ]);

    $activity = OrganizationActivityLog::query()->create([
        'organization_id' => $organization->id,
        'user_id' => $member->id,
        'action' => 'property.updated',
        'resource_type' => Organization::class,
        'resource_id' => $organization->id,
        'metadata' => ['source' => 'test'],
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
    ]);

    $auditLog = SuperAdminAuditLog::query()->create([
        'admin_id' => $systemAdmin->id,
        'action' => 'organization.updated',
        'target_type' => Organization::class,
        'target_id' => $organization->id,
        'system_tenant_id' => $systemTenant->id,
        'changes' => ['status' => ['old' => 'trial', 'new' => 'active']],
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
        'impersonation_session_id' => null,
        'metadata' => ['source' => 'test'],
    ]);

    expect($organization->fresh()->activityLogs->contains($activity))->toBeTrue()
        ->and($organization->fresh()->memberships->contains($membership))->toBeTrue()
        ->and($member->fresh()->organizationMemberships->contains($membership))->toBeTrue()
        ->and($systemAdmin->fresh()->superAdminAuditLogs->contains($auditLog))->toBeTrue()
        ->and($systemTenant->fresh()->superAdminAuditLogs->contains($auditLog))->toBeTrue();
});
