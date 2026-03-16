<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum SuperAdminAuditAction: string
{
    use HasLabel;

    case SYSTEM_TENANT_CREATED = 'system_tenant_created';
    case SYSTEM_TENANT_UPDATED = 'system_tenant_updated';
    case SYSTEM_TENANT_SUSPENDED = 'system_tenant_suspended';
    case SYSTEM_TENANT_ACTIVATED = 'system_tenant_activated';
    case SYSTEM_TENANT_DELETED = 'system_tenant_deleted';
    case USER_IMPERSONATED = 'user_impersonated';
    case IMPERSONATION_ENDED = 'impersonation_ended';
    case BULK_OPERATION = 'bulk_operation';
    case SYSTEM_CONFIG_CHANGED = 'system_config_changed';
    case BACKUP_CREATED = 'backup_created';
    case BACKUP_RESTORED = 'backup_restored';
    case NOTIFICATION_SENT = 'notification_sent';
    case FEATURE_FLAG_CHANGED = 'feature_flag_changed';
    case SECURITY_POLICY_CHANGED = 'security_policy_changed';

    public function getLabel(): string
    {
        return match ($this) {
            self::SYSTEM_TENANT_CREATED => __('enums.super_admin_audit_action.system_tenant_created'),
            self::SYSTEM_TENANT_UPDATED => __('enums.super_admin_audit_action.system_tenant_updated'),
            self::SYSTEM_TENANT_SUSPENDED => __('enums.super_admin_audit_action.system_tenant_suspended'),
            self::SYSTEM_TENANT_ACTIVATED => __('enums.super_admin_audit_action.system_tenant_activated'),
            self::SYSTEM_TENANT_DELETED => __('enums.super_admin_audit_action.system_tenant_deleted'),
            self::USER_IMPERSONATED => __('enums.super_admin_audit_action.user_impersonated'),
            self::IMPERSONATION_ENDED => __('enums.super_admin_audit_action.impersonation_ended'),
            self::BULK_OPERATION => __('enums.super_admin_audit_action.bulk_operation'),
            self::SYSTEM_CONFIG_CHANGED => __('enums.super_admin_audit_action.system_config_changed'),
            self::BACKUP_CREATED => __('enums.super_admin_audit_action.backup_created'),
            self::BACKUP_RESTORED => __('enums.super_admin_audit_action.backup_restored'),
            self::NOTIFICATION_SENT => __('enums.super_admin_audit_action.notification_sent'),
            self::FEATURE_FLAG_CHANGED => __('enums.super_admin_audit_action.feature_flag_changed'),
            self::SECURITY_POLICY_CHANGED => __('enums.super_admin_audit_action.security_policy_changed'),
        };
    }
}