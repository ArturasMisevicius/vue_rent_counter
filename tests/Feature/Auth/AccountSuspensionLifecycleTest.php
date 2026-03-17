<?php

use App\Enums\OrganizationStatus;
use App\Filament\Actions\Superadmin\Organizations\SuspendOrganizationAction;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('shows the suspended-account message on login when the organization is suspended', function () {
    $organization = Organization::factory()->create([
        'status' => OrganizationStatus::SUSPENDED,
    ]);

    $admin = User::factory()->admin()->for($organization)->create([
        'password' => 'password',
    ]);

    $this->from(route('login'))
        ->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors([
            'email' => __('auth.account_suspended'),
        ]);
});

it('invalidates all active organization sessions when an organization is suspended', function () {
    $organization = Organization::factory()->create();
    $member = User::factory()->admin()->for($organization)->create();
    $otherOrganization = Organization::factory()->create();
    $otherMember = User::factory()->admin()->for($otherOrganization)->create();

    DB::table('sessions')->insert([
        [
            'id' => 'organization-session',
            'user_id' => $member->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Pest',
            'payload' => base64_encode('payload'),
            'last_activity' => now()->timestamp,
        ],
        [
            'id' => 'other-session',
            'user_id' => $otherMember->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Pest',
            'payload' => base64_encode('payload'),
            'last_activity' => now()->timestamp,
        ],
    ]);

    app(SuspendOrganizationAction::class)
        ->handle($organization);

    expect(DB::table('sessions')->where('user_id', $member->id)->count())->toBe(0)
        ->and(DB::table('sessions')->where('user_id', $otherMember->id)->count())->toBe(1)
        ->and($organization->fresh()->status)->toBe(OrganizationStatus::SUSPENDED);
});
