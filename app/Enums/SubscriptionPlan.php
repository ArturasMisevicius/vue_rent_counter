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
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $plan): array => [$plan->value => $plan->label()])
            ->all();
    }

    /**
     * @return array<string, int>
     */
    public function limitsSnapshot(): array
    {
        return match ($this) {
            self::BASIC => [
                'users' => 5,
                'buildings' => 3,
                'properties' => 25,
                'meters' => 50,
            ],
            self::PROFESSIONAL => [
                'users' => 25,
                'buildings' => 15,
                'properties' => 250,
                'meters' => 500,
            ],
            self::ENTERPRISE => [
                'users' => 100,
                'buildings' => 50,
                'properties' => 1000,
                'meters' => 5000,
            ],
        };
    }

    /**
     * @return array{plan_name_snapshot: string, limits_snapshot: array<string, int>}
     */
    public function snapshotAttributes(): array
    {
        return [
            'plan_name_snapshot' => $this->label(),
            'limits_snapshot' => $this->limitsSnapshot(),
        ];
    }
}
