<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\PlatformNotification;
use App\Models\PlatformNotificationRecipient;
use App\Models\PlatformOrganizationInvitation;
use App\Models\SuperAdminAuditLog;
use App\Models\SystemConfiguration;
use App\Models\SystemTenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class LegacyPlatformFoundationSeeder extends Seeder
{
    public function run(): void
    {
        $superadmin = User::query()
            ->select(['id'])
            ->where('role', 'superadmin')
            ->orderBy('id')
            ->first();

        $organization = Organization::query()
            ->select(['id', 'name', 'slug'])
            ->orderBy('id')
            ->first();

        if ($superadmin === null || $organization === null) {
            return;
        }

        $systemTenant = SystemTenant::query()->updateOrCreate(
            ['slug' => 'tenanto-platform'],
            [
                'name' => 'Tenanto Platform',
                'domain' => 'tenanto.test',
                'status' => 'active',
                'subscription_plan' => 'enterprise',
                'settings' => ['timezone' => 'Europe/Vilnius'],
                'resource_quotas' => ['max_users' => 500],
                'billing_info' => ['currency' => 'EUR'],
                'primary_contact_email' => 'platform@tenanto.test',
                'created_by_admin_id' => $superadmin->id,
            ],
        );

        $superadmin->forceFill([
            'system_tenant_id' => $systemTenant->id,
            'is_super_admin' => true,
        ])->save();

        $organization->forceFill([
            'system_tenant_id' => $systemTenant->id,
        ])->save();

        SystemConfiguration::query()->updateOrCreate(
            ['key' => 'platform.default_currency'],
            [
                'value' => ['value' => 'EUR'],
                'type' => 'string',
                'description' => 'Default billing currency for platform-level operations.',
                'category' => 'billing',
                'validation_rules' => null,
                'default_value' => ['value' => 'EUR'],
                'is_tenant_configurable' => false,
                'requires_restart' => false,
                'updated_by_admin_id' => $superadmin->id,
            ],
        );

        $notification = PlatformNotification::query()->updateOrCreate(
            ['title' => 'Platform Foundation Ready'],
            [
                'body' => 'Legacy platform support foundation has been imported.',
                'severity' => 'info',
                'status' => 'sent',
                'scheduled_for' => null,
                'sent_at' => now(),
            ],
        );

        PlatformNotificationRecipient::query()->updateOrCreate(
            [
                'platform_notification_id' => $notification->id,
                'organization_id' => $organization->id,
                'email' => 'admin@example.com',
            ],
            [
                'delivery_status' => 'sent',
                'sent_at' => now(),
                'read_at' => null,
                'failure_reason' => null,
            ],
        );

        PlatformOrganizationInvitation::query()->updateOrCreate(
            [
                'organization_name' => 'Invited Legacy Organization',
                'admin_email' => 'legacy-owner@example.com',
            ],
            [
                'plan_type' => 'professional',
                'max_properties' => 25,
                'max_users' => 15,
                'token' => 'legacy-platform-foundation-token',
                'status' => 'pending',
                'expires_at' => now()->addDays(7),
                'accepted_at' => null,
                'invited_by' => $superadmin->id,
            ],
        );

        SuperAdminAuditLog::query()->updateOrCreate(
            [
                'admin_id' => $superadmin->id,
                'action' => 'legacy.platform.seeded',
                'system_tenant_id' => $systemTenant->id,
            ],
            [
                'target_type' => Organization::class,
                'target_id' => $organization->id,
                'changes' => ['system_tenant_id' => ['old' => null, 'new' => $systemTenant->id]],
                'ip_address' => '127.0.0.1',
                'user_agent' => 'legacy-platform-seeder',
                'impersonation_session_id' => null,
                'metadata' => ['seed' => true],
            ],
        );
    }
}
