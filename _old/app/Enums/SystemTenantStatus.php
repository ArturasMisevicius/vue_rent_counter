<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Enums\Concerns\HasColor;

enum SystemTenantStatus: string
{
    use HasLabel, HasColor;

    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case PENDING = 'pending';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => __('enums.system_tenant_status.active'),
            self::SUSPENDED => __('enums.system_tenant_status.suspended'),
            self::PENDING => __('enums.system_tenant_status.pending'),
            self::CANCELLED => __('enums.system_tenant_status.cancelled'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::SUSPENDED => 'warning',
            self::PENDING => 'info',
            self::CANCELLED => 'danger',
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::PENDING => in_array($status, [self::ACTIVE, self::CANCELLED]),
            self::ACTIVE => in_array($status, [self::SUSPENDED, self::CANCELLED]),
            self::SUSPENDED => in_array($status, [self::ACTIVE, self::CANCELLED]),
            self::CANCELLED => false,
        };
    }
}