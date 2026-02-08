<?php

use App\Enums\UserRole;
use App\Filament\Pages\SystemSettings;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    // Create a superadmin user
    $this->superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'email' => 'superadmin@test.com',
    ]);
});

test('superadmin can access system settings page', function () {
    actingAs($this->superadmin);

    $response = get(SystemSettings::getUrl());

    $response->assertSuccessful();
});

test('non-superadmin cannot access system settings page', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    actingAs($admin);

    $response = get(SystemSettings::getUrl());

    $response->assertForbidden();
});

test('system settings page displays email configuration section', function () {
    actingAs($this->superadmin);

    $response = get(SystemSettings::getUrl());

    $response->assertSuccessful();
    $response->assertSee('Email Configuration');
});

test('system settings page displays backup configuration section', function () {
    actingAs($this->superadmin);

    $response = get(SystemSettings::getUrl());

    $response->assertSuccessful();
    $response->assertSee('Backup Configuration');
    $response->assertSee('Backup Schedule');
    $response->assertSee('Retention Period');
});

test('system settings page displays queue configuration section', function () {
    actingAs($this->superadmin);

    $response = get(SystemSettings::getUrl());

    $response->assertSuccessful();
    $response->assertSee('Queue Configuration');
    $response->assertSee('Default Queue Connection');
    $response->assertSee('Retry Attempts');
});

test('system settings page displays feature flags section', function () {
    actingAs($this->superadmin);

    $response = get(SystemSettings::getUrl());

    $response->assertSuccessful();
    $response->assertSee('Feature Flags');
    $response->assertSee('Maintenance Mode');
    $response->assertSee('User Registration');
});

test('system settings page displays platform settings section', function () {
    actingAs($this->superadmin);

    $response = get(SystemSettings::getUrl());

    $response->assertSuccessful();
    $response->assertSee('Platform Settings');
    $response->assertSee('Default Timezone');
    $response->assertSee('Default Locale');
    $response->assertSee('Session Timeout');
});

test('system settings page has save configuration action', function () {
    actingAs($this->superadmin);

    $response = get(SystemSettings::getUrl());

    $response->assertSuccessful();
    $response->assertSee('Save Configuration');
});

test('system settings page has reset to defaults action', function () {
    actingAs($this->superadmin);

    $response = get(SystemSettings::getUrl());

    $response->assertSuccessful();
    $response->assertSee('Reset to Defaults');
});

test('system settings page has export configuration action', function () {
    actingAs($this->superadmin);

    $response = get(SystemSettings::getUrl());

    $response->assertSuccessful();
    $response->assertSee('Export Configuration');
});

test('system settings page has import configuration action', function () {
    actingAs($this->superadmin);

    $response = get(SystemSettings::getUrl());

    $response->assertSuccessful();
    $response->assertSee('Import Configuration');
});

test('system settings page has send test email action', function () {
    actingAs($this->superadmin);

    $response = get(SystemSettings::getUrl());

    $response->assertSuccessful();
    $response->assertSee('Send Test Email');
});

test('system settings page loads default values from config', function () {
    actingAs($this->superadmin);

    Config::set('mail.default', 'smtp');
    Config::set('mail.mailers.smtp.host', 'smtp.example.com');
    Config::set('mail.mailers.smtp.port', 587);

    $response = get(SystemSettings::getUrl());

    $response->assertSuccessful();
});

test('system settings page displays configuration notes', function () {
    actingAs($this->superadmin);

    $response = get(SystemSettings::getUrl());

    $response->assertSuccessful();
    $response->assertSee('Configuration Notes');
    $response->assertSee('Email settings require valid SMTP credentials');
});

test('system settings page displays warning message', function () {
    actingAs($this->superadmin);

    $response = get(SystemSettings::getUrl());

    $response->assertSuccessful();
    $response->assertSee('Important Warning');
    $response->assertSee('Modifying system settings can affect platform stability');
});

test('system settings page is in system navigation group', function () {
    expect(SystemSettings::getNavigationGroup())->toBe('System');
});

test('system settings page has correct navigation sort order', function () {
    expect(SystemSettings::getNavigationSort())->toBe(3);
});

test('system settings page has correct icon', function () {
    expect(SystemSettings::getNavigationIcon())->toBe('heroicon-o-cog-6-tooth');
});

test('system settings page has correct title', function () {
    actingAs($this->superadmin);

    $response = get(SystemSettings::getUrl());

    $response->assertSuccessful();
    $response->assertSee('System Settings');
});
