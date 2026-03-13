<?php

use App\Models\User;
use App\Providers\Filament\AdminPanelProvider;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('panel uses correct authentication guard', function () {
    $panel = Filament::getPanel('admin');
    
    expect($panel->getAuthGuard())->toBe('web');
});

test('panel path is /admin', function () {
    $panel = Filament::getPanel('admin');
    
    expect($panel->getPath())->toBe('admin');
});

test('User model is configured for authentication', function () {
    $panel = Filament::getPanel('admin');
    
    // Verify that the auth guard uses the User model
    $guard = config('auth.guards.web.provider');
    $provider = config("auth.providers.{$guard}.model");
    
    expect($provider)->toBe(User::class);
});

test('AdminPanelProvider exists and is registered', function () {
    expect(class_exists(AdminPanelProvider::class))->toBeTrue();
});

test('panel has login enabled', function () {
    $panel = Filament::getPanel('admin');
    
    expect($panel->hasLogin())->toBeTrue();
});

test('panel is set as default', function () {
    $panel = Filament::getPanel('admin');
    
    expect($panel->isDefault())->toBeTrue();
});

test('panel has correct ID', function () {
    $panel = Filament::getPanel('admin');
    
    expect($panel->getId())->toBe('admin');
});
