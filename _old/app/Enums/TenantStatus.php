<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TenantStatus: string implements HasLabel, HasColor
{
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case PENDING = 'pending';
    case CANCELLED = 'cancelled';
    
    public function getLabel(): string
    {
        return match($this) {
            self::ACTIVE => __('superadmin.tenant.status.active'),
            self::SUSPENDED => __('superadmin.tenant.status.suspended'),
            self::PENDING => __('superadmin.tenant.status.pending'),
            self::CANCELLED => __('superadmin.tenant.status.cancelled'),
        };
    }
    
    public function getColor(): string
    {
        return match($this) {
            self::ACTIVE => 'success',
            self::SUSPENDED => 'warning',
            self::PENDING => 'info',
            self::CANCELLED => 'danger',
        };
    }
    
    public function getIcon(): string
    {
        return match($this) {
            self::ACTIVE => 'heroicon-o-check-circle',
            self::SUSPENDED => 'heroicon-o-pause-circle',
            self::PENDING => 'heroicon-o-clock',
            self::CANCELLED => 'heroicon-o-x-circle',
        };
    }
    
    public function canTransitionTo(self $status): bool
    {
        return match($this) {
            self::PENDING => in_array($status, [self::ACTIVE, self::CANCELLED]),
            self::ACTIVE => in_array($status, [self::SUSPENDED, self::CANCELLED]),
            self::SUSPENDED => in_array($status, [self::ACTIVE, self::CANCELLED]),
            self::CANCELLED => false, // Cannot transition from cancelled
        };
    }
}