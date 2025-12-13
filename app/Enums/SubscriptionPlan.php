<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SubscriptionPlan: string implements HasLabel, HasColor
{
    case STARTER = 'starter';
    case PROFESSIONAL = 'professional';
    case ENTERPRISE = 'enterprise';
    case CUSTOM = 'custom';
    
    public function getLabel(): string
    {
        return match($this) {
            self::STARTER => __('superadmin.subscription.plan.starter'),
            self::PROFESSIONAL => __('superadmin.subscription.plan.professional'),
            self::ENTERPRISE => __('superadmin.subscription.plan.enterprise'),
            self::CUSTOM => __('superadmin.subscription.plan.custom'),
        };
    }
    
    public function getColor(): string
    {
        return match($this) {
            self::STARTER => 'gray',
            self::PROFESSIONAL => 'info',
            self::ENTERPRISE => 'success',
            self::CUSTOM => 'warning',
        };
    }
    
    public function getMaxProperties(): int
    {
        return match($this) {
            self::STARTER => 100,
            self::PROFESSIONAL => 500,
            self::ENTERPRISE => 9999,
            self::CUSTOM => 9999,
        };
    }
    
    public function getMaxUsers(): int
    {
        return match($this) {
            self::STARTER => 10,
            self::PROFESSIONAL => 50,
            self::ENTERPRISE => 999,
            self::CUSTOM => 999,
        };
    }
    
    public function getMonthlyPrice(): float
    {
        return match($this) {
            self::STARTER => 29.99,
            self::PROFESSIONAL => 99.99,
            self::ENTERPRISE => 299.99,
            self::CUSTOM => 0.00, // Custom pricing
        };
    }
    
    public function getFeatures(): array
    {
        return match($this) {
            self::STARTER => [
                'basic_reporting',
                'email_support',
                'standard_integrations',
            ],
            self::PROFESSIONAL => [
                'advanced_reporting',
                'priority_support',
                'api_access',
                'bulk_operations',
                'audit_logs',
            ],
            self::ENTERPRISE => [
                'custom_reporting',
                'dedicated_support',
                'full_api_access',
                'advanced_integrations',
                'custom_branding',
                'sso_integration',
                'advanced_security',
            ],
            self::CUSTOM => [
                'all_features',
                'custom_development',
                'dedicated_infrastructure',
            ],
        };
    }
}