<?php

use Illuminate\Support\Facades\Route;

it('does not register platform notification resource routes anymore', function () {
    expect(Route::has('filament.admin.resources.platform-notifications.index'))->toBeFalse()
        ->and(Route::has('filament.admin.resources.platform-notifications.create'))->toBeFalse()
        ->and(Route::has('filament.admin.resources.platform-notifications.view'))->toBeFalse()
        ->and(Route::has('filament.admin.resources.platform-notifications.edit'))->toBeFalse();
});

it('removes the platform notification Filament resource class', function () {
    expect(class_exists('App\\Filament\\Resources\\PlatformNotifications\\PlatformNotificationResource', false))->toBeFalse();
});
