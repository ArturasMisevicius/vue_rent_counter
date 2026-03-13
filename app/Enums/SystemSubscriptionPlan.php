<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

enum SystemSubscriptionPlan: string
{
    use HasLabel;

    case STARTER = 'starter';
    case PROFESSIONAL = 'professional';
    case ENTERPRISE = 'enterprise';
    case CUSTOM = 'custom';

    public function getLabel(): string
    {
        return match ($this) {
            self::STARTER => __('enums.system_subscription_plan.starter'),
            self::PROFESSIONAL => __('enums.system_subscription_plan.professional'),
            self::ENTERPRISE => __('enums.system_subscription_plan.enterprise'),
            self::CUSTOM => __('enums.system_subscription_plan.custom'),
        };
    }

    public function getDefaultQuotas(): array
    {
        return match ($this) {
            self::STARTER => [
                'max_users' => 10,
                'max_storage_gb' => 5,
                'max_api_calls_per_month' => 10000,
            ],
            self::PROFESSIONAL => [
                'max_users' => 50,
                'max_storage_gb' => 25,
                'max_api_calls_per_month' => 50000,
            ],
            self::ENTERPRISE => [
                'max_users' => 200,
                'max_storage_gb' => 100,
                'max_api_calls_per_month' => 200000,
            ],
            self::CUSTOM => [
                'max_users' => null,
                'max_storage_gb' => null,
                'max_api_calls_per_month' => null,
            ],
        };
    }
}