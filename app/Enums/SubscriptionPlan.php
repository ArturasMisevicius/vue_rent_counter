<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum SubscriptionPlan: string implements HasLabel
{
    use HasTranslatedLabel;

    case STARTER = 'starter';
    case BASIC = 'basic';
    case PROFESSIONAL = 'professional';
    case ENTERPRISE = 'enterprise';
    case CUSTOM = 'custom';

    /**
     * @return array{properties: int, tenants: int, meters: int, invoices: int}
     */
    public function limits(): array
    {
        return match ($this) {
            self::STARTER => [
                'properties' => 3,
                'tenants' => 8,
                'meters' => 15,
                'invoices' => 30,
            ],
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
            self::CUSTOM => [
                'properties' => 2500,
                'tenants' => 10000,
                'meters' => 20000,
                'invoices' => 100000,
            ],
        };
    }
}
