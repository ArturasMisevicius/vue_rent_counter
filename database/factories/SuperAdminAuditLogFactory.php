<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\SuperAdminAuditLog;
use App\Models\SystemTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SuperAdminAuditLog>
 */
class SuperAdminAuditLogFactory extends Factory
{
    public function definition(): array
    {
        $admin = User::factory()->superadmin();
        $systemTenant = SystemTenant::factory()->for($admin, 'createdByAdmin');

        return [
            'admin_id' => $admin,
            'action' => fake()->randomElement([
                'organization.created',
                'organization.updated',
                'subscription.updated',
                'user.impersonated',
            ]),
            'target_type' => Organization::class,
            'target_id' => 1,
            'system_tenant_id' => $systemTenant,
            'changes' => ['status' => ['old' => 'trial', 'new' => 'active']],
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'impersonation_session_id' => null,
            'metadata' => ['source' => 'factory'],
        ];
    }
}
