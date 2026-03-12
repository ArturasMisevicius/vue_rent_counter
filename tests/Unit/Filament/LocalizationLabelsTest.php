<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Pages\SystemSettings;
use App\Filament\Resources\PlatformUserResource;
use App\Models\User;

test('filament system settings navigation labels are localized', function () {
    app()->setLocale('en');

    expect(SystemSettings::getNavigationGroup())->toBe(__('filament.pages.system_settings.navigation_group'))
        ->and(SystemSettings::getNavigationLabel())->toBe(__('filament.pages.system_settings.navigation_label'));
});

test('platform user global search labels are localized', function () {
    app()->setLocale('lt');

    $user = User::factory()->make([
        'role' => UserRole::ADMIN,
        'organization_name' => null,
    ]);

    $details = PlatformUserResource::getGlobalSearchResultDetails($user);

    expect($details)->toHaveKey(__('filament.resources.platform_users.global_search.email'))
        ->and($details)->toHaveKey(__('filament.resources.platform_users.global_search.role'))
        ->and($details)->toHaveKey(__('filament.resources.platform_users.global_search.organization'))
        ->and($details[__('filament.resources.platform_users.global_search.organization')])->toBe(__('app.common.na'));
});
