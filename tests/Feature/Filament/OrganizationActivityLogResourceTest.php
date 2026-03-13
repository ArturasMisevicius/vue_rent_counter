<?php

use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationActivityLog;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
    ]);
    
    $this->organization = Organization::factory()->create();
    
    $this->actingAs($this->superadmin);
});

test('superadmin can view activity logs list', function () {
    OrganizationActivityLog::factory()->count(5)->create([
        'organization_id' => $this->organization->id,
    ]);
    
    $response = $this->get(route('filament.admin.resources.organization-activity-logs.index'));
    
    $response->assertSuccessful();
});

test('superadmin can view individual activity log', function () {
    $log = OrganizationActivityLog::factory()->create([
        'organization_id' => $this->organization->id,
        'user_id' => $this->superadmin->id,
        'action' => 'create',
        'resource_type' => 'App\\Models\\Organization',
        'resource_id' => $this->organization->id,
    ]);
    
    $response = $this->get(route('filament.admin.resources.organization-activity-logs.view', ['record' => $log->id]));
    
    $response->assertSuccessful();
});

test('non-superadmin cannot access activity logs', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $this->organization->id,
    ]);
    
    $this->actingAs($admin);
    
    $response = $this->get(route('filament.admin.resources.organization-activity-logs.index'));
    
    $response->assertForbidden();
});

test('activity log displays correct action badge colors', function () {
    $actions = [
        'create' => 'success',
        'update' => 'info',
        'delete' => 'danger',
        'suspend' => 'warning',
        'view' => 'gray',
    ];
    
    foreach ($actions as $action => $expectedColor) {
        $log = OrganizationActivityLog::factory()->create([
            'organization_id' => $this->organization->id,
            'action' => $action,
        ]);
        
        expect($log->action)->toBe($action);
    }
});

test('activity logs can be filtered by organization', function () {
    $org1 = Organization::factory()->create();
    $org2 = Organization::factory()->create();
    
    OrganizationActivityLog::factory()->count(3)->create(['organization_id' => $org1->id]);
    OrganizationActivityLog::factory()->count(2)->create(['organization_id' => $org2->id]);
    
    $logs = OrganizationActivityLog::where('organization_id', $org1->id)->get();
    
    expect($logs)->toHaveCount(3);
});

test('activity logs can be filtered by date range', function () {
    OrganizationActivityLog::factory()->create([
        'organization_id' => $this->organization->id,
        'created_at' => now()->subDays(10),
    ]);
    
    OrganizationActivityLog::factory()->create([
        'organization_id' => $this->organization->id,
        'created_at' => now()->subDays(5),
    ]);
    
    OrganizationActivityLog::factory()->create([
        'organization_id' => $this->organization->id,
        'created_at' => now()->subDay(),
    ]);
    
    $logs = OrganizationActivityLog::whereBetween('created_at', [
        now()->subDays(7)->startOfDay(),
        now()->endOfDay(),
    ])->get();
    
    expect($logs)->toHaveCount(2);
});

test('metadata is properly formatted in view', function () {
    $metadata = [
        'old_value' => 'test1',
        'new_value' => 'test2',
        'field' => 'name',
    ];
    
    $log = OrganizationActivityLog::factory()->create([
        'organization_id' => $this->organization->id,
        'metadata' => $metadata,
    ]);
    
    expect($log->metadata)->toBe($metadata);
    expect(json_encode($log->metadata))->toContain('old_value');
});
