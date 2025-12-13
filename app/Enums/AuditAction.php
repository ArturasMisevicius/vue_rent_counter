<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AuditAction: string implements HasLabel, HasColor
{
    case TENANT_CREATED = 'tenant_created';
    case TENANT_UPDATED = 'tenant_updated';
    case TENANT_SUSPENDED = 'tenant_suspended';
    case TENANT_ACTIVATED = 'tenant_activated';
    case TENANT_DELETED = 'tenant_deleted';
    case USER_IMPERSONATED = 'user_impersonated';
    case IMPERSONATION_ENDED = 'impersonation_ended';
    case BULK_OPERATION = 'bulk_operation';
    case SYSTEM_CONFIG_CHANGED = 'system_config_changed';
    case BACKUP_CREATED = 'backup_created';
    case BACKUP_RESTORED = 'backup_restored';
    case NOTIFICATION_SENT = 'notification_sent';
    case RESOURCE_QUOTA_CHANGED = 'resource_quota_changed';
    case BILLING_UPDATED = 'billing_updated';
    case FEATURE_FLAG_CHANGED = 'feature_flag_changed';
    
    public function getLabel(): string
    {
        return match($this) {
            self::TENANT_CREATED => __('superadmin.audit.action.tenant_created'),
            self::TENANT_UPDATED => __('superadmin.audit.action.tenant_updated'),
            self::TENANT_SUSPENDED => __('superadmin.audit.action.tenant_suspended'),
            self::TENANT_ACTIVATED => __('superadmin.audit.action.tenant_activated'),
            self::TENANT_DELETED => __('superadmin.audit.action.tenant_deleted'),
            self::USER_IMPERSONATED => __('superadmin.audit.action.user_impersonated'),
            self::IMPERSONATION_ENDED => __('superadmin.audit.action.impersonation_ended'),
            self::BULK_OPERATION => __('superadmin.audit.action.bulk_operation'),
            self::SYSTEM_CONFIG_CHANGED => __('superadmin.audit.action.system_config_changed'),
            self::BACKUP_CREATED => __('superadmin.audit.action.backup_created'),
            self::BACKUP_RESTORED => __('superadmin.audit.action.backup_restored'),
            self::NOTIFICATION_SENT => __('superadmin.audit.action.notification_sent'),
            self::RESOURCE_QUOTA_CHANGED => __('superadmin.audit.action.resource_quota_changed'),
            self::BILLING_UPDATED => __('superadmin.audit.action.billing_updated'),
            self::FEATURE_FLAG_CHANGED => __('superadmin.audit.action.feature_flag_changed'),
        };
    }
    
    public function getColor(): string
    {
        return match($this) {
            self::TENANT_CREATED => 'success',
            self::TENANT_UPDATED => 'info',
            self::TENANT_SUSPENDED => 'warning',
            self::TENANT_ACTIVATED => 'success',
            self::TENANT_DELETED => 'danger',
            self::USER_IMPERSONATED => 'warning',
            self::IMPERSONATION_ENDED => 'info',
            self::BULK_OPERATION => 'info',
            self::SYSTEM_CONFIG_CHANGED => 'warning',
            self::BACKUP_CREATED => 'success',
            self::BACKUP_RESTORED => 'warning',
            self::NOTIFICATION_SENT => 'info',
            self::RESOURCE_QUOTA_CHANGED => 'warning',
            self::BILLING_UPDATED => 'info',
            self::FEATURE_FLAG_CHANGED => 'warning',
        };
    }
    
    public function getIcon(): string
    {
        return match($this) {
            self::TENANT_CREATED => 'heroicon-o-plus-circle',
            self::TENANT_UPDATED => 'heroicon-o-pencil-square',
            self::TENANT_SUSPENDED => 'heroicon-o-pause-circle',
            self::TENANT_ACTIVATED => 'heroicon-o-play-circle',
            self::TENANT_DELETED => 'heroicon-o-trash',
            self::USER_IMPERSONATED => 'heroicon-o-user-circle',
            self::IMPERSONATION_ENDED => 'heroicon-o-arrow-left-on-rectangle',
            self::BULK_OPERATION => 'heroicon-o-squares-2x2',
            self::SYSTEM_CONFIG_CHANGED => 'heroicon-o-cog-6-tooth',
            self::BACKUP_CREATED => 'heroicon-o-archive-box',
            self::BACKUP_RESTORED => 'heroicon-o-arrow-uturn-left',
            self::NOTIFICATION_SENT => 'heroicon-o-bell',
            self::RESOURCE_QUOTA_CHANGED => 'heroicon-o-chart-bar',
            self::BILLING_UPDATED => 'heroicon-o-credit-card',
            self::FEATURE_FLAG_CHANGED => 'heroicon-o-flag',
        };
    }
    
    public function getSeverity(): string
    {
        return match($this) {
            self::TENANT_DELETED, self::BACKUP_RESTORED => 'high',
            self::TENANT_SUSPENDED, self::USER_IMPERSONATED, self::SYSTEM_CONFIG_CHANGED => 'medium',
            default => 'low',
        };
    }
}