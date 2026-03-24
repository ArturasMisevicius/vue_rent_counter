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
        ->assertSeeText(__('superadmin.system_configuration.title'))
        ->assertSeeText(__('superadmin.system_configuration.categories.billing'))
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
        ->assertSeeText(__('superadmin.system_configuration.title'))
        ->assertSeeText(__('superadmin.system_configuration.categories.billing'))
        ->assertSeeText(__('superadmin.system_configuration.categories.notifications'))
        ->assertSeeText(__('superadmin.system_configuration.categories.security'))
        ->assertSeeText(__('superadmin.system_configuration.categories.subscription_limits'))
        ->assertSeeText(__('superadmin.system_configuration.categories.email'))
        ->assertSeeText(__('superadmin.system_configuration.categories.localization'))
        ->assertDontSeeText('General')
        ->assertDontSeeText('platform.name')
        ->assertSeeText('platform.billing.currency')
        ->assertSeeText(__('superadmin.system_configuration.definitions.billing_currency.description'))
        ->assertSeeText('EUR')
        ->assertSeeText('platform.notifications.email.enabled')
        ->assertSeeText(__('superadmin.system_configuration.definitions.email_notifications_enabled.description'))
        ->assertSeeText('true')
        ->assertSeeText('platform.security.require_mfa')
        ->assertSeeText(__('superadmin.system_configuration.definitions.require_mfa.description'))
        ->assertSeeText('false')
        ->assertSeeText('platform.subscription.default_property_limit')
        ->assertSeeText(__('superadmin.system_configuration.definitions.default_property_limit.description'))
        ->assertSeeText('25')
        ->assertSeeText('platform.email.from_address')
        ->assertSeeText(__('superadmin.system_configuration.definitions.default_from_address.description'))
        ->assertSeeText('platform@example.test')
        ->assertSeeText('platform.localization.supported_locales')
        ->assertSeeText(__('superadmin.system_configuration.definitions.supported_locales.description'))
        ->assertSeeText('en, lt')
        ->assertSeeText(__('superadmin.system_configuration.actions.edit'));
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
        ->assertSet('savedMessage', __('superadmin.system_configuration.messages.saved'));

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
