<?php

use App\Enums\AuditLogAction;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the audit log resource only to superadmins', function () {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create([
        'name' => 'Northwind Towers',
    ]);

    AuditLog::factory()->create([
        'organization_id' => $organization->id,
        'actor_user_id' => $superadmin->id,
        'action' => AuditLogAction::SUSPENDED,
        'description' => 'Organization suspended',
        'metadata' => [
            'before' => ['status' => 'active'],
            'after' => ['status' => 'suspended'],
        ],
    ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.audit-logs.index'))
        ->assertSuccessful()
        ->assertSeeText('Audit Logs')
        ->assertSeeText('Organization suspended')
        ->assertSeeText($superadmin->name)
        ->assertSeeText('suspended');

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.audit-logs.index'))
        ->assertForbidden();
});
