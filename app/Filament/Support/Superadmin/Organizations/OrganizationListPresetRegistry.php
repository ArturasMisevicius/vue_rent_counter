<?php

namespace App\Filament\Support\Superadmin\Organizations;

final class OrganizationListPresetRegistry
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function presets(): array
    {
        return [
            'overdue_orgs' => [
                'label' => __('superadmin.organizations.presets.overdue_orgs'),
                'filters' => [
                    'has_overdue_invoices' => true,
                ],
            ],
            'expiring_trials' => [
                'label' => __('superadmin.organizations.presets.expiring_trials'),
                'filters' => [
                    'trial_expiry_range' => [
                        'trial_expires_from' => now()->toDateString(),
                        'trial_expires_to' => now()->addDays(7)->toDateString(),
                    ],
                ],
            ],
            'high_value' => [
                'label' => __('superadmin.organizations.presets.high_value'),
                'constraints' => [
                    'mrr_min' => 500,
                ],
            ],
            'new_this_month' => [
                'label' => __('superadmin.organizations.presets.new_this_month'),
                'filters' => [
                    'created_between' => [
                        'created_from' => now()->startOfMonth()->toDateString(),
                        'created_to' => now()->endOfMonth()->toDateString(),
                    ],
                ],
            ],
        ];
    }
}
