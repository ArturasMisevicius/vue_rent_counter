<?php

use App\Filament\Support\Superadmin\Organizations\OrganizationListPresetRegistry;

it('defines the pinned presets for the organizations list', function () {
    $presets = OrganizationListPresetRegistry::presets();

    expect(array_keys($presets))->toBe([
        'overdue_orgs',
        'expiring_trials',
        'high_value',
        'new_this_month',
    ])->and($presets['overdue_orgs'])->toMatchArray([
        'label' => __('superadmin.organizations.presets.overdue_orgs'),
        'filters' => [
            'has_overdue_invoices' => true,
        ],
    ])->and($presets['expiring_trials'])->toMatchArray([
        'label' => __('superadmin.organizations.presets.expiring_trials'),
    ])->and($presets['high_value'])->toMatchArray([
        'label' => __('superadmin.organizations.presets.high_value'),
        'constraints' => [
            'mrr_min' => 500,
        ],
    ])->and($presets['new_this_month'])->toMatchArray([
        'label' => __('superadmin.organizations.presets.new_this_month'),
    ]);
});
