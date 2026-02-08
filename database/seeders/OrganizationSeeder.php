<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\OrganizationActivityLog;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    /**
     * Seed organizations along with invitations and activity logs.
     */
    public function run(): void
    {
        $organizations = Organization::factory()
            ->count(3)
            ->sequence(
                [
                    'id' => 1,
                    'name' => 'Test Organization 1',
                    'domain' => 'org1.example.com',
                    'email' => 'org1@example.com',
                    'plan' => 'professional',
                    'max_properties' => 200,
                    'max_users' => 50,
                ],
                [
                    'id' => 2,
                    'name' => 'Test Organization 2',
                    'domain' => 'org2.example.com',
                    'email' => 'org2@example.com',
                    'plan' => 'basic',
                    'max_properties' => 100,
                    'max_users' => 25,
                ],
                [
                    'id' => 3,
                    'name' => 'Test Organization 3',
                    'domain' => 'org3.example.com',
                    'email' => 'org3@example.com',
                    'plan' => 'enterprise',
                    'max_properties' => 500,
                    'max_users' => 200,
                ],
            )
            ->create();

        foreach ($organizations as $organization) {
            $admin = User::where('tenant_id', $organization->id)
                ->where('role', UserRole::ADMIN)
                ->first();

            if (! $admin) {
                $admin = User::factory()->admin($organization->id)->create();
            }

            OrganizationInvitation::factory()
                ->count(2)
                ->for($organization)
                ->create([
                    'invited_by' => $admin->id,
                    'role' => UserRole::MANAGER->value,
                ]);

            OrganizationActivityLog::factory()
                ->count(3)
                ->for($organization)
                ->create([
                    'user_id' => $admin->id,
                    'action' => 'login',
                    'metadata' => ['seeded' => true],
                ]);
        }
    }
}
