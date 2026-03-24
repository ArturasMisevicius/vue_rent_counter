<?php

use App\Enums\AuditLogAction;
use App\Enums\SystemSettingCategory;
use App\Filament\Actions\Superadmin\SystemConfiguration\UpdateSystemSettingAction;
use App\Filament\Pages\SystemConfiguration as SystemConfigurationPage;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows grouped system configuration only to superadmins', function () {
    $superadmin = User::factory()->superadmin()->create();
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    SystemSetting::factory()->create([
        'category' => SystemSettingCategory::BILLING,
        'label' => 'Billing Currency',
        'key' => 'platform.billing.currency',
        'value' => ['value' => 'EUR'],
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.pages.system-configuration'))
        ->assertSuccessful()
        ->assertSeeText('System Configuration')
        ->assertSeeText('Billing')
        ->assertSeeText('platform.billing.currency');

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.system-configuration'))
        ->assertForbidden();
});

it('renders only the seeded system configuration categories with descriptions and edit links', function () {
    $superadmin = User::factory()->superadmin()->create();

    SystemSetting::factory()->create([
        'category' => SystemSettingCategory::GENERAL,
        'label' => 'Platform Name',
        'key' => 'platform.name',
        'value' => ['value' => 'Tenanto'],
    ]);

    SystemSetting::factory()->create([
        'category' => SystemSettingCategory::BILLING,
        'label' => 'Billing Currency',
        'key' => 'platform.billing.currency',
        'value' => ['value' => 'EUR'],
    ]);

    SystemSetting::factory()->create([
        'category' => SystemSettingCategory::NOTIFICATIONS,
        'label' => 'Email Notifications Enabled',
        'key' => 'platform.notifications.email.enabled',
        'value' => ['value' => true],
    ]);

    SystemSetting::factory()->create([
        'category' => SystemSettingCategory::SECURITY,
        'label' => 'Require MFA',
        'key' => 'platform.security.require_mfa',
        'value' => ['value' => false],
    ]);

    SystemSetting::factory()->create([
        'category' => SystemSettingCategory::SUBSCRIPTION,
        'label' => 'Default Property Limit',
        'key' => 'platform.subscription.default_property_limit',
        'value' => ['value' => 25],
    ]);

    SystemSetting::factory()->create([
        'category' => SystemSettingCategory::EMAIL,
        'label' => 'Default From Address',
        'key' => 'platform.email.from_address',
        'value' => ['value' => 'platform@example.test'],
    ]);

    SystemSetting::factory()->create([
        'category' => SystemSettingCategory::LOCALIZATION,
        'label' => 'Supported Locales',
        'key' => 'platform.localization.supported_locales',
        'value' => ['value' => ['en', 'lt']],
    ]);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.pages.system-configuration'))
        ->assertSuccessful()
        ->assertSeeText('System Configuration')
        ->assertSeeText('Billing')
        ->assertSeeText('Notifications')
        ->assertSeeText('Security')
        ->assertSeeText('Subscription Limits')
        ->assertSeeText('Email')
        ->assertSeeText('Localization')
        ->assertDontSeeText('General')
        ->assertDontSeeText('platform.name')
        ->assertSeeText('platform.billing.currency')
        ->assertSeeText('Default billing currency for platform invoices.')
        ->assertSeeText('EUR')
        ->assertSeeText('platform.notifications.email.enabled')
        ->assertSeeText('Enables email delivery for platform notifications.')
        ->assertSeeText('true')
        ->assertSeeText('platform.security.require_mfa')
        ->assertSeeText('Requires multi-factor authentication for superadmin accounts.')
        ->assertSeeText('false')
        ->assertSeeText('platform.subscription.default_property_limit')
        ->assertSeeText('Sets the default property cap applied to subscription limits.')
        ->assertSeeText('25')
        ->assertSeeText('platform.email.from_address')
        ->assertSeeText('Defines the sender address used for platform emails.')
        ->assertSeeText('platform@example.test')
        ->assertSeeText('platform.localization.supported_locales')
        ->assertSeeText('Lists the locales available across the platform.')
        ->assertSeeText('en, lt')
        ->assertSeeText('Edit');
});

it('supports inline edit save and cancel flows for system settings', function () {
    $superadmin = User::factory()->superadmin()->create();
    $this->actingAs($superadmin);

    $billingSetting = SystemSetting::factory()->create([
        'category' => SystemSettingCategory::BILLING,
        'label' => 'Billing Currency',
        'key' => 'platform.billing.currency',
        'value' => ['value' => 'EUR'],
    ]);

    $localesSetting = SystemSetting::factory()->create([
        'category' => SystemSettingCategory::LOCALIZATION,
        'label' => 'Supported Locales',
        'key' => 'platform.localization.supported_locales',
        'value' => ['value' => ['en', 'lt']],
    ]);

    Livewire::test(SystemConfigurationPage::class)
        ->call('startEditing', $billingSetting->id)
        ->assertSet("editing.{$billingSetting->id}", true)
        ->assertSet("draftValues.{$billingSetting->id}", 'EUR')
        ->set("draftValues.{$billingSetting->id}", 'USD')
        ->call('saveSetting', $billingSetting->id)
        ->assertSet("editing.{$billingSetting->id}", false)
        ->assertSet('savedMessage', 'Configuration updated.');

    expect($billingSetting->fresh()->value)->toBe(['value' => 'USD']);

    Livewire::test(SystemConfigurationPage::class)
        ->call('startEditing', $localesSetting->id)
        ->assertSet("editing.{$localesSetting->id}", true)
        ->assertSet("draftValues.{$localesSetting->id}", 'en, lt')
        ->set("draftValues.{$localesSetting->id}", 'en, lt, ru')
        ->call('cancelEditing', $localesSetting->id)
        ->assertSet("editing.{$localesSetting->id}", false)
        ->assertSet('savedMessage', null);

    expect($localesSetting->fresh()->value)->toBe(['value' => ['en', 'lt']]);
});

it('updates system settings through the action and writes audit history', function () {
    $superadmin = User::factory()->superadmin()->create();
    $this->actingAs($superadmin);

    $setting = SystemSetting::factory()->create([
        'label' => 'Platform Name',
        'key' => 'platform.name',
        'value' => ['value' => 'Tenanto'],
    ]);

    $updated = app(UpdateSystemSettingAction::class)->handle($setting, [
        'value' => 'Tenanto OS',
    ]);

    $audit = AuditLog::query()
        ->where('subject_type', SystemSetting::class)
        ->where('subject_id', $setting->id)
        ->where('action', AuditLogAction::UPDATED)
        ->latest('id')
        ->first();

    expect($updated->value)->toBe(['value' => 'Tenanto OS'])
        ->and($audit)->not->toBeNull();
});
