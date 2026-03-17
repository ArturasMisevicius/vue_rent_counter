<?php

namespace App\Enums;

enum SubscriptionPlan: string
{
    case BASIC = 'basic';
    case PROFESSIONAL = 'professional';
    case ENTERPRISE = 'enterprise';

    public function label(): string
    {
        return match ($this) {
            self::BASIC => 'Basic',
            self::PROFESSIONAL => 'Professional',
            self::ENTERPRISE => 'Enterprise',
        };
    }

    /**
     * @return array{properties: int, tenants: int, meters: int, invoices: int}
     */
    public function limits(): array
    {
        return match ($this) {
            self::BASIC => [
                'properties' => 10,
                'tenants' => 25,
                'meters' => 50,
                'invoices' => 100,
            ],
            self::PROFESSIONAL => [
                'properties' => 50,
                'tenants' => 150,
                'meters' => 300,
                'invoices' => 750,
            ],
            self::ENTERPRISE => [
                'properties' => 500,
                'tenants' => 1500,
                'meters' => 3000,
                'invoices' => 10000,
            ],
        };
    }
}
