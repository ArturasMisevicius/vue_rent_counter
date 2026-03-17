<?php

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the superadmin users resource pages only to superadmins', function () {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create([
        'name' => 'Northwind Towers',
    ]);

    $managedUser = User::factory()->admin()->create([
        'organization_id' => $organization->id,
        'name' => 'Avery Admin',
        'email' => 'avery@example.com',
    ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.users.index'))
        ->assertSuccessful()
        ->assertSeeText('Users')
        ->assertSeeText($managedUser->name)
        ->assertSeeText($managedUser->email)
        ->assertSeeText($organization->name);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.users.create'))
        ->assertSuccessful()
        ->assertSeeText('Role')
        ->assertSeeText('Organization')
        ->assertSeeText('Status');

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.users.view', $managedUser))
        ->assertSuccessful()
        ->assertSeeText($managedUser->name)
        ->assertSeeText($managedUser->email);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.users.edit', $managedUser))
        ->assertSuccessful()
        ->assertSeeText('Save changes')
        ->assertSeeText($managedUser->email);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.users.index'))
        ->assertForbidden();
});

it('keeps superadmin user deletion disabled when dependent activity exists', function () {
    $organization = Organization::factory()->create();
    $protectedUser = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    AuditLog::factory()->create([
        'organization_id' => $organization->id,
        'actor_user_id' => $protectedUser->id,
    ]);

    $disposableUser = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    expect($protectedUser->fresh()->canBeDeletedFromSuperadmin())->toBeFalse()
        ->and($disposableUser->fresh()->canBeDeletedFromSuperadmin())->toBeTrue();
});
