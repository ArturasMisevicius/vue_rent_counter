<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum SubscriptionPlan: string implements HasLabel
{
    use HasTranslatedLabel;

    case BASIC = 'basic';
    case PROFESSIONAL = 'professional';
    case ENTERPRISE = 'enterprise';

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
