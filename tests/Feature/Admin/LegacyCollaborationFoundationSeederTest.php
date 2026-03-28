<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\OrganizationUser;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\LegacyCollaborationFoundationSeeder;
use Database\Seeders\LoginDemoUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

it('keeps login demo tenants scoped as tenants when seeding the legacy collaboration foundation', function (): void {
    Artisan::call('db:seed', [
        '--class' => LoginDemoUsersSeeder::class,
        '--no-interaction' => true,
    ]);

    $tenant = User::query()
        ->where('email', 'tenant.alina@example.com')
        ->firstOrFail();
    $manager = User::query()
        ->where('email', 'manager@example.com')
        ->firstOrFail();

    expect(OrganizationUser::query()
        ->where('organization_id', $tenant->organization_id)
        ->where('user_id', $tenant->id)
        ->value('role'))->toBe(UserRole::TENANT->value);

    Artisan::call('db:seed', [
        '--class' => LegacyCollaborationFoundationSeeder::class,
        '--no-interaction' => true,
    ]);

    $project = Project::query()
        ->where('name', 'Legacy Collaboration Demo Project')
        ->firstOrFail();

    expect(OrganizationUser::query()
        ->where('organization_id', $tenant->organization_id)
        ->where('user_id', $tenant->id)
        ->value('role'))->toBe(UserRole::TENANT->value)
        ->and($project->manager_id)->toBe($manager->id);
});

it('defaults organization user factory records to supported roles without legacy permission payloads', function (): void {
    $membership = OrganizationUser::factory()->create();

    expect($membership->role)->toBeIn([
        UserRole::ADMIN->value,
        UserRole::MANAGER->value,
        UserRole::TENANT->value,
        'viewer',
    ])->and($membership->permissions)->toBeNull();
});
